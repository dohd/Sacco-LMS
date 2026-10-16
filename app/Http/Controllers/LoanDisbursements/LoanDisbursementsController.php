<?php

namespace App\Http\Controllers\LoanDisbursements;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\{Loan, LoanDisbursement, LoanRepayment};
use App\Models\Memberships\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanDisbursementsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();
            abort_if(in_array($action, ['create', 'store', 'destroy', 'submit', 'approve'], true),
                405, 'Disbursements are generated and approved through loan approval.');
            if (!in_array($action, ['index', 'show', 'loanShow'], true)) {
                $this->requireSaccoPermission($action === 'reverse' ? 'reverse-disbursement' : 'process-disbursement');
                $this->rejectSystemFields($request, ['loan_id', 'loan_application_id', 'gross_amount',
                    'deductions_amount', 'net_amount', 'disbursement_number', 'status']);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $query = LoanDisbursement::latest();
        if (!$this->hasSaccoPermission('process-disbursement')) {
            $this->requireSaccoPermission('view-own-loan');
            $query->whereHas('loan', function ($loan) {
                $loan->whereIn('member_id', Member::where('user_id', auth()->id())->select('id'));
            });
        }
        $loanDisbursements = $query->get();
        return view('loan_disbursements.index', compact('loanDisbursements'));
    }

    public function show(LoanDisbursement $loanDisbursement)
    {
        if (!$this->hasSaccoPermission('process-disbursement')) {
            $this->requireSaccoPermission('view-own-loan');
            abort_unless($loanDisbursement->loan && Member::whereKey($loanDisbursement->loan->member_id)
                ->where('user_id', auth()->id())->exists(), 403);
        }
        return view('loan_disbursements.view', compact('loanDisbursement'));
    }

    public function edit(LoanDisbursement $loanDisbursement)
    {
        $this->requireState($loanDisbursement, ['approved']);
        return view('loan_disbursements.edit', compact('loanDisbursement'));
    }

    public function update(Request $request, LoanDisbursement $loanDisbursement)
    {
        $validated = $request->validate([
            'disbursement_method' => ['required', 'in:bank_transfer,mobile_money,cheque,cash,account_credit'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['required_if:disbursement_method,cheque', 'nullable', 'string', 'max:255'],
            'cheque_date' => ['nullable', 'date'],
            'bank_name' => ['required_if:disbursement_method,bank_transfer', 'nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'payee_national_id' => ['nullable', 'string', 'max:255'],
            'payee_phone' => ['required_if:disbursement_method,mobile_money', 'nullable', 'string', 'max:255'],
            'disbursement_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'collection_date' => ['nullable', 'date'],
            'postal_reference' => ['nullable', 'string', 'max:255'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);
        DB::transaction(function () use ($loanDisbursement, $validated) {
            $record = LoanDisbursement::whereKey($loanDisbursement->id)->lockForUpdate()->firstOrFail();
            $this->requireState($record, ['approved']);
            $record->update($validated);
        });
        return redirect()->route('loan_disbursements.show', $loanDisbursement)->with('success', 'Payment details updated.');
    }

    public function process($id)
    {
        DB::transaction(function () use ($id) {
            $record = LoanDisbursement::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->requireState($record, ['approved', 'processing']);
            $loan = Loan::whereKey($record->loan_id)->lockForUpdate()->firstOrFail();
            if (!$record->loanApplication || $record->loanApplication->status !== 'approved'
                || $loan->loan_application_id != $record->loan_application_id) {
                throw ValidationException::withMessages(['disbursement' => 'An approved application and matching loan are required.']);
            }
            $external = in_array($record->disbursement_method, ['bank_transfer', 'mobile_money'], true);
            $status = $external && $record->status === 'approved' ? 'processing' : 'processed';
            if ($external && !$record->transaction_reference) {
                throw ValidationException::withMessages(['transaction_reference' => 'Save the payment reference before processing, then confirm settlement.']);
            }
            if ($status === 'processed') {
                if ((float) $loan->amount_disbursed > 0) {
                    throw ValidationException::withMessages(['disbursement' => 'This loan already has a posted disbursement.']);
                }
                $loan->update(['amount_disbursed' => $record->gross_amount,
                    'disbursement_method' => $record->disbursement_method,
                    'disbursement_reference' => $record->transaction_reference,
                    'disbursement_date' => $record->disbursement_date,
                    'disbursed_by' => auth()->id()]);
            }
            $record->update(['status' => $status, 'processed_by' => auth()->id(), 'processed_at' => now()]);
        });
        return back()->with('success', 'Disbursement processing updated.');
    }

    public function reverse($id)
    {
        $validated = request()->validate(['reversal_reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($id, $validated) {
            $record = LoanDisbursement::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->requireState($record, ['processed']);
            $loan = Loan::whereKey($record->loan_id)->lockForUpdate()->firstOrFail();
            if (LoanRepayment::where('loan_id', $loan->id)->whereIn('status', ['pending', 'confirmed'])->exists()) {
                throw ValidationException::withMessages(['disbursement' => 'Resolve repayments before reversing this disbursement.']);
            }
            $loan->update(['amount_disbursed' => 0, 'principal_balance' => 0, 'interest_balance' => 0,
                'fees_balance' => 0, 'penalty_balance' => 0, 'total_outstanding_balance' => 0,
                'status' => 'closed', 'closed_by' => auth()->id(), 'closed_date' => today(),
                'closure_reason' => $validated['reversal_reason']]);
            \App\Models\LoanApplications\LoanRepaymentSchedule::where('loan_id', $loan->id)
                ->update(['status' => 'waived', 'outstanding_amount' => 0]);
            $record->update(['status' => 'reversed', 'reversed_by' => auth()->id(),
                'reversed_at' => now(), 'reversal_reason' => $validated['reversal_reason']]);
        });
        return back()->with('success', 'Disbursement reversed and loan closed.');
    }

    private function requireState(LoanDisbursement $record, array $states): void
    {
        if (!in_array($record->status, $states, true)) {
            throw ValidationException::withMessages(['disbursement' => 'This action is not allowed in the current disbursement status.']);
        }
    }

    public function create() { abort(405); }
    public function store() { abort(405); }
    public function destroy() { abort(405); }
    public function submit() { abort(405); }
    public function approve() { abort(405); }
    public function loanShow($id) { return redirect()->route('loan_disbursements.show', $id); }

    public function generateDisbursementNumber()
    {
        do {
            $number = 'DIS-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
        } while (LoanDisbursement::where('disbursement_number', $number)->exists());
        return $number;
    }
}
