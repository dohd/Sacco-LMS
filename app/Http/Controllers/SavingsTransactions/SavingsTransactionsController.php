<?php

namespace App\Http\Controllers\SavingsTransactions;

use App\Http\Controllers\Controller;
use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsProduct;
use App\Models\Savings\SavingsTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SavingsTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $savingsTranx = SavingsTransaction::latest()->get();

        return view('savings_transactions.index', compact('savingsTranx'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $savingsAccounts = SavingsAccount::all();

        return view('savings_transactions.create', compact('savingsAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'savings_account_id' => ['required', 'integer', 'exists:savings_accounts,id'],
            'transaction_type' => ['required', 'in:deposit,withdrawal,interest,fee,transfer_in,transfer_out,adjustment'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'value_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,mobile_money,bank_transfer,cheque,check_off,internal_transfer,system'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'receipt_number' => ['nullable', 'string', 'max:255', 'unique:savings_transactions,receipt_number'],
            'description' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:credit,debit'],
        ]);

        try {
            if ($validated['transaction_type'] === 'reversal') {
                throw ValidationException::withMessages([
                    'transaction_type' => 'Reversals must be processed using the reversal workflow.'
                ]);
            }

            $transaction = DB::transaction(function () use ($validated, $request) {

                $account = SavingsAccount::where('id', $validated['savings_account_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Transactions cannot be posted to a ' . $account->status . ' savings account.'
                    ]);
                }

                if ($validated['transaction_type'] === 'adjustment') {
                    $direction = $validated['direction'] ?? null;

                    if (!$direction) {
                        throw ValidationException::withMessages([
                            'direction' => 'An adjustment must specify credit or debit.'
                        ]);
                    }
                } elseif (in_array($validated['transaction_type'], ['deposit', 'interest', 'transfer_in'])) {
                    $direction = 'credit';
                } elseif (in_array($validated['transaction_type'], ['withdrawal', 'fee', 'transfer_out'])) {
                    $direction = 'debit';
                } else {
                    throw ValidationException::withMessages([
                        'transaction_type' => 'This transaction type must be processed through its dedicated workflow.'
                    ]);
                }

                $amount = round((float) $validated['amount'], 2);

                /*
                 * The transaction is NOT posted to the account here.
                 *
                 * ledger_balance
                 * available_balance
                 * running_balance
                 *
                 * remain unchanged until confirmation.
                 */
                $transaction = SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'transaction_number' => $this->generateSavingsTransactionNumber(),
                    'transaction_type' => $validated['transaction_type'],
                    'direction' => $direction,
                    'amount' => $amount,
                    'running_balance' => $account->ledger_balance,
                    'transaction_date' => $validated['transaction_date'],
                    'value_date' => $validated['value_date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'external_reference' => $validated['external_reference'] ?? null,
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'status' => 'pending',
                    'recorded_by' => Auth::id(),
                    'description' => $validated['description'] ?? null,
                ]);

                return $transaction;
            });

            return redirect()
                ->route('savings_transactions.show', $transaction->id)
                ->with('success', 'Savings transaction saved and is awaiting confirmation.');

        } catch (Exception $e) {
            return errorHandler("Error saving savings transaction. Please try again.", $e);
        }
    }

    /**
     * Transaction confirmation
     * */
    public function confirm($id)
    {
        try {
            $transaction = DB::transaction(function () use ($id) {
                $transaction = SavingsTransaction::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Savings transaction was not found.'
                    ]);
                }

                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Only pending transactions can be confirmed.'
                    ]);
                }

                $account = SavingsAccount::where('id', $transaction->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Transactions cannot be posted to a ' . $account->status . ' savings account.'
                    ]);
                }

                $amount = round((float) $transaction->amount, 2);
                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                /*
                 * Determine the financial effect.
                 */
                if ($transaction->direction === 'credit') {
                    $newLedgerBalance = round(
                        $ledgerBalance + $amount,
                        2
                    );

                } else {
                    if ($amount > $availableBalance) {
                        throw ValidationException::withMessages([
                            'amount' => 'Insufficient available savings balance. Available balance is KES ' .
                                number_format($availableBalance, 2)
                        ]);
                    }

                    $newLedgerBalance = round(
                        $ledgerBalance - $amount,
                        2
                    );
                }

                $newAvailableBalance = round(
                    $newLedgerBalance - $heldBalance,
                    2
                );

                if ($newAvailableBalance < 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'The transaction would result in a negative available balance.'
                    ]);
                }

                /*
                 * Check savings product maximum balance.
                 */
                $product = SavingsProduct::find($account->savings_product_id);

                if (
                    $transaction->direction === 'credit' &&
                    $product &&
                    !is_null($product->maximum_balance) &&
                    $newLedgerBalance > $product->maximum_balance
                ) {
                    throw ValidationException::withMessages([
                        'amount' => 'The transaction would exceed the maximum balance allowed for this savings product.'
                    ]);
                }

                /*
                 * Update the transaction.
                 */
                $transaction->update([
                    'running_balance' => $newLedgerBalance,
                    'status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                ]);

                /*
                 * Update the actual savings account.
                 */
                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => $transaction->transaction_date,
                ]);

                return $transaction;
            });

            return redirect()
                ->route('savings_transactions.show', $transaction->id)
                ->with('success', 'Savings transaction confirmed and posted successfully.');
        } catch (Exception $e) {
            return errorHandler("Error confirming savings transaction. Please try again.", $e);
        }
    }


    /**
     *  Transaction reversal
     * */
    public function reverse(Request $request, $id)
    {
        // 1. Create the validator instance manually
        $validator = Validator::make($request->all(), [
            // 'value_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        // 2. Check if validation failed using an if-statement
        if ($validator->fails()) {
            
            // 3. Directly access the error object (MessageBag)
            $errors = $validator->errors();

            // You can now manipulate the error object freely:
            $allMessages = $errors->all();
            $firstDateError = $errors->first('value_date');

            // Example: Return a fully custom response
            return response()->json([
                'meta' => ['status' => 'error', 'code' => 400],
                'validation_failures' => $errors->messages()
            ], 400);
        }

        // 4. If it passes, get the validated data safely
        $validated = $validator->validated();
        $validated['value_date'] = date('Y-m-d');

        try {
            $transaction = DB::transaction(function () use ($id, $validated) {

                $original = SavingsTransaction::where('id', $id)->lockForUpdate()->first();

                if (!$original) {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'Transaction was not found.'
                    // ]);
                    trigger_error('Transaction was not found.');
                }

                if ($original->status !== 'confirmed') {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'Only confirmed transactions can be reversed.'
                    // ]);
                    trigger_error('Only confirmed transactions can be reversed.');
                }

                if ($original->transaction_type === 'reversal') {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'A reversal transaction cannot be reversed.'
                    // ]);
                    trigger_error('A reversal transaction cannot be reversed.');
                }

                $alreadyReversed = SavingsTransaction::where('reversal_of_id', $original->id)
                    ->where('status', 'confirmed')
                    ->exists();

                if ($alreadyReversed) {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'This transaction has already been reversed.'
                    // ]);
                    trigger_error('This transaction has already been reversed.');
                }

                $account = SavingsAccount::where('id', $original->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'The associated savings account was not found.'
                    // ]);
                    trigger_error('The associated savings account was not found.');
                }

                if ($account->status !== 'active') {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'The savings account is not active.'
                    // ]);
                    trigger_error('The savings account is not active.');
                }

                $amount = round((float) $original->amount, 2);
                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                $direction = $original->direction === 'credit' ? 'debit' : 'credit';

                if ($direction === 'debit' && $amount > $availableBalance) {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'The reversal cannot be completed because the available balance is insufficient.'
                    // ]);
                    trigger_error('The reversal cannot be completed because the available balance is insufficient.');
                }

                $newLedgerBalance = $direction === 'credit'
                    ? $ledgerBalance + $amount
                    : $ledgerBalance - $amount;

                $newAvailableBalance = $newLedgerBalance - $heldBalance;

                if ($newAvailableBalance < 0) {
                    // throw ValidationException::withMessages([
                    //     'transaction' => 'The reversal would result in a negative available balance.'
                    // ]);
                    trigger_error('The reversal would result in a negative available balance.');
                }

                $reversal = SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'transaction_number' => $this->generateSavingsTransactionNumber(),
                    'transaction_type' => 'reversal',
                    'direction' => $direction,
                    'amount' => $amount,
                    'running_balance' => $newLedgerBalance,
                    'transaction_date' => now()->toDateString(),
                    'value_date' => $validated['value_date'],
                    'payment_method' => 'system',
                    'external_reference' => $original->transaction_number,
                    'status' => 'confirmed',
                    'recorded_by' => Auth::id(),
                    'reversal_of_id' => $original->id,
                    'description' => $validated['description'] ?? 'Reversal of transaction ' . $original->transaction_number,
                ]);

                $original->update(['status' => 'reversed']);

                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => now()->toDateString(),
                ]);

                return $reversal;
            });

            // return redirect()->back()->with('success', 'Transaction reversed successfully.');
            return response()->json(['status' => 'success', 'message' => 'Transaction reversed successfully.']);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' at ' . $e->getFile() . ':'. $e->getLine());
            // return errorHandler("Error reversing transaction. Please try again.", $e);
            return response()->json(['status' => 'error', 'message' => "Error reversing transaction. Please try again."], 500);
        }            
    }   

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SavingsTransaction $savingsTransaction)
    {
        return view('savings_transactions.view', compact('savingsTransaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(SavingsTransaction $savingsTransaction)
    {
        $savingsAccounts = SavingsAccount::all();

        return view('savings_transactions.edit', compact('savingsTransaction', 'savingsAccounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SavingsTransaction $savingsTransaction)
    {
        $validated = $request->validate([
            'transaction_type' => ['required', 'in:deposit,withdrawal,interest,fee,transfer_in,transfer_out,adjustment'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'value_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,mobile_money,bank_transfer,cheque,check_off,internal_transfer,system'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $id = $savingsTransaction->id;

        try {
            $transaction = DB::transaction(function () use ($validated, $request, $id) {

                $transaction = SavingsTransaction::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Savings transaction was not found.'
                    ]);
                }

                /*
                 * Confirmed transactions must never be edited.
                 */
                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Only pending savings transactions can be edited. Confirmed transactions must be reversed.'
                    ]);
                }

                $account = SavingsAccount::where('id', $transaction->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Transactions cannot be updated on a ' . $account->status . ' savings account.'
                    ]);
                }

                /*
                 * A pending transaction should not have been included
                 * in the account balance. Therefore, its original
                 * financial effect does not need to be reversed.
                 *
                 * However, do not allow editing if another transaction
                 * has already been posted after this transaction.
                 */
                $latestTransaction = SavingsTransaction::where('savings_account_id', $account->id)
                    ->where('id', '!=', $transaction->id)
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($latestTransaction && $latestTransaction->transaction_date > $transaction->transaction_date) {
                    throw ValidationException::withMessages([
                        'transaction' => 'This transaction cannot be edited because later transactions already exist.'
                    ]);
                }

                if ($validated['transaction_type'] === 'adjustment') {

                    $direction = $request->input('direction');

                    if (!in_array($direction, ['credit', 'debit'])) {
                        throw ValidationException::withMessages([
                            'direction' => 'An adjustment must specify credit or debit.'
                        ]);
                    }

                } elseif (in_array($validated['transaction_type'], ['deposit', 'interest', 'transfer_in'])) {

                    $direction = 'credit';

                } elseif (in_array($validated['transaction_type'], ['withdrawal', 'fee', 'transfer_out'])) {

                    $direction = 'debit';

                } else {

                    throw ValidationException::withMessages([
                        'transaction_type' => 'This transaction type must be processed through its dedicated workflow.'
                    ]);
                }

                $amount = round((float) $validated['amount'], 2);

                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                if ($direction === 'debit' && $amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient available savings balance. Available balance is KES ' . number_format($availableBalance, 2)
                    ]);
                }

                if ($direction === 'credit') {
                    $newLedgerBalance = round($ledgerBalance + $amount, 2);
                } else {
                    $newLedgerBalance = round($ledgerBalance - $amount, 2);
                }

                $newAvailableBalance = round($newLedgerBalance - $heldBalance, 2);

                if ($newAvailableBalance < 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'The transaction would result in a negative available balance.'
                    ]);
                }

                $product = SavingsProduct::find($account->savings_product_id);

                if (
                    $direction === 'credit' &&
                    $product &&
                    !is_null($product->maximum_balance) &&
                    $newLedgerBalance > $product->maximum_balance
                ) {
                    throw ValidationException::withMessages([
                        'amount' => 'The transaction would exceed the maximum balance allowed for this savings product.'
                    ]);
                }

                /*
                 * Update the pending transaction.
                 */
                $transaction->update([
                    'transaction_type' => $validated['transaction_type'],
                    'direction' => $direction,
                    'amount' => $amount,
                    'running_balance' => $newLedgerBalance,
                    'transaction_date' => $validated['transaction_date'],
                    'value_date' => $validated['value_date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'external_reference' => $validated['external_reference'] ?? null,
                    'receipt_number' => $validated['receipt_number'] ?: null,
                    'description' => $validated['description'] ?? null,
                ]);

                /*
                 * If the pending transaction is actually included in the
                 * account balance in your pending workflow, update it here.
                 *
                 * Your current store() method creates transactions as
                 * confirmed, so normally this branch should not be needed.
                 */
                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => $validated['transaction_date'],
                ]);

                return $transaction;
            });

            return redirect()
                ->route('savings_transactions.show', $transaction->id)
                ->with('success', 'Savings transaction updated successfully.');

        } catch (Exception $e) {
            return errorHandler( "Error updating savings transaction. Please try again.", $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function generateSavingsTransactionNumber()
    {
        do {
            $number = 'SAV-TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (SavingsTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
