<?php

namespace App\Http\Controllers\LoanRepaymentSchedules;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Models\Memberships\Member;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoanRepaymentSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $schedules = LoanRepaymentSchedule::latest()->get();

       return view('loan_repayment_schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('loan_repayment_schedules.index');
        
        $loans = Loan::all();
        $members = Member::all();

        return view('loan_repayment_schedules.create', compact('loans', 'members'));
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
            'installment_number' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'opening_principal_balance' => ['required', 'numeric', 'min:0'],
            'principal_due' => ['required', 'numeric', 'min:0'],
            'interest_due' => ['nullable', 'numeric', 'min:0'],
            'fees_due' => ['nullable', 'numeric', 'min:0'],
            'penalty_due' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $schedule = DB::transaction(function () use ($validated) {

                $loan = Loan::whereKey($validated['loan_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($loan->member_id != $validated['member_id']) {
                    throw ValidationException::withMessages([
                        'member_id' => 'The selected member does not belong to this loan.'
                    ]);
                }

                if ($loan->status !== 'active') {
                    throw ValidationException::withMessages([
                        'loan_id' => 'Repayment schedules can only be created for active loans.'
                    ]);
                }

                if (LoanRepaymentSchedule::where('loan_id', $loan->id)
                    ->where('installment_number', $validated['installment_number'])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'installment_number' => 'This installment number already exists for this loan.'
                    ]);
                }

                $opening = round((float) $validated['opening_principal_balance'], 2);
                $principal = round((float) $validated['principal_due'], 2);
                $interest = round((float) ($validated['interest_due'] ?? 0), 2);
                $fees = round((float) ($validated['fees_due'] ?? 0), 2);
                $penalty = round((float) ($validated['penalty_due'] ?? 0), 2);

                if ($principal > $opening) {
                    throw ValidationException::withMessages([
                        'principal_due' => 'Principal due cannot exceed the opening principal balance.'
                    ]);
                }

                $totalDue = round(
                    $principal + $interest + $fees + $penalty,
                    2
                );

                $closing = round($opening - $principal, 2);

                return LoanRepaymentSchedule::create([
                    'loan_id' => $loan->id,
                    'member_id' => $loan->member_id,
                    'installment_number' => $validated['installment_number'],
                    'due_date' => $validated['due_date'],

                    'opening_principal_balance' => $opening,

                    'principal_due' => $principal,
                    'interest_due' => $interest,
                    'fees_due' => $fees,
                    'penalty_due' => $penalty,

                    'total_due' => $totalDue,
                    'closing_principal_balance' => $closing,

                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'fees_paid' => 0,
                    'penalty_paid' => 0,
                    'total_paid' => 0,
                    'outstanding_amount' => $totalDue,

                    'fully_paid_date' => null,
                    'status' => 'pending',
                    'remarks' => $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_repayment_schedules.show', $schedule->id)
                ->with('success', 'Repayment schedule created successfully.');

        }  catch (Exception $e) {
            return errorHandler(
                'Error creating repayment schedule. Please try again.',
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
    public function show(LoanRepaymentSchedule $loan_repayment_schedule)
    {
        $schedule = $loan_repayment_schedule;

        return view('loan_repayment_schedules.view', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LoanRepaymentSchedule $loan_repayment_schedule)
    {
        $schedule = $loan_repayment_schedule;
        $loans = Loan::all();
        $members = Member::all();

        return view('loan_repayment_schedules.edit', compact('schedule', 'loans', 'members'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LoanRepaymentSchedule $loan_repayment_schedule)
    {
        $schedule = $loan_repayment_schedule;
        if ($schedule->total_paid > 0) {
            throw ValidationException::withMessages([
                'schedule' => 'A schedule with payments cannot be edited.'
            ]);
        }

        $validated = $request->validate([
            'installment_number' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'opening_principal_balance' => ['required', 'numeric', 'min:0'],
            'principal_due' => ['required', 'numeric', 'min:0'],
            'interest_due' => ['nullable', 'numeric', 'min:0'],
            'fees_due' => ['nullable', 'numeric', 'min:0'],
            'penalty_due' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated, $schedule) {

                $schedule = LoanRepaymentSchedule::whereKey($schedule->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($schedule->total_paid > 0) {
                    throw ValidationException::withMessages([
                        'schedule' => 'A schedule with payments cannot be edited.'
                    ]);
                }

                $duplicate = LoanRepaymentSchedule::where('loan_id', $schedule->loan_id)
                    ->where('installment_number', $validated['installment_number'])
                     ->where('id', '!=', $schedule->id)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'installment_number' => 'This installment number already exists for this loan.'
                    ]);
                }

                $opening = round((float) $validated['opening_principal_balance'], 2);
                $principal = round((float) $validated['principal_due'], 2);
                $interest = round((float) ($validated['interest_due'] ?? 0), 2);
                $fees = round((float) ($validated['fees_due'] ?? 0), 2);
                $penalty = round((float) ($validated['penalty_due'] ?? 0), 2);

                if ($principal > $opening) {
                    throw ValidationException::withMessages([
                        'principal_due' => 'Principal due cannot exceed the opening principal balance.'
                    ]);
                }

                $totalDue = round(
                    $principal + $interest + $fees + $penalty,
                    2
                );

                $closing = round($opening - $principal, 2);

                $schedule->update([
                    'installment_number' => $validated['installment_number'],
                    'due_date' => $validated['due_date'],

                    'opening_principal_balance' => $opening,
                    'principal_due' => $principal,
                    'interest_due' => $interest,
                    'fees_due' => $fees,
                    'penalty_due' => $penalty,

                    'total_due' => $totalDue,
                    'closing_principal_balance' => $closing,

                    'outstanding_amount' => $totalDue,
                    'remarks' => $validated['remarks'] ?? null,
                ]);
            });

            return redirect()
                ->route('loan_repayment_schedules.show', $schedule->id)
                ->with('success', 'Repayment schedule updated successfully.');

        } catch (Exception $e) {
            return errorHandler(
                'Error updating repayment schedule. Please try again.',
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
}
