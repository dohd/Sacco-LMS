<?php

namespace App\Http\Controllers\LoanGuarantors;

use App\Http\Controllers\Controller;
use App\Models\LoanApplications\LoanGuarantor;
use Illuminate\Http\Request;

class LoanGuarantorsController extends Controller
{
    /**
     * Guarantors with active share holds, optionally limited to loans
     * in arrears.
     */
    public function index(Request $request)
    {
        $this->requireSaccoPermission('manage-guarantors');
        $arrearsOnly = $request->query('filter') === 'in_arrears';

        $guarantors = LoanGuarantor::with(['loan', 'application', 'member'])
            ->where('status', 'held')
            ->when($arrearsOnly, fn($query) => $query->whereHas('loan', fn($loan) => $loan->where('status', 'in_arrears')))
            ->orderBy('member_id')
            ->get();

        return view('loan_guarantors.index', compact('guarantors', 'arrearsOnly'));
    }
}
