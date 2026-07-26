<?php

namespace App\Services;

use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneralLedgerService
{
    public function post(array $entryData, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($entryData, $lines) {
            $existing = JournalEntry::query()
                ->where('posting_key', $entryData['posting_key'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $totalDebit = round(
                collect($lines)->sum(
                    fn (array $line) => (float) ($line['debit'] ?? 0)
                ),
                2
            );

            $totalCredit = round(
                collect($lines)->sum(
                    fn (array $line) => (float) ($line['credit'] ?? 0)
                ),
                2
            );

            if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.01) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal entry debits and credits must balance.',
                ]);
            }

            foreach ($lines as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                if (
                    ($debit > 0 && $credit > 0) ||
                    ($debit <= 0 && $credit <= 0)
                ) {
                    throw ValidationException::withMessages([
                        'journal' =>
                            'Each journal line must contain either a debit or a credit.',
                    ]);
                }
            }

            $period = AccountingPeriod::query()
                ->whereDate('start_date', '<=', $entryData['posting_date'])
                ->whereDate('end_date', '>=', $entryData['posting_date'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($period->status !== 'open') {
                throw ValidationException::withMessages([
                    'posting_date' =>
                        'The selected accounting period is not open.',
                ]);
            }

            $entry = JournalEntry::create([
                'accounting_period_id' => $period->id,
                'entry_number' => $entryData['entry_number'],
                'posting_key' => $entryData['posting_key'],
                'transaction_date' => $entryData['transaction_date'],
                'posting_date' => $entryData['posting_date'],
                'reference_number' =>
                    $entryData['reference_number'] ?? null,
                'event_type' => $entryData['event_type'],
                'source_type' => $entryData['source_type'] ?? null,
                'source_id' => $entryData['source_id'] ?? null,
                'description' => $entryData['description'],
                'status' => 'posted',
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'chart_of_account_id' =>
                        $line['chart_of_account_id'],

                    'debit' =>
                        round((float) ($line['debit'] ?? 0), 2),

                    'credit' =>
                        round((float) ($line['credit'] ?? 0), 2),

                    'description' =>
                        $line['description'] ?? null,

                    'member_id' =>
                        $line['member_id'] ?? null,

                    'loan_id' =>
                        $line['loan_id'] ?? null,

                    'savings_account_id' =>
                        $line['savings_account_id'] ?? null,

                    'member_share_account_id' =>
                        $line['member_share_account_id'] ?? null,
                ]);
            }

            return $entry->load('lines');
        });
    }
}