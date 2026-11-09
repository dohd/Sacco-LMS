<?php

namespace App\Http\Controllers\PilotImports;

use App\Http\Controllers\Controller;
use App\Services\PilotImportService;
use Illuminate\Http\Request;

class PilotImportsController extends Controller
{
    public function index(PilotImportService $imports)
    {
        if (!userCan('view-members')) abort(403);
        return view('pilot_imports.index', ['products' => $imports->products(), 'report' => null, 'batchToken' => null]);
    }

    public function preview(Request $request, PilotImportService $imports)
    {
        if (!userCan('view-members')) abort(403);
        $request->validate([
            'membership_file' => 'required|file|mimes:xls,xlsx|max:20480',
            'savings_product_id' => 'required|integer',
            'shares_product_id' => 'required|integer',
            'loans_product_id' => 'required|integer',
            'opening_equity_account_id' => 'required|integer',
        ]);
        $ids = $request->only(['savings_product_id', 'shares_product_id', 'loans_product_id', 'opening_equity_account_id']);
        $report = $imports->preview($request->file('membership_file'), $ids);
        $batchToken = null;
        if (!$report['errors']) $batchToken = $imports->stage($request->file('membership_file'), $ids, (int) auth()->id())->token;

        return view('pilot_imports.index', [
            'products' => $imports->products(), 'report' => $report, 'selected' => $ids, 'batchToken' => $batchToken,
        ]);
    }

    public function commit(Request $request, PilotImportService $imports)
    {
        foreach (['view-members', 'approve-membership', 'manage-savings-accounts', 'confirm-savings-transaction', 'manage-share-accounts', 'confirm-share-transaction', 'approve-loan'] as $permission) {
            if (!userCan($permission)) abort(403);
        }
        $request->validate(['batch_token' => 'required|uuid', 'confirm_import' => 'required|accepted']);
        $result = $imports->commit($request->input('batch_token'), (int) auth()->id());
        return redirect()->route('pilot_imports.index')->with('pilot_import_result', $result)->with('success', 'Pilot opening balances were committed.');
    }
}
