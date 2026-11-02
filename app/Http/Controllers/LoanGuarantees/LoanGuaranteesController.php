<?php

namespace App\Http\Controllers\LoanGuarantees;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\LoanGuarantor;
use Illuminate\Http\Request;

class LoanGuaranteesController extends Controller
{
    /**
     * Guarantees currently held against loans, optionally limited to loans
     * in arrears so staff can see whose shares stand behind them.
     */
    public function index(Request $request)
    {
        $this->requireSaccoPermission('manage-guarantors');
        $arrearsOnly = $request->query('filter') === 'in_arrears';

        $guarantees = LoanGuarantor::with(['loan', 'application', 'member'])
            ->where('status', 'held')
            ->when($arrearsOnly, fn($query) => $query->whereHas('loan', fn($loan) => $loan->where('status', 'in_arrears')))
            ->orderBy('member_id')
            ->get();

        return view('loan_guarantees.index', compact('guarantees', 'arrearsOnly'));
    }
}
