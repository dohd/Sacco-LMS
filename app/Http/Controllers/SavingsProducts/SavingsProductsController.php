<?php

namespace App\Http\Controllers\SavingsProducts;

use App\Http\Controllers\Controller;
use App\Models\Savings\SavingsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SavingsProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $savingsProducts = SavingsProduct::latest()->get();

        return view('savings_products.index', compact('savingsProducts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accounts = collect();
        
        return view('savings_products.create', compact('accounts'));
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
            'code' => ['required', 'string', 'max:255', 'unique:savings_products,code'],
            'name' => ['required', 'string', 'max:255', 'unique:savings_products,name'],
            'product_type' => ['required', Rule::in(['compulsory', 'voluntary', 'fixed_deposit'])],
            'savings_control_account_id' => ['required', 'integer'],
            'interest_expense_account_id' => ['required', 'integer'],
            'fee_income_account_id' => ['required', 'integer'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_frequency' => ['required', Rule::in(['annual', 'monthly', 'quarterly', 'semi_annual', 'at_maturity'])],
            'interest_calculation_method' => ['required', Rule::in(['simple', 'compound'])],
            'minimum_term_months' => ['nullable', 'integer', 'min:0'],
            'maximum_term_months' => ['nullable', 'integer', 'gte:minimum_term_months'],
            'allows_premature_withdrawal' => ['nullable', 'boolean'],
            'premature_withdrawal_penalty_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_rollover' => ['nullable', 'boolean'],
            'rollover_option' => ['nullable', Rule::in(['principal_only', 'principal_and_interest'])],
            'maximum_balance' => ['nullable', 'numeric', 'min:0'],
            'allows_partial_withdrawals' => ['nullable', 'boolean'],
            'minimum_balance' => ['required', 'numeric', 'min:0'],
            'minimum_monthly_contribution' => ['required', 'numeric', 'min:0'],
            'allows_withdrawals' => ['nullable', 'boolean'],
            'withdrawal_notice_days' => ['nullable', 'integer', 'min:0'],
            'can_secure_loan' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['allows_premature_withdrawal'] = $validated['allows_premature_withdrawal'] ?? null;
        $validated['auto_rollover'] = $validated['auto_rollover'] ?? null;

        // Product-specific business rules
        if ($validated['allows_premature_withdrawal']) {
            if ((float) $validated['premature_withdrawal_penalty_percentage'] <= 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'premature_withdrawal_penalty_percentage' =>
                            'A premature withdrawal penalty must be greater than 0 when premature withdrawals are allowed.',
                    ]);
            }
        } else {
            $validated['premature_withdrawal_penalty_percentage'] = 0;
        }

        if ($validated['auto_rollover'] && empty($validated['rollover_option'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'rollover_option' =>
                        'Please select a rollover option when automatic rollover is enabled.',
                ]);
        }

        if (!$validated['auto_rollover']) {
            $validated['rollover_option'] = null;
        }

        if ($validated['product_type'] !== 'fixed_deposit') {
            $validated['minimum_term_months'] = 0;
            $validated['maximum_term_months'] = null;
            $validated['allows_premature_withdrawal'] = false;
            $validated['premature_withdrawal_penalty_percentage'] = 0;
            $validated['auto_rollover'] = false;
            $validated['rollover_option'] = null;
            $validated['allows_partial_withdrawals'] = false;
        }

        if ($validated['product_type'] === 'fixed_deposit') {
            if ((int) $validated['minimum_term_months'] < 1) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'minimum_term_months' =>
                            'A fixed deposit product must have a minimum term of at least 1 month.',
                    ]);
            }
        }

        if ($validated['minimum_balance'] > 0 && $validated['maximum_balance'] !== null) {
            if ($validated['minimum_balance'] > $validated['maximum_balance']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'maximum_balance' =>
                            'Maximum balance cannot be less than minimum balance.',
                    ]);
            }
        }

        // Verify that the GL accounts are distinct
        $accountIds = [
            $validated['savings_control_account_id'],
            $validated['interest_expense_account_id'],
            $validated['fee_income_account_id'],
        ];

        // if (count($accountIds) !== count(array_unique($accountIds))) {
        //     return back()
        //         ->withInput()
        //         ->withErrors([
        //             'savings_control_account_id' =>
        //                 'The savings control, interest expense and fee income accounts must be different accounts.',
        //         ]);
        // }

        try {
            DB::transaction(function() use($validated) {
                $product = SavingsProduct::create([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'product_type' => $validated['product_type'],
                    'savings_control_account_id' => $validated['savings_control_account_id'],
                    'interest_expense_account_id' => $validated['interest_expense_account_id'],
                    'fee_income_account_id' => $validated['fee_income_account_id'],
                    'interest_rate' => $validated['interest_rate'],
                    'interest_frequency' => $validated['interest_frequency'],
                    'interest_calculation_method' => $validated['interest_calculation_method'],
                    'minimum_term_months' => $validated['minimum_term_months'],
                    'maximum_term_months' => $validated['maximum_term_months'],
                    'allows_premature_withdrawal' => $validated['allows_premature_withdrawal'],
                    'premature_withdrawal_penalty_percentage' => $validated['premature_withdrawal_penalty_percentage'],
                    'auto_rollover' => $validated['auto_rollover'],
                    'rollover_option' => $validated['rollover_option'],
                    'maximum_balance' => $validated['maximum_balance'],
                    'allows_partial_withdrawals' => $validated['allows_partial_withdrawals'],
                    'minimum_balance' => $validated['minimum_balance'],
                    'minimum_monthly_contribution' => $validated['minimum_monthly_contribution'],
                    'allows_withdrawals' => $validated['allows_withdrawals'],
                    'withdrawal_notice_days' => $validated['withdrawal_notice_days'],
                    'can_secure_loan' => $validated['can_secure_loan'],
                    'is_active' => $validated['is_active'],
                ]);                
            });

            return redirect()->route('savings_products.index')->with('success', 'Savings product created successfully.');
        } catch (\Throwable $e) {
            return errorHandler("Error creating savings product. Please try again", $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SavingsProduct $savingsProduct)
    {
        return view('savings_products.view', compact('savingsProduct'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(SavingsProduct $savingsProduct)
    {
        $accounts = collect();
        
        return view('savings_products.edit', compact('savingsProduct', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SavingsProduct $savingsProduct)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(['compulsory', 'voluntary', 'fixed_deposit'])],
            'savings_control_account_id' => ['required', 'integer'],
            'interest_expense_account_id' => ['required', 'integer'],
            'fee_income_account_id' => ['required', 'integer'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_frequency' => ['required', Rule::in(['annual', 'monthly', 'quarterly', 'semi_annual', 'at_maturity'])],
            'interest_calculation_method' => ['required', Rule::in(['simple', 'compound'])],
            'minimum_term_months' => ['nullable', 'integer', 'min:0'],
            'maximum_term_months' => ['nullable', 'integer', 'gte:minimum_term_months'],
            'allows_premature_withdrawal' => ['nullable', 'boolean'],
            'premature_withdrawal_penalty_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_rollover' => ['nullable', 'boolean'],
            'rollover_option' => ['nullable', Rule::in(['principal_only', 'principal_and_interest'])],
            'maximum_balance' => ['nullable', 'numeric', 'min:0'],
            'allows_partial_withdrawals' => ['nullable', 'boolean'],
            'minimum_balance' => ['required', 'numeric', 'min:0'],
            'minimum_monthly_contribution' => ['required', 'numeric', 'min:0'],
            'allows_withdrawals' => ['nullable', 'boolean'],
            'withdrawal_notice_days' => ['nullable', 'integer', 'min:0'],
            'can_secure_loan' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['allows_premature_withdrawal'] = $validated['allows_premature_withdrawal'] ?? null;
        $validated['auto_rollover'] = $validated['auto_rollover'] ?? null;

        // Product-specific business rules
        if ($validated['allows_premature_withdrawal']) {
            if ((float) $validated['premature_withdrawal_penalty_percentage'] <= 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'premature_withdrawal_penalty_percentage' =>
                            'A premature withdrawal penalty must be greater than 0 when premature withdrawals are allowed.',
                    ]);
            }
        } else {
            $validated['premature_withdrawal_penalty_percentage'] = 0;
        }

        if ($validated['auto_rollover'] && empty($validated['rollover_option'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'rollover_option' =>
                        'Please select a rollover option when automatic rollover is enabled.',
                ]);
        }

        if (!$validated['auto_rollover']) {
            $validated['rollover_option'] = null;
        }

        if ($validated['product_type'] !== 'fixed_deposit') {
            $validated['minimum_term_months'] = 0;
            $validated['maximum_term_months'] = null;
            $validated['allows_premature_withdrawal'] = false;
            $validated['premature_withdrawal_penalty_percentage'] = 0;
            $validated['auto_rollover'] = false;
            $validated['rollover_option'] = null;
            $validated['allows_partial_withdrawals'] = false;
        }

        if ($validated['product_type'] === 'fixed_deposit') {
            if ((int) $validated['minimum_term_months'] < 1) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'minimum_term_months' =>
                            'A fixed deposit product must have a minimum term of at least 1 month.',
                    ]);
            }
        }

        if ($validated['minimum_balance'] > 0 && $validated['maximum_balance'] !== null) {
            if ($validated['minimum_balance'] > $validated['maximum_balance']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'maximum_balance' =>
                            'Maximum balance cannot be less than minimum balance.',
                    ]);
            }
        }

        // Verify that the GL accounts are distinct
        $accountIds = [
            $validated['savings_control_account_id'],
            $validated['interest_expense_account_id'],
            $validated['fee_income_account_id'],
        ];

        // if (count($accountIds) !== count(array_unique($accountIds))) {
        //     return back()
        //         ->withInput()
        //         ->withErrors([
        //             'savings_control_account_id' =>
        //                 'The savings control, interest expense and fee income accounts must be different accounts.',
        //         ]);
        // }

        try {
            DB::transaction(function() use($savingsProduct, $validated) {
                $savingsProduct->update([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'product_type' => $validated['product_type'],
                    'savings_control_account_id' => $validated['savings_control_account_id'],
                    'interest_expense_account_id' => $validated['interest_expense_account_id'],
                    'fee_income_account_id' => $validated['fee_income_account_id'],
                    'interest_rate' => $validated['interest_rate'],
                    'interest_frequency' => $validated['interest_frequency'],
                    'interest_calculation_method' => $validated['interest_calculation_method'],
                    'minimum_term_months' => $validated['minimum_term_months'],
                    'maximum_term_months' => $validated['maximum_term_months'],
                    'allows_premature_withdrawal' => $validated['allows_premature_withdrawal'],
                    'premature_withdrawal_penalty_percentage' => $validated['premature_withdrawal_penalty_percentage'],
                    'auto_rollover' => $validated['auto_rollover'],
                    'rollover_option' => $validated['rollover_option'],
                    'maximum_balance' => $validated['maximum_balance'],
                    'allows_partial_withdrawals' => $validated['allows_partial_withdrawals'],
                    'minimum_balance' => $validated['minimum_balance'],
                    'minimum_monthly_contribution' => $validated['minimum_monthly_contribution'],
                    'allows_withdrawals' => $validated['allows_withdrawals'],
                    'withdrawal_notice_days' => $validated['withdrawal_notice_days'],
                    'can_secure_loan' => $validated['can_secure_loan'],
                    'is_active' => $validated['is_active'],
                ]);                
            });

            return redirect()->route('savings_products.show', $savingsProduct)->with('success', 'Savings product updated successfully.');
        } catch (\Throwable $e) {
            return errorHandler("Error updating savings product. Please try again", $e);
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
}
