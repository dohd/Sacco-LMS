<?php

namespace App\Http\Controllers\LoanApplications;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanApproval;
use App\Models\LoanApplications\LoanGuarantor;
use App\Models\LoanApplications\LoanProduct;
use App\Models\LoanApplications\LoanSecurity;
use App\Models\LoanApplications\LoanWitness;
use App\Models\Memberships\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanApplicationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $loanApplications = LoanApplication::latest()->get();

        return view('loan_applications.index', compact('loanApplications'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $applicationNumber = $this->generateApplicationNumber();
        $members = Member::all();
        $loanProducts = LoanProduct::all();

        return view('loan_applications.create', compact('applicationNumber', 'members', 'loanProducts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_old(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'loan_product_id' => ['required', 'integer', 'exists:loan_products,id'],
            'amount_requested' => ['required', 'numeric', 'gt:0'],
            'amount_in_words' => ['nullable', 'string'],
            'repayment_period_months' => ['required', 'integer', 'min:1'],
            'monthly_installment' => ['required', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date'],
            'payment_mode' => ['required', Rule::in(['standing_order', 'check_off', 'post_dated_cheques', 'cash'])],
            'loan_purpose' => ['required', 'string'],
            'purpose_amount' => ['nullable', 'numeric', 'min:0'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['permanent', 'seasonal', 'contract', 'self_employed'])],
            'work_station' => ['nullable', 'string', 'max:255'],
            'employer_postal_address' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_postal_address' => ['nullable', 'string', 'max:255'],
            'total_share_contribution' => ['nullable', 'numeric', 'min:0'],
            'outstanding_loan_balance' => ['nullable', 'numeric', 'min:0'],
            'monthly_share_contribution' => ['nullable', 'numeric', 'min:0'],
            'security_shares' => ['nullable', 'numeric', 'min:0'],
            'guarantor_security' => ['nullable', 'numeric', 'min:0'],
            'applicant_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'declaration_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['nullable', Rule::in(['draft', 'submitted'])],
        ]);

        $signaturePath = null;

        try {
            $loanProduct = LoanProduct::findOrFail($validated['loan_product_id']);

            if (!$loanProduct->is_active) {
                throw ValidationException::withMessages([
                    'loan_product_id' => 'The selected loan product is not currently active.',
                ]);
            }

            if ($validated['amount_requested'] < $loanProduct->minimum_amount) {
                throw ValidationException::withMessages([
                    'amount_requested' => 'The requested amount is below the minimum allowed for this loan product.',
                ]);
            }

            if ($loanProduct->maximum_amount !== null &&
                $validated['amount_requested'] > $loanProduct->maximum_amount) {
                throw ValidationException::withMessages([
                    'amount_requested' => 'The requested amount exceeds the maximum allowed for this loan product.',
                ]);
            }

            if ($validated['repayment_period_months'] < $loanProduct->minimum_repayment_months ||
                $validated['repayment_period_months'] > $loanProduct->maximum_repayment_months) {
                throw ValidationException::withMessages([
                    'repayment_period_months' => 'The repayment period is outside the allowed range for this loan product.',
                ]);
            }
            
            if ($request->hasFile('applicant_signature')) {
                $signaturePath = $request->file('applicant_signature')->store('loan_applications/signatures', 'public');
            }

            $loanApplication = DB::transaction(function () use ($validated, $signaturePath) {
                $application = LoanApplication::create([
                    'member_id' => $validated['member_id'],
                    'loan_product_id' => $validated['loan_product_id'],
                    'application_number' => $this->generateApplicationNumber(),
                    'amount_requested' => $validated['amount_requested'],
                    'amount_in_words' => $validated['amount_in_words'] ?? null,
                    'repayment_period_months' => $validated['repayment_period_months'],
                    'monthly_installment' => $validated['monthly_installment'],
                    'required_date' => $validated['required_date'] ?? null,
                    'payment_mode' => $validated['payment_mode'],
                    'loan_purpose' => $validated['loan_purpose'],
                    'purpose_amount' => $validated['purpose_amount'] ?? null,
                    'employer_name' => $validated['employer_name'] ?? null,
                    'employment_type' => $validated['employment_type'] ?? null,
                    'work_station' => $validated['work_station'] ?? null,
                    'employer_postal_address' => $validated['employer_postal_address'] ?? null,
                    'business_name' => $validated['business_name'] ?? null,
                    'business_postal_address' => $validated['business_postal_address'] ?? null,
                    'total_share_contribution' => $validated['total_share_contribution'] ?? 0,
                    'outstanding_loan_balance' => $validated['outstanding_loan_balance'] ?? 0,
                    'monthly_share_contribution' => $validated['monthly_share_contribution'] ?? 0,
                    'security_shares' => $validated['security_shares'] ?? 0,
                    'guarantor_security' => $validated['guarantor_security'] ?? 0,
                    'applicant_signature' => $signaturePath,
                    'declaration_date' => $validated['declaration_date'] ?? null,
                    'status' => $validated['status'] ?? 'submitted',
                ]);

                return $application;
            });

            return redirect()
                ->route('loan_applications.show', $loanApplication->id)
                ->with('success', 'Loan application created successfully.');
        } catch (\Exception $e) {
            if (isset($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return errorHandler("The loan application could not be created. Please try again.", $e);
        }            
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(LoanApplication $application)
    {
        return view('loan_applications.view', compact('application'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->submission_action === 'draft';

        $validated = $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],

            'member_id' => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:members,id'],
            'loan_product_id' => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:loan_products,id'],

            'amount_requested' => [$isDraft ? 'nullable' : 'required', 'numeric', 'gt:0'],
            'amount_in_words' => ['nullable', 'string'],

            'repayment_period_months' => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'monthly_installment' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],

            'required_date' => ['nullable', 'date'],
            'payment_mode' => [$isDraft ? 'nullable' : 'required', Rule::in(['standing_order', 'check_off', 'post_dated_cheques', 'cash'])],

            'loan_purpose' => [$isDraft ? 'nullable' : 'required', 'string'],
            'purpose_amount' => ['nullable', 'numeric', 'min:0'],

            'employer_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['permanent', 'seasonal', 'contract', 'self_employed'])],
            'work_station' => ['nullable', 'string', 'max:255'],
            'employer_postal_address' => ['nullable', 'string', 'max:255'],

            'business_name' => ['nullable', 'string', 'max:255'],
            'business_postal_address' => ['nullable', 'string', 'max:255'],

            'total_share_contribution' => ['nullable', 'numeric', 'min:0'],
            'outstanding_loan_balance' => ['nullable', 'numeric', 'min:0'],
            'monthly_share_contribution' => ['nullable', 'numeric', 'min:0'],

            'applicant_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'declaration_date' => ['nullable', 'date', 'before_or_equal:today'],

            'guarantors' => ['nullable', 'array'],
            'guarantors.*.member_id' => ['nullable', 'integer', 'exists:members,id'],
            'guarantors.*.guarantor_name' => ['nullable', 'string', 'max:255'],
            'guarantors.*.member_number' => ['nullable', 'string', 'max:100'],
            'guarantors.*.shares_offered' => ['nullable', 'numeric', 'min:0'],
            'guarantors.*.national_id' => ['nullable', 'string', 'max:100'],
            'guarantors.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'guarantors.*.witness_name' => ['nullable', 'string', 'max:255'],

            'securities' => ['nullable', 'array'],
            'securities.*.security_type' => ['nullable', Rule::in(['pledged_shares', 'additional_collateral'])],
            'securities.*.security_name' => ['nullable', 'string', 'max:255'],
            'securities.*.description' => ['nullable', 'string'],
            'securities.*.security_value' => ['nullable', 'numeric', 'min:0'],
            'securities.*.reference_number' => ['nullable', 'string', 'max:255'],
            'securities.*.owner_name' => ['nullable', 'string', 'max:255'],
            'securities.*.owner_national_id' => ['nullable', 'string', 'max:100'],
            'securities.*.supporting_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'securities.*.remarks' => ['nullable', 'string'],

            'witnesses' => ['nullable', 'array'],
            'witnesses.*.member_id' => ['nullable', 'integer', 'exists:members,id'],
            'witnesses.*.name' => ['nullable', 'string', 'max:255'],
            'witnesses.*.national_id' => ['nullable', 'string', 'max:100'],
            'witnesses.*.payroll_number' => ['nullable', 'string', 'max:100'],
            'witnesses.*.employer' => ['nullable', 'string', 'max:255'],
            'witnesses.*.station' => ['nullable', 'string', 'max:255'],
            'witnesses.*.address' => ['nullable', 'string'],
            'witnesses.*.phone' => ['nullable', 'string', 'max:50'],
            'witnesses.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'witnesses.*.signed_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $loanProduct = null;

        if (!empty($validated['loan_product_id'])) {
            $loanProduct = LoanProduct::findOrFail($validated['loan_product_id']);

            if (!$loanProduct->is_active && !$isDraft) {
                throw ValidationException::withMessages([
                    'loan_product_id' => 'The selected loan product is inactive.',
                ]);
            }
        }

        if (!$isDraft && $loanProduct) {
            $this->validateLoanProductRules($validated, $loanProduct);
            $this->validateGuarantors($validated, $loanProduct);
        }

        $uploadedFiles = [];

        try {
            $application = DB::transaction(function () use ($request, $validated, $loanProduct, $isDraft, &$uploadedFiles) {

                $signaturePath = null;

                if ($request->hasFile('applicant_signature')) {
                    $signaturePath = $request->file('applicant_signature')
                        ->store('loan_applications/applicant-signatures', 'public');

                    $uploadedFiles[] = $signaturePath;
                }

                $monthlyInstallment = 0;

                if ($loanProduct && !empty($validated['amount_requested']) && !empty($validated['repayment_period_months'])) {
                    $monthlyInstallment = $this->calculateMonthlyInstallment(
                        $validated['amount_requested'],
                        $validated['repayment_period_months'],
                        $loanProduct
                    );
                }

                $totalGuarantorSecurity = collect($validated['guarantors'] ?? [])
                    ->sum(fn($item) => (float) ($item['shares_offered'] ?? 0));

                $securityShares = collect($validated['securities'] ?? [])
                    ->where('security_type', 'pledged_shares')
                    ->sum(fn($item) => (float) ($item['security_value'] ?? 0));

                $application = LoanApplication::create([
                    'member_id' => $validated['member_id'] ?? null,
                    'loan_product_id' => $validated['loan_product_id'] ?? null,

                    'application_number' => $this->generateApplicationNumber(),
                    'amount_requested' => $validated['amount_requested'] ?? 0,
                    'amount_in_words' => $validated['amount_in_words'] ?? null,

                    'repayment_period_months' => $validated['repayment_period_months'] ?? 0,
                    'monthly_installment' => $monthlyInstallment,

                    'required_date' => $validated['required_date'] ?? null,
                    'payment_mode' => $validated['payment_mode'] ?? null,

                    'loan_purpose' => $validated['loan_purpose'] ?? '',
                    'purpose_amount' => $validated['purpose_amount'] ?? null,

                    'employer_name' => $validated['employer_name'] ?? null,
                    'employment_type' => $validated['employment_type'] ?? null,
                    'work_station' => $validated['work_station'] ?? null,
                    'employer_postal_address' => $validated['employer_postal_address'] ?? null,

                    'business_name' => $validated['business_name'] ?? null,
                    'business_postal_address' => $validated['business_postal_address'] ?? null,

                    'total_share_contribution' => $validated['total_share_contribution'] ?? 0,
                    'outstanding_loan_balance' => $validated['outstanding_loan_balance'] ?? 0,
                    'monthly_share_contribution' => $validated['monthly_share_contribution'] ?? 0,

                    'security_shares' => $securityShares,
                    'guarantor_security' => $totalGuarantorSecurity,

                    'applicant_signature' => $signaturePath,
                    'declaration_date' => $validated['declaration_date'] ?? null,

                    'status' => $isDraft ? 'draft' : 'submitted',

                    'drafted_by' => $isDraft ? Auth::id() : null,
                    'submitted_by' => $isDraft ? null : Auth::id(),

                    'drafted_at' => $isDraft ? now() : null,
                    'submitted_at' => $isDraft ? null : now(),
                ]);

                foreach ($validated['guarantors'] ?? [] as $index => $guarantor) {
                    if (empty($guarantor['member_id'])) {
                        continue;
                    }

                    $signature = null;

                    if ($request->hasFile("guarantors.$index.signature")) {
                        $signature = $request->file("guarantors.$index.signature")
                            ->store('loan_applications/guarantors', 'public');

                        $uploadedFiles[] = $signature;
                    }

                    LoanGuarantor::create([
                        'loan_application_id' => $application->id,
                        'member_id' => $guarantor['member_id'],
                        'guarantor_name' => $guarantor['guarantor_name'],
                        'member_number' => $guarantor['member_number'],
                        'shares_offered' => $guarantor['shares_offered'] ?? 0,
                        'national_id' => $guarantor['national_id'],
                        'signature' => $signature,
                        'witness_name' => $guarantor['witness_name'] ?? null,
                    ]);
                }

                foreach ($validated['securities'] ?? [] as $index => $security) {
                    if (empty($security['security_type']) || empty($security['security_name'])) {
                        continue;
                    }

                    $document = null;

                    if ($request->hasFile("securities.$index.supporting_document")) {
                        $document = $request->file("securities.$index.supporting_document")
                            ->store('loan_applications/securities', 'public');

                        $uploadedFiles[] = $document;
                    }

                    LoanSecurity::create([
                        'loan_application_id' => $application->id,
                        'member_id' => $application->member_id,

                        'security_type' => $security['security_type'],
                        'security_name' => $security['security_name'],
                        'description' => $security['description'] ?? null,

                        'security_value' => $security['security_value'] ?? 0,
                        'accepted_value' => null,

                        'reference_number' => $security['reference_number'] ?? null,
                        'owner_name' => $security['owner_name'] ?? null,
                        'owner_national_id' => $security['owner_national_id'] ?? null,

                        'supporting_document' => $document,

                        'is_verified' => false,
                        'verified_by' => null,
                        'verified_at' => null,

                        'status' => 'pending',
                        'pledged_date' => null,
                        'released_date' => null,

                        'remarks' => $security['remarks'] ?? null,
                    ]);
                }

                foreach ($validated['witnesses'] ?? [] as $index => $witness) {
                    if (empty($witness['name']) && empty($witness['member_id'])) {
                        continue;
                    }

                    $signature = null;

                    if ($request->hasFile("witnesses.$index.signature")) {
                        $signature = $request->file("witnesses.$index.signature")
                            ->store('loan_applications/witnesses', 'public');

                        $uploadedFiles[] = $signature;
                    }

                    LoanWitness::create([
                        'loan_application_id' => $application->id,
                        'member_id' => $witness['member_id'] ?? $application->member_id,
                        'name' => $witness['name'],
                        'national_id' => $witness['national_id'],
                        'payroll_number' => $witness['payroll_number'] ?? null,
                        'employer' => $witness['employer'] ?? null,
                        'station' => $witness['station'] ?? null,
                        'address' => $witness['address'] ?? null,
                        'phone' => $witness['phone'] ?? null,
                        'signature' => $signature,
                        'signed_at' => $witness['signed_at'] ?? null,
                    ]);
                }

                return $application;
            });

        } catch (\Throwable $e) {
            foreach ($uploadedFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            return errorHandler("The loan application could not be saved. Please try again.", $e);
        }

        return redirect()
            ->route('loan_applications.show', $application->id)
            ->with(
                'success',
                $isDraft
                    ? 'Loan application saved as draft.'
                    : 'Loan application submitted successfully.'
            );
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

    public function workflow(LoanApplication $application, Request $request)
    {
        //
    }


    // Approve Loan
    public function approve(LoanApplication $application, Request $request)
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'gt:0'],
            'repayment_period_months' => ['required', 'integer', 'min:1'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $application->load([
            'loanProduct',
            'guarantors',
            'securities',
            'witnesses',
        ]);

        if (!in_array($application->status, ['submitted', 'under_review', 'deferred'])) {
            throw ValidationException::withMessages([
                'status' => 'This application cannot be approved from its current status.',
            ]);
        }

        $product = $application->loanProduct;

        if (!$product) {
            throw ValidationException::withMessages([
                'loan_product_id' => 'The loan product could not be found.',
            ]);
        }

        $approvedAmount = (float) $validated['approved_amount'];
        $repaymentMonths = (int) $validated['repayment_period_months'];

        if ($approvedAmount > $application->amount_requested) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Approved amount cannot exceed the requested amount.',
            ]);
        }

        if ($approvedAmount < $product->minimum_amount) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Approved amount is below the product minimum.',
            ]);
        }

        if ($product->maximum_amount !== null && $approvedAmount > $product->maximum_amount) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Approved amount exceeds the product maximum.',
            ]);
        }

        if (
            $repaymentMonths < $product->minimum_repayment_months ||
            $repaymentMonths > $product->maximum_repayment_months
        ) {
            throw ValidationException::withMessages([
                'repayment_period_months' => 'Approved repayment period is outside the product limits.',
            ]);
        }

        if ($product->requires_guarantors) {
            $acceptedGuarantorSecurity = $application->guarantors
                ->sum(fn($guarantor) => (float) $guarantor->shares_offered);

            $requiredCoverage = $approvedAmount
                * ((float) $product->minimum_guarantor_coverage_percentage / 100);

            if ($acceptedGuarantorSecurity < $requiredCoverage) {
                throw ValidationException::withMessages([
                    'guarantors' => 'Guarantor security does not sufficiently cover the approved amount.',
                ]);
            }

            if ($application->guarantors->count() < $product->minimum_guarantors) {
                throw ValidationException::withMessages([
                    'guarantors' => 'Minimum guarantor requirement has not been met.',
                ]);
            }
        }

        $unverifiedSecurities = $application->securities
            ->where('status', '!=', 'rejected')
            ->filter(fn($security) => !$security->is_verified);

        if ($unverifiedSecurities->isNotEmpty()) {
            throw ValidationException::withMessages([
                'securities' => 'All securities being used for this loan must be verified before approval.',
            ]);
        }

        $monthlyInstallment = $this->calculateMonthlyInstallment(
            $approvedAmount,
            $repaymentMonths,
            $product
        );

        DB::transaction(function () use (
            $application,
            $approvedAmount,
            $repaymentMonths,
            $monthlyInstallment,
            $validated,
            $product
        ) {
            $application->update([
                'amount_requested' => $approvedAmount,
                'repayment_period_months' => $repaymentMonths,
                'monthly_installment' => $monthlyInstallment,

                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),

                'defer_note' => null,
                'rejection_note' => null,
            ]);

            $application->securities()
                ->where('is_verified', true)
                ->whereIn('status', ['pending', 'verified'])
                ->update([
                    'status' => 'pledged',
                    'pledged_date' => now()->toDateString(),
                ]);

            LoanApproval::create([
                'loan_application_id' => $application->id,
                'member_id' => $application->member_id,
                'approved_amount' => $approvedAmount,
                'repayment_months' => $repaymentMonths,
                'monthly_installment' => $monthlyInstallment,
                'interest_rate' => $product->interest_rate,
                'decision' => 'approved',
                'reason' => $validated['approval_note'] ?? null,
                'approved_by' => Auth::id(),
            ]);    
        });

        return redirect()
            ->route('loan_applications.show', $application->id)
            ->with('success', 'Loan application approved successfully.');
    }    

    private function generateApplicationNumber()
    {
        do {
            $number = 'LA-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
        } while (LoanApplication::where('application_number', $number)->exists());

        return $number;
    }

    private function calculateMonthlyInstallment($amount, $months, LoanProduct $product)
    {
        $amount = (float) $amount;
        $months = (int) $months;
        $rate = (float) $product->interest_rate;

        if ($amount <= 0 || $months <= 0) {
            return 0;
        }

        if ($product->interest_method === 'flat_rate') {
            if ($product->interest_frequency === 'annual') {
                $interest = $amount * ($rate / 100) * ($months / 12);
            } elseif ($product->interest_frequency === 'monthly') {
                $interest = $amount * ($rate / 100) * $months;
            } else {
                $interest = $amount * ($rate / 100);
            }

            return round(($amount + $interest) / $months, 2);
        }

        if ($product->interest_frequency === 'one_time') {
            return round(($amount + ($amount * ($rate / 100))) / $months, 2);
        }

        $monthlyRate = $product->interest_frequency === 'annual'
            ? ($rate / 100) / 12
            : ($rate / 100);

        if ($monthlyRate <= 0) {
            return round($amount / $months, 2);
        }

        return round(
            $amount
            * $monthlyRate
            * pow(1 + $monthlyRate, $months)
            / (pow(1 + $monthlyRate, $months) - 1),
            2
        );
    }

    private function validateGuarantors(array $validated, LoanProduct $loanProduct)
    {
        if (!$loanProduct->requires_guarantors) {
            return;
        }

        $guarantors = collect($validated['guarantors'] ?? [])
            ->filter(fn($item) => !empty($item['member_id']));

        $count = $guarantors->count();

        if ($count < $loanProduct->minimum_guarantors) {
            throw ValidationException::withMessages([
                'guarantors' => 'At least ' . $loanProduct->minimum_guarantors . ' guarantor(s) are required.',
            ]);
        }

        if ($loanProduct->maximum_guarantors !== null && $count > $loanProduct->maximum_guarantors) {
            throw ValidationException::withMessages([
                'guarantors' => 'A maximum of ' . $loanProduct->maximum_guarantors . ' guarantor(s) is allowed.',
            ]);
        }

        $memberIds = $guarantors->pluck('member_id');

        if ($memberIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'guarantors' => 'The same member cannot be added more than once as a guarantor.',
            ]);
        }

        if ($memberIds->contains((int) $validated['member_id'])) {
            throw ValidationException::withMessages([
                'guarantors' => 'The applicant cannot guarantee their own loan.',
            ]);
        }

        $totalGuaranteed = $guarantors->sum(
            fn($item) => (float) ($item['shares_offered'] ?? 0)
        );

        $requiredCoverage = (float) $validated['amount_requested']
            * ((float) $loanProduct->minimum_guarantor_coverage_percentage / 100);

        if ($totalGuaranteed < $requiredCoverage) {
            throw ValidationException::withMessages([
                'guarantors' => 'Guarantor security must cover at least ' .
                    number_format($loanProduct->minimum_guarantor_coverage_percentage, 2) .
                    '% of the requested amount.',
            ]);
        }
    }

    private function validateLoanProductRules(array $validated, LoanProduct $loanProduct)
    {
        $amount = (float) $validated['amount_requested'];
        $months = (int) $validated['repayment_period_months'];

        if ($amount < $loanProduct->minimum_amount) {
            throw ValidationException::withMessages([
                'amount_requested' => 'The requested amount is below the product minimum of KES ' . number_format($loanProduct->minimum_amount, 2) . '.',
            ]);
        }

        if ($loanProduct->maximum_amount !== null && $amount > $loanProduct->maximum_amount) {
            throw ValidationException::withMessages([
                'amount_requested' => 'The requested amount exceeds the product maximum of KES ' . number_format($loanProduct->maximum_amount, 2) . '.',
            ]);
        }

        if ($months < $loanProduct->minimum_repayment_months || $months > $loanProduct->maximum_repayment_months) {
            throw ValidationException::withMessages([
                'repayment_period_months' => 'Repayment period must be between ' .
                    $loanProduct->minimum_repayment_months . ' and ' .
                    $loanProduct->maximum_repayment_months . ' months.',
            ]);
        }

        $shares = (float) ($validated['total_share_contribution'] ?? 0);
        $monthlyContribution = (float) ($validated['monthly_share_contribution'] ?? 0);

        if ($shares < $loanProduct->minimum_share_contribution) {
            throw ValidationException::withMessages([
                'member_id' => 'Member does not meet the minimum share contribution requirement.',
            ]);
        }

        if ($monthlyContribution < $loanProduct->minimum_monthly_contribution) {
            throw ValidationException::withMessages([
                'member_id' => 'Member does not meet the minimum monthly contribution requirement.',
            ]);
        }

        if ($loanProduct->share_multiplier !== null) {
            $maximumByShares = $shares * $loanProduct->share_multiplier;

            if ($amount > $maximumByShares) {
                throw ValidationException::withMessages([
                    'amount_requested' => 'Requested amount exceeds the member share multiplier limit of KES ' .
                        number_format($maximumByShares, 2) . '.',
                ]);
            }
        }
    }

}
