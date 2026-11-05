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
 * Invoking guarantees: a defaulting loan is repaid from the guarantors held shares.
 *
 * The loan is KES 12,000 over 12 months at 12% flat with a 2% processing fee,
 * so fees of 240 are capitalised and interest is 1,468.80. Each installment
 * is 1,000 principal + 122.40 interest + 20 fees = 1,142.40.
 */
class LoanGuaranteeInvocationTest extends TestCase
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
        $this->database = 'guarantee_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.guarantee_posting_test' => $connection,
            'database.default' => 'guarantee_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('guarantee_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'guarantee_posting_test', '--force' => true]), Artisan::output());

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
            DB::purge('guarantee_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^guarantee_posting_test_[a-f0-9]{16}$/', $this->database)) {
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

    private $guarantor;
    private $guarantorAccountId;

    /** A guarantor with 50 units of 100 each, of which $held is held against the loan. */
    private function addGuarantor(float $held = 3000): void
    {
        $member = Member::create([
            'member_application_id' => 2, 'membership_number' => 'GUARANTOR',
            'first_name' => 'GUARANTOR', 'last_name' => 'Test', 'date_of_birth' => '1985-01-01',
            'national_id' => 'GUARANTOR', 'phone' => '0711111111', 'email' => 'guarantor@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => 1,
        ]);
        $productId = DB::table('share_products')->insertGetId([
            'code' => 'ORD', 'name' => 'Ordinary shares', 'share_capital_account_id' => $this->accounts['capital'],
            'share_premium_account_id' => $this->accounts['income'], 'unit_value' => 100, 'minimum_units' => 1,
            'maximum_units' => 100000, 'dividend_eligible' => 1, 'allows_transfer' => 1, 'allows_redemption' => 1,
            'can_secure_loan' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guarantorAccountId = DB::table('share_accounts')->insertGetId([
            'member_id' => $member->id, 'share_product_id' => $productId, 'account_number' => 'GUARANTOR',
            'opened_date' => '2020-01-01', 'total_units' => 50, 'share_balance' => 5000,
            'held_amount' => $held, 'available_amount' => 5000 - $held, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guarantor = \App\Models\LoanApplications\LoanGuarantor::create([
            'loan_application_id' => $this->loan->loan_application_id, 'member_id' => $member->id,
            'guarantor_name' => 'GUARANTOR Test', 'member_number' => 'GUARANTOR', 'national_id' => 'GUARANTOR',
            'shares_offered' => $held, 'status' => 'held', 'loan_id' => $this->loan->id, 'held_amount' => $held,
        ]);
        \App\Models\LoanApplications\LoanGuarantorHold::create([
            'loan_guarantor_id' => $this->guarantor->id, 'loan_id' => $this->loan->id,
            'share_account_id' => $this->guarantorAccountId, 'amount' => $held, 'status' => 'active',
        ]);
    }

    private function invoke(array $overrides = [])
    {
        return $this->postJson(route('loans.guarantee_invoke', [$this->loan, $this->guarantor]), array_replace([
            'invocation_date' => today()->toDateString(), 'invocation_reason' => 'Borrower in default',
        ], $overrides));
    }

    private function ageSchedule(): void
    {
        $this->disburse();
        $schedules = DB::table('loan_repayment_schedules')->where('loan_id', $this->loan->id)->orderBy('installment_number')->get();
        DB::table('loan_repayment_schedules')->where('id', $schedules[0]->id)->update(['due_date' => today()->subDays(30)->toDateString()]);
        DB::table('loan_repayment_schedules')->where('id', $schedules[1]->id)->update(['due_date' => today()->subDays(3)->toDateString()]);
        Artisan::call('loans:assess-penalties', ['--date' => today()->toDateString()]);
    }

    public function test_invoking_a_guarantee_redeems_shares_and_repays_the_loan_without_cash(): void
    {
        $this->ageSchedule();
        $this->addGuarantor(3000);
        $loan = $this->loan->fresh();
        $arrears = (float) $loan->arrears_amount;
        $this->assertGreaterThan(2300, $arrears);
        $cashBefore = $this->balance('cash');

        $this->invoke()->assertRedirect(route('loans.show', $this->loan));

        // 23 whole units of 100 are taken, penalty first.
        $repayment = LoanRepayment::where('payment_method', 'guarantee')->sole();
        $this->assertSame('confirmed', $repayment->status);
        $this->assertEquals(2300, (float) $repayment->amount_paid);
        $this->assertEquals(0, (float) $repayment->unallocated_amount);
        $this->assertEquals(2300, round((float) $repayment->principal_amount + $repayment->interest_amount + $repayment->fees_amount + $repayment->penalty_amount, 2));
        $this->assertGreaterThan(0, (float) $repayment->penalty_amount);

        $account = DB::table('share_accounts')->find($this->guarantorAccountId);
        $this->assertEquals(27, $account->total_units);
        $this->assertEquals(2700, (float) $account->share_balance);
        $this->assertEquals(700, (float) $account->held_amount);
        $this->assertEquals(2000, (float) $account->available_amount);

        $redemption = \App\Models\Shares\ShareTransaction::where('loan_guarantor_id', $this->guarantor->id)->sole();
        $this->assertSame('redemption', $redemption->transaction_type);
        $this->assertSame(23, (int) $redemption->units);
        $this->assertEquals(27, $redemption->running_units);

        $guarantor = $this->guarantor->fresh();
        $this->assertSame('held', $guarantor->status);
        $this->assertEquals(700, (float) $guarantor->held_amount);
        $this->assertEquals(2300, (float) $guarantor->invoked_amount);
        $this->assertEquals(700, (float) \App\Models\LoanApplications\LoanGuarantorHold::sole()->amount);

        // One balanced entry: shares debited (tagged to the guarantor), loan receivables credited (tagged to the loan). No cash.
        $entry = JournalEntry::where('event_type', 'guarantee_invocation')->sole();
        $this->assertSame('guarantee_invocation:' . $repayment->id . ':confirmed', $entry->posting_key);
        $this->assertEquals(2300, $this->balance('capital'));
        $this->assertEquals($cashBefore, $this->balance('cash'));
        $this->assertEquals(1, JournalEntry::where('event_type', 'guarantee_invocation')->count());
        $this->assertEquals(1, DB::table('journal_entry_lines')->where('journal_entry_id', $entry->id)->where('share_account_id', $this->guarantorAccountId)->where('member_id', $this->guarantor->member_id)->count());
        $this->assertTrue(DB::table('journal_entry_lines')->where('journal_entry_id', $entry->id)->where('credit', '>', 0)->get()->every(fn($line) => (int) $line->loan_id === $this->loan->id));
        $this->assertEquals(0, $this->balance('penaltyrec') - (float) $this->loan->fresh()->penalty_balance);
        $this->assertLedgerMatchesLoan('after invocation');

        // Neither side can be reversed from its own screen.
        $this->postJson(route('loan_repayments.reverse', $repayment), ['reversal_reason' => 'x'])->assertStatus(422)->assertJsonValidationErrors('repayment');
        $this->postJson(route('share_transactions.reverse', $redemption), ['description' => 'x'])->assertStatus(422)->assertJsonValidationErrors('transaction');
        $this->assertSame(1, \App\Models\Shares\ShareTransaction::where('transaction_type', 'redemption')->count());

        $this->get(route('loans.show', $this->loan))->assertOk()->assertSee('GUARANTOR Test');
    }

    public function test_the_whole_hold_is_used_up_and_the_guarantee_is_marked_invoked(): void
    {
        $this->ageSchedule();
        $this->addGuarantor(1000);

        $this->invoke(['amount' => 1000])->assertSessionHasNoErrors();
        $guarantor = $this->guarantor->fresh();
        $this->assertSame('invoked', $guarantor->status);
        $this->assertEquals(0, (float) $guarantor->held_amount);
        $this->assertSame('invoked', \App\Models\LoanApplications\LoanGuarantorHold::sole()->status);
        $account = DB::table('share_accounts')->find($this->guarantorAccountId);
        $this->assertEquals(40, $account->total_units);
        $this->assertEquals(0, (float) $account->held_amount);

        // Nothing is left to invoke.
        $this->invoke(['amount' => 100])->assertStatus(422)->assertJsonValidationErrors('guarantee');
        $this->assertLedgerMatchesLoan('after full invocation');
    }

    public function test_invalid_invocations_are_refused_and_change_nothing(): void
    {
        $this->ageSchedule();
        $this->addGuarantor(3000);
        $owed = (float) DB::table('loan_repayment_schedules')->where('loan_id', $this->loan->id)->sum('outstanding_amount');

        $this->invoke(['invocation_reason' => ''])->assertStatus(422)->assertJsonValidationErrors('invocation_reason');
        $this->invoke(['amount' => round($owed + 1, 2)])->assertStatus(422)->assertJsonValidationErrors('amount');
        // Less than one whole unit.
        $this->invoke(['amount' => 50])->assertStatus(422)->assertJsonValidationErrors('guarantee');

        // A closed period rolls the whole invocation back.
        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->invoke()->assertStatus(422)->assertJsonValidationErrors('ledger');
        DB::table('accounting_periods')->update(['status' => 'open']);

        $account = DB::table('share_accounts')->find($this->guarantorAccountId);
        $this->assertEquals(50, $account->total_units);
        $this->assertEquals(3000, (float) $account->held_amount);
        $this->assertSame('held', $this->guarantor->fresh()->status);
        $this->assertSame(0, LoanRepayment::where('payment_method', 'guarantee')->count());
        $this->assertSame(0, \App\Models\Shares\ShareTransaction::count());
        $this->assertSame(0, JournalEntry::where('event_type', 'guarantee_invocation')->count());

        // A released guarantee cannot be invoked, and neither can a written-off loan.
        $this->guarantor->update(['status' => 'released']);
        $this->invoke()->assertStatus(422)->assertJsonValidationErrors('guarantee');
        $this->guarantor->update(['status' => 'held']);
        $this->loan->update(['status' => 'written_off']);
        $this->invoke()->assertStatus(422)->assertJsonValidationErrors('guarantee');
    }

    public function test_a_larger_amount_can_be_called_when_the_guarantor_holds_enough(): void
    {
        $this->ageSchedule();
        $this->addGuarantor(30000);
        DB::table('share_accounts')->where('id', $this->guarantorAccountId)->update(['total_units' => 400, 'share_balance' => 40000, 'available_amount' => 10000]);

        $this->invoke(['amount' => 12000])->assertSessionHasNoErrors();
        $this->assertEquals(12000, (float) LoanRepayment::where('payment_method', 'guarantee')->sole()->amount_paid);
        $this->assertEquals(12000, $this->balance('capital'));
        $this->assertEquals(18000, (float) DB::table('loan_guarantor_holds')->sum('amount'));
        $this->assertLedgerMatchesLoan('after large invocation');
    }

    public function test_confirming_a_normal_repayment_still_works_through_the_shared_service(): void
    {
        $this->ageSchedule();
        $cash = $this->balance('cash');
        $this->pay(self::INSTALLMENT);
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_repayment')->count());
        $this->assertEquals(self::INSTALLMENT, round($this->balance('cash') - $cash, 2));
        $this->assertLedgerMatchesLoan('after repayment');
    }
}
