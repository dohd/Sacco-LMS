<?php

namespace Tests\Feature;

use App\Models\Accounting\JournalEntry;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanDisbursement;
use App\Models\LoanApplications\LoanProduct;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\Memberships\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unearned loan interest: held at disbursement, released as installments fall due.
 *
 * The loan is KES 12,000 over 12 months at 12% flat with a 2% processing fee,
 * so fees of 240 are capitalised and interest is 1,468.80. Each installment
 * is 1,000 principal + 122.40 interest + 20 fees = 1,142.40.
 */
class LoanUnearnedInterestTest extends TestCase
{
    private const INSTALLMENT = 1142.4;

    private $database;
    private $originalConnection;
    private $member;
    private $loan;
    private $disbursement;
    private $accounts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'unearned_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.unearned_posting_test' => $connection,
            'database.default' => 'unearned_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('unearned_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'unearned_posting_test', '--force' => true]), Artisan::output());

        $userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        DB::table('model_has_roles')->insert(['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $userId]);
        $this->actingAs(User::findOrFail($userId));

        foreach ([
            'cash' => ['1000', 'Cash on hand', 'asset', 'debit'],
            'bank' => ['1010', 'Bank', 'asset', 'debit'],
            'principal' => ['1200', 'Loan principal', 'asset', 'debit'],
            'interest' => ['1210', 'Interest receivable', 'asset', 'debit'],
            'fees' => ['1220', 'Fee receivable', 'asset', 'debit'],
            'penalty' => ['4200', 'Penalty income', 'income', 'credit'],
            'penaltyrec' => ['1230', 'Penalty receivable', 'asset', 'debit'],
            'writeoff' => ['5100', 'Loan write-off expense', 'expense', 'debit'],
            'income' => ['4000', 'Interest and fee income', 'income', 'credit'],
            'savings' => ['2101', 'Individual savings register', 'liability', 'credit'],
            'capital' => ['3000', 'Share capital', 'equity', 'credit'],
            'unearned' => ['2206', 'Unearned interest', 'liability', 'credit'],
        ] as $key => [$code, $name, $type, $balance]) {
            $this->accounts[$key] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => $name, 'account_type' => $type, 'normal_balance' => $balance,
                'is_control_account' => false, 'config_key' => $key === 'savings' ? 'individual_savings_register' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('accounting_periods')->insert([
            'name' => 'Current year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->member = Member::create([
            'member_application_id' => 1, 'membership_number' => 'BORROWER',
            'first_name' => 'BORROWER', 'last_name' => 'Test', 'date_of_birth' => '1990-01-01',
            'national_id' => 'BORROWER', 'phone' => '0700000000', 'email' => 'borrower@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => $userId,
        ]);
        DB::table('share_accounts')->insert([
            'member_id' => $this->member->id, 'share_product_id' => 1, 'account_number' => 'BORROWER',
            'opened_date' => '2020-01-01', 'share_balance' => 50000, 'available_amount' => 50000,
        ]);
        $product = LoanProduct::create([
            'code' => 'TEST', 'name' => 'Test Loan', 'minimum_amount' => 1000,
            'maximum_amount' => 100000, 'maximum_repayment_months' => 24,
            'interest_rate' => 12, 'interest_method' => 'flat_rate', 'interest_frequency' => 'annual',
            'requires_guarantors' => false, 'processing_fee_percentage' => 2,
            'loan_principal_account_id' => $this->accounts['principal'],
            'interest_receivable_account_id' => $this->accounts['interest'],
            'fee_receivable_account_id' => $this->accounts['fees'],
            'interest_income_account_id' => $this->accounts['income'],
            'penalty_income_account_id' => $this->accounts['penalty'],
            'penalty_rate' => 5, 'grace_period_days' => 5,
            'unearned_interest_account_id' => $this->accounts['unearned'],
            'penalty_receivable_account_id' => $this->accounts['penaltyrec'],
            'write_off_expense_account_id' => $this->accounts['writeoff'],
            'processing_fee_income_account_id' => $this->accounts['income'],
        ]);

        // Submit and approve a KES 12,000 / 12 month loan through the real workflow.
        $this->post(route('loan_applications.store'), [
            'submission_action' => 'submit', 'member_id' => $this->member->id,
            'loan_product_id' => $product->id, 'amount_requested' => 12000,
            'repayment_period_months' => 12, 'payment_mode' => 'cash', 'loan_purpose' => 'School fees',
        ])->assertSessionHasNoErrors();
        $application = LoanApplication::firstOrFail();
        $this->patch(route('loan_applications.approve', $application), ['approved_amount' => 12000, 'repayment_period_months' => 12])->assertSessionHasNoErrors();
        $this->loan = Loan::firstOrFail();
        $this->disbursement = LoanDisbursement::where('loan_id', $this->loan->id)->firstOrFail();
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('unearned_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^unearned_posting_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    /** Record the payment details and pay the loan out of the cash account. */
    private function disburse(): void
    {
        $this->patch(route('loan_disbursements.update', $this->disbursement), [
            'disbursement_method' => 'cash', 'disbursement_account_id' => $this->accounts['cash'],
            'disbursement_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('loan_disbursements.process', $this->disbursement))->assertSessionHasNoErrors();
        $this->assertSame('processed', $this->disbursement->fresh()->status);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'loan_id' => $this->loan->id, 'member_id' => $this->member->id,
            'payment_date' => today()->toDateString(), 'amount_paid' => self::INSTALLMENT,
            'payment_method' => 'cash', 'deposit_account_id' => $this->accounts['cash'],
        ], $overrides);
    }

    /** Create and confirm a repayment. */
    private function pay(float $amount = self::INSTALLMENT, array $overrides = []): LoanRepayment
    {
        $this->post(route('loan_repayments.store'), $this->payload(['amount_paid' => $amount] + $overrides))->assertSessionHasNoErrors();
        $repayment = LoanRepayment::latest('id')->firstOrFail();
        $this->post(route('loan_repayments.confirm', $repayment))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $repayment->fresh()->status);
        return $repayment->fresh();
    }

    /** Net debit balance of a ledger account. */
    private function balance(string $account): float
    {
        return round((float) DB::table('journal_entry_lines')->where('chart_of_account_id', $this->accounts[$account])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance'), 2);
    }

    private function releaseInterest(string $date = null): int
    {
        return Artisan::call('loans:release-interest', ['--date' => $date ?: today()->toDateString()]);
    }

    private function schedule(int $number)
    {
        return \App\Models\LoanApplications\LoanRepaymentSchedule::where('loan_id', $this->loan->id)->where('installment_number', $number)->firstOrFail();
    }

    /** Installment 1 fell due 30 days ago and installment 2 three days ago. */
    private function ageSchedule(): void
    {
        $schedules = DB::table('loan_repayment_schedules')->where('loan_id', $this->loan->id)->orderBy('installment_number')->get();
        DB::table('loan_repayment_schedules')->where('id', $schedules[0]->id)->update(['due_date' => today()->subDays(30)->toDateString()]);
        DB::table('loan_repayment_schedules')->where('id', $schedules[1]->id)->update(['due_date' => today()->subDays(3)->toDateString()]);
    }

    private function assertLedgerMatchesLoan(string $when): void
    {
        $loan = $this->loan->fresh();
        $this->assertEquals((float) $loan->interest_balance, $this->balance('interest'), "$when: interest receivable vs interest balance");
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2), "$when: trial balance");
        // Unearned interest is exactly the interest of the installments not yet released.
        $unreleased = round((float) DB::table('loan_repayment_schedules')->where('loan_id', $loan->id)->whereNull('interest_released_on')->where('status', '!=', 'waived')->sum('interest_due'), 2);
        if ($loan->interest_deferred && $loan->status !== 'closed') {
            $this->assertEquals($unreleased, -$this->balance('unearned'), "$when: unearned interest vs unreleased installments");
        }
    }

    public function test_disbursement_holds_interest_as_unearned_and_flags_the_loan(): void
    {
        $this->disburse();

        // 240 of fees are income now; the 1,468.80 of interest is not.
        $this->assertEquals(-240, $this->balance('income'));
        $this->assertEquals(-1468.8, $this->balance('unearned'));
        $this->assertEquals(1468.8, $this->balance('interest'));
        $this->assertSame(1, (int) $this->loan->fresh()->interest_deferred);
        $this->assertLedgerMatchesLoan('after disbursement');
    }

    public function test_interest_is_released_on_the_due_date_whether_or_not_it_was_paid(): void
    {
        DB::table('accounting_periods')->update(['end_date' => today()->addYears(2)->toDateString()]);
        $this->disburse();
        $this->ageSchedule();
        $this->pay(self::INSTALLMENT);

        $this->assertSame(0, $this->releaseInterest(), Artisan::output());

        // Installments 1 (paid) and 2 (unpaid) are due; the other ten are not.
        $this->assertEquals(round(-240 - 244.8, 2), $this->balance('income'));
        $this->assertEquals(-1224, $this->balance('unearned'));
        $this->assertSame(2, JournalEntry::where('event_type', 'loan_interest_release')->count());
        $entry = JournalEntry::where('posting_key', 'loan_interest:' . $this->schedule(1)->id . ':released')->sole();
        $this->assertEquals(today()->subDays(30)->toDateString(), $entry->transaction_date->toDateString());
        $this->assertEquals(today()->subDays(30)->toDateString(), Carbon::parse($this->schedule(1)->interest_released_on)->toDateString());
        $this->assertNull($this->schedule(3)->interest_released_on);
        $this->assertLedgerMatchesLoan('after release');

        // Running again releases nothing more.
        $this->assertSame(0, $this->releaseInterest());
        $this->assertSame(2, JournalEntry::where('event_type', 'loan_interest_release')->count());

        // Installment 3 falls due later and is released then.
        $this->assertSame(0, $this->releaseInterest(Carbon::parse($this->schedule(3)->due_date)->toDateString()));
        $this->assertSame(3, JournalEntry::where('event_type', 'loan_interest_release')->count());
        $this->assertEquals(round(-240 - 367.2, 2), $this->balance('income'));
        $this->assertLedgerMatchesLoan('after third release');
    }

    public function test_a_period_that_is_closed_does_not_block_the_release(): void
    {
        $this->disburse();
        $this->ageSchedule();
        // The period of the due date is gone, so the release is dated on the run date instead.
        DB::table('accounting_periods')->update(['start_date' => today()->subDays(10)->toDateString()]);

        $this->assertSame(0, $this->releaseInterest());
        $this->assertEquals(today()->toDateString(), JournalEntry::where('posting_key', 'loan_interest:' . $this->schedule(1)->id . ':released')->sole()->transaction_date->toDateString());
        $this->assertEquals(today()->subDays(3)->toDateString(), JournalEntry::where('posting_key', 'loan_interest:' . $this->schedule(2)->id . ':released')->sole()->transaction_date->toDateString());

        // With no open period at all the failure is reported and nothing is released.
        DB::table('accounting_periods')->update(['status' => 'closed']);
        DB::table('loan_repayment_schedules')->update(['interest_released_on' => null]);
        DB::table('journal_entries')->where('event_type', 'loan_interest_release')->delete();
        $this->assertSame(1, $this->releaseInterest());
        $this->assertNull($this->schedule(1)->interest_released_on);
    }

    public function test_reversing_the_disbursement_returns_released_and_unearned_interest(): void
    {
        $this->disburse();
        $this->ageSchedule();
        $this->releaseInterest();
        $this->assertSame(2, JournalEntry::where('event_type', 'loan_interest_release')->count());

        $this->post(route('loan_disbursements.reverse', $this->disbursement), ['reversal_reason' => 'Paid in error'])->assertSessionHasNoErrors();

        foreach (['income', 'unearned', 'interest', 'principal', 'fees', 'cash'] as $account) {
            $this->assertEquals(0, $this->balance($account), $account);
        }
        $this->assertSame(0, DB::table('loan_repayment_schedules')->where('loan_id', $this->loan->id)->whereNotNull('interest_released_on')->count());
        $this->assertSame(0, JournalEntry::where('event_type', 'loan_interest_release')->whereDoesntHave('lines', fn($q) => $q)->count());
    }

    public function test_write_off_clears_unearned_interest_against_the_receivable_not_expense(): void
    {
        $this->disburse();
        $this->ageSchedule();
        $this->pay(self::INSTALLMENT);
        $this->releaseInterest();

        $principal = $this->balance('principal');
        $fees = $this->balance('fees');
        $interestOwed = $this->balance('interest');
        $unearned = -$this->balance('unearned');
        $this->assertEquals(1224, $unearned);
        $earnedUnpaid = round($interestOwed - $unearned, 2);
        $this->assertGreaterThan(0, $earnedUnpaid);

        $this->post(route('loans.write_off', $this->loan), ['write_off_date' => today()->toDateString(), 'write_off_reason' => 'Untraceable'])
            ->assertSessionHasNoErrors();

        // Expense is only what was owed and earned: principal, fees and the earned interest not paid.
        $this->assertEquals(round($principal + $fees + $earnedUnpaid, 2), $this->balance('writeoff'));
        foreach (['principal', 'interest', 'fees', 'unearned'] as $account) {
            $this->assertEquals(0, $this->balance($account), $account);
        }
        $loan = $this->loan->fresh();
        $this->assertEquals($earnedUnpaid, (float) $loan->written_off_interest);
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2));

        // Written-off loans take no further releases.
        $this->assertSame(0, $this->releaseInterest(today()->addYear()->toDateString()));
        $this->assertSame(2, JournalEntry::where('event_type', 'loan_interest_release')->count());
    }

    public function test_existing_loans_are_reclassified_on_request_and_only_once(): void
    {
        DB::table('accounting_periods')->update(['end_date' => today()->addYears(2)->toDateString()]);
        // A loan booked the old way: all interest to income.
        LoanProduct::query()->update(['unearned_interest_account_id' => null]);
        $this->disburse();
        $this->ageSchedule();
        $this->assertSame(0, (int) $this->loan->fresh()->interest_deferred);
        $this->assertEquals(round(-240 - 1468.8, 2), $this->balance('income'));
        LoanProduct::query()->update(['unearned_interest_account_id' => $this->accounts['unearned']]);

        // Nothing happens until the loan is reclassified.
        $this->assertSame(0, $this->releaseInterest());
        $this->assertSame(0, JournalEntry::where('event_type', 'loan_interest_release')->count());

        $this->assertSame(0, Artisan::call('loans:reclassify-interest', ['--dry-run' => true]));
        $this->assertSame(0, JournalEntry::where('event_type', 'loan_interest_reclassification')->count());

        // Ten installments (3 to 12) are not yet due: 10 x 122.40.
        $this->assertSame(0, Artisan::call('loans:reclassify-interest'), Artisan::output());
        $this->assertEquals(round(-240 - 1468.8 + 1224, 2), $this->balance('income'));
        $this->assertEquals(-1224, $this->balance('unearned'));
        $this->assertSame(1, (int) $this->loan->fresh()->interest_deferred);
        $this->assertNotNull($this->schedule(1)->interest_released_on);
        $this->assertNull($this->schedule(3)->interest_released_on);
        $this->assertLedgerMatchesLoan('after reclassification');

        // Once only.
        $this->assertSame(0, Artisan::call('loans:reclassify-interest'));
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_interest_reclassification')->count());

        // From now on installments are released as they fall due.
        $this->assertSame(0, $this->releaseInterest(Carbon::parse($this->schedule(3)->due_date)->toDateString()));
        $this->assertEquals(round(-240 - 1468.8 + 1224 - 122.4, 2), $this->balance('income'));
        $this->assertLedgerMatchesLoan('after release of an old loan');

        // Reversing the disbursement undoes the reclassification and the release too.
        $this->post(route('loan_disbursements.reverse', $this->disbursement), ['reversal_reason' => 'Error'])->assertSessionHasNoErrors();
        foreach (['income', 'unearned', 'interest', 'principal', 'fees', 'cash'] as $account) {
            $this->assertEquals(0, $this->balance($account), $account);
        }
    }

    public function test_the_unearned_account_must_be_a_postable_liability_and_is_closed_to_manual_journals(): void
    {
        $product = LoanProduct::firstOrFail();
        $payload = $product->only(['code', 'name', 'minimum_amount', 'maximum_amount', 'interest_rate', 'interest_method',
            'interest_frequency', 'processing_fee_percentage', 'loan_principal_account_id', 'interest_receivable_account_id',
            'fee_receivable_account_id', 'interest_income_account_id', 'penalty_income_account_id', 'processing_fee_income_account_id',
            'penalty_receivable_account_id']);
        $payload += ['minimum_repayment_months' => 1, 'maximum_repayment_months' => 24];

        $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('Unearned Interest Account');
        $this->putJson(route('loan_products.update', $product), $payload + ['unearned_interest_account_id' => $this->accounts['cash']])
            ->assertStatus(422)->assertJsonValidationErrors('unearned_interest_account_id');
        $this->putJson(route('loan_products.update', $product), $payload + ['unearned_interest_account_id' => $this->accounts['unearned']])->assertSessionHasNoErrors();
        $this->assertSame($this->accounts['unearned'], (int) $product->fresh()->unearned_interest_account_id);

        $this->postJson(route('journal_entries.store'), [
            'accounting_period_id' => DB::table('accounting_periods')->value('id'), 'transaction_date' => today()->toDateString(),
            'posting_date' => today()->toDateString(), 'description' => 'Manual',
            'lines' => [
                ['account_id' => $this->accounts['cash'], 'debit' => 10, 'credit' => 0],
                ['account_id' => $this->accounts['unearned'], 'debit' => 0, 'credit' => 10],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('lines');
    }

    public function test_scheduler_is_registered(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn($event) => strpos($event->command, 'loans:release-interest') !== false));
    }
}
