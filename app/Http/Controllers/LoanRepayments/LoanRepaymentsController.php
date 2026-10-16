<?php

namespace App\Http\Controllers\LoanRepayments;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentAllocation;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\Memberships\Member;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanRepaymentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();
            if (!in_array($action, ['index', 'show'], true)) {
                $this->requireSaccoPermission($action === 'reverse' ? 'reverse-repayment' : 'confirm-repayment');
            } elseif (!$this->hasSaccoPermission('confirm-repayment')) {
                $this->requireSaccoPermission('view-own-repayment');
                $record = $request->route('loan_repayment');
                if ($record) {
                    abort_unless($this->ownsMember($record->member_id), 403);
                }
            }
            $this->rejectSystemFields($request, ['status']);
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $loanRepayments = LoanRepayment::when(!$this->hasSaccoPermission('confirm-repayment'), function ($query) {
            $query->whereIn('member_id', \App\Models\Memberships\Member::where('user_id', auth()->id())->select('id'));
        })->latest()->get();

        return view('loan_repayments.index', compact('loanRepayments'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $members = Member::all();
        $loans = Loan::with('member')
            ->whereIn('status', ['active', 'in_arrears'])
            ->orderBy('loan_number')
            ->get();


        return view('loan_repayments.create', compact('members', 'loans'));
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
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:loan_repayments,receipt_number'
            ],
            'payment_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'payment_method' => [
                'required',
                'in:cash,bank_transfer,mobile_money,cheque,standing_order,check_off,post_dated_cheque,account_credit'
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:255'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $repayment = DB::transaction(function () use ($validated) {

                $loan = Loan::whereKey($validated['loan_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($loan->member_id != $validated['member_id']) {
                    throw ValidationException::withMessages([
                        'member_id' => 'The selected member does not belong to this loan.'
                    ]);
                }

                if ((float) $loan->amount_disbursed <= 0) {
                    throw ValidationException::withMessages(['loan_id' => 'The loan must be disbursed before accepting repayments.']);
                }

                if (!in_array($loan->status, ['active', 'in_arrears'])) {
                    throw ValidationException::withMessages([
                        'loan_id' => "Repayments cannot be posted to a {$loan->status} loan."
                    ]);
                }

                return LoanRepayment::create([
                    'loan_id' => $loan->id,
                    'member_id' => $loan->member_id,

                    'repayment_number' =>
                        $this->generateLoanRepaymentNumber(),

                    'receipt_number' =>
                        $validated['receipt_number'] ?? null,

                    'payment_date' =>
                        $validated['payment_date'],

                    'value_date' =>
                        $validated['value_date']
                        ?? $validated['payment_date'],

                    'amount_paid' =>
                        round((float) $validated['amount_paid'], 2),

                    'principal_amount' => 0,
                    'interest_amount' => 0,
                    'penalty_amount' => 0,
                    'fees_amount' => 0,
                    'unallocated_amount' => 0,

                    'payment_method' =>
                        $validated['payment_method'],

                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,

                    'payer_name' =>
                        $validated['payer_name'] ?? null,

                    'payer_phone' =>
                        $validated['payer_phone'] ?? null,

                    'recorded_by' => Auth::id(),

                    'status' => 'pending',

                    'reversed_at' => null,
                    'reversed_by' => null,
                    'reversal_reason' => null,

                    'supporting_document' =>
                        $validated['supporting_document'] ?? null,

                    'remarks' =>
                        $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_repayments.show', $repayment->id)
                ->with('success', 'Loan repayment created and is pending confirmation.');

        } catch (ValidationException $e) {
            throw $e;

        } catch (Exception $e) {
            return errorHandler(
                'Error creating loan repayment. Please try again.',
                $e
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id|
     * @return \Illuminate\Http\Response
     */
    public function show(LoanRepayment $loanRepayment)
    {
        $loanRepayment->load([
            'loan',
            'member',
            'recordedBy',
            'reversedBy',
            'allocations.schedule',
            'allocations.allocatedBy',
            'allocations.reversedBy',
        ]);

        // dd($loanRepayment->recordedBy()->first(), $loanRepayment->recordedBy()->first()->full_name);

        return view('loan_repayments.view', compact('loanRepayment'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LoanRepayment $loanRepayment)
    {
        if ($loanRepayment->status !== 'pending') {
            throw ValidationException::withMessages([
                'repayment' => 'Only pending repayments can be edited.'
            ]);
        }

        $repayment = $loanRepayment;

        $members = Member::all();
        $loanRepayment->load(['loan', 'member']);

        return view('loan_repayments.show', compact('loanRepayment', 'repayment', 'members', 'loans'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LoanRepayment $loanRepayment)
    {
        if ($loanRepayment->status !== 'pending') {
            throw ValidationException::withMessages([
                'repayment' =>
                    'Only pending repayments can be edited. Confirmed repayments must be reversed.'
            ]);
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'payment_method' => [
                'required',
                'in:cash,bank_transfer,mobile_money,cheque,standing_order,check_off,post_dated_cheque,account_credit'
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('loan_repayments', 'receipt_number')
                    ->ignore($loanRepayment->id),
            ],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:255'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($loanRepayment, $validated) {

                $repayment = LoanRepayment::whereKey($loanRepayment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($repayment->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'repayment' =>
                            'Only pending repayments can be edited.'
                    ]);
                }

                $repayment->update([
                    'payment_date' => $validated['payment_date'],
                    'value_date' =>
                        $validated['value_date']
                        ?? $validated['payment_date'],

                    'amount_paid' =>
                        round((float) $validated['amount_paid'], 2),

                    'payment_method' =>
                        $validated['payment_method'],

                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,

                    'receipt_number' =>
                        $validated['receipt_number'] ?? null,

                    'payer_name' =>
                        $validated['payer_name'] ?? null,

                    'payer_phone' =>
                        $validated['payer_phone'] ?? null,

                    'supporting_document' =>
                        $validated['supporting_document'] ?? null,

                    'remarks' =>
                        $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_repayments.show', $loanRepayment->id)
                ->with('success', 'Loan repayment updated successfully.');

        } catch (ValidationException $e) {
            throw $e;

        } catch (Exception $e) {
            return errorHandler(
                'Error updating loan repayment. Please try again.',
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

    /**
     * Confirm Repayment
     * */
    public function confirm($id)
    {
        $this->requireSaccoPermission('confirm-repayment');
        try {
            DB::transaction(function () use ($id) {

                $repayment = LoanRepayment::whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($repayment->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'repayment' => 'Only pending repayments can be confirmed.'
                    ]);
                }

                $loan = Loan::whereKey($repayment->loan_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $loan->amount_disbursed <= 0) {
                    throw ValidationException::withMessages(['loan_id' => 'The loan must be disbursed before accepting repayments.']);
                }

                if (!in_array($loan->status, ['active', 'in_arrears'])) {
                    throw ValidationException::withMessages([
                        'repayment' => "Repayments cannot be posted to a {$loan->status} loan."
                    ]);
                }

                // Match the 4-decimal precision tracking used in the approval workflow engine
                $remaining = round((float) $repayment->amount_paid, 4);

                $principalAmount = 0;
                $interestAmount = 0;
                $feesAmount = 0;
                $penaltyAmount = 0;

                $allocations = [];
                $scheduleStatuses = []; // Tracks schedule outcomes dynamically for absolute status updating

                $schedules = LoanRepaymentSchedule::where('loan_id', $loan->id)
                    ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->where('outstanding_amount', '>', 0)
                    ->orderBy('due_date')
                    ->orderBy('installment_number')
                    ->lockForUpdate()
                    ->get();

                foreach ($schedules as $schedule) {

                    if ($remaining <= 0) {
                        // Unmodified schedules retain their existing state profiles
                        $scheduleStatuses[] = $schedule->status;
                        continue;
                    }

                    // 1. Penalty Component Allocation
                    $penaltyOutstanding = max(0, round($schedule->penalty_due - $schedule->penalty_paid, 4));
                    $penaltyPaid = min($remaining, $penaltyOutstanding);
                    $remaining = round($remaining - $penaltyPaid, 4);
                    $penaltyAmount = round($penaltyAmount + $penaltyPaid, 4);

                    // 2. Interest Component Allocation
                    $interestOutstanding = max(0, round($schedule->interest_due - $schedule->interest_paid, 4));
                    $interestPaid = min($remaining, $interestOutstanding);
                    $remaining = round($remaining - $interestPaid, 4);
                    $interestAmount = round($interestAmount + $interestPaid, 4);

                    // 3. Capitalized Fees Component Allocation
                    $feesOutstanding = max(0, round($schedule->fees_due - $schedule->fees_paid, 4));
                    $feesPaid = min($remaining, $feesOutstanding);
                    $remaining = round($remaining - $feesPaid, 4);
                    $feesAmount = round($feesAmount + $feesPaid, 4);

                    // 4. Base Principal Component Allocation
                    $principalOutstanding = max(0, round($schedule->principal_due - $schedule->principal_paid, 4));
                    $principalPaid = min($remaining, $principalOutstanding);
                    $remaining = round($remaining - $principalPaid, 4);
                    $principalAmount = round($principalAmount + $principalPaid, 4);

                    $allocationTotal = round($principalPaid + $interestPaid + $feesPaid + $penaltyPaid, 4);

                    if ($allocationTotal <= 0) {
                        $scheduleStatuses[] = $schedule->status;
                        continue;
                    }

                    $newPrincipalPaid = round($schedule->principal_paid + $principalPaid, 4);
                    $newInterestPaid = round($schedule->interest_paid + $interestPaid, 4);
                    $newFeesPaid = round($schedule->fees_paid + $feesPaid, 4);
                    $newPenaltyPaid = round($schedule->penalty_paid + $penaltyPaid, 4);

                    $totalPaid = round($newPrincipalPaid + $newInterestPaid + $newFeesPaid + $newPenaltyPaid, 4);
                    $outstanding = max(0, round($schedule->total_due - $totalPaid, 4));

                    if ($outstanding <= 0) {
                        $status = 'paid';
                        $fullyPaidDate = $repayment->payment_date;
                    } elseif (Carbon::parse($schedule->due_date)->lt(Carbon::parse($repayment->payment_date))) {
                        $status = 'overdue';
                        $fullyPaidDate = null;
                    } else {
                        $status = 'partially_paid';
                        $fullyPaidDate = null;
                    }

                    $scheduleStatuses[] = $status;

                    // Compute the closing principal balance reduction to align with approval capitalization flow
                    $newClosingPrincipal = max(0, round($schedule->opening_principal_balance - ($newPrincipalPaid + $newFeesPaid), 4));

                    $schedule->update([
                        'principal_paid' => $newPrincipalPaid,
                        'interest_paid' => $newInterestPaid,
                        'fees_paid' => $newFeesPaid,
                        'penalty_paid' => $newPenaltyPaid,
                        'total_paid' => $totalPaid,
                        'outstanding_amount' => $outstanding,
                        'closing_principal_balance' => $newClosingPrincipal,
                        'fully_paid_date' => $fullyPaidDate,
                        'status' => $status,
                    ]);

                    $allocations[] = [
                        'loan_repayment_schedule_id' => $schedule->id,
                        'principal_allocated' => $principalPaid,
                        'interest_allocated' => $interestPaid,
                        'fees_allocated' => $feesPaid,
                        'penalty_allocated' => $penaltyPaid,
                        'total_allocated' => $allocationTotal,
                    ];
                }

                $unallocatedAmount = round($remaining, 4);

                /*
                 * Process structural adjustments against Loan balances.
                 * NOTE: Because fees were capitalized into the principal asset baseline at approval,
                 * both principalAmount and feesAmount are deducted from the principal_balance parameter.
                 */
                $newPrincipalBalance = max(0, round($loan->principal_balance - ($principalAmount + $feesAmount), 4));
                $newInterestBalance = max(0, round($loan->interest_balance - $interestAmount, 4));
                $newFeesBalance = max(0, round($loan->fees_balance - $feesAmount, 4));
                $newPenaltyBalance = max(0, round($loan->penalty_balance - $penaltyAmount, 4));

                $newTotalOutstanding = round($newPrincipalBalance + $newInterestBalance + $newFeesBalance + $newPenaltyBalance, 4);

                $newLoanPrincipalPaid = round($loan->principal_paid + $principalAmount, 4);
                $newLoanInterestPaid = round($loan->interest_paid + $interestAmount, 4);
                $newLoanFeesPaid = round($loan->fees_paid + $feesAmount, 4);
                $newLoanPenaltiesPaid = round($loan->penalties_paid + $penaltyAmount, 4);

                $newTotalPaid = round($loan->total_paid + $principalAmount + $interestAmount + $feesAmount + $penaltyAmount, 4);

                // Deterministically deduce the target loan state matrix directly from active records
                $hasOverdue = in_array('overdue', $scheduleStatuses);
                $loanStatus = $newTotalOutstanding <= 0 ? 'fully_paid' : ($hasOverdue ? 'in_arrears' : 'active');

                /*
                 * Persist transaction allocation detail records
                 */
                foreach ($allocations as $allocation) {
                    $repayment->allocations()->create([
                        'loan_repayment_schedule_id' => $allocation['loan_repayment_schedule_id'],
                        'loan_id' => $loan->id,
                        'member_id' => $loan->member_id,
                        'principal_allocated' => $allocation['principal_allocated'],
                        'interest_allocated' => $allocation['interest_allocated'],
                        'fees_allocated' => $allocation['fees_allocated'],
                        'penalty_allocated' => $allocation['penalty_allocated'],
                        'total_allocated' => $allocation['total_allocated'],
                        'allocated_by' => Auth::id(),
                        'allocated_at' => now(),
                        'status' => 'active',
                    ]);
                }

                /*
                 * Update the parent repayment execution entity ledger state
                 */
                $repayment->update([
                    'principal_amount' => round($principalAmount, 2),
                    'interest_amount' => round($interestAmount, 2),
                    'fees_amount' => round($feesAmount, 2),
                    'penalty_amount' => round($penaltyAmount, 2),
                    'unallocated_amount' => round($unallocatedAmount, 2),
                    'status' => 'confirmed',
                ]);

                /*
                 * Sync core parameters on the active Loan entity model
                 */
                $loan->update([
                    'principal_balance' => round($newPrincipalBalance, 2),
                    'interest_balance' => round($newInterestBalance, 2),
                    'fees_balance' => round($newFeesBalance, 2),
                    'penalty_balance' => round($newPenaltyBalance, 2),
                    'total_outstanding_balance' => round($newTotalOutstanding, 2),
                    'principal_paid' => round($newLoanPrincipalPaid, 2),
                    'interest_paid' => round($newLoanInterestPaid, 2),
                    'fees_paid' => round($newLoanFeesPaid, 2),
                    'penalties_paid' => round($newLoanPenaltiesPaid, 2),
                    'total_paid' => round($newTotalPaid, 2),
                    'status' => $loanStatus,
                ]);
            });

            return back()->with('success', 'Loan repayment confirmed and allocated successfully.');
        } catch (Exception $e) {
            return errorHandler('Error confirming loan repayment. Please try again.', $e);
        }
    }

    // reverse repayment
    public function reverse(Request $request, $id)
    {
        $this->requireSaccoPermission('reverse-repayment');
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($id, $validated) {

                $repayment = LoanRepayment::whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($repayment->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'repayment' => 'Only confirmed repayments can be reversed.'
                    ]);
                }

                $loan = Loan::whereKey($repayment->loan_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $allocations = LoanRepaymentAllocation::where(
                    'loan_repayment_id',
                    $repayment->id
                )
                ->lockForUpdate()
                ->get();

                /*
                 * Reverse schedule allocations.
                 */
                foreach ($allocations as $allocation) {

                    $schedule = LoanRepaymentSchedule::whereKey(
                        $allocation->loan_repayment_schedule_id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $principalPaid = max(
                        0,
                        round(
                            $schedule->principal_paid -
                            $allocation->principal_amount,
                            2
                        )
                    );

                    $interestPaid = max(
                        0,
                        round(
                            $schedule->interest_paid -
                            $allocation->interest_amount,
                            2
                        )
                    );

                    $feesPaid = max(
                        0,
                        round(
                            $schedule->fees_paid -
                            $allocation->fees_amount,
                            2
                        )
                    );

                    $penaltyPaid = max(
                        0,
                        round(
                            $schedule->penalty_paid -
                            $allocation->penalty_amount,
                            2
                        )
                    );

                    $totalPaid = round(
                        $principalPaid +
                        $interestPaid +
                        $feesPaid +
                        $penaltyPaid,
                        2
                    );

                    $outstanding = max(
                        0,
                        round(
                            $schedule->total_due - $totalPaid,
                            2
                        )
                    );

                    if ($outstanding <= 0) {
                        $status = 'paid';
                        $fullyPaidDate = $schedule->fully_paid_date;
                    } elseif (
                        Carbon::parse($schedule->due_date)->lt(now())
                    ) {
                        $status = 'overdue';
                        $fullyPaidDate = null;
                    } elseif ($totalPaid > 0) {
                        $status = 'partially_paid';
                        $fullyPaidDate = null;
                    } else {
                        $status = 'pending';
                        $fullyPaidDate = null;
                    }

                    $schedule->update([
                        'principal_paid' => $principalPaid,
                        'interest_paid' => $interestPaid,
                        'fees_paid' => $feesPaid,
                        'penalty_paid' => $penaltyPaid,
                        'total_paid' => $totalPaid,
                        'outstanding_amount' => $outstanding,
                        'fully_paid_date' => $fullyPaidDate,
                        'status' => $status,
                    ]);
                }

                /*
                 * Restore loan balances.
                 */
                $principalBalance = round(
                    $loan->principal_balance +
                    $repayment->principal_amount,
                    2
                );

                $interestBalance = round(
                    $loan->interest_balance +
                    $repayment->interest_amount,
                    2
                );

                $feesBalance = round(
                    $loan->fees_balance +
                    $repayment->fees_amount,
                    2
                );

                $penaltyBalance = round(
                    $loan->penalty_balance +
                    $repayment->penalty_amount,
                    2
                );

                $totalOutstanding = round(
                    $principalBalance +
                    $interestBalance +
                    $feesBalance +
                    $penaltyBalance,
                    2
                );

                $principalPaid = max(
                    0,
                    round(
                        $loan->principal_paid -
                        $repayment->principal_amount,
                        2
                    )
                );

                $interestPaid = max(
                    0,
                    round(
                        $loan->interest_paid -
                        $repayment->interest_amount,
                        2
                    )
                );

                $feesPaid = max(
                    0,
                    round(
                        $loan->fees_paid -
                        $repayment->fees_amount,
                        2
                    )
                );

                $penaltiesPaid = max(
                    0,
                    round(
                        $loan->penalties_paid -
                        $repayment->penalty_amount,
                        2
                    )
                );

                $totalPaid = max(
                    0,
                    round(
                        $loan->total_paid -
                        $repayment->principal_amount -
                        $repayment->interest_amount -
                        $repayment->fees_amount -
                        $repayment->penalty_amount,
                        2
                    )
                );

                $hasOverdue = LoanRepaymentSchedule::where('loan_id', $loan->id)
                    ->where('status', 'overdue')
                    ->where('outstanding_amount', '>', 0)
                    ->exists();

                $status = $totalOutstanding <= 0
                    ? 'fully_paid'
                    : ($hasOverdue ? 'in_arrears' : 'active');

                $loan->update([
                    'principal_balance' => $principalBalance,
                    'interest_balance' => $interestBalance,
                    'fees_balance' => $feesBalance,
                    'penalty_balance' => $penaltyBalance,

                    'total_outstanding_balance' => $totalOutstanding,

                    'principal_paid' => $principalPaid,
                    'interest_paid' => $interestPaid,
                    'fees_paid' => $feesPaid,
                    'penalties_paid' => $penaltiesPaid,
                    'total_paid' => $totalPaid,

                    'status' => $status,
                ]);

                /*
                 * Mark original repayment as reversed.
                 */
                $repayment->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'reversed_by' => Auth::id(),
                    'reversal_reason' => $validated['reversal_reason'],
                ]);
            });

            return redirect()
                ->route('loan_repayments.show', $id)
                ->with('success', 'Loan repayment reversed successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return errorHandler(
                'Error reversing loan repayment. Please try again.',
                $e
            );
        }
    }

    private function generateLoanRepaymentNumber()
    {
        do {
            $number = 'RP-' . now()->format('YmdHis') . '-' .
                strtoupper(Str::random(6));
        } while (
            LoanRepayment::where('repayment_number', $number)->exists()
        );

        return $number;
    }
}
