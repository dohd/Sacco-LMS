<?php

namespace App\Http\Controllers\Dividends;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Dividends\DividendAllocation;
use App\Models\Dividends\DividendDeduction;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DividendDeductionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->requireSaccoPermission('manage-dividends');

            return $next($request);
        });
    }

    public function index()
    {
        $deductions = DividendDeduction::with('allocation.member')
            ->latest()
            ->get();

        return view('dividends.deductions.index', compact('deductions'));
    }

    public function create()
    {
        $allocations = DividendAllocation::where('status', 'pending')
            ->with('member')
            ->get();
        $accounts = $this->ledgerAccounts();

        return view('dividends.deductions.form', compact('allocations', 'accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->assertOffsetAccountAllowed($data);

        $deduction = DB::transaction(function () use ($data) {
            $allocation = DividendAllocation::whereKey($data['dividend_allocation_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $allocation->status === 'pending',
                422,
                'Only pending allocations can be adjusted.'
            );

            $deduction = DividendDeduction::create($data);
            $this->recalculate($allocation);

            app(DividendRunsController::class)->refreshTotals($allocation->run);

            return $deduction;
        });

        return redirect()
            ->route('dividend_deductions.index')
            ->with('success', 'Deduction recorded.');
    }

    public function edit(DividendDeduction $dividendDeduction)
    {
        abort_unless($dividendDeduction->allocation->status === 'pending', 422);

        $allocations = DividendAllocation::where('status', 'pending')
            ->with('member')
            ->get();
        $accounts = $this->ledgerAccounts();

        return view('dividends.deductions.form', [
            'deduction' => $dividendDeduction,
            'allocations' => $allocations,
            'accounts' => $accounts,
        ]);
    }

    public function update(Request $request, DividendDeduction $dividendDeduction)
    {
        $data = $this->validated($request);
        $this->assertOffsetAccountAllowed($data);

        DB::transaction(function () use ($data, $dividendDeduction) {
            $oldAllocation = $dividendDeduction->allocation;

            abort_unless(
                $oldAllocation->status === 'pending',
                422,
                'Only pending allocations can be adjusted.'
            );

            $targetAllocation = DividendAllocation::findOrFail($data['dividend_allocation_id']);

            abort_unless(
                $targetAllocation->status === 'pending',
                422,
                'Only pending allocations can receive deductions.'
            );

            $deduction = DividendDeduction::whereKey($dividendDeduction->id)
                ->lockForUpdate()
                ->first();
            $deduction->update($data);

            $this->recalculate($oldAllocation->fresh());

            if ($oldAllocation->id !== $targetAllocation->id) {
                $this->recalculate($targetAllocation);
            }

            app(DividendRunsController::class)->refreshTotals($oldAllocation->run);

            if ($oldAllocation->run->id !== $targetAllocation->run->id) {
                app(DividendRunsController::class)->refreshTotals($targetAllocation->run);
            }
        });

        return redirect()
            ->route('dividend_deductions.index')
            ->with('success', 'Deduction updated.');
    }

    public function destroy(DividendDeduction $dividendDeduction)
    {
        DB::transaction(function () use ($dividendDeduction) {
            $allocation = $dividendDeduction->allocation;

            abort_unless($allocation->status === 'pending', 422);

            $run = $allocation->run;
            $dividendDeduction->delete();

            $this->recalculate($allocation->fresh());
            app(DividendRunsController::class)->refreshTotals($run);
        });

        return redirect()
            ->route('dividend_deductions.index')
            ->with('success', 'Deduction deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'dividend_allocation_id' => ['required', 'exists:dividend_allocations,id'],
            'deduction_type' => ['required', 'in:withholding_tax,fee,other'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'offset_account_id' => [
                'required_unless:deduction_type,withholding_tax',
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                        ->where('is_postable', true)
                        ->where('is_control_account', false);
                }),
            ],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function ledgerAccounts()
    {
        return ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();
    }

    private function assertOffsetAccountAllowed(array $data): void
    {
        if (!empty($data['offset_account_id'])) {
            app(JournalPostingService::class)
                ->assertManualAccountsAllowed([$data['offset_account_id']]);
        }
    }

    private function recalculate(DividendAllocation $allocation): void
    {
        $tax = (float) $allocation->deductions()
            ->where('deduction_type', 'withholding_tax')
            ->sum('amount');
        $other = (float) $allocation->deductions()
            ->where('deduction_type', '!=', 'withholding_tax')
            ->sum('amount');

        if (round($tax + $other, 2) > round((float) $allocation->gross_dividend, 2)) {
            throw ValidationException::withMessages([
                'amount' => 'Total deductions cannot exceed the gross dividend.',
            ]);
        }

        $allocation->update([
            'withholding_tax' => $tax,
            'other_deductions' => $other,
            'net_dividend' => round((float) $allocation->gross_dividend - $tax - $other, 2),
        ]);
    }
}
