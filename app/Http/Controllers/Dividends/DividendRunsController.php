<?php

namespace App\Http\Controllers\Dividends;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Dividends\DividendRun;
use App\Services\DividendCalculationService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DividendRunsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissions = [
                'approve' => 'approve-dividend-runs',
                'post' => 'post-dividend-runs',
                'cancel' => 'reverse-dividends',
            ];

            $this->requireSaccoPermission($permissions[$action] ?? 'manage-dividends');

            return $next($request);
        });
    }

    public function index()
    {
        $dividendRuns = DividendRun::latest()->get();

        return view('dividends.runs.index', compact('dividendRuns'));
    }

    public function create()
    {
        return view('dividends.runs.form', [
            'run' => new DividendRun([
                'declaration_account_id' => saccoSetting('RETAINED_EARNINGS_ACCOUNT_ID'),
                'payable_account_id' => saccoSetting('DIVIDEND_PAYABLE_ACCOUNT_ID'),
                'withholding_account_id' => saccoSetting('WITHHOLDING_TAX_ACCOUNT_ID'),
            ]),
            'accounts' => $this->ledgerAccounts(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['run_number'] = $this->number();
        $data['status'] = 'draft';
        $data['calculated_by'] = auth()->id();

        $run = DividendRun::create($data);

        return redirect()
            ->route('dividend_runs.show', $run)
            ->with('success', 'Dividend run created.');
    }

    public function show(DividendRun $dividendRun)
    {
        $dividendRun->load('allocations.member');
        $journalEntry = JournalEntry::where('posting_key', 'dividend_run:' . $dividendRun->id . ':posted')
            ->first();

        return view('dividends.runs.show', [
            'run' => $dividendRun,
            'journalEntry' => $journalEntry,
        ]);
    }

    public function edit(DividendRun $dividendRun)
    {
        abort_unless(
            $dividendRun->status === 'draft' && !$dividendRun->allocations()->exists(),
            422,
            'Only empty draft runs can be edited.'
        );

        return view('dividends.runs.form', [
            'run' => $dividendRun,
            'accounts' => $this->ledgerAccounts(),
        ]);
    }

    public function update(Request $request, DividendRun $dividendRun)
    {
        abort_unless(
            $dividendRun->status === 'draft' && !$dividendRun->allocations()->exists(),
            422,
            'Only empty draft runs can be edited.'
        );

        $dividendRun->update($this->validated($request));

        return redirect()
            ->route('dividend_runs.show', $dividendRun)
            ->with('success', 'Dividend run updated.');
    }

    public function calculate(
        DividendRun $dividendRun,
        DividendCalculationService $calculator
    ) {
        $calculator->calculate($dividendRun);

        return redirect()
            ->route('dividend_runs.show', $dividendRun)
            ->with('success', 'Dividend allocations calculated from confirmed share transactions.');
    }

    public function approve(DividendRun $dividendRun)
    {
        DB::transaction(function () use ($dividendRun) {
            $run = DividendRun::whereKey($dividendRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $run->status === 'calculated' && $run->allocations()->exists(),
                422,
                'Calculate a run with allocations before approving it.'
            );

            $run->forceFill([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            $run->allocations()
                ->where('status', 'pending')
                ->update(['status' => 'approved']);
        });

        return redirect()
            ->route('dividend_runs.show', $dividendRun)
            ->with('success', 'Dividend run approved.');
    }

    public function post(DividendRun $dividendRun, JournalPostingService $posting)
    {
        DB::transaction(function () use ($dividendRun, $posting) {
            $run = DividendRun::whereKey($dividendRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($run->status === 'approved', 422, 'Only approved runs can be posted.');

            $posting->postDividendRun($run);

            $run->forceFill([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('dividend_runs.show', $dividendRun)
            ->with('success', 'Dividend run posted to the general ledger.');
    }

    public function cancel(
        Request $request,
        DividendRun $dividendRun,
        JournalPostingService $posting
    ) {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($dividendRun, $posting, $data) {
            $run = DividendRun::whereKey($dividendRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                in_array($run->status, ['draft', 'calculated', 'approved', 'posted'], true),
                422,
                'This run has already been cancelled.'
            );

            if ($run->status === 'posted') {
                $posting->reverseDividendRun($run, $data['reason']);
            }

            $run->forceFill(['status' => 'cancelled'])->save();
        });

        return redirect()
            ->route('dividend_runs.show', $dividendRun)
            ->with('success', 'Dividend run cancelled. Any posted declaration was reversed.');
    }

    public function destroy(DividendRun $dividendRun)
    {
        abort_unless(
            $dividendRun->status === 'draft' && !$dividendRun->allocations()->exists(),
            422,
            'Only empty draft runs can be deleted.'
        );

        $dividendRun->delete();

        return redirect()
            ->route('dividend_runs.index')
            ->with('success', 'Dividend run deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'declaration_date' => ['required', 'date'],
            'payment_date' => ['nullable', 'date'],
            'dividend_rate' => ['required', 'numeric', 'min:0', 'max:9999.9999'],
            'calculation_method' => [
                'required',
                Rule::in(['closing_balance', 'average_balance', 'minimum_balance', 'weighted_average']),
            ],
            'declaration_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(function ($query) {
                    $query->where('account_type', 'equity')
                        ->where('name', 'like', '%retained earnings%')
                        ->where('is_active', true)
                        ->where('is_postable', true)
                        ->where('is_control_account', false);
                }),
            ],
            'payable_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(function ($query) {
                    $query->where('account_type', 'liability')
                        ->where('name', 'like', '%dividend%')
                        ->where('is_active', true)
                        ->where('is_postable', true)
                        ->where('is_control_account', false);
                }),
            ],
            'withholding_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where(function ($query) {
                    $query->where('account_type', 'liability')
                        ->where('name', 'like', '%withholding%')
                        ->where('is_active', true)
                        ->where('is_postable', true)
                        ->where('is_control_account', false);
                }),
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        if ((int) $data['declaration_account_id'] === (int) $data['payable_account_id']) {
            throw ValidationException::withMessages([
                'payable_account_id' => 'The retained earnings and dividends payable accounts must be different.',
            ]);
        }

        return $data;
    }

    private function ledgerAccounts()
    {
        return ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();
    }

    public function refreshTotals(DividendRun $run): void
    {
        $run->forceFill([
            'total_qualifying_shares' => $run->allocations()->sum('qualifying_share_balance'),
            'gross_dividend_amount' => $run->allocations()->sum('gross_dividend'),
            'total_withholding_tax' => $run->allocations()->sum('withholding_tax'),
            'net_dividend_amount' => $run->allocations()->sum('net_dividend'),
        ])->save();
    }

    private function number(): string
    {
        do {
            $number = 'DIV-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
        } while (DividendRun::where('run_number', $number)->exists());

        return $number;
    }
}
