<?php

namespace App\Http\Controllers\SavingsAccounts;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsProduct;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $savingsAccounts = SavingsAccount::latest()->get();

        return view('savings_accounts.index', compact('savingsAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $members = Member::all();
        $savingsProducts = SavingsProduct::all();

        return view('savings_accounts.create', compact('members', 'savingsProducts'));
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'savings_product_id' => ['required', 'integer', 'exists:savings_products,id'],
            'opened_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $savingsAccount = DB::transaction(function () use ($validated) {

                $member = Member::find($validated['member_id']);

                if (!$member) {
                    throw ValidationException::withMessages([
                        'member_id' => 'The selected member does not exist.'
                    ]);
                }

                $product = SavingsProduct::where('id', $validated['savings_product_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'savings_product_id' => 'The selected savings product is not active.'
                    ]);
                }

                $existingAccount = SavingsAccount::where('member_id', $member->id)
                    ->where('savings_product_id', $product->id)
                    ->whereIn('status', ['active', 'frozen'])
                    ->exists();

                if ($existingAccount) {
                    throw ValidationException::withMessages([
                        'savings_product_id' => 'This member already has an account for this savings product.'
                    ]);
                }

                $savingsAccount = SavingsAccount::create([
                    'member_id' => $member->id,
                    'savings_product_id' => $product->id,
                    'account_number' => $this->generateSavingsAccountNumber(),
                    'ledger_balance' => 0,
                    'held_balance' => 0,
                    'available_balance' => 0,
                    'opened_date' => $validated['opened_date'],
                    'opened_by' => Auth::id(),
                    'status' => 'active',
                ]);

                return $savingsAccount;
            });

            return redirect()
                ->route('savings_accounts.index')
                ->with('success', 'Savings account opened successfully.');
        } catch (Exception $e) {
            return errorHandler("Error opening savings account. Please try again.", $e);
        }            
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SavingsAccount $savingsAccount)
    {
        return view('savings_accounts.view', compact('savingsAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(SavingsAccount $savingsAccount)
    {
        $members = Member::all();
        $savingsProducts = SavingsProduct::all();

        return view('savings_accounts.edit', compact('savingsAccount', 'members', 'savingsProducts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SavingsAccount $savingsAccount)
    {
        $validated = $request->validate([
            'savings_product_id' => ['required', 'integer', 'exists:savings_products,id'],
            'status' => ['required', 'in:active,frozen,closed'],
            'closed_date' => ['nullable', 'date'],
            'closure_reason' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($savingsAccount, $validated) {

                $product = SavingsProduct::where('id', $validated['savings_product_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'savings_product_id' => 'The selected savings product is not active.'
                    ]);
                }

                /*
                 * A closed account should not be reopened through
                 * a normal update without an explicit workflow.
                 */
                if ($savingsAccount->status === 'closed' && $validated['status'] !== 'closed') {
                    throw ValidationException::withMessages([
                        'status' => 'A closed savings account cannot be reopened.'
                    ]);
                }

                /*
                 * Closing an account requires a closure date and reason.
                 */
                if ($validated['status'] === 'closed') {

                    if (empty($validated['closed_date'])) {
                        throw ValidationException::withMessages([
                            'closed_date' => 'A closure date is required when closing an account.'
                        ]);
                    }

                    if (empty($validated['closure_reason'])) {
                        throw ValidationException::withMessages([
                            'closure_reason' => 'A closure reason is required when closing an account.'
                        ]);
                    }

                    /*
                     * Do not allow an account with money to be closed.
                     */
                    if ((float) $savingsAccount->ledger_balance > 0) {
                        throw ValidationException::withMessages([
                            'status' => 'An account with an outstanding balance cannot be closed.'
                        ]);
                    }

                    $savingsAccount->closed_by = Auth::id();
                }

                /*
                 * Clear closure information when the account is not closed.
                 */
                if ($validated['status'] !== 'closed') {
                    $savingsAccount->closed_date = null;
                    $savingsAccount->closed_by = null;
                    $savingsAccount->closure_reason = null;
                }

                $savingsAccount->savings_product_id = $validated['savings_product_id'];
                $savingsAccount->status = $validated['status'];

                if ($validated['status'] === 'closed') {
                    $savingsAccount->closed_date = $validated['closed_date'];
                    $savingsAccount->closure_reason = $validated['closure_reason'];
                }

                $savingsAccount->save();
            });

            return redirect()
                ->route('savings_accounts.show', $savingsAccount->id)
                ->with('success', 'Savings account updated successfully.');
        } catch (Exception $e) {
            return errorHandler("Error updating savings account. Please try again.", $e);
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

    private function generateSavingsAccountNumber()
    {
        do {
            $number = 'SAV-' . date('Y') . '-' . str_pad((SavingsAccount::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
        } while (SavingsAccount::where('account_number', $number)->exists());

        return $number;
    }
}
