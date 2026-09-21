<?php

namespace App\Http\Controllers\SavingsWithdrawals;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SavingsTransactions\SavingsTransactionsController;
use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsProduct;
use App\Models\Savings\SavingsTransaction;
use App\Models\Savings\SavingsWithdrawal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsWithdrawalsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $savingsWithdrawals = SavingsWithdrawal::all();

        return view('savings_withdrawals.index', compact('savingsWithdrawals'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $savingsAccounts = SavingsAccount::where('status', 'active')->get();

        return view('savings_withdrawals.create', compact('savingsAccounts'));
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
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'requested_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,mobile_money,bank_transfer,cheque'],
            'mobile_money_number' => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $withdrawal = DB::transaction(function () use ($validated) {

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
                        'savings_account_id' => 'A withdrawal cannot be requested from a ' . $account->status . ' savings account.'
                    ]);
                }

                $amount = round((float) $validated['amount'], 2);
                $availableBalance = round((float) $account->available_balance, 2);

                if ($amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient available savings balance. Available balance is KES ' . number_format($availableBalance, 2)
                    ]);
                }

                $product = SavingsProduct::find($account->savings_product_id);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'The savings product associated with this account was not found.'
                    ]);
                }

                if (!$product->allows_withdrawals) {
                    throw ValidationException::withMessages([
                        'amount' => 'Withdrawals are not allowed for this savings product.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'mobile_money' &&
                    empty($validated['mobile_money_number'])
                ) {
                    throw ValidationException::withMessages([
                        'mobile_money_number' => 'Mobile money number is required for mobile money payments.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'bank_transfer' &&
                    (empty($validated['bank_account_number']) || empty($validated['bank_name']))
                ) {
                    throw ValidationException::withMessages([
                        'bank_account_number' => 'Bank account number and bank name are required for bank transfers.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'cheque' &&
                    empty($validated['cheque_number'])
                ) {
                    throw ValidationException::withMessages([
                        'cheque_number' => 'Cheque number is required for cheque payments.'
                    ]);
                }

                /*
                 * Create the pending savings transaction first because
                 * savings_transaction_id is mandatory on withdrawals.
                 *
                 * This transaction does NOT affect the account balance
                 * while it remains pending.
                 */
                $transaction = SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'transaction_number' => app(SavingsTransactionsController::class)->generateSavingsTransactionNumber(),
                    'transaction_type' => 'withdrawal',
                    'direction' => 'debit',
                    'amount' => $amount,
                    'running_balance' => $account->ledger_balance,
                    'transaction_date' => $validated['requested_date'],
                    'value_date' => now()->toDateString(),
                    'payment_method' => $validated['payment_method'],
                    'status' => 'pending',
                    'recorded_by' => Auth::id(),
                    'description' => $validated['reason'] ?? null,
                ]);

                $withdrawal = SavingsWithdrawal::create([
                    'savings_account_id' => $account->id,
                    'request_number' => $this->generateWithdrawalRequestNumber(),
                    'amount' => $amount,
                    'requested_date' => $validated['requested_date'],
                    'payment_method' => $validated['payment_method'],
                    'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                    'bank_account_number' => $validated['bank_account_number'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'cheque_number' => $validated['cheque_number'] ?? null,
                    'requested_by' => Auth::id(),
                    'status' => 'pending',
                    'savings_transaction_id' => $transaction->id,
                    'reason' => $validated['reason'] ?? null,
                ]);

                return $withdrawal;
            });

            return redirect()
                ->route('savings_withdrawals.show', $withdrawal->id)
                ->with('success', 'Savings withdrawal request submitted successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error submitting savings withdrawal request. Please try again.',
                $e
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SavingsWithdrawal $savingsWithdrawal)
    {
        return view('savings_withdrawals.view', compact('savingsWithdrawal'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(SavingsWithdrawal $savingsWithdrawal)
    {
        if ($savingsWithdrawal->status !== 'pending') {
            return redirect()->back()->with('error', 'Only withdrawal requests with pending status can be edited');
        }

        // Inject the key-value pair into the request payload
        $payload = $savingsWithdrawal->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $savingsAccounts = SavingsAccount::where('status', 'active')->get();

        return view('savings_withdrawals.edit', compact('savingsWithdrawal', 'savingsAccounts'));
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
        $withdrawal = SavingsWithdrawal::findOrFail($id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'requested_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,mobile_money,bank_transfer,cheque'],
            'mobile_money_number' => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated, $id) {

                $withdrawal = SavingsWithdrawal::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$withdrawal) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Withdrawal request was not found.'
                    ]);
                }

                if ($withdrawal->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Only pending withdrawal requests can be edited.'
                    ]);
                }

                $account = SavingsAccount::where('id', $withdrawal->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'The savings account is no longer active.'
                    ]);
                }

                $amount = round((float) $validated['amount'], 2);
                $availableBalance = round((float) $account->available_balance, 2);

                if ($amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient available savings balance. Available balance is KES ' . number_format($availableBalance, 2)
                    ]);
                }

                $product = SavingsProduct::find($account->savings_product_id);

                if (!$product || !$product->allows_withdrawals) {
                    throw ValidationException::withMessages([
                        'amount' => 'Withdrawals are not allowed for this savings product.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'mobile_money' &&
                    empty($validated['mobile_money_number'])
                ) {
                    throw ValidationException::withMessages([
                        'mobile_money_number' => 'Mobile money number is required for mobile money payments.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'bank_transfer' &&
                    (empty($validated['bank_account_number']) || empty($validated['bank_name']))
                ) {
                    throw ValidationException::withMessages([
                        'bank_account_number' => 'Bank account number and bank name are required for bank transfers.'
                    ]);
                }

                if (
                    $validated['payment_method'] === 'cheque' &&
                    empty($validated['cheque_number'])
                ) {
                    throw ValidationException::withMessages([
                        'cheque_number' => 'Cheque number is required for cheque payments.'
                    ]);
                }

                /*
                 * Lock the linked pending transaction.
                 */
                $transaction = SavingsTransaction::where('id', $withdrawal->savings_transaction_id)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'The linked savings transaction could not be found.'
                    ]);
                }

                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'The linked savings transaction has already been processed and the withdrawal cannot be edited.'
                    ]);
                }

                /*
                 * Update withdrawal request.
                 */
                $withdrawal->update([
                    'amount' => $amount,
                    'requested_date' => $validated['requested_date'],
                    'payment_method' => $validated['payment_method'],
                    'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                    'bank_account_number' => $validated['bank_account_number'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'cheque_number' => $validated['cheque_number'] ?? null,
                    'reason' => $validated['reason'] ?? null,
                ]);

                /*
                 * Keep the pending savings transaction synchronized
                 * with the withdrawal request.
                 */
                $transaction->update([
                    'amount' => $amount,
                    'transaction_date' => $validated['requested_date'],
                    'value_date' => now()->toDateString(),
                    'payment_method' => $validated['payment_method'],
                    'description' => $validated['reason'] ?? null,
                ]);
            });

            return redirect()
                ->route('savings_withdrawals.show', $id)
                ->with('success', 'Savings withdrawal request updated successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error updating savings withdrawal request. Please try again.',
                $e
            );
        }
    }

    /**
     *  Withdrawal Approval Workflow
     * */
    public function approve($id)
    {
        try {

            $withdrawal = DB::transaction(function () use ($id) {

                $withdrawal = SavingsWithdrawal::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$withdrawal) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Withdrawal request was not found.'
                    ]);
                }

                if ($withdrawal->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Only pending withdrawal requests can be approved.'
                    ]);
                }

                $account = SavingsAccount::where('id', $withdrawal->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'The savings account is not active.'
                    ]);
                }

                $transaction = SavingsTransaction::where(
                    'id',
                    $withdrawal->savings_transaction_id
                )->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction was not found.'
                    ]);
                }

                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction has already been processed.'
                    ]);
                }

                if (
                    $transaction->transaction_type !== 'withdrawal' ||
                    $transaction->direction !== 'debit'
                ) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked transaction is not a valid withdrawal transaction.'
                    ]);
                }

                $amount = round((float) $withdrawal->amount, 2);
                $ledgerBalance = round((float) $account->ledger_balance, 2);
                $heldBalance = round((float) $account->held_balance, 2);
                $availableBalance = round($ledgerBalance - $heldBalance, 2);

                /*
                 * Confirm that the withdrawal amount has not changed
                 * independently from the linked transaction.
                 */
                if (round((float) $transaction->amount, 2) !== $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'The withdrawal amount does not match the linked savings transaction.'
                    ]);
                }

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

                $newAvailableBalance = round(
                    $newLedgerBalance - $heldBalance,
                    2
                );

                if ($newAvailableBalance < 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'The withdrawal would result in a negative available balance.'
                    ]);
                }

                /*
                 * Update the savings transaction.
                 *
                 * This is the point at which the withdrawal actually
                 * becomes part of the financial ledger.
                 */
                $transaction->update([
                    'running_balance' => $newLedgerBalance,
                    'status' => 'confirmed',
                    'value_date' => now()->toDateString(),
                ]);

                /*
                 * Update the savings account.
                 */
                $account->update([
                    'ledger_balance' => $newLedgerBalance,
                    'available_balance' => $newAvailableBalance,
                    'last_transaction_date' => $transaction->transaction_date,
                ]);

                /*
                 * Approve the withdrawal request.
                 *
                 * It is NOT marked as paid here.
                 */
                $withdrawal->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                return $withdrawal;
            });

            return redirect()
                ->route('savings_withdrawals.show', $withdrawal->id)
                ->with('success', 'Savings withdrawal approved successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error approving savings withdrawal. Please try again.',
                $e
            );
        }
    } 

    /**
     * Withdrawl payment workflow
     * */   
    public function pay(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        try {

            $withdrawal = DB::transaction(function () use ($validated, $id) {

                $withdrawal = SavingsWithdrawal::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$withdrawal) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Withdrawal request was not found.'
                    ]);
                }

                if ($withdrawal->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Only approved withdrawals can be paid.'
                    ]);
                }

                $account = SavingsAccount::where('id', $withdrawal->savings_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'Savings account was not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'savings_account_id' => 'The savings account is not active.'
                    ]);
                }

                /*
                 * Make sure the linked transaction exists and
                 * has already been confirmed during approval.
                 */
                $transaction = SavingsTransaction::where(
                    'id',
                    $withdrawal->savings_transaction_id
                )->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction was not found.'
                    ]);
                }

                if ($transaction->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction has not been confirmed.'
                    ]);
                }

                if (
                    $transaction->transaction_type !== 'withdrawal' ||
                    $transaction->direction !== 'debit'
                ) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction is not a valid withdrawal transaction.'
                    ]);
                }

                /*
                 * Prevent a mismatch between the withdrawal request
                 * and the posted savings transaction.
                 */
                if (
                    round((float) $withdrawal->amount, 2) !==
                    round((float) $transaction->amount, 2)
                ) {
                    throw ValidationException::withMessages([
                        'amount' => 'The withdrawal amount does not match the posted savings transaction.'
                    ]);
                }

                /*
                 * Record actual payment.
                 *
                 * The account balance is NOT changed here because
                 * it was already reduced when the withdrawal was approved.
                 */
                $withdrawal->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::id(),
                    'payment_reference' => $validated['payment_reference'],
                ]);

                return $withdrawal;
            });

            return redirect()
                ->route('savings_withdrawals.show', $withdrawal->id)
                ->with('success', 'Savings withdrawal paid successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error processing savings withdrawal payment. Please try again.',
                $e
            );
        }
    }

    /**
     * Withdrawal Rejection workflow
     * */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'decision_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {

            DB::transaction(function () use ($validated, $id) {

                $withdrawal = SavingsWithdrawal::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$withdrawal) {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Withdrawal request was not found.'
                    ]);
                }

                if ($withdrawal->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'withdrawal' => 'Only pending withdrawal requests can be rejected.'
                    ]);
                }

                $transaction = SavingsTransaction::where(
                    'id',
                    $withdrawal->savings_transaction_id
                )
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction was not found.'
                    ]);
                }

                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'The linked savings transaction has already been processed.'
                    ]);
                }

                $withdrawal->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'decision_reason' => $validated['decision_reason'],
                ]);

                /*
                 * The transaction was never posted to the account,
                 * therefore the account balance is not changed.
                 *
                 * Mark the pending transaction as reversed so that
                 * it cannot subsequently be confirmed.
                 */
                $transaction->update([
                    'status' => 'reversed',
                    'description' => trim(
                        ($transaction->description ? $transaction->description . "\n" : '') .
                        'Withdrawal request rejected: ' .
                        $validated['decision_reason']
                    ),
                ]);
            });

            return redirect()
                ->route('savings_withdrawals.show', $id)
                ->with('success', 'Savings withdrawal rejected successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error rejecting savings withdrawal. Please try again.',
                $e
            );
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

    private function generateWithdrawalRequestNumber()
    {
        do {
            $number = 'WDR-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
        } while (SavingsWithdrawal::where('request_number', $number)->exists());

        return $number;
    }
}
