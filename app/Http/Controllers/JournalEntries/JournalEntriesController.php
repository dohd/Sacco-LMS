<?php

namespace App\Http\Controllers\JournalEntries;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JournalEntriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $entries = JournalEntry::latest()->get();

        return view('journal_entries.index', compact('entries'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accountingPeriods = AccountingPeriod::where('status', 'open')
        ->orderBy('start_date')
        ->get();

        $accounts = ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        return view('journal_entries.create', compact('accountingPeriods', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'transaction_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $journalEntry = DB::transaction(function () use ($validated) {

                $period = AccountingPeriod::lockForUpdate()
                    ->findOrFail($validated['accounting_period_id']);

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'accounting_period_id' => 'The accounting period is not open.',
                    ]);
                }

                if ($validated['transaction_date'] < $period->start_date ||
                    $validated['transaction_date'] > $period->end_date) {
                    throw ValidationException::withMessages([
                        'transaction_date' => 'Transaction date must fall within the accounting period.',
                    ]);
                }

                $debit = 0;
                $credit = 0;

                foreach ($validated['lines'] as $index => $line) {
                    $lineDebit = (float) ($line['debit'] ?? 0);
                    $lineCredit = (float) ($line['credit'] ?? 0);

                    if ($lineDebit > 0 && $lineCredit > 0) {
                        throw ValidationException::withMessages([
                            "lines.$index.debit" => 'A journal line cannot contain both debit and credit.',
                        ]);
                    }

                    if ($lineDebit == 0 && $lineCredit == 0) {
                        throw ValidationException::withMessages([
                            "lines.$index.debit" => 'Each journal line must have a debit or credit amount.',
                        ]);
                    }

                    $debit += $lineDebit;
                    $credit += $lineCredit;
                }

                if (round($debit, 2) !== round($credit, 2) || $debit <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'The journal must be balanced and contain a valid amount.',
                    ]);
                }

                $entryNumber = $this->generateJournalEntryNumber();

                $journalEntry = JournalEntry::create([
                    'accounting_period_id' => $period->id,
                    'entry_number' => $entryNumber,
                    'posting_key' => 'manual:' . $entryNumber,
                    'transaction_date' => $validated['transaction_date'],
                    'posting_date' => $validated['posting_date'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'event_type' => 'manual',
                    'description' => $validated['description'],
                    'status' => 'draft',
                    'created_by' => Auth::id(),                    
                ]);

                foreach ($validated['lines'] as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'chart_of_account_id' => $line['account_id'],
                        'debit' => round((float) ($line['debit'] ?? 0), 2),
                        'credit' => round((float) ($line['credit'] ?? 0), 2),
                    ]);
                }

                return $journalEntry;
            });

            return redirect()
                ->route('journal_entries.show', $journalEntry->id)
                ->with('success', 'Journal entry created successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return errorHandler(
                'Error creating journal entry. Please try again.',
                $e
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load([
            'lines.account',
            'accountingPeriod',
            'creator',
            'poster',
            'reversalOf',
            'reversals',
        ]);

        return view('journal_entries.view', compact('journalEntry'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            throw ValidationException::withMessages([
                'journal' => 'Only draft journal entries can be edited.'
            ]);
        }

        $accountingPeriods = AccountingPeriod::where('status', 'open')
            ->orderBy('start_date')
            ->get();

        $accounts = ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        $journalEntry->load('lines');

        return view('journal_entries.edit', compact('journalEntry', 'accountingPeriods', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            throw ValidationException::withMessages([
                'journal' => 'Only draft journal entries can be edited.',
            ]);
        }

        $validated = $request->validate([
            'accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'transaction_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($journalEntry, $validated) {
                $journalEntry = JournalEntry::whereKey($journalEntry->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($journalEntry->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'journal' => 'Only draft journal entries can be edited.',
                    ]);
                }

                $period = AccountingPeriod::whereKey(
                    $validated['accounting_period_id']
                )->lockForUpdate()->firstOrFail();

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'accounting_period_id' => 'The accounting period is not open.',
                    ]);
                }

                if (
                    $validated['transaction_date'] < $period->start_date ||
                    $validated['transaction_date'] > $period->end_date
                ) {
                    throw ValidationException::withMessages([
                        'transaction_date' =>
                            'Transaction date must fall within the accounting period.',
                    ]);
                }

                $debit = 0;
                $credit = 0;

                foreach ($validated['lines'] as $index => $line) {
                    $lineDebit = (float) ($line['debit'] ?? 0);
                    $lineCredit = (float) ($line['credit'] ?? 0);

                    if ($lineDebit > 0 && $lineCredit > 0) {
                        throw ValidationException::withMessages([
                            "lines.$index.debit" =>
                                'A journal line cannot contain both debit and credit.',
                        ]);
                    }

                    if ($lineDebit == 0 && $lineCredit == 0) {
                        throw ValidationException::withMessages([
                            "lines.$index.debit" =>
                                'Each journal line must have a debit or credit amount.',
                        ]);
                    }

                    $debit += $lineDebit;
                    $credit += $lineCredit;

                    $account = ChartOfAccount::find($line['account_id']);

                    if (!$account || !$account->is_active) {
                        throw ValidationException::withMessages([
                            "lines.$index.account_id" =>
                                'The selected account is inactive or unavailable.',
                        ]);
                    }

                    if (!$account->is_postable) {
                        throw ValidationException::withMessages([
                            "lines.$index.account_id" =>
                                "Account {$account->code} is not postable.",
                        ]);
                    }
                }

                if (
                    $debit <= 0 ||
                    round($debit, 2) !== round($credit, 2)
                ) {
                    throw ValidationException::withMessages([
                        'lines' => 'The journal must be balanced and contain a valid amount.',
                    ]);
                }

                $journalEntry->update([
                    'accounting_period_id' => $period->id,
                    'transaction_date' => $validated['transaction_date'],
                    'posting_date' => $validated['posting_date'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'description' => $validated['description'],
                ]);

                $journalEntry->lines()->delete();

                foreach ($validated['lines'] as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'chart_of_account_id' => $line['account_id'],
                        'debit' => round((float) ($line['debit'] ?? 0), 2),
                        'credit' => round((float) ($line['credit'] ?? 0), 2),
                    ]);
                }
            });

            return redirect()
                ->route('journal_entries.show', $journalEntry->id)
                ->with('success', 'Journal entry updated successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return errorHandler(
                'Error updating journal entry. Please try again.',
                $e
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(JournalEntry $journalEntry)
    {
        try {
            if ($journalEntry->status !== 'draft') {
                throw ValidationException::withMessages([
                    'journal' => 'Only draft journals can be deleted.',
                ]);
            }

            DB::transaction(function () use ($journalEntry) {
                $journalEntry->lines()->delete();
                $journalEntry->delete();
            });

            return redirect()
                ->route('journal_entries.index')
                ->with('success', 'Draft journal deleted successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return errorHandler(
                'Error deleting journal entry. Please try again.',
                $e
            );
        }
    }

    // Posting journal entry
    public function post($id)
    {
        try {
            DB::transaction(function () use ($id) {

                $journalEntry = JournalEntry::whereKey($id)
                    ->lockForUpdate()
                    ->with('lines')
                    ->firstOrFail();

                if ($journalEntry->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'journal' => 'Only draft journals can be posted.',
                    ]);
                }

                $period = AccountingPeriod::whereKey(
                    $journalEntry->accounting_period_id
                )->lockForUpdate()->firstOrFail();

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'journal' => 'The accounting period is not open.',
                    ]);
                }

                if (
                    $journalEntry->transaction_date < $period->start_date ||
                    $journalEntry->transaction_date > $period->end_date
                ) {
                    throw ValidationException::withMessages([
                        'journal' => 'Transaction date falls outside the accounting period.',
                    ]);
                }

                if ($journalEntry->lines->count() < 2) {
                    throw ValidationException::withMessages([
                        'journal' => 'A journal must contain at least two lines.',
                    ]);
                }

                $debit = $journalEntry->lines->sum('debit');
                $credit = $journalEntry->lines->sum('credit');

                if (
                    $debit <= 0 ||
                    round($debit, 2) !== round($credit, 2)
                ) {
                    throw ValidationException::withMessages([
                        'journal' => 'The journal is not balanced.',
                    ]);
                }

                foreach ($journalEntry->lines as $line) {
                    $account = ChartOfAccount::find($line->chart_of_account_id);

                    if (!$account || !$account->is_active) {
                        throw ValidationException::withMessages([
                            'journal' => 'One or more accounts are inactive.',
                        ]);
                    }

                    if (!$account->is_postable) {
                        throw ValidationException::withMessages([
                            'journal' => "Account {$account->code} is not postable.",
                        ]);
                    }
                }

                $journalEntry->update([
                    'status' => 'posted',
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                ]);
            });

            return back()->with(
                'success',
                'Journal entry posted successfully.'
            );

        } catch (\Exception $e) {
            return errorHandler(
                'Error posting journal entry. Please try again.',
                $e
            );
        }
    }   

    // Reverse journal entry
    public function reverse(Request $request, $id)
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $reversal = DB::transaction(function () use ($id, $validated) {
                $original = JournalEntry::whereKey($id)
                    ->lockForUpdate()
                    ->with('lines')
                    ->firstOrFail();

                if ($original->status !== 'posted') {
                    throw ValidationException::withMessages([
                        'journal' => 'Only posted journals can be reversed.',
                    ]);
                }

                if ($original->reversal_of_id) {
                    throw ValidationException::withMessages([
                        'journal' => 'This journal is already a reversal.',
                    ]);
                }

                if ($original->reversals()->where('status', 'posted')->exists()) {
                    throw ValidationException::withMessages([
                        'journal' => 'This journal has already been reversed.',
                    ]);
                }

                $period = AccountingPeriod::whereKey(
                    $original->accounting_period_id
                )->lockForUpdate()->firstOrFail();

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'journal' => 'The accounting period is not open.',
                    ]);
                }

                $entryNumber = $this->generateJournalEntryNumber();

                $reversal = JournalEntry::create([
                    'accounting_period_id' => $period->id,
                    'entry_number' => $entryNumber,
                    'posting_key' => 'manual_reversal:' . $original->id,
                    'transaction_date' => now()->toDateString(),
                    'posting_date' => now()->toDateString(),
                    'reference_number' => $original->entry_number,
                    'event_type' => 'manual_reversal',
                    'description' => 'Reversal of ' . $original->entry_number,
                    'status' => 'posted',
                    'created_by' => Auth::id(),
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'reversal_of_id' => $original->id,
                    'reversal_reason' => $validated['reversal_reason'],
                ]);

                foreach ($original->lines as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $reversal->id,
                        'chart_of_account_id' => $line->chart_of_account_id,
                        'debit' => $line->credit,
                        'credit' => $line->debit,
                    ]);
                }

                $original->update([
                    'status' => 'reversed',
                ]);

                return $reversal;
            });

            return redirect()
                ->route('journal_entries.show', $reversal->id)
                ->with('success', 'Journal entry reversed successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return errorHandler(
                'Error reversing journal entry. Please try again.',
                $e
            );
        }
    }

    private function generateJournalEntryNumber()
    {
        do {
            $number = 'JE-' . now()->format('Y') . '-' .
                str_pad(
                    JournalEntry::whereYear('created_at', now()->year)->count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
        } while (JournalEntry::where('entry_number', $number)->exists());

        return $number;
    }
}
