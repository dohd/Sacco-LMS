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
 * Loan ledger postings: disbursement, repayment and their reversals.
 *
 * The loan is KES 12,000 over 12 months at 12% flat with a 2% processing fee,
 * so fees of 240 are capitalised and interest is 1,468.80. Each installment
 * is 1,000 principal + 122.40 interest + 20 fees = 1,142.40.
 */
class LoanRepaymentPostingTest extends TestCase
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
        $this->database = 'repayment_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.repayment_posting_test' => $connection,
            'database.default' => 'repayment_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('repayment_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'repayment_posting_test', '--force' => true]), Artisan::output());

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
            DB::purge('repayment_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^repayment_posting_test_[a-f0-9]{16}$/', $this->database)) {
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

    public function test_ledger_account_is_required_validated_and_saved_on_the_repayment(): void
    {
        $this->disburse();

        // The form offers postable asset accounts that are not control accounts.
        $this->get(route('loan_repayments.create'))->assertOk()->assertSee('Cash on hand')->assertSee('Bank')->assertDontSee('Share capital');

        $this->postJson(route('loan_repayments.store'), $this->payload(['deposit_account_id' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->postJson(route('loan_repayments.store'), $this->payload(['deposit_account_id' => $this->accounts['capital']]))
            ->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->postJson(route('loan_repayments.store'), $this->payload(['deposit_account_id' => $this->accounts['savings']]))
            ->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->assertSame(0, LoanRepayment::count());

        $this->post(route('loan_repayments.store'), $this->payload(['deposit_account_id' => $this->accounts['bank'], 'payment_method' => 'bank_transfer']))
            ->assertSessionHasNoErrors();
        $repayment = LoanRepayment::firstOrFail();
        $this->assertEquals($this->accounts['bank'], $repayment->deposit_account_id);
        $this->get(route('loan_repayments.show', $repayment))->assertOk()->assertSee('1010 - Bank');

        // The edit page works and the account can be changed while pending.
        $this->get(route('loan_repayments.edit', $repayment))->assertOk();
        $this->patch(route('loan_repayments.update', $repayment), $this->payload(['deposit_account_id' => $this->accounts['cash']]))->assertSessionHasNoErrors();
        $this->assertEquals($this->accounts['cash'], $repayment->fresh()->deposit_account_id);

        // Account credit needs no cash or bank account and ignores any that is sent.
        $this->post(route('loan_repayments.store'), $this->payload(['payment_method' => 'account_credit']))->assertSessionHasNoErrors();
        $this->assertNull(LoanRepayment::latest('id')->first()->deposit_account_id);
    }

    public function test_disbursement_needs_a_ledger_account_and_posts_principal_interest_and_fees(): void
    {
        $update = fn(array $data) => $this->patchJson(route('loan_disbursements.update', $this->disbursement), $data + [
            'disbursement_method' => 'cash', 'disbursement_date' => today()->toDateString()]);

        // The edit form lists only usable cash and bank style accounts.
        $this->get(route('loan_disbursements.edit', $this->disbursement))->assertOk()->assertSee('Cash on hand')->assertDontSee('Share capital');
        $update([])->assertStatus(422)->assertJsonValidationErrors('disbursement_account_id');
        $update(['disbursement_account_id' => $this->accounts['capital']])->assertStatus(422)->assertJsonValidationErrors('disbursement_account_id');

        // No open period: processing fails as a whole and the loan stays undisbursed.
        $update(['disbursement_account_id' => $this->accounts['cash']])->assertRedirect();
        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->postJson(route('loan_disbursements.process', $this->disbursement))->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('approved', $this->disbursement->fresh()->status);
        $this->assertEquals(0, $this->loan->fresh()->amount_disbursed);
        $this->assertSame(0, JournalEntry::count());

        DB::table('accounting_periods')->update(['status' => 'open']);
        $this->post(route('loan_disbursements.process', $this->disbursement))->assertSessionHasNoErrors();
        $this->get(route('loan_disbursements.show', $this->disbursement))->assertOk()->assertSee('1000 - Cash on hand');

        $entry = JournalEntry::sole();
        $this->assertSame('loan_disbursement', $entry->event_type);
        $this->assertSame('loan_disbursement:' . $this->disbursement->id . ':processed', $entry->posting_key);
        $this->assertSame(LoanDisbursement::class, $entry->source_type);
        $this->assertEquals(12000, $this->balance('principal'));
        $this->assertEquals(240, $this->balance('fees'));
        $this->assertEquals(1468.8, $this->balance('interest'));
        $this->assertEquals(-12000, $this->balance('cash'));
        $this->assertEquals(-1708.8, $this->balance('income'));
        $this->assertSame(6, $entry->lines()->where('member_id', $this->member->id)->where('loan_id', $this->loan->id)->count());
        $this->assertLedgerMatchesLoan('after disbursement');

        // Processing again is refused and nothing is posted twice.
        $this->postJson(route('loan_disbursements.process', $this->disbursement))->assertStatus(422);
        $this->assertSame(1, JournalEntry::count());

        // Reversing the disbursement reverses the entry.
        $this->post(route('loan_disbursements.reverse', $this->disbursement), ['reversal_reason' => 'Paid in error'])->assertSessionHasNoErrors();
        $this->assertSame('reversed', $entry->fresh()->status);
        $this->assertSame('posted', JournalEntry::where('reversal_of_id', $entry->id)->sole()->status);
        foreach (['principal', 'fees', 'interest', 'cash', 'income'] as $account) {
            $this->assertEquals(0, $this->balance($account), "$account nets to zero");
        }
    }

    public function test_account_credit_disbursement_pays_out_of_savings_control(): void
    {
        $this->patch(route('loan_disbursements.update', $this->disbursement), [
            'disbursement_method' => 'account_credit', 'disbursement_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('loan_disbursements.process', $this->disbursement))->assertSessionHasNoErrors();
        $this->assertEquals(-12000, $this->balance('savings'));
        $this->assertEquals(0, $this->balance('cash'));
    }

    public function test_confirmation_posts_to_the_chosen_account_and_reversal_undoes_it(): void
    {
        $this->disburse();
        $this->post(route('loan_repayments.store'), $this->payload())->assertSessionHasNoErrors();
        $repayment = LoanRepayment::firstOrFail();
        $this->assertSame(1, JournalEntry::count()); // the disbursement only

        $this->post(route('loan_repayments.confirm', $repayment))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $repayment->fresh()->status);
        $entry = JournalEntry::where('event_type', 'loan_repayment')->sole();
        $this->assertSame('posted', $entry->status);
        $this->assertSame('loan_repayment:' . $repayment->id . ':confirmed', $entry->posting_key);
        $this->assertSame(LoanRepayment::class, $entry->source_type);
        $this->assertEquals($repayment->id, $entry->source_id);
        $this->assertEquals(self::INSTALLMENT, $entry->lines()->sum('debit'));
        $this->assertEquals(round(-12000 + self::INSTALLMENT, 2), $this->balance("cash"));
        $this->assertEquals(12000 - 1000, $this->balance('principal'));
        $this->assertEquals(round(1468.8 - 122.4, 2), $this->balance('interest'));
        $this->assertEquals(240 - 20, $this->balance('fees'));
        $this->assertSame(4, $entry->lines()->where('member_id', $this->member->id)->where('loan_id', $this->loan->id)->count());
        $this->assertLedgerMatchesLoan('after repayment');

        // A second confirmation is refused and nothing is posted twice.
        $this->postJson(route('loan_repayments.confirm', $repayment))->assertStatus(422);
        $this->assertSame(1, JournalEntry::where('event_type', 'loan_repayment')->count());

        $this->post(route('loan_repayments.reverse', $repayment), ['reversal_reason' => 'Posted in error'])->assertSessionHasNoErrors();
        $this->assertSame('reversed', $repayment->fresh()->status);
        $this->assertSame('reversed', $entry->fresh()->status);
        $reversal = JournalEntry::where('reversal_of_id', $entry->id)->sole();
        $this->assertSame('posted', $reversal->status);
        $this->assertSame('Posted in error', $reversal->reversal_reason);
        $this->assertEquals(-12000, $this->balance('cash'));
        $this->assertEquals(12000, $this->balance('principal'));
        $this->assertLedgerMatchesLoan('after reversal');
    }

    public function test_account_credit_repayment_posts_to_savings_control(): void
    {
        $this->disburse();
        $this->pay(self::INSTALLMENT, ['payment_method' => 'account_credit', 'deposit_account_id' => null]);
        $this->assertEquals(self::INSTALLMENT, $this->balance('savings'));
        $this->assertEquals(-12000, $this->balance('cash'));
    }

    public function test_confirmation_is_rolled_back_when_it_cannot_be_posted(): void
    {
        $this->disburse();
        $this->post(route('loan_repayments.store'), $this->payload())->assertSessionHasNoErrors();
        $repayment = LoanRepayment::firstOrFail();
        $before = (float) $this->loan->fresh()->total_outstanding_balance;
        $entries = JournalEntry::count();

        // No open accounting period: the repayment must stay pending and the loan untouched.
        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->postJson(route('loan_repayments.confirm', $repayment))->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('pending', $repayment->fresh()->status);
        $this->assertEquals($before, $this->loan->fresh()->total_outstanding_balance);
        $this->assertSame($entries, JournalEntry::count());
        $this->assertSame(0, DB::table('loan_repayment_allocations')->count());

        // Inactive ledger account: same outcome.
        DB::table('accounting_periods')->update(['status' => 'open']);
        DB::table('chart_of_accounts')->where('id', $this->accounts['cash'])->update(['is_active' => false]);
        $this->postJson(route('loan_repayments.confirm', $repayment))->assertStatus(422);
        $this->assertSame('pending', $repayment->fresh()->status);

        DB::table('chart_of_accounts')->where('id', $this->accounts['cash'])->update(['is_active' => true]);
        $this->post(route('loan_repayments.confirm', $repayment))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $repayment->fresh()->status);
        $this->assertSame($entries + 1, JournalEntry::count());
    }

    public function test_full_repayment_with_a_reversal_reconciles_ledger_and_loan(): void
    {
        $this->disburse();
        $this->assertLedgerMatchesLoan('after disbursement');

        for ($installment = 1; $installment <= 12; $installment++) {
            $method = $installment % 2 ? ['payment_method' => 'cash'] : ['payment_method' => 'bank_transfer', 'deposit_account_id' => $this->accounts['bank']];
            if ($installment === 5) { // a partial payment followed by the balance
                $this->pay(600, $method);
                $this->pay(round(self::INSTALLMENT - 600, 2), $method);
                continue;
            }
            $repayment = $this->pay(self::INSTALLMENT, $method);
            if ($installment === 3) { // reversed, then paid again
                $this->post(route('loan_repayments.reverse', $repayment), ['reversal_reason' => 'Wrong receipt'])->assertSessionHasNoErrors();
                $this->assertSame('reversed', $repayment->fresh()->status);
                $this->assertLedgerMatchesLoan('after reversing installment 3');
                $this->pay(self::INSTALLMENT, $method);
            }
            $this->assertLedgerMatchesLoan("after installment $installment");
        }

        $loan = $this->loan->fresh();
        $this->assertSame('fully_paid', $loan->status);
        $this->assertEquals(0, $loan->total_outstanding_balance);
        $this->assertEquals(0, $this->balance('principal'));
        $this->assertEquals(0, $this->balance('interest'));
        $this->assertEquals(0, $this->balance('fees'));
        // Cash and bank hold what was collected less what was paid out; income is interest plus fees.
        $this->assertEquals(round(12 * self::INSTALLMENT - 12000, 2), round($this->balance('cash') + $this->balance('bank'), 2));
        $this->assertEquals(-1708.8, $this->balance('income'));
        $this->assertSame(0, JournalEntry::all()->filter(fn($entry) => round($entry->lines()->sum('debit') - $entry->lines()->sum('credit'), 2) != 0)->count());
        $this->assertSame(14, JournalEntry::where('event_type', 'loan_repayment')->count());
        $this->assertSame(0, LoanRepayment::where('status', 'confirmed')->get()->filter(fn($r) => !JournalEntry::where('posting_key', "loan_repayment:{$r->id}:confirmed")->where('status', 'posted')->exists())->count());
    }
}
