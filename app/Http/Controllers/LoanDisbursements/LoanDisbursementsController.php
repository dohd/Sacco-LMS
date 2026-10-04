<?php

namespace App\Http\Controllers\LoanDisbursements;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanDisbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanDisbursementsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('loan_disbursements.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);
        
        $loanApplications  = LoanApplication::all();
        $loans = Loan::all();

        return view('loan_disbursements.create', compact('loanApplications', 'loans'));
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
            'loan_application_id' => ['required', 'integer', 'exists:loan_applications,id'],
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'gross_amount' => ['required', 'numeric', 'min:0.01'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'disbursement_method' => [
                'required',
                'in:bank_transfer,mobile_money,cheque,cash,account_credit'
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:255'],
            'cheque_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'payee_national_id' => ['nullable', 'string', 'max:255'],
            'payee_phone' => ['nullable', 'string', 'max:255'],
            'disbursement_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'collection_date' => ['nullable', 'date'],
            'postal_reference' => ['nullable', 'string', 'max:255'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $disbursement = DB::transaction(function () use ($validated) {

                $loan = Loan::whereKey($validated['loan_id'])->lockForUpdate()->first();

                if (!$loan) {
                    throw ValidationException::withMessages([
                        'loan_id' => 'Loan account not found.'
                    ]);
                }
                if ($loan->status !== 'active') {
                    throw ValidationException::withMessages([
                        'loan_id' => 'Only active loan accounts can be disbursed.'
                    ]);
                }

                $application = LoanApplication::whereKey($validated['loan_application_id'])->lockForUpdate()->first();

                if (!$application) {
                    throw ValidationException::withMessages([
                        'loan_application_id' => 'Loan application not found.'
                    ]);
                }

                if ($application->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'loan_application_id' =>
                            'Only approved loan applications can be disbursed.'
                    ]);
                }

                $grossAmount = round((float) $validated['gross_amount'], 2);
                $deductions = round((float) ($validated['deductions_amount'] ?? 0), 2);
                $netAmount = round($grossAmount - $deductions, 2);

                if ($deductions > $grossAmount) {
                    throw ValidationException::withMessages([
                        'deductions_amount' =>
                            'Deductions cannot exceed the gross disbursement amount.'
                    ]);
                }

                if ($netAmount <= 0) {
                    throw ValidationException::withMessages([
                        'gross_amount' =>
                            'Net disbursement amount must be greater than zero.'
                    ]);
                }

                $method = $validated['disbursement_method'];

                if ($method === 'cheque' && empty($validated['cheque_number'])) {
                    throw ValidationException::withMessages([
                        'cheque_number' =>
                            'Cheque number is required for cheque disbursements.'
                    ]);
                }

                if ($method === 'bank_transfer' && empty($validated['bank_name'])) {
                    throw ValidationException::withMessages([
                        'bank_name' =>
                            'Bank name is required for bank transfers.'
                    ]);
                }

                if ($method === 'mobile_money' && empty($validated['payee_phone'])) {
                    throw ValidationException::withMessages([
                        'payee_phone' =>
                            'Payee phone number is required for mobile-money disbursements.'
                    ]);
                }

                return LoanDisbursement::create([
                    'disbursement_number' => $this->generateDisbursementNumber(),
                    'loan_application_id' => $application->id,
                    'loan_id' => $validated['loan_id'] ?? null,

                    'gross_amount' => $grossAmount,
                    'deductions_amount' => $deductions,
                    'net_amount' => $netAmount,

                    'disbursement_method' => $method,
                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,

                    'cheque_number' => $validated['cheque_number'] ?? null,
                    'cheque_date' => $validated['cheque_date'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,

                    'payee_name' => $validated['payee_name'] ?? null,
                    'payee_national_id' =>
                        $validated['payee_national_id'] ?? null,
                    'payee_phone' => $validated['payee_phone'] ?? null,

                    'disbursement_date' => $validated['disbursement_date'],
                    'value_date' => $validated['value_date'] ?? null,
                    'collection_date' => $validated['collection_date'] ?? null,
                    'postal_reference' =>
                        $validated['postal_reference'] ?? null,

                    'status' => 'draft',

                    'supporting_document' =>
                        $validated['supporting_document'] ?? null,

                    'remarks' => $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_disbursements.show', $disbursement->id)
                ->with('success', 'Loan disbursement created successfully.');

        } catch (\Exception $e) {
            return errorHandler(
                'Error creating loan disbursement. Please try again.',
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
    public function show(LoanDisbursement $loanDisbursement)
    {
        return view('loan_disbursements.view', compact('loanDisbursement'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LoanDisbursement $loanDisbursement)
    {
        // Inject the key-value pair into the request payload
        $payload = $loanDisbursement->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $loanApplications  = LoanApplication::all();
        $loans = Loan::all();

        return view('loan_disbursements.edit', compact('loanDisbursement', 'loanApplications', 'loans'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LoanDisbursement $loanDisbursement)
    {
        if (!in_array($loanDisbursement->status, ['draft', 'pending_approval'])) {
            throw ValidationException::withMessages([
                'disbursement' => 'Only draft or pending-approval disbursements can be edited.'
            ]);
        }

        $validated = $request->validate([
            'loan_application_id' => ['required', 'integer', 'exists:loan_applications,id'],
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'gross_amount' => ['required', 'numeric', 'min:0.01'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'disbursement_method' => [
                'required',
                'in:bank_transfer,mobile_money,cheque,cash,account_credit'
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:255'],
            'cheque_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'payee_national_id' => ['nullable', 'string', 'max:255'],
            'payee_phone' => ['nullable', 'string', 'max:255'],
            'disbursement_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'collection_date' => ['nullable', 'date'],
            'postal_reference' => ['nullable', 'string', 'max:255'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated, $loanDisbursement) {

                $disbursement = LoanDisbursement::whereKey($loanDisbursement->id)
                    ->lockForUpdate()
                    ->first();

                if (!in_array($disbursement->status, ['draft', 'pending_approval'])) {
                    throw ValidationException::withMessages([
                        'disbursement' => 'This disbursement can no longer be edited.'
                    ]);
                }

                $application = LoanApplication::whereKey(
                    $validated['loan_application_id']
                )->lockForUpdate()->first();

                if ($application->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'loan_application_id' =>
                            'Only approved loan applications can be disbursed.'
                    ]);
                }

                $loan = Loan::whereKey($validated['loan_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$loan) {
                    throw ValidationException::withMessages([
                        'loan_id' => 'Loan account not found.'
                    ]);
                }

                if ($loan->loan_application_id != $application->id) {
                    throw ValidationException::withMessages([
                        'loan_id' =>
                            'The selected loan account does not belong to this loan application.'
                    ]);
                }

                if ($loan->status !== 'active') {
                    throw ValidationException::withMessages([
                        'loan_id' =>
                            'Only active loan accounts can be disbursed.'
                    ]);
                }

                $grossAmount = round((float) $validated['gross_amount'], 2);
                $deductions = round(
                    (float) ($validated['deductions_amount'] ?? 0),
                    2
                );

                if ($deductions > $grossAmount) {
                    throw ValidationException::withMessages([
                        'deductions_amount' =>
                            'Deductions cannot exceed the gross amount.'
                    ]);
                }

                $netAmount = round($grossAmount - $deductions, 2);

                if ($netAmount <= 0) {
                    throw ValidationException::withMessages([
                        'gross_amount' =>
                            'Net disbursement amount must be greater than zero.'
                    ]);
                }

                $method = $validated['disbursement_method'];

                if ($method === 'cheque' && empty($validated['cheque_number'])) {
                    throw ValidationException::withMessages([
                        'cheque_number' =>
                            'Cheque number is required for cheque disbursements.'
                    ]);
                }

                if ($method === 'bank_transfer' && empty($validated['bank_name'])) {
                    throw ValidationException::withMessages([
                        'bank_name' =>
                            'Bank name is required for bank transfers.'
                    ]);
                }

                if ($method === 'mobile_money' && empty($validated['payee_phone'])) {
                    throw ValidationException::withMessages([
                        'payee_phone' =>
                            'Payee phone number is required for mobile-money disbursements.'
                    ]);
                }

                $disbursement->update([
                    'loan_application_id' => $application->id,
                    'loan_id' => $loan->id,

                    'gross_amount' => $grossAmount,
                    'deductions_amount' => $deductions,
                    'net_amount' => $netAmount,

                    'disbursement_method' => $method,
                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,

                    'cheque_number' =>
                        $validated['cheque_number'] ?? null,
                    'cheque_date' =>
                        $validated['cheque_date'] ?? null,
                    'bank_name' =>
                        $validated['bank_name'] ?? null,

                    'payee_name' =>
                        $validated['payee_name'] ?? null,
                    'payee_national_id' =>
                        $validated['payee_national_id'] ?? null,
                    'payee_phone' =>
                        $validated['payee_phone'] ?? null,

                    'disbursement_date' =>
                        $validated['disbursement_date'],
                    'value_date' =>
                        $validated['value_date'] ?? null,
                    'collection_date' =>
                        $validated['collection_date'] ?? null,

                    'postal_reference' =>
                        $validated['postal_reference'] ?? null,

                    'supporting_document' =>
                        $validated['supporting_document'] ?? null,

                    'remarks' =>
                        $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_disbursements.show', $loanDisbursement->id)
                ->with('success', 'Loan disbursement updated successfully.');

        } catch (\Exception $e) {
            return errorHandler(
                'Error updating loan disbursement. Please try again.',
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

    public function submit($id)
    {
        return redirect()
                ->route('loan_disbursements.show', $id)
                ->with('success', 'Loan disbursement submitted successfully.');
    }

    public function loanShow($id)
    {
        return redirect()
                ->route('loan_disbursements.show', $id)
                ->with('success', 'Oops! loan show under development');
    }

    public function generateDisbursementNumber()
    {
        do {
            $number = 'DIS-' . now()->format('YmdHis') . '-' .
                strtoupper(Str::random(8));
        } while (LoanDisbursement::where('disbursement_number', $number)->exists());

        return $number;
    }
}
