<?php

namespace App\Services;

use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsProduct;
use App\Models\Savings\SavingsTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Calculates and credits savings interest for a closed interest period.
 *
 * Interest is rate / 100 * the sum of end-of-day balances / 365 (a daily
 * balance method). Simple interest ignores interest already credited to the
 * account; compound interest includes it. Each credit is a confirmed
 * `interest` transaction, posted to the ledger, and is keyed by period so a
 * period can never be credited twice.
 */
class SavingsInterestService
{
    private const MONTHS = ['monthly' => 1, 'quarterly' => 3, 'semi_annual' => 6, 'annual' => 12];

    /** @var JournalPostingService */
    private $postingService;

    public function __construct(JournalPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * The interest period that ends on $date for a frequency, or null when
     * $date is not a period end. Periods are aligned to calendar months.
     *
     * @return array|null [Carbon start, Carbon end]
     */
    public function periodEndingOn(string $frequency, Carbon $date): ?array
    {
        if (!isset(self::MONTHS[$frequency]) || !$date->isSameDay($date->copy()->endOfMonth())) {
            return null;
        }

        $months = self::MONTHS[$frequency];
        if (($date->month % $months) !== 0) {
            return null;
        }

        return [$date->copy()->subMonthsNoOverflow($months - 1)->startOfMonth(), $date->copy()->startOfDay()];
    }

    /**
     * Credit interest for every product whose interest period ends on $date.
     *
     * @return array{credited:int, skipped:int, failed:int, total:float}
     */
    public function run(Carbon $date, int $actorId): array
    {
        $result = ['credited' => 0, 'skipped' => 0, 'failed' => 0, 'total' => 0.0];

        $products = SavingsProduct::where('is_active', true)
            ->where('interest_rate', '>', 0)
            ->whereIn('interest_frequency', array_keys(self::MONTHS))
            ->get();

        foreach ($products as $product) {
            $period = $this->periodEndingOn($product->interest_frequency, $date);
            if (!$period) {
                continue;
            }

            SavingsAccount::where('savings_product_id', $product->id)
                ->where('status', 'active')
                ->orderBy('id')
                ->pluck('id')
                ->each(function ($accountId) use ($product, $period, $actorId, &$result) {
                    try {
                        $amount = $this->creditAccount($accountId, $product, $period[0], $period[1], $actorId);
                    } catch (\Throwable $e) {
                        // One bad account (for example a closed period) must not stop the rest.
                        $result['failed']++;
                        \Log::error('Savings interest failed for account ' . $accountId . ': ' . $e->getMessage());
                        return;
                    }
                    if ($amount === null) {
                        $result['skipped']++;
                    } else {
                        $result['credited']++;
                        $result['total'] = round($result['total'] + $amount, 2);
                    }
                });
        }

        return $result;
    }

    /**
     * Credit one account for one period. Returns the amount credited, or null
     * when nothing was credited (already done, or no interest earned).
     */
    public function creditAccount(int $accountId, SavingsProduct $product, Carbon $start, Carbon $end, int $actorId): ?float
    {
        $reference = 'interest:' . $accountId . ':' . $end->toDateString();

        return DB::transaction(function () use ($accountId, $product, $start, $end, $actorId, $reference) {
            $account = SavingsAccount::whereKey($accountId)->lockForUpdate()->first();
            if (!$account || $account->status !== 'active') {
                return null;
            }

            if (SavingsTransaction::where('savings_account_id', $account->id)
                ->where('transaction_type', 'interest')
                ->where('external_reference', $reference)
                ->exists()) {
                return null;
            }

            $amount = $this->calculate($account, $product, $start, $end);
            if ($amount <= 0) {
                return null;
            }

            $newBalance = round((float) $account->ledger_balance + $amount, 2);
            $transaction = SavingsTransaction::create([
                'savings_account_id' => $account->id,
                'transaction_number' => $this->transactionNumber(),
                'transaction_type' => 'interest',
                'direction' => 'credit',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'transaction_date' => $end->toDateString(),
                'value_date' => $end->toDateString(),
                'payment_method' => 'system',
                'external_reference' => $reference,
                'status' => 'confirmed',
                'recorded_by' => $actorId,
                'confirmed_by' => $actorId,
                'confirmed_at' => now(),
                'description' => 'Interest ' . $start->toDateString() . ' to ' . $end->toDateString()
                    . ' at ' . rtrim(rtrim(number_format((float) $product->interest_rate, 4), '0'), '.') . '% p.a.',
            ]);

            $account->update([
                'ledger_balance' => $newBalance,
                'available_balance' => round($newBalance - (float) $account->held_balance, 2),
                'last_transaction_date' => $end->toDateString(),
            ]);

            $this->postingService->postSavingsMovement($transaction);

            return $amount;
        });
    }

    /**
     * Interest earned on the account over the period, from end-of-day balances.
     */
    public function calculate(SavingsAccount $account, SavingsProduct $product, Carbon $start, Carbon $end): float
    {
        $query = SavingsTransaction::where('savings_account_id', $account->id)
            ->whereIn('status', ['confirmed', 'reversed'])
            ->whereDate('value_date', '<=', $end->toDateString());

        // Simple interest earns nothing on interest that was already credited.
        if ($product->interest_calculation_method === 'simple') {
            $query->where('transaction_type', '!=', 'interest');
            $query->where(function ($q) {
                $q->whereNull('reversal_of_id')->orWhereHas('reversalOf', function ($r) {
                    $r->where('transaction_type', '!=', 'interest');
                });
            });
        }

        $movements = [];
        $balance = 0.0;
        foreach ($query->get(['direction', 'amount', 'value_date']) as $row) {
            $signed = ($row->direction === 'credit' ? 1 : -1) * (float) $row->amount;
            $day = Carbon::parse($row->value_date)->toDateString();
            if ($day < $start->toDateString()) {
                $balance += $signed;
            } else {
                $movements[$day] = ($movements[$day] ?? 0) + $signed;
            }
        }

        $weighted = 0.0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $balance += $movements[$day->toDateString()] ?? 0;
            if ($balance > 0) {
                $weighted += $balance;
            }
        }

        return round($weighted * ((float) $product->interest_rate / 100) / 365, 2);
    }

    private function transactionNumber(): string
    {
        do {
            $number = 'SAV-TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (SavingsTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
