<?php

namespace App\Http\Controllers\Loans;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\LoanApplications\LoanRepaymentSchedule;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $loans = Loan::latest()->get();

        return view('loans.index', compact('loans'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Loan $loan)
    {
        $loan->load([
            'member',
            'loanApplication',
            'loanApproval',
            'disbursements',
            'repaymentSchedules',
        ]);
        
        return view('loans.view', compact('loan'));
    }

    /**
     * Write off what is still owed on a loan.
     *
     * The ledger balances of the loan are charged to the product's write-off
     * expense account and the open installments are closed. Guarantor holds
     * are kept, because a guarantee may still be called.
     */
    public function writeOff(Request $request, $id)
    {
        $this->requireSaccoPermission('write-off-loan');
        $validated = $request->validate([
            'write_off_date' => ['required', 'date', 'before_or_equal:today'],
            'write_off_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($id, $validated) {
                $loan = Loan::whereKey($id)->lockForUpdate()->firstOrFail();

                if (!in_array($loan->status, ['active', 'in_arrears'], true)) {
                    throw ValidationException::withMessages([
                        'write_off' => 'Only an active loan can be written off.',
                    ]);
                }

                if (LoanRepayment::where('loan_id', $loan->id)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages([
                        'write_off' => 'Confirm or cancel the pending repayments before writing off this loan.',
                    ]);
                }

                $written = app(JournalPostingService::class)
                    ->postLoanWriteOff($loan, $validated['write_off_date'], $validated['write_off_reason']);

                $loan->update([
                    'status' => 'written_off',
                    'written_off_date' => $validated['write_off_date'],
                    'written_off_by' => auth()->id(),
                    'write_off_reason' => $validated['write_off_reason'],
                    'written_off_principal' => $written['principal'],
                    'written_off_interest' => $written['interest'],
                    'written_off_fees' => $written['fees'],
                    'written_off_penalty' => $written['penalty'],
                    'principal_balance' => 0,
                    'interest_balance' => 0,
                    'fees_balance' => 0,
                    'penalty_balance' => 0,
                    'total_outstanding_balance' => 0,
                    'arrears_amount' => 0,
                ]);

                LoanRepaymentSchedule::where('loan_id', $loan->id)
                    ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->update(['status' => 'waived', 'outstanding_amount' => 0]);
            });

            return redirect()->route('loans.show', $id)->with('success', 'Loan written off.');
        } catch (\Exception $e) {
            return errorHandler('The loan could not be written off. Please try again.', $e);
        }
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
}
