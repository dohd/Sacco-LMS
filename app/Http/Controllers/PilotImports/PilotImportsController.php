<?php

namespace App\Http\Controllers\PilotImports;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Services\PilotImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PilotImportsController extends Controller
{
    public function index(PilotImportService $imports)
    {
        if (!userCan('view-members')) {
            abort(403);
        }

        $products = $imports->products();
        $openingEquityAccount = $this->configuredOpeningEquityAccount($products['opening_accounts']);
        $accountingPeriods = AccountingPeriod::where('status', 'open')
            ->orderByDesc('end_date')
            ->get();
        $selectedAccountingPeriod = $accountingPeriods->first();

        return view('pilot_imports.index', [
            'products' => $products,
            'openingEquityAccount' => $openingEquityAccount,
            'accountingPeriods' => $accountingPeriods,
            'selectedAccountingPeriod' => $selectedAccountingPeriod,
            'report' => null,
            'batchToken' => null,
            'selected' => [
                'opening_equity_account_id' => optional($openingEquityAccount)->id,
                'accounting_period_id' => optional($selectedAccountingPeriod)->id,
            ],
        ]);
    }

    public function template()
    {
        if (!userCan('view-members')) abort(403);

        $headers = [
            'import_membership_number',
            'first_name',
            'middle_name',
            'last_name',
            'date_of_birth',
            'national_id',
            'phone',
            'email',
            'admission_date',
            'shares',
            'deposit_balance',
            'loan_balance',
            'interest_accrued',
            'status',
            'loan_status',
            'interest_period_covered',
            'remarks',
            'comments',
        ];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Membership');
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicitByColumnAndRow($index + 1, 1, $header, DataType::TYPE_STRING);
            $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
        }
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xls($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'upenyo_membership_import_template.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    public function preview(Request $request, PilotImportService $imports)
    {
        if (!userCan('view-members')) {
            abort(403);
        }

        $validated = $request->validate([
            'membership_file' => 'required|file|max:20480',
            'accounting_period_id' => 'required|integer',
            'savings_product_id' => 'required|integer',
            'shares_product_id' => 'required|integer',
            'loans_product_id' => 'required|integer',
        ]);

        $accountingPeriod = AccountingPeriod::where('status', 'open')
            ->whereKey($validated['accounting_period_id'])
            ->first();
        if (!$accountingPeriod) {
            throw ValidationException::withMessages([
                'accounting_period_id' => 'Choose an open accounting period. The opening date is derived from its end date.',
            ]);
        }

        $products = $imports->products();
        $openingEquityAccount = $this->configuredOpeningEquityAccount($products['opening_accounts']);
        if (!$openingEquityAccount) {
            throw ValidationException::withMessages([
                'opening_equity_account_id' => 'Configure an active, postable opening balance equity account in Settings before importing.',
            ]);
        }
        $extension = strtolower($request->file('membership_file')->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            throw ValidationException::withMessages(['membership_file' => 'Upload a CSV, XLS, or XLSX membership file.']);
        }
        $ids = $request->only(['savings_product_id', 'shares_product_id', 'loans_product_id']);
        $ids['opening_equity_account_id'] = $openingEquityAccount->id;
        $openingDate = $accountingPeriod->end_date->toDateString();
        $report = $imports->preview($request->file('membership_file'), $ids, $openingDate);
        $batchToken = null;
        if (!$report['errors']) {
            $batchToken = $imports->stage(
                $request->file('membership_file'),
                $ids,
                (int) auth()->id(),
                $openingDate
            )->token;
        }

        return view('pilot_imports.index', [
            'products' => $products,
            'openingEquityAccount' => $openingEquityAccount,
            'accountingPeriods' => AccountingPeriod::where('status', 'open')->orderByDesc('end_date')->get(),
            'selectedAccountingPeriod' => $accountingPeriod,
            'report' => $report,
            'selected' => array_merge($ids, ['accounting_period_id' => $accountingPeriod->id]),
            'batchToken' => $batchToken,
        ]);
    }

    private function configuredOpeningEquityAccount($availableAccounts): ?ChartOfAccount
    {
        $accountId = saccoSetting('OPENING_BALANCE_EQUITY_ACCOUNT_ID');
        if (!$accountId) {
            return null;
        }

        return $availableAccounts->firstWhere('id', (int) $accountId);
    }

    public function commit(Request $request, PilotImportService $imports)
    {
        foreach ([
            'view-members',
            'approve-membership',
            'manage-savings-accounts',
            'confirm-savings-transaction',
            'manage-share-accounts',
            'confirm-share-transaction',
            'approve-loan',
        ] as $permission) {
            if (!userCan($permission)) {
                abort(403);
            }
        }

        $request->validate([
            'batch_token' => 'required|uuid',
            'confirm_import' => 'required|accepted',
        ]);

        $result = $imports->commit($request->input('batch_token'), (int) auth()->id());

        return redirect()
            ->route('pilot_imports.index')
            ->with('pilot_import_result', $result)
            ->with('success', 'Member opening balances were committed.');
    }
}
