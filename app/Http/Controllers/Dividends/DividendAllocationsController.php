<?php

namespace App\Http\Controllers\Dividends;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Dividends\DividendAllocation;
use App\Models\Dividends\DividendRun;
use App\Models\Shares\ShareAccount;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DividendAllocationsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissions = [
                'pay' => 'pay-dividends',
                'reversePayment' => 'reverse-dividends',
            ];

            $this->requireSaccoPermission($permissions[$action] ?? 'manage-dividends');

            return $next($request);
        });
    }

    public function index()
    {
        $allocations = DividendAllocation::with(['run', 'member', 'shareAccount'])
            ->latest()
            ->get();

        return view('dividends.allocations.index', compact('allocations'));
    }

    public function create()
    {
        $runs = DividendRun::whereIn('status', ['draft', 'calculated'])->get();
        $accounts = ShareAccount::with('member')->get();

        return view('dividends.allocations.form', compact('runs', 'accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $allocation = DB::transaction(function () use ($data) {
            $run = DividendRun::whereKey($data['dividend_run_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($run->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages([
                    'dividend_run_id' => 'Allocations can only be added to draft or calculated runs.',
                ]);
            }

            $account = ShareAccount::with('member')->findOrFail($data['share_account_id']);
            $gross = round($data['qualifying_share_balance'] * $run->dividend_rate / 100, 2);

            $allocation = DividendAllocation::create($data + [
                'member_id' => $account->member_id,
                'dividend_rate' => $run->dividend_rate,
                'gross_dividend' => $gross,
                'withholding_tax' => 0,
                'other_deductions' => 0,
                'net_dividend' => $gross,
                'status' => 'pending',
            ]);

            app(DividendRunsController::class)->refreshTotals($run);

            return $allocation;
        });

        return redirect()
            ->route('dividend_allocations.show', $allocation)
            ->with('success', 'Allocation created.');
    }

    public function show(DividendAllocation $dividendAllocation)
    {
        $dividendAllocation->load(['run', 'member', 'shareAccount', 'deductions']);
        $paymentJournal = JournalEntry::where(
            'posting_key',
            'dividend_allocation:' . $dividendAllocation->id . ':paid'
        )->first();
        $reversalJournal = JournalEntry::where(
            'posting_key',
            'dividend_allocation:' . $dividendAllocation->id . ':reversed'
        )->first();

        return view('dividends.allocations.show', [
            'allocation' => $dividendAllocation,
            'paymentJournal' => $paymentJournal,
            'reversalJournal' => $reversalJournal,
        ]);
    }

    public function edit(DividendAllocation $dividendAllocation)
    {
        abort_unless(
            $dividendAllocation->status === 'pending',
            422,
            'Only pending allocations can be edited.'
        );

        $runs = DividendRun::whereIn('status', ['draft', 'calculated'])->get();
        $accounts = ShareAccount::with('member')->get();

        return view('dividends.allocations.form', [
            'allocation' => $dividendAllocation,
            'runs' => $runs,
            'accounts' => $accounts,
        ]);
    }

    public function update(Request $request, DividendAllocation $dividendAllocation)
    {
        abort_unless(
            $dividendAllocation->status === 'pending',
            422,
            'Only pending allocations can be edited.'
        );

        $data = $this->validated($request);
        $run = DividendRun::findOrFail($data['dividend_run_id']);

        abort_unless(
            $run->status === 'draft',
            422,
            'Allocations can only belong to draft runs.'
        );

        $account = ShareAccount::findOrFail($data['share_account_id']);
        $gross = round($data['qualifying_share_balance'] * $run->dividend_rate / 100, 2);
        $oldRun = $dividendAllocation->run;

        $dividendAllocation->update($data + [
            'member_id' => $account->member_id,
            'dividend_rate' => $run->dividend_rate,
            'gross_dividend' => $gross,
            'net_dividend' => max(
                0,
                $gross - (float) $dividendAllocation->withholding_tax - (float) $dividendAllocation->other_deductions
            ),
        ]);

        app(DividendRunsController::class)->refreshTotals($run);

        if ($oldRun->id !== $run->id) {
            app(DividendRunsController::class)->refreshTotals($oldRun);
        }

        return redirect()
            ->route('dividend_allocations.show', $dividendAllocation)
            ->with('success', 'Allocation updated.');
    }

    public function destroy(DividendAllocation $dividendAllocation)
    {
        abort_unless(
            $dividendAllocation->status === 'pending' && !$dividendAllocation->deductions()->exists(),
            422,
            'Only pending allocations without deductions can be deleted.'
        );

        $run = $dividendAllocation->run;
        $dividendAllocation->delete();

        app(DividendRunsController::class)->refreshTotals($run);

        return redirect()
            ->route('dividend_allocations.index')
            ->with('success', 'Allocation deleted.');
    }

    public function pay(
        Request $request,
        DividendAllocation $dividendAllocation,
        JournalPostingService $posting
    ) {
        $data = $request->validate([
            'payment_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'payment_option' => ['required', 'in:cash,bank_transfer,mobile_money'],
            'paid_date' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $account = ChartOfAccount::whereKey($data['payment_account_id'])
            ->cashAndBank()
            ->where(function ($query) {
                $query->where('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%')
                    ->orWhere('name', 'like', '%mobile money%');
            })
            ->firstOrFail();

        DB::transaction(function () use ($data, $account, $dividendAllocation, $posting) {
            $allocation = DividendAllocation::whereKey($dividendAllocation->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $allocation->status === 'approved' && $allocation->run->status === 'posted',
                422,
                'Only approved allocations on a posted run can be paid.'
            );

            $posting->postDividendPayment(
                $allocation,
                $account->id,
                $data['paid_date'],
                $data['payment_reference'] ?? null
            );

            $allocation->update([
                'status' => 'paid',
                'payment_option' => $data['payment_option'],
                'payment_account_id' => $account->id,
                'paid_date' => $data['paid_date'],
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);
        });

        return redirect()
            ->route('dividend_allocations.show', $dividendAllocation)
            ->with('success', 'Dividend payment posted to the ledger.');
    }

    public function reversePayment(
        Request $request,
        DividendAllocation $dividendAllocation,
        JournalPostingService $posting
    ) {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $dividendAllocation, $posting) {
            $allocation = DividendAllocation::whereKey($dividendAllocation->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($allocation->status === 'paid', 422, 'Only paid allocations can be reversed.');

            $posting->reverseDividendPayment($allocation, $data['reason']);
            $allocation->update(['status' => 'cancelled']);
        });

        return redirect()
            ->route('dividend_allocations.show', $dividendAllocation)
            ->with('success', 'Payment journal reversed and allocation cancelled.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'dividend_run_id' => ['required', 'exists:dividend_runs,id'],
            'share_account_id' => ['required', 'exists:share_accounts,id'],
            'qualifying_share_balance' => ['required', 'numeric', 'min:0'],
            'payment_option' => ['nullable', 'in:cash,bank_transfer,mobile_money'],
        ]);
    }
}
