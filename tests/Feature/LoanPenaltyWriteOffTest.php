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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Loan penalty accrual, arrears tracking and write-off.
 *
 * The loan is KES 12,000 over 12 months at 12% flat with a 2% processing fee,
 * so fees of 240 are capitalised and interest is 1,468.80. Each installment
 * is 1,000 principal + 122.40 interest + 20 fees = 1,142.40.
 */
class LoanPenaltyWriteOffTest extends TestCase
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
        $this->database = 'penalty_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.penalty_posting_test' => $connection,
            'database.default' => 'penalty_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('penalty_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'penalty_posting_test', '--force' => true]), Artisan::output());

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
            DB::purge('penalty_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^penalty_posting_test_[a-f0-9]{16}$/', $this->database)) {
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

    /** The control accounts must agree with the loan's own balances. */
    private function assertLedgerMatchesLoan(string $when): void
    {
        $loan = $this->loan->fresh();
        $this->assertEquals((float) $loan->principal_balance, $this->balance('principal') + $this->balance('fees'), "$when: principal and fees receivable vs principal balance");
        $this->assertEquals((float) $loan->interest_balance, $this->balance('interest'), "$when: interest receivable vs interest balance");
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2), "$when: trial balance");
    }

    /** Make installment 1 30 days overdue and installment 2 3 days overdue (inside the 5 day grace period). */
    private function ageSchedule(): void
    {
        $this->disburse();
        $schedules = DB::table('loan_repayment_schedules')->where('loan_id', $this->loan->id)->orderBy('installment_number')->get();
        DB::table('loan_repayment_schedules')->where('id', $schedules[0]->id)->update(['due_date' => today()->subDays(30)->toDateString()]);
        DB::table('loan_repayment_schedules')->where('id', $schedules[1]->id)->update(['due_date' => today()->subDays(3)->toDateString()]);
    }

    private function assess(string $date = null): int
    {
        return Artisan::call('loans:assess-penalties', ['--date' => $date ?: today()->toDateString()]);
    }

    private function schedule(int $number)
    {
        return \App\Models\LoanApplications\LoanRepaymentSchedule::where('loan_id', $this->loan->id)->where('installment_number', $number)->firstOrFail();
    }

    public function test_overdue_installments_are_marked_penalised_once_and_accrued(): void
    {
        $this->ageSchedule();
        $first = $this->schedule(1);
        $base = round($first->principal_due + $first->interest_due + $first->fees_due, 2);
        $penalty = round($base * 0.05, 2);
        $this->assertGreaterThan(0, $penalty);

        $this->assertSame(0, $this->assess(), Artisan::output());

        // Installment 1 is past its grace period and is penalised; installment 2 is overdue but inside the grace period.
        $first = $this->schedule(1);
        $this->assertSame('overdue', $first->status);
        $this->assertEquals($penalty, (float) $first->penalty_due);
        $this->assertEquals(round($base + $penalty, 2), (float) $first->total_due);
        $this->assertEquals(round($base + $penalty, 2), (float) $first->outstanding_amount);
        $this->assertSame('overdue', $this->schedule(2)->status);
        $this->assertEquals(0, (float) $this->schedule(2)->penalty_due);

        $loan = $this->loan->fresh();
        $this->assertSame('in_arrears', $loan->status);
        $this->assertEquals($penalty, (float) $loan->penalty_balance);
        $this->assertEquals(30, $loan->days_in_arrears);
        $this->assertEquals(round((float) $this->schedule(1)->outstanding_amount + (float) $this->schedule(2)->outstanding_amount, 2), (float) $loan->arrears_amount);

        $entry = JournalEntry::where('event_type', 'loan_penalty')->sole();
        $this->assertSame('loan_penalty:' . $first->id . ':assessed', $entry->posting_key);
        $this->assertEquals($penalty, $this->balance('penaltyrec'));
        $this->assertEquals(-$penalty, $this->balance('penalty'));
        $this->assertEquals((float) $loan->penalty_balance, $this->balance('penaltyrec'));

        // Running it again changes nothing.
        $this->assertSame(0, $this->assess());
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_penalty')->count());
        $this->assertEquals($penalty, (float) $this->loan->fresh()->penalty_balance);

        // Four days later installment 2 is past its grace period too.
        $this->assertSame(0, $this->assess(today()->addDays(4)->toDateString()));
        $this->assertSame(2, JournalEntry::where('event_type', 'loan_penalty')->count());
        $this->assertEquals(round($penalty + 0.05 * round($this->schedule(2)->principal_due + $this->schedule(2)->interest_due + $this->schedule(2)->fees_due, 2), 2), (float) $this->loan->fresh()->penalty_balance);
        $this->assertEquals((float) $this->loan->fresh()->penalty_balance, $this->balance('penaltyrec'));
    }

    public function test_paying_a_penalty_clears_the_receivable_and_reversal_restores_it(): void
    {
        $this->ageSchedule();
        $this->assess();
        $penalty = (float) $this->schedule(1)->penalty_due;

        $repayment = $this->pay(round(self::INSTALLMENT + $penalty, 2));
        $this->assertEquals($penalty, (float) $repayment->penalty_amount);
        $this->assertEquals(0, $this->balance('penaltyrec'));
        // Penalty income was recognised when assessed, not when collected.
        $this->assertEquals(-$penalty, $this->balance('penalty'));
        $this->assertEquals(0, (float) $this->loan->fresh()->penalty_balance);
        $this->assertSame('paid', $this->schedule(1)->status);

        $this->post(route('loan_repayments.reverse', $repayment), ['reversal_reason' => 'Bounced'])->assertSessionHasNoErrors();
        $this->assertEquals($penalty, $this->balance('penaltyrec'));
        $this->assertEquals($penalty, (float) $this->loan->fresh()->penalty_balance);
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2));

        // Once the overdue installments are paid the loan is back in good standing.
        $this->pay(round(self::INSTALLMENT + $penalty, 2));
        $this->pay(self::INSTALLMENT);
        $this->assertSame(0, $this->assess());
        $loan = $this->loan->fresh();
        $this->assertSame('active', $loan->status);
        $this->assertEquals(0, (float) $loan->arrears_amount);
        $this->assertEquals(0, $loan->days_in_arrears);
    }

    public function test_penalty_that_cannot_be_posted_is_not_applied(): void
    {
        $this->ageSchedule();
        DB::table('accounting_periods')->update(['status' => 'closed']);

        $this->assertSame(1, $this->assess());
        $this->assertSame(0, JournalEntry::where('event_type', 'loan_penalty')->count());
        $this->assertEquals(0, (float) $this->schedule(1)->penalty_due);
        $this->assertSame('active', $this->loan->fresh()->status);
        $this->assertNull($this->schedule(1)->penalty_assessed_on);

        DB::table('accounting_periods')->update(['status' => 'open']);
        $this->assertSame(0, $this->assess());
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_penalty')->count());
    }

    public function test_penalty_rate_needs_a_receivable_account_and_accounts_are_validated(): void
    {
        $product = LoanProduct::firstOrFail();
        $payload = $product->only(['code', 'name', 'minimum_amount', 'maximum_amount', 'interest_rate', 'interest_method',
            'interest_frequency', 'processing_fee_percentage', 'loan_principal_account_id', 'interest_receivable_account_id',
            'fee_receivable_account_id', 'interest_income_account_id', 'penalty_income_account_id', 'processing_fee_income_account_id']);
        $payload += ['minimum_repayment_months' => 1, 'maximum_repayment_months' => 24];

        $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('Penalty Receivable Account')->assertSee('Loan Write-off Expense Account');

        $this->putJson(route('loan_products.update', $product), $payload + ['penalty_rate' => 5, 'penalty_receivable_account_id' => null])
            ->assertStatus(422)->assertJsonValidationErrors('penalty_receivable_account_id');
        $this->putJson(route('loan_products.update', $product), $payload + ['penalty_rate' => 5, 'penalty_receivable_account_id' => $this->accounts['income']])
            ->assertStatus(422)->assertJsonValidationErrors('penalty_receivable_account_id');
        $this->putJson(route('loan_products.update', $product), $payload + ['penalty_rate' => 0, 'write_off_expense_account_id' => $this->accounts['cash']])
            ->assertStatus(422)->assertJsonValidationErrors('write_off_expense_account_id');
    }

    public function test_write_off_clears_the_loan_in_the_ledger_and_closes_it(): void
    {
        $this->ageSchedule();
        $this->assess();
        $this->pay(self::INSTALLMENT);
        $penalty = (float) $this->schedule(1)->penalty_due;

        $principal = $this->balance('principal');
        $interest = $this->balance('interest');
        $fees = $this->balance('fees');
        $penaltyOwed = $this->balance('penaltyrec');
        $total = round($principal + $interest + $fees + $penaltyOwed, 2);
        $this->assertGreaterThan(0, $total);

        $this->postJson(route('loans.write_off', $this->loan), ['write_off_date' => today()->toDateString()])
            ->assertStatus(422)->assertJsonValidationErrors('write_off_reason');
        $this->post(route('loans.write_off', $this->loan), ['write_off_date' => today()->toDateString(), 'write_off_reason' => 'Member untraceable'])
            ->assertSessionHasNoErrors();

        $entry = JournalEntry::where('event_type', 'loan_write_off')->sole();
        $this->assertSame('loan_write_off:' . $this->loan->id, $entry->posting_key);
        $this->assertEquals($total, $this->balance('writeoff'));
        foreach (['principal', 'interest', 'fees', 'penaltyrec'] as $account) {
            $this->assertEquals(0, $this->balance($account), $account);
        }
        $this->assertSame(0, JournalEntry::all()->filter(fn($entry) => round($entry->lines()->sum('debit') - $entry->lines()->sum('credit'), 2) != 0)->count());

        $loan = $this->loan->fresh();
        $this->assertSame('written_off', $loan->status);
        $this->assertEquals(today()->toDateString(), \Carbon\Carbon::parse($loan->written_off_date)->toDateString());
        $this->assertEquals($principal, (float) $loan->written_off_principal);
        $this->assertEquals($interest, (float) $loan->written_off_interest);
        $this->assertEquals($fees, (float) $loan->written_off_fees);
        $this->assertEquals($penaltyOwed, (float) $loan->written_off_penalty);
        $this->assertEquals(0, (float) $loan->total_outstanding_balance);
        $this->assertEquals(0, (float) $loan->arrears_amount);
        $this->assertSame(0, DB::table('loan_repayment_schedules')->where('loan_id', $loan->id)->whereIn('status', ['pending', 'partially_paid', 'overdue'])->count());
        $this->get(route('loans.show', $loan))->assertOk()->assertSee('Written off on');

        // A written-off loan cannot be written off again or assessed for penalties.
        $this->postJson(route('loans.write_off', $loan), ['write_off_date' => today()->toDateString(), 'write_off_reason' => 'Again'])->assertStatus(422);
        $this->assertSame(0, $this->assess(today()->addDays(60)->toDateString()));
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_write_off')->count());
        $this->assertEquals($penalty, (float) $this->schedule(1)->penalty_due);
    }

    public function test_write_off_needs_an_expense_account_and_a_loan_with_something_owed(): void
    {
        // A loan that was never paid out has nothing in the ledger to write off.
        $this->postJson(route('loans.write_off', $this->loan), ['write_off_date' => today()->toDateString(), 'write_off_reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('write_off');
        $this->assertSame(0, JournalEntry::count());

        $this->ageSchedule();
        LoanProduct::query()->update(['write_off_expense_account_id' => null]);
        $this->postJson(route('loans.write_off', $this->loan), ['write_off_date' => today()->toDateString(), 'write_off_reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('active', $this->loan->fresh()->status);
        $this->assertSame(0, JournalEntry::where('event_type', 'loan_write_off')->count());

        // A future date is refused.
        $this->postJson(route('loans.write_off', $this->loan), ['write_off_date' => today()->addDay()->toDateString(), 'write_off_reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('write_off_date');
    }

    public function test_scheduler_is_registered(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn($event) => strpos($event->command, 'loans:assess-penalties') !== false));
    }
}
