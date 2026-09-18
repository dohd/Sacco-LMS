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
        return view('savings_transactions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $member = optional();
        $accounts = collect();
        return view('savings_transactions.create', compact('member', 'accounts'));
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
        ]);

        try {
            if ($validated['transaction_type'] === 'reversal') {
                throw ValidationException::withMessages([
                    'transaction_type' => 'Reversals must be processed using the reversal workflow.'
                ]);
            }

            $transaction = DB::transaction(function () use ($validated, $request) {
                $account = SavingsAccount::where('id', $validated['savings_account_id'])->lockForUpdate()->first();

                if (!$account) {
                    throw ValidationException::withMessages(['savings_account_id' => 'Savings account was not found.']);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages(['savings_account_id' => 'Transactions cannot be posted to a ' . $account->status . ' savings account.']);
                }

                $amount = round((float) $validated['amount'], 2);
                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                $creditTransactions = ['deposit', 'interest', 'transfer_in'];
                $debitTransactions = ['withdrawal', 'fee', 'transfer_out'];

                if ($validated['transaction_type'] === 'adjustment') {
                    $direction = $request->input('direction');

                    if (!in_array($direction, ['credit', 'debit'])) {
                        throw ValidationException::withMessages(['direction' => 'An adjustment must specify credit or debit.']);
                    }
                } elseif (in_array($validated['transaction_type'], $creditTransactions)) {
                    $direction = 'credit';
                } elseif (in_array($validated['transaction_type'], $debitTransactions)) {
                    $direction = 'debit';
                } else {
                    throw ValidationException::withMessages(['transaction_type' => 'This transaction type must be processed through its dedicated workflow.']);
                }

                if ($direction === 'credit') {
                    $newLedgerBalance = $ledgerBalance + $amount;
                } else {
                    if ($amount > $availableBalance) {
                        throw ValidationException::withMessages(['amount' => 'Insufficient available savings balance. Available balance is KES ' . number_format($availableBalance, 2)]);
                    }

                    $newLedgerBalance = $ledgerBalance - $amount;
                }

                $newAvailableBalance = $newLedgerBalance - $heldBalance;

                if ($newAvailableBalance < 0) {
                    throw ValidationException::withMessages(['amount' => 'The transaction would result in a negative available balance.']);
                }

                $product = SavingsProduct::find($account->savings_product_id);

                if ($direction === 'credit' && $product && !is_null($product->maximum_balance) && $newLedgerBalance > $product->maximum_balance) {
                    throw ValidationException::withMessages(['amount' => 'The transaction would exceed the maximum balance allowed for this savings product.']);
                }

                $transaction = SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'transaction_number' => $this->generateSavingsTransactionNumber(),
                    'transaction_type' => $validated['transaction_type'],
                    'direction' => $direction,
                    'amount' => $amount,
                    'running_balance' => $newLedgerBalance,
                    'transaction_date' => $validated['transaction_date'],
                    'value_date' => $validated['value_date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'external_reference' => $validated['external_reference'] ?? null,
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'status' => 'confirmed',
                    'recorded_by' => Auth::id(),
                    'description' => $validated['description'] ?? null,
                ]);

                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => $validated['transaction_date'],
                ]);

                return $transaction;
            });

            return redirect()->route('savings_transactions.index')->with('success', 'Savings transaction posted successfully.');
        } catch (Exception $e) {
            return errorHandler("Error posting savings transaction. Please try again.", $e);
        }            
    } 

    /**
     *  Transaction reversal
     * */
    public function reverse(Request $request, $id)
    {
        $validated = $request->validate([
            'value_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $transaction = DB::transaction(function () use ($id, $validated) {

                $original = SavingsTransaction::where('id', $id)->lockForUpdate()->first();

                if (!$original) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaction was not found.'
                    ]);
                }

                if ($original->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Only confirmed transactions can be reversed.'
                    ]);
                }

                if ($original->transaction_type === 'reversal') {
                    throw ValidationException::withMessages([
                        'transaction' => 'A reversal transaction cannot be reversed.'
                    ]);
                }

                $alreadyReversed = SavingsTransaction::where('reversal_of_id', $original->id)
                    ->where('status', 'confirmed')
                    ->exists();

                if ($alreadyReversed) {
                    throw ValidationException::withMessages([
                        'transaction' => 'This transaction has already been reversed.'
                    ]);
                }

                $account = SavingsAccount::where('id', $original->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The associated savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'transaction' => 'The savings account is not active.'
                    ]);
                }

                $amount = round((float) $original->amount, 2);
                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                $direction = $original->direction === 'credit' ? 'debit' : 'credit';

                if ($direction === 'debit' && $amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The reversal cannot be completed because the available balance is insufficient.'
                    ]);
                }

                $newLedgerBalance = $direction === 'credit'
                    ? $ledgerBalance + $amount
                    : $ledgerBalance - $amount;

                $newAvailableBalance = $newLedgerBalance - $heldBalance;

                if ($newAvailableBalance < 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The reversal would result in a negative available balance.'
                    ]);
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

                $original->update([
                    'status' => 'reversed'
                ]);

                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => now()->toDateString(),
                ]);

                return $reversal;
            });

            return redirect()->route('savings_transactions.index')->with('success', 'Transaction reversed successfully.');
        } catch (Exception $e) {
            return errorHandler("Error reversing transaction. Please try again.", $e);
        }            
    }   

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

    private function generateSavingsTransactionNumber()
    {
        do {
            $number = 'SAV-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (SavingsTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
