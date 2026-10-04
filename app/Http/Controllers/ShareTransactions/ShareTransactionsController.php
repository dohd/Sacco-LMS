<?php

namespace App\Http\Controllers\ShareTransactions;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareProduct;
use App\Models\Shares\ShareTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShareTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shareTransactions = ShareTransaction::latest()->get();

        return view('share_transactions.index', compact('shareTransactions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);

        $shareAccounts = ShareAccount::all();

        return view('share_transactions.create', compact('shareAccounts'));
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
            'share_account_id' => ['required', 'integer', 'exists:share_accounts,id'],
            'transaction_type' => ['required', 'in:purchase,transfer_in,transfer_out,redemption,bonus_issue,adjustment'],
            'direction' => ['nullable', 'in:credit,debit'],
            'units' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'value_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,mobile_money,bank_transfer,cheque,check_off,internal_transfer,system'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'receipt_number' => ['nullable', 'string', 'max:255', 'unique:share_transactions,receipt_number'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $transaction = DB::transaction(function () use ($validated) {
                $account = ShareAccount::whereKey($validated['share_account_id'])->lockForUpdate()->first();

                if (!$account) {
                    throw ValidationException::withMessages(['share_account_id' => 'Share account not found.']);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages(['share_account_id' => "Transactions cannot be posted to a {$account->status} share account."]);
                }

                $product = ShareProduct::find($account->share_product_id);

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages(['share_account_id' => 'The share product is inactive or unavailable.']);
                }

                $type = $validated['transaction_type'];
                $creditTypes = ['purchase', 'transfer_in', 'bonus_issue'];
                $debitTypes = ['transfer_out', 'redemption'];

                $direction = in_array($type, $creditTypes) ? 'credit' : (in_array($type, $debitTypes) ? 'debit' : $validated['direction']);

                if (!$direction) {
                    throw ValidationException::withMessages(['direction' => 'An adjustment must specify credit or debit.']);
                }

                $units = (int) $validated['units'];
                $unitValue = round((float) $product->unit_value, 2);
                $amount = round($units * $unitValue, 2);
                $currentUnits = (int) $account->total_units;
                $currentBalance = round((float) $account->share_balance, 2);

                if ($direction === 'debit' && $units > $currentUnits) {
                    throw ValidationException::withMessages(['units' => 'Insufficient share units. Available units: ' . number_format($currentUnits)]);
                }

                $newUnits = $direction === 'credit' ? $currentUnits + $units : $currentUnits - $units;
                $newBalance = $direction === 'credit' ? $currentBalance + $amount : $currentBalance - $amount;

                if ($newBalance < 0) {
                    throw ValidationException::withMessages(['units' => 'The transaction would result in a negative share balance.']);
                }

                if ($direction === 'credit' && !is_null($product->maximum_units) && $newUnits > $product->maximum_units) {
                    throw ValidationException::withMessages(['units' => 'The transaction would exceed the maximum units allowed for this share product.']);
                }                

                $transaction = ShareTransaction::create([
                    'share_account_id' => $account->id,
                    'transaction_number' => $this->generateShareTransactionNumber(),
                    'transaction_type' => $type,
                    'direction' => $direction,
                    'units' => $units,
                    'unit_value' => $unitValue,
                    'amount' => $amount,
                    'running_units' => $newUnits,
                    'running_balance' => $newBalance,
                    'transaction_date' => $validated['transaction_date'],
                    'value_date' => $validated['value_date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'status' => 'pending',
                    'recorded_by' => Auth::id(),
                    'reversal_of_id' => null,
                    'description' => $validated['description'] ?? null,
                ]);

                return $transaction;
            });

            return redirect()->route('share_transactions.show', $transaction->id)
                ->with('success', 'Share transaction posted successfully.');
        } catch (\Exception $e) {
            return errorHandler('Error posting share transaction. Please try again.', $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ShareTransaction $shareTransaction)
    {
        return view('share_transactions.view', compact('shareTransaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ShareTransaction $shareTransaction)
    {
        // Inject the key-value pair into the request payload
        $payload = $shareTransaction->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $shareAccounts = ShareAccount::all();

        return view('share_transactions.edit', compact('shareTransaction', 'shareAccounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShareTransaction $shareTransaction)
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'value_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,mobile_money,bank_transfer,cheque,check_off,internal_transfer,system'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'receipt_number' => ['nullable', 'string', 'max:255', Rule::unique('share_transactions', 'receipt_number')->ignore($shareTransaction->id)],
            'description' => ['nullable', 'string'],
        ]);

        try {
            
            DB::transaction(function() use($shareTransaction, $validated) {
                $transaction  = ShareTransaction::whereKey($shareTransaction->id)
                    ->lockForUpdate()
                    ->first();

                if ($shareTransaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Confirmed or reversed share transactions cannot be edited. Use the reversal workflow.'
                    ]);
                }

                $transaction->update($validated); 
            });

            return redirect()->route('share_transactions.show', $shareTransaction->id)
                ->with('success', 'Share transaction updated successfully.');
        } catch (\Exception $e) {
            return errorHandler('Error updating share transaction. Please try again.', $e);
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

    /**
     * Confirm transaction
     * */
    public function confirm($id)
    {
        $shareTransaction = ShareTransaction::findOrFail($id);

        try {
            DB::transaction(function () use ($shareTransaction) {

                $transaction = ShareTransaction::whereKey($shareTransaction->id)
                    ->lockForUpdate()
                    ->first();

                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Only pending transactions can be confirmed.'
                    ]);
                }

                $account = ShareAccount::whereKey($transaction->share_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Share account not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'transaction' => "Transactions cannot be posted to a {$account->status} share account."
                    ]);
                }

                $product = ShareProduct::find($account->share_product_id);

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The share product is inactive or unavailable.'
                    ]);
                }

                $currentUnits = (int) $account->total_units;
                $currentBalance = round((float) $account->share_balance, 2);

                if ($transaction->direction === 'debit' &&
                    $transaction->units > $currentUnits) {

                    throw ValidationException::withMessages([
                        'transaction' => 'Insufficient share units. Available units: ' .
                            number_format($currentUnits)
                    ]);
                }

                $newUnits = $transaction->direction === 'credit'
                    ? $currentUnits + $transaction->units
                    : $currentUnits - $transaction->units;

                $newBalance = $transaction->direction === 'credit'
                    ? $currentBalance + $transaction->amount
                    : $currentBalance - $transaction->amount;

                if ($newBalance < 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The transaction would result in a negative share balance.'
                    ]);
                }

                if (
                    $transaction->direction === 'credit' &&
                    !is_null($product->maximum_units) &&
                    $newUnits > $product->maximum_units
                ) {
                    throw ValidationException::withMessages([
                        'transaction' => 'The transaction would exceed the maximum units allowed for this share product.'
                    ]);
                }

                $transaction->update([
                    'running_units' => $newUnits,
                    'running_balance' => $newBalance,
                    'status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                ]);

                $account->update([
                    'total_units' => $newUnits,
                    'share_balance' => $newBalance,
                    'available_amount' => max(
                        0,
                        $newBalance - $account->held_amount
                    ),
                ]);
            });

            return back()->with(
                'success',
                'Share transaction confirmed successfully.'
            );

        } catch (\Exception $e) {
            return errorHandler(
                'Error confirming share transaction. Please try again.',
                $e
            );
        }
    }

    /**
     * Reverse transaction
     * */
    public function reverse(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $shareTransaction = ShareTransaction::findOrFail($id);

        try {
            $reversal = DB::transaction(function () use ($shareTransaction, $validated) {

                $original = ShareTransaction::whereKey($shareTransaction->id)
                    ->lockForUpdate()
                    ->first();

                if ($original->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'transaction' => 'Only confirmed transactions can be reversed.'
                    ]);
                }

                if (ShareTransaction::where('reversal_of_id', $original->id)->exists()) {
                    throw ValidationException::withMessages([
                        'transaction' => 'This transaction has already been reversed.'
                    ]);
                }

                $account = ShareAccount::whereKey($original->share_account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Share account not found.'
                    ]);
                }

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'transaction' =>
                            "Transactions cannot be posted to a {$account->status} share account."
                    ]);
                }

                if (
                    $original->direction === 'credit' &&
                    $original->units > $account->total_units
                ) {
                    throw ValidationException::withMessages([
                        'transaction' =>
                            'The transaction cannot be reversed because the account no longer has sufficient units.'
                    ]);
                }

                $direction = $original->direction === 'credit'
                    ? 'debit'
                    : 'credit';

                $newUnits = $direction === 'credit'
                    ? $account->total_units + $original->units
                    : $account->total_units - $original->units;

                $newBalance = $direction === 'credit'
                    ? $account->share_balance + $original->amount
                    : $account->share_balance - $original->amount;

                if ($newBalance < 0) {
                    throw ValidationException::withMessages([
                        'transaction' =>
                            'The reversal would result in a negative share balance.'
                    ]);
                }

                $reversal = ShareTransaction::create([
                    'share_account_id' => $account->id,
                    'transaction_number' => $this->generateShareTransactionNumber(),
                    'transaction_type' => 'reversal',
                    'direction' => $direction,
                    'units' => $original->units,
                    'unit_value' => $original->unit_value,
                    'amount' => $original->amount,
                    'running_units' => $newUnits,
                    'running_balance' => $newBalance,
                    'transaction_date' => now()->toDateString(),
                    'value_date' => now()->toDateString(),
                    'payment_method' => 'system',
                    'payment_reference' => null,
                    'receipt_number' => null,
                    'status' => 'confirmed',
                    'recorded_by' => Auth::id(),
                    'reversal_of_id' => $original->id,
                    'description' => $validated['description'],
                ]);

                $account->update([
                    'total_units' => $newUnits,
                    'share_balance' => $newBalance,
                    'available_amount' => max(
                        0,
                        $newBalance - $account->held_amount
                    ),
                ]);

                $original->update([
                    'status' => 'reversed',
                ]);

                return $reversal;
            });

            return redirect()
                ->route('share_transactions.show', $reversal->id)
                ->with('success', 'Share transaction reversed successfully.');

        } catch (\Exception $e) {
            return errorHandler(
                'Error reversing share transaction. Please try again.',
                $e
            );
        }
    }

    public function generateShareTransactionNumber()
    {
        do {
            $number = 'SHA-TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (ShareTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
