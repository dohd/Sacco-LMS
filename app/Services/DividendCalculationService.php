<?php

namespace App\Services;

use App\Models\Dividends\DividendAllocation;
use App\Models\Dividends\DividendRun;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DividendCalculationService
{
    public function calculate(DividendRun $run): void
    {
        DB::transaction(function () use ($run) {
            $run = DividendRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            if (!in_array($run->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages(['run' => 'Only draft or calculated runs can be calculated.']);
            }
            if ($run->allocations()->whereHas('deductions')->exists()) {
                throw ValidationException::withMessages(['run' => 'Remove existing deductions before recalculating this run.']);
            }

            $run->allocations()->delete();
            $accounts = ShareAccount::withoutGlobalScopes()
                ->with('shareProduct')
                ->whereHas('shareProduct', function ($query) {
                    $query->where('dividend_eligible', true)
                        ->where('is_active', true);
                })
                ->get();
            foreach ($accounts as $account) {
                $balance = $this->qualifyingBalance($account, $run);
                if ($balance <= 0) {
                    continue;
                }
                $gross = round($balance * (float) $run->dividend_rate / 100, 2);
                DividendAllocation::create([
                    'dividend_run_id' => $run->id,
                    'member_id' => $account->member_id,
                    'share_account_id' => $account->id,
                    'qualifying_share_balance' => $balance,
                    'dividend_rate' => $run->dividend_rate,
                    'gross_dividend' => $gross,
                    'withholding_tax' => 0,
                    'other_deductions' => 0,
                    'net_dividend' => $gross,
                    'status' => 'pending',
                ]);
            }
            app(\App\Http\Controllers\Dividends\DividendRunsController::class)->refreshTotals($run);
            $run->forceFill([
                'status' => 'calculated',
                'calculated_by' => auth()->id(),
            ])->save();
        });
    }

    private function qualifyingBalance(ShareAccount $account, DividendRun $run): float
    {
        $transactions = ShareTransaction::withoutGlobalScopes()
            ->where('share_account_id', $account->id)
            ->where('status', 'confirmed')
            ->whereDate('transaction_date', '<=', $run->period_end)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['transaction_date', 'direction', 'amount']);

        $balance = 0.0;
        $startBalance = 0.0;
        $dailyTotal = 0.0;
        $snapshotTotal = 0.0;
        $snapshotCount = 0;
        $minimum = null;
        $cursor = $run->period_start->copy();
        foreach ($transactions as $transaction) {
            $date = $transaction->transaction_date->toDateString();
            if ($date < $run->period_start->toDateString()) {
                $balance += $transaction->direction === 'credit' ? (float) $transaction->amount : -(float) $transaction->amount;
                continue;
            }
            if ($date > $cursor->toDateString()) {
                $days = $cursor->diffInDays($date);
                $dailyTotal += max(0, $balance) * $days;
                $minimum = $minimum === null
                    ? max(0, $balance)
                    : min($minimum, max(0, $balance));
                $cursor = $cursor->copy()->addDays($days);
            }
            $balance += $transaction->direction === 'credit'
                ? (float) $transaction->amount
                : -(float) $transaction->amount;
            $snapshotTotal += max(0, $balance);
            $snapshotCount++;
            $minimum = $minimum === null
                ? max(0, $balance)
                : min($minimum, max(0, $balance));
        }
        $startBalance = $balance;
        $end = $run->period_end->copy()->addDay();
        if ($cursor->lt($end)) {
            $dailyTotal += max(0, $balance) * $cursor->diffInDays($end);
        }
        $daysInPeriod = max(1, $run->period_start->diffInDays($end));
        $closing = max(0, $balance);
        $minimum = $minimum === null ? max(0, $startBalance) : $minimum;

        switch ($run->calculation_method) {
            case 'minimum_balance':
                $qualifying = $minimum;
                break;
            case 'average_balance':
                $qualifying = $snapshotCount ? $snapshotTotal / $snapshotCount : max(0, $startBalance);
                break;
            case 'weighted_average':
                $qualifying = $dailyTotal / $daysInPeriod;
                break;
            case 'closing_balance':
            default:
                $qualifying = $closing;
        }

        return round($qualifying, 2);
    }
}
