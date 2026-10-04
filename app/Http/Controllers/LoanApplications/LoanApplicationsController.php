<?php

namespace App\Http\Controllers\LoanApplications;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanApproval;
use App\Models\LoanApplications\LoanProduct;
use App\Models\Memberships\Member;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
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
        $loanApplications = LoanApplication::with(['member', 'loanProduct'])->latest()->get();

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
        $members = $this->formMembers();
        $loanProducts = LoanProduct::all();

        return view('loan_applications.create', compact('applicationNumber', 'members', 'loanProducts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->saveApplication($request, new LoanApplication);
    }

    private function validateApplication(Request $request, bool $isDraft, LoanApplication $application): array
    {
        return $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],

            'member_id' => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:members,id'],
            'loan_product_id' => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:loan_products,id'],

            'amount_requested' => [$isDraft ? 'nullable' : 'required', 'numeric', 'gt:0'],
            'amount_in_words' => ['nullable', 'string'],

            'repayment_period_months' => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'monthly_installment' => ['nullable', 'numeric', 'min:0'],

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
            'guarantors.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('loan_guarantors', 'id')->where('loan_application_id', $application->id ?? 0)],
            'guarantors.*.member_id' => ['required', 'integer', 'distinct', 'different:member_id', 'exists:members,id'],
            'guarantors.*.guarantor_name' => ['nullable', 'string', 'max:255'],
            'guarantors.*.member_number' => ['nullable', 'string', 'max:100'],
            'guarantors.*.shares_offered' => ['nullable', 'numeric', 'min:0'],
            'guarantors.*.national_id' => ['nullable', 'string', 'max:100'],
            'guarantors.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'guarantors.*.witness_name' => ['nullable', 'string', 'max:255'],

            'securities' => ['nullable', 'array'],
            'securities.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('loan_securities', 'id')->where('loan_application_id', $application->id ?? 0)],
            'securities.*.security_type' => ['required', Rule::in(['pledged_shares', 'additional_collateral'])],
            'securities.*.security_name' => ['required', 'string', 'max:255'],
            'securities.*.description' => ['nullable', 'string'],
            'securities.*.security_value' => ['nullable', 'numeric', 'min:0'],
            'securities.*.reference_number' => ['nullable', 'string', 'max:255'],
            'securities.*.owner_name' => ['nullable', 'string', 'max:255'],
            'securities.*.owner_national_id' => ['nullable', 'string', 'max:100'],
            'securities.*.supporting_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'securities.*.remarks' => ['nullable', 'string'],

            'witnesses' => ['nullable', 'array'],
            'witnesses.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('loan_witnesses', 'id')->where('loan_application_id', $application->id ?? 0)],
            'witnesses.*.member_id' => ['nullable', 'integer', 'exists:members,id'],
            'witnesses.*.name' => ['required', 'string', 'max:255'],
            'witnesses.*.national_id' => ['required', 'string', 'max:100'],
            'witnesses.*.payroll_number' => ['nullable', 'string', 'max:100'],
            'witnesses.*.employer' => ['nullable', 'string', 'max:255'],
            'witnesses.*.station' => ['nullable', 'string', 'max:255'],
            'witnesses.*.address' => ['nullable', 'string'],
            'witnesses.*.phone' => ['nullable', 'string', 'max:50'],
            'witnesses.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'witnesses.*.signed_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

    }

    private function saveApplication(Request $request, LoanApplication $application)
    {
        $isDraft = $request->input('submission_action') === 'draft';
        $validated = $this->validateApplication($request, $isDraft, $application);
        $uploadedFiles = [];
        $obsoleteFiles = [];

        try {
            $application = DB::transaction(function () use ($request, $validated, $application, $isDraft, &$uploadedFiles, &$obsoleteFiles) {
                if ($application->exists) {
                    $application = LoanApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
                    $this->requireStatus($application, ['draft', 'deferred']);
                }
                $loanProduct = empty($validated['loan_product_id']) ? null : LoanProduct::findOrFail($validated['loan_product_id']);
                $member = empty($validated['member_id']) ? null : Member::findOrFail($validated['member_id']);
                $validated = array_merge($validated, $this->memberFinancials($member));
                if (!$isDraft) {
                    $this->validateEligibility($validated, $loanProduct, $application);
                }
                if (!empty($validated['securities']) && !$member) {
                    throw ValidationException::withMessages(['member_id' => 'Select the member before adding securities.']);
                }

                $signaturePath = $application->applicant_signature;

                if ($request->hasFile('applicant_signature')) {
                    $obsoleteFiles[] = $signaturePath;
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

                $application->fill([
                    'member_id' => $validated['member_id'] ?? null,
                    'loan_product_id' => $validated['loan_product_id'] ?? null,

                    'application_number' => $application->application_number ?: $this->generateApplicationNumber(),
                    'amount_requested' => $validated['amount_requested'] ?? 0,
                    'amount_in_words' => $validated['amount_in_words'] ?? null,

                    'repayment_period_months' => $validated['repayment_period_months'] ?? 0,
                    'monthly_installment' => $monthlyInstallment,

                    'required_date' => $validated['required_date'] ?? null,
                    'payment_mode' => $validated['payment_mode'] ?? null,

                    'loan_purpose' => $validated['loan_purpose'] ?? '',
                    'purpose_amount' => $validated['purpose_amount'] ?? 0,

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

                    'drafted_by' => $application->drafted_by ?? ($isDraft ? Auth::id() : null),
                    'submitted_by' => $isDraft ? $application->submitted_by : Auth::id(),

                    'drafted_at' => $application->drafted_at ?? ($isDraft ? now() : null),
                    'submitted_at' => $isDraft ? $application->submitted_at : now(),
                    'defer_note' => null,
                    'deferred_by' => null,
                    'deferred_at' => null,
                ]);

                $application->save();
                foreach (['guarantors' => 'signature', 'securities' => 'supporting_document', 'witnesses' => 'signature'] as $relation => $fileField) {
                    $this->syncChildren($application, $relation, $fileField, $validated[$relation] ?? [], $request, $uploadedFiles, $obsoleteFiles);
                }

                return $application;
            });

        } catch (\Throwable $e) {
            foreach ($uploadedFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            if ($e instanceof ValidationException) {
                throw $e;
            }
            return errorHandler("The loan application could not be saved. Please try again.", $e);
        }

        Storage::disk('public')->delete(array_filter($obsoleteFiles));
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(LoanApplication $application)
    {
        $application->load(['member', 'loanProduct', 'guarantors', 'securities', 'witnesses']);
        return view('loan_applications.view', compact('application'));
    }   

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LoanApplication $application)
    {
        $this->requireStatus($application, ['draft', 'deferred']);
        $application->load(['guarantors', 'securities', 'witnesses']);
        if (!session()->hasOldInput()) {
            $input = $application->toArray();
            foreach (['required_date', 'declaration_date'] as $date) {
                $input[$date] = optional($application->$date)->format('Y-m-d');
            }
            session()->flashInput($input);
        }
        $members = $this->formMembers();
        $loanProducts = LoanProduct::all();
        return view('loan_applications.edit', compact('application', 'members', 'loanProducts'));
    }

    public function update(Request $request, LoanApplication $application)
    {
        return $this->saveApplication($request, $application);
    }

    public function destroy(LoanApplication $application)
    {
        $files = DB::transaction(function () use ($application) {
            $application = LoanApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($application, ['draft']);
            $files = [$application->applicant_signature];
            foreach (['guarantors' => 'signature', 'securities' => 'supporting_document', 'witnesses' => 'signature'] as $relation => $field) {
                $files = array_merge($files, $application->$relation()->pluck($field)->all());
                $application->$relation()->delete();
            }
            $application->delete();
            return array_filter($files);
        });
        Storage::disk('public')->delete($files);
        return redirect()->route('loan_applications.index')->with('success', 'Draft deleted successfully.');
    }

    public function workflow(LoanApplication $application, Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['submit', 'review', 'defer', 'reject'])],
            'note' => ['nullable', 'required_if:action,defer,reject', 'string'],
        ]);
        DB::transaction(function () use ($application, $data) {
            $application = LoanApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $action = $data['action'];
            $allowed = ['submit' => ['draft', 'deferred'], 'review' => ['submitted'],
                'defer' => ['submitted', 'under_review'], 'reject' => ['submitted', 'under_review', 'deferred']];
            $this->requireStatus($application, $allowed[$action]);
            if ($action === 'submit') {
                $payload = $application->toArray();
                unset($payload['applicant_signature']);
                foreach (['guarantors' => 'signature', 'securities' => 'supporting_document', 'witnesses' => 'signature'] as $relation => $field) {
                    $payload[$relation] = $application->$relation->map(fn($row) => Arr::except($row->toArray(), [$field]))->all();
                }
                $payload['submission_action'] = 'submit';
                $payload = $this->validateApplication(new Request($payload), false, $application);
                $payload = array_merge($payload, $this->memberFinancials($application->member));
                $this->validateEligibility($payload, $application->loanProduct, $application);
                $application->fill(Arr::only($payload, ['total_share_contribution', 'monthly_share_contribution', 'outstanding_loan_balance']));
                $application->monthly_installment = $this->calculateMonthlyInstallment($application->amount_requested, $application->repayment_period_months, $application->loanProduct);
            }
            $status = ['submit' => 'submitted', 'review' => 'under_review', 'defer' => 'deferred', 'reject' => 'rejected'][$action];
            $prefix = ['submit' => 'submitted', 'review' => 'reviewed', 'defer' => 'deferred', 'reject' => 'rejected'][$action];
            $application->fill(['status' => $status, $prefix . '_by' => Auth::id(), $prefix . '_at' => now()]);
            if ($action === 'submit') {
                $application->fill(['defer_note' => null, 'deferred_by' => null, 'deferred_at' => null]);
            } elseif (in_array($action, ['defer', 'reject'])) {
                $application->{$action === 'defer' ? 'defer_note' : 'rejection_note'} = $data['note'];
            }
            $application->save();
        });
        return back()->with('success', 'Application status updated successfully.');
    }

    public function verifySecurity(LoanApplication $application, Request $request)
    {
        $data = $request->validate([
            'security_id' => ['required', 'integer'],
            'accepted_value' => ['required', 'numeric', 'gt:0'],
        ]);
        DB::transaction(function () use ($application, $data) {
            $application = LoanApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($application, ['submitted', 'under_review']);
            $security = $application->securities()->findOrFail($data['security_id']);
            if ($data['accepted_value'] > $security->security_value) {
                throw ValidationException::withMessages(['accepted_value' => 'Accepted value cannot exceed declared value.']);
            }
            $security->update(['accepted_value' => $data['accepted_value'], 'is_verified' => true,
                'status' => 'verified', 'verified_by' => Auth::id(), 'verified_at' => now()]);
        });
        return back()->with('success', 'Security verified successfully.');
    }

    // Approve Loan
    public function approve(LoanApplication $application, Request $request)
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'gt:0'],
            'repayment_period_months' => ['required', 'integer', 'min:1'],
            'approval_note' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use (&$application, $validated) {
                $application = LoanApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();

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

                $eligibility = array_merge($application->toArray(), $this->memberFinancials($application->member), [
                    'amount_requested' => $approvedAmount,
                    'repayment_period_months' => $repaymentMonths,
                    'guarantors' => $application->guarantors->toArray(),
                ]);
                $this->validateEligibility($eligibility, $product, $application);

                if ($approvedAmount > $application->amount_requested) {
                    throw ValidationException::withMessages([
                        'approved_amount' => 'Approved amount cannot exceed the requested amount.',
                    ]);
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

                $application->update([
                    'amount_approved' => $approvedAmount,
                    'approved_repayment_months' => $repaymentMonths,
                    'approved_monthly_installment' => $monthlyInstallment,
                    'approved_interest_rate' => $product->interest_rate,
                    'approved_interest_method' => $product->interest_method,
                    'approved_interest_frequency' => $product->interest_frequency,

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

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return errorHandler("Error approving loan application. Try again later", $e);
        }
    }    

    private function requireStatus(LoanApplication $application, array $statuses): void
    {
        if (!in_array($application->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => 'This action is not allowed in the current application status.']);
        }
    }

    private function syncChildren(LoanApplication $application, string $relation, string $fileField, array $rows, Request $request, array &$uploaded, array &$obsolete): void
    {
        $fields = [
            'guarantors' => ['member_id', 'shares_offered', 'witness_name'],
            'securities' => ['security_type', 'security_name', 'description', 'security_value', 'reference_number', 'owner_name', 'owner_national_id', 'remarks'],
            'witnesses' => ['member_id', 'name', 'national_id', 'payroll_number', 'employer', 'station', 'address', 'phone', 'signed_at'],
        ];
        $retainedIds = array_filter(array_column($rows, 'id'));
        $removed = $application->$relation()->whereNotIn('id', $retainedIds);
        $obsolete = array_merge($obsolete, $removed->pluck($fileField)->all());
        $removed->delete();
        foreach ($rows as $index => $row) {
            $child = empty($row['id']) ? $application->$relation()->make() : $application->$relation()->findOrFail($row['id']);
            $data = Arr::only($row, $fields[$relation]);
            if ($relation === 'guarantors') {
                $member = Member::findOrFail($row['member_id']);
                $data += ['guarantor_name' => $member->full_name, 'member_number' => $member->membership_number, 'national_id' => $member->national_id];
                $data['shares_offered'] = $row['shares_offered'] ?? 0;
            } elseif ($relation === 'securities') {
                $data += ['member_id' => $application->member_id, 'accepted_value' => 0,
                    'is_verified' => false, 'verified_by' => null, 'verified_at' => null, 'status' => 'pending'];
                $data['security_value'] = $row['security_value'] ?? 0;
            } else {
                $data['member_id'] = $row['member_id'] ?? null;
            }

            $oldFile = $child->$fileField;
            $child->fill($data);
            // A changed signer cannot retain another person's signature.
            if ($relation !== 'securities' && $child->isDirty(['member_id', 'national_id', 'name'])) {
                $child->$fileField = null;
            }
            if ($request->hasFile("$relation.$index.$fileField")) {
                $child->$fileField = $request->file("$relation.$index.$fileField")->store("loan_applications/$relation", 'public');
                $uploaded[] = $child->$fileField;
            }
            if ($oldFile && $oldFile !== $child->$fileField) {
                $obsolete[] = $oldFile;
            }
            $child->save();
        }
    }

    private function memberFinancials(?Member $member): array
    {
        return [
            'total_share_contribution' => $member ? DB::table('share_accounts')->where('member_id', $member->id)->where('status', 'active')->sum('share_balance') : 0,
            'outstanding_loan_balance' => $member ? DB::table('loans')->where('member_id', $member->id)->sum('total_outstanding_balance') : 0,
            'monthly_share_contribution' => $member ? (DB::table('member_applications')->where('id', $member->member_application_id)->value('monthly_contribution') ?? 0) : 0,
        ];
    }

    private function formMembers()
    {
        return Member::whereNull('deleted_at')->get()->each(function ($member) {
            foreach ($this->memberFinancials($member) as $field => $value) {
                $member->setAttribute($field, $value);
            }
        });
    }

    private function validateEligibility(array $data, ?LoanProduct $product, LoanApplication $application): void
    {
        $member = Member::whereKey($data['member_id'])->lockForUpdate()->firstOrFail();
        if (!$member->is_active || $member->status !== 'active' || $member->deleted_at) {
            throw ValidationException::withMessages(['member_id' => 'The member must be active.']);
        }
        if (!$product || !$product->is_active || ($product->effective_from && $product->effective_from->isAfter(today())) || ($product->effective_to && $product->effective_to->isBefore(today()))) {
            throw ValidationException::withMessages(['loan_product_id' => 'The product is not available on this date.']);
        }
        if (\Carbon\Carbon::parse($member->admission_date)->addMonths($product->minimum_membership_months)->isAfter(today())) {
            throw ValidationException::withMessages(['member_id' => 'The minimum membership period has not been met.']);
        }
        $activeLoans = DB::table('loans')->where('member_id', $member->id)->whereIn('status', ['active', 'in_arrears', 'restructured'])->count();
        $approvedApplications = LoanApplication::where('member_id', $member->id)->where('status', 'approved')->where('id', '!=', $application->id ?? 0)->count();
        if ($product->maximum_active_loans !== null && $activeLoans + $approvedApplications >= $product->maximum_active_loans) {
            throw ValidationException::withMessages(['member_id' => 'The maximum active loan limit has been reached.']);
        }
        $this->validateLoanProductRules($data, $product);
        $this->validateGuarantors($data, $product);
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
        $guarantors = collect($validated['guarantors'] ?? [])
            ->filter(fn($item) => !empty($item['member_id']));

        $count = $guarantors->count();

        if ($loanProduct->requires_guarantors && $count < $loanProduct->minimum_guarantors) {
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

        foreach ($guarantors as $guarantor) {
            $member = Member::findOrFail($guarantor['member_id']);
            $available = DB::table('share_accounts')->where('member_id', $member->id)->where('status', 'active')->sum('available_amount');
            if (!$member->is_active || $member->status !== 'active' || $member->deleted_at || ($guarantor['shares_offered'] ?? 0) > $available) {
                throw ValidationException::withMessages(['guarantors' => 'Each guarantor must be active and have enough available shares.']);
            }
        }

        if (!$loanProduct->requires_guarantors) {
            return;
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
