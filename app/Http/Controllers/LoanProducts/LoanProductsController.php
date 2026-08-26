<?php

namespace App\Http\Controllers\LoanProducts;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $loanProducts = LoanProduct::latest()->get();
        return view('loan_products.index', compact('loanProducts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);

        $accounts = collect();
        return view('loan_products.create', compact('accounts'));
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
            'code' => ['required', 'string', 'max:50', 'unique:loan_products,code'],
            'name' => ['required', 'string', 'max:255', 'unique:loan_products,name'],
            'description' => ['nullable', 'string'],

            'loan_principal_account_id' => ['nullable', 'integer'],
            'interest_receivable_account_id' => ['nullable', 'integer'],
            'interest_income_account_id' => ['nullable', 'integer'],
            'penalty_receivable_account_id' => ['nullable', 'integer'],
            'penalty_income_account_id' => ['nullable', 'integer'],
            'processing_fee_income_account_id' => ['nullable', 'integer'],

            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'gt:0'],

            'minimum_repayment_months' => ['required', 'integer', 'min:1'],
            'maximum_repayment_months' => ['required', 'integer', 'min:1'],

            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_method' => ['required', Rule::in(['flat_rate', 'reducing_balance'])],
            'interest_frequency' => ['required', Rule::in(['annual', 'monthly', 'one_time'])],

            'requires_guarantors' => ['nullable', 'boolean'],
            'minimum_guarantors' => ['nullable', 'integer', 'min:0'],
            'maximum_guarantors' => ['nullable', 'integer', 'min:1'],
            'minimum_guarantor_coverage_percentage' => ['nullable', 'numeric', 'min:0'],

            'minimum_membership_months' => ['nullable', 'integer', 'min:0'],
            'minimum_share_contribution' => ['nullable', 'numeric', 'min:0'],
            'minimum_monthly_contribution' => ['nullable', 'numeric', 'min:0'],
            'share_multiplier' => ['nullable', 'numeric', 'gt:0'],
            'maximum_active_loans' => ['nullable', 'integer', 'min:0'],

            'allows_top_up' => ['nullable', 'boolean'],
            'allows_refinancing' => ['nullable', 'boolean'],
            'minimum_repaid_percentage_for_top_up' => ['nullable', 'numeric', 'between:0,100'],

            'eligibility_rules' => ['nullable'],

            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'insurance_fee_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'grace_period_days' => ['nullable', 'integer', 'min:0'],

            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        try {

            if (!empty($validated['maximum_amount']) &&
                $validated['maximum_amount'] < $validated['minimum_amount']) {
                throw ValidationException::withMessages([
                    'maximum_amount' => 'Maximum amount cannot be less than the minimum amount.',
                ]);
            }

            if ($validated['maximum_repayment_months'] < $validated['minimum_repayment_months']) {
                throw ValidationException::withMessages([
                    'maximum_repayment_months' => 'Maximum repayment months cannot be less than minimum repayment months.',
                ]);
            }

            $requiresGuarantors = $request->boolean('requires_guarantors');
            $allowsTopUp = $request->boolean('allows_top_up');

            if ($requiresGuarantors) {
                $minimumGuarantors = (int) ($validated['minimum_guarantors'] ?? 0);
                $maximumGuarantors = $validated['maximum_guarantors'] ?? null;

                if ($minimumGuarantors < 1) {
                    throw ValidationException::withMessages([
                        'minimum_guarantors' => 'At least one guarantor is required when guarantors are enabled.',
                    ]);
                }

                if ($maximumGuarantors && $maximumGuarantors < $minimumGuarantors) {
                    throw ValidationException::withMessages([
                        'maximum_guarantors' => 'Maximum guarantors cannot be less than minimum guarantors.',
                    ]);
                }
            }

            if ($allowsTopUp && empty($validated['minimum_repaid_percentage_for_top_up'])) {
                throw ValidationException::withMessages([
                    'minimum_repaid_percentage_for_top_up' => 'Specify the minimum repaid percentage required for a top-up.',
                ]);
            }

            $eligibilityRules = null;

            if (!empty($validated['eligibility_rules'])) {
                if (is_array($validated['eligibility_rules'])) {
                    $eligibilityRules = $validated['eligibility_rules'];
                } else {
                    $eligibilityRules = json_decode($validated['eligibility_rules'], true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw ValidationException::withMessages([
                            'eligibility_rules' => 'Eligibility rules must contain valid JSON.',
                        ]);
                    }
                }
            }

            $loanProduct = DB::transaction(function () use ($request, $validated, $eligibilityRules, $requiresGuarantors, $allowsTopUp) {
                return LoanProduct::create([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,

                    'loan_principal_account_id' => $validated['loan_principal_account_id'],
                    'interest_receivable_account_id' => $validated['interest_receivable_account_id'],
                    'interest_income_account_id' => $validated['interest_income_account_id'],
                    'penalty_receivable_account_id' => $validated['penalty_receivable_account_id'],
                    'penalty_income_account_id' => $validated['penalty_income_account_id'],
                    'processing_fee_income_account_id' => $validated['processing_fee_income_account_id'],

                    'minimum_amount' => $validated['minimum_amount'],
                    'maximum_amount' => $validated['maximum_amount'] ?? null,

                    'minimum_repayment_months' => $validated['minimum_repayment_months'],
                    'maximum_repayment_months' => $validated['maximum_repayment_months'],

                    'interest_rate' => $validated['interest_rate'],
                    'interest_method' => $validated['interest_method'],
                    'interest_frequency' => $validated['interest_frequency'],

                    'requires_guarantors' => $requiresGuarantors,
                    'minimum_guarantors' => $requiresGuarantors ? ($validated['minimum_guarantors'] ?? 1) : 0,
                    'maximum_guarantors' => $requiresGuarantors ? ($validated['maximum_guarantors'] ?? null) : null,
                    'minimum_guarantor_coverage_percentage' => $requiresGuarantors
                        ? ($validated['minimum_guarantor_coverage_percentage'] ?? 100)
                        : 0,

                    'minimum_membership_months' => $validated['minimum_membership_months'] ?? 0,
                    'minimum_share_contribution' => $validated['minimum_share_contribution'] ?? 0,
                    'minimum_monthly_contribution' => $validated['minimum_monthly_contribution'] ?? 0,
                    'share_multiplier' => $validated['share_multiplier'] ?? null,
                    'maximum_active_loans' => $validated['maximum_active_loans'] ?? null,

                    'allows_top_up' => $allowsTopUp,
                    'allows_refinancing' => $request->boolean('allows_refinancing'),
                    'minimum_repaid_percentage_for_top_up' => $allowsTopUp
                        ? ($validated['minimum_repaid_percentage_for_top_up'] ?? null)
                        : null,

                    'eligibility_rules' => $eligibilityRules,

                    'application_fee' => $validated['application_fee'] ?? 0,
                    'processing_fee_percentage' => $validated['processing_fee_percentage'] ?? 0,
                    'insurance_fee_percentage' => $validated['insurance_fee_percentage'] ?? 0,
                    'grace_period_days' => $validated['grace_period_days'] ?? 0,

                    'is_active' => $request->boolean('is_active'),
                    'effective_from' => $validated['effective_from'] ?? null,
                    'effective_to' => $validated['effective_to'] ?? null,
                ]);
            });

            return redirect()
                // ->route('loan_products.show', $loanProduct->id)
                ->route('loan_products.index')
                ->with('success', 'Loan product created successfully.');
        } catch (\Exception $e) {
            return errorHandler("The loan product could not be submitted. Please try again.", $e);
        }            
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(LoanProduct $loanProduct)
    {
        return view('loan_products.view', compact('loanProduct'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LoanProduct $loanProduct)
    {
        // Inject the key-value pair into the request payload
        $payload = $loanProduct->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $accounts = collect();
        return view('loan_products.edit', compact('loanProduct', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LoanProduct $loanProduct)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:loan_products,code'],
            'name' => ['required', 'string', 'max:255', 'unique:loan_products,name'],
            'description' => ['nullable', 'string'],

            'loan_principal_account_id' => ['nullable', 'integer'],
            'interest_receivable_account_id' => ['nullable', 'integer'],
            'interest_income_account_id' => ['nullable', 'integer'],
            'penalty_receivable_account_id' => ['nullable', 'integer'],
            'penalty_income_account_id' => ['nullable', 'integer'],
            'processing_fee_income_account_id' => ['nullable', 'integer'],

            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'gt:0'],

            'minimum_repayment_months' => ['required', 'integer', 'min:1'],
            'maximum_repayment_months' => ['required', 'integer', 'min:1'],

            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_method' => ['required', Rule::in(['flat_rate', 'reducing_balance'])],
            'interest_frequency' => ['required', Rule::in(['annual', 'monthly', 'one_time'])],

            'requires_guarantors' => ['nullable', 'boolean'],
            'minimum_guarantors' => ['nullable', 'integer', 'min:0'],
            'maximum_guarantors' => ['nullable', 'integer', 'min:1'],
            'minimum_guarantor_coverage_percentage' => ['nullable', 'numeric', 'min:0'],

            'minimum_membership_months' => ['nullable', 'integer', 'min:0'],
            'minimum_share_contribution' => ['nullable', 'numeric', 'min:0'],
            'minimum_monthly_contribution' => ['nullable', 'numeric', 'min:0'],
            'share_multiplier' => ['nullable', 'numeric', 'gt:0'],
            'maximum_active_loans' => ['nullable', 'integer', 'min:0'],

            'allows_top_up' => ['nullable', 'boolean'],
            'allows_refinancing' => ['nullable', 'boolean'],
            'minimum_repaid_percentage_for_top_up' => ['nullable', 'numeric', 'between:0,100'],

            'eligibility_rules' => ['nullable'],

            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'insurance_fee_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'grace_period_days' => ['nullable', 'integer', 'min:0'],

            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        try {

            if (!empty($validated['maximum_amount']) &&
                $validated['maximum_amount'] < $validated['minimum_amount']) {
                throw ValidationException::withMessages([
                    'maximum_amount' => 'Maximum amount cannot be less than the minimum amount.',
                ]);
            }

            if ($validated['maximum_repayment_months'] < $validated['minimum_repayment_months']) {
                throw ValidationException::withMessages([
                    'maximum_repayment_months' => 'Maximum repayment months cannot be less than minimum repayment months.',
                ]);
            }

            $requiresGuarantors = $request->boolean('requires_guarantors');
            $allowsTopUp = $request->boolean('allows_top_up');

            if ($requiresGuarantors) {
                $minimumGuarantors = (int) ($validated['minimum_guarantors'] ?? 0);
                $maximumGuarantors = $validated['maximum_guarantors'] ?? null;

                if ($minimumGuarantors < 1) {
                    throw ValidationException::withMessages([
                        'minimum_guarantors' => 'At least one guarantor is required when guarantors are enabled.',
                    ]);
                }

                if ($maximumGuarantors && $maximumGuarantors < $minimumGuarantors) {
                    throw ValidationException::withMessages([
                        'maximum_guarantors' => 'Maximum guarantors cannot be less than minimum guarantors.',
                    ]);
                }
            }

            if ($allowsTopUp && empty($validated['minimum_repaid_percentage_for_top_up'])) {
                throw ValidationException::withMessages([
                    'minimum_repaid_percentage_for_top_up' => 'Specify the minimum repaid percentage required for a top-up.',
                ]);
            }

            $eligibilityRules = null;

            if (!empty($validated['eligibility_rules'])) {
                if (is_array($validated['eligibility_rules'])) {
                    $eligibilityRules = $validated['eligibility_rules'];
                } else {
                    $eligibilityRules = json_decode($validated['eligibility_rules'], true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw ValidationException::withMessages([
                            'eligibility_rules' => 'Eligibility rules must contain valid JSON.',
                        ]);
                    }
                }
            }

            DB::transaction(function () use ($request, $validated, $eligibilityRules, $requiresGuarantors, $allowsTopUp, $loanProduct) {
                return $loanProduct->update([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,

                    'loan_principal_account_id' => $validated['loan_principal_account_id'],
                    'interest_receivable_account_id' => $validated['interest_receivable_account_id'],
                    'interest_income_account_id' => $validated['interest_income_account_id'],
                    'penalty_receivable_account_id' => $validated['penalty_receivable_account_id'],
                    'penalty_income_account_id' => $validated['penalty_income_account_id'],
                    'processing_fee_income_account_id' => $validated['processing_fee_income_account_id'],

                    'minimum_amount' => $validated['minimum_amount'],
                    'maximum_amount' => $validated['maximum_amount'] ?? null,

                    'minimum_repayment_months' => $validated['minimum_repayment_months'],
                    'maximum_repayment_months' => $validated['maximum_repayment_months'],

                    'interest_rate' => $validated['interest_rate'],
                    'interest_method' => $validated['interest_method'],
                    'interest_frequency' => $validated['interest_frequency'],

                    'requires_guarantors' => $requiresGuarantors,
                    'minimum_guarantors' => $requiresGuarantors ? ($validated['minimum_guarantors'] ?? 1) : 0,
                    'maximum_guarantors' => $requiresGuarantors ? ($validated['maximum_guarantors'] ?? null) : null,
                    'minimum_guarantor_coverage_percentage' => $requiresGuarantors
                        ? ($validated['minimum_guarantor_coverage_percentage'] ?? 100)
                        : 0,

                    'minimum_membership_months' => $validated['minimum_membership_months'] ?? 0,
                    'minimum_share_contribution' => $validated['minimum_share_contribution'] ?? 0,
                    'minimum_monthly_contribution' => $validated['minimum_monthly_contribution'] ?? 0,
                    'share_multiplier' => $validated['share_multiplier'] ?? null,
                    'maximum_active_loans' => $validated['maximum_active_loans'] ?? null,

                    'allows_top_up' => $allowsTopUp,
                    'allows_refinancing' => $request->boolean('allows_refinancing'),
                    'minimum_repaid_percentage_for_top_up' => $allowsTopUp
                        ? ($validated['minimum_repaid_percentage_for_top_up'] ?? null)
                        : null,

                    'eligibility_rules' => $eligibilityRules,

                    'application_fee' => $validated['application_fee'] ?? 0,
                    'processing_fee_percentage' => $validated['processing_fee_percentage'] ?? 0,
                    'insurance_fee_percentage' => $validated['insurance_fee_percentage'] ?? 0,
                    'grace_period_days' => $validated['grace_period_days'] ?? 0,

                    'is_active' => $request->boolean('is_active'),
                    'effective_from' => $validated['effective_from'] ?? null,
                    'effective_to' => $validated['effective_to'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_products.show', $loanProduct)
                ->with('success', 'Loan product updated successfully.');
        } catch (\Exception $e) {
            return errorHandler("The loan product could not be updated. Please try again.", $e);
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

    public function status(LoanProduct $loanProduct)
    {
        if (request('is_active') == 1) {
            $loanProduct->update(['is_active' => 1]);
            return redirect()
                ->route('loan_products.show', $loanProduct)
                ->with('success', 'The loan product has been activated successfully.');            
        } else {
            $loanProduct->update(['is_active' => 0]);
            return redirect()
                ->route('loan_products.show', $loanProduct)
                ->with('success', 'The loan product has been deactivated successfully.');  
        }
    }
}
