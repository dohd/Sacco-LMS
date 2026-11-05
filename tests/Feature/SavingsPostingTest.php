<?php

namespace Tests\Feature;

use App\Models\Accounting\JournalEntry;
use App\Models\Memberships\Member;
use App\Models\Memberships\MemberApplication;
use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsTransaction;
use App\Models\Savings\SavingsWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Ledger postings for savings deposits and withdrawals, and their reversals. */
class SavingsPostingTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $accounts = [];
    private $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'savings_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.savings_posting_test' => $connection,
            'database.default' => 'savings_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('savings_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'savings_posting_test', '--force' => true]), Artisan::output());

        $userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        DB::table('model_has_roles')->insert(['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $userId]);
        $this->actingAs(User::findOrFail($userId));

        foreach ([
            'cash' => ['1000', 'Cash on hand', 'asset', 'debit', false],
            'bank' => ['1010', 'Bank', 'asset', 'debit', false],
            'control' => ['2100', 'Member savings control', 'liability', 'credit', true],
            'register' => ['2101', 'Individual savings register', 'liability', 'credit', false],
            'expense' => ['5000', 'Interest expense', 'expense', 'debit', false],
            'income' => ['4000', 'Fee income', 'income', 'credit', false],
        ] as $key => [$code, $name, $type, $balance, $control]) {
            $this->accounts[$key] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => $name, 'account_type' => $type, 'normal_balance' => $balance,
                'is_control_account' => $control, 'config_key' => ['control' => 'member_savings_control', 'register' => 'individual_savings_register'][$key] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('accounting_periods')->insert([
            'name' => 'Current year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $application = MemberApplication::create(['application_number' => 'SAV-1',
            'first_name' => 'Savings', 'last_name' => 'Member', 'email' => 'savings@example.test',
            'national_id' => 'SAV-ID', 'status' => 'approved', 'date_of_birth' => '1990-01-01',
            'place_of_birth' => 'Nairobi', 'phone' => '0700000002', 'residential_address' => 'Nairobi',
            'next_of_kin_name' => 'Kin', 'next_of_kin_id' => 'SAV-KIN', 'next_of_kin_relationship' => 'sibling',
            'monthly_contribution' => 1000, 'contribution_start_date' => today()->toDateString()]);
        $member = Member::create(['member_application_id' => $application->id,
            'membership_number' => 'SAV-1', 'first_name' => 'Savings', 'last_name' => 'Member',
            'email' => 'savings@example.test', 'national_id' => 'SAV-ID', 'phone' => '0700000002',
            'date_of_birth' => '1990-01-01', 'admission_date' => today()->toDateString(),
            'approved_by' => $userId, 'status' => 'active', 'is_active' => true]);
        $this->post(route('savings_products.store'), ['code' => 'SAV', 'name' => 'Savings', 'product_type' => 'voluntary',
            'savings_control_account_id' => $this->accounts['control'], 'interest_expense_account_id' => $this->accounts['expense'],
            'fee_income_account_id' => $this->accounts['income'], 'interest_rate' => 0, 'interest_frequency' => 'annual',
            'interest_calculation_method' => 'simple', 'minimum_balance' => 1000,
            'maximum_balance' => 100000, 'minimum_monthly_contribution' => 0,
            'allows_withdrawals' => 1, 'withdrawal_notice_days' => 0, 'can_secure_loan' => 1,
            'is_active' => 1, 'allows_premature_withdrawal' => 0, 'auto_rollover' => 0])->assertSessionHasNoErrors();
        $this->post(route('savings_accounts.store'), ['member_id' => $member->id,
            'savings_product_id' => \App\Models\Savings\SavingsProduct::firstOrFail()->id, 'opened_date' => today()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->account = SavingsAccount::firstOrFail();
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('savings_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^savings_posting_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function depositRequest(float $amount = 5000, string $type = 'deposit'): SavingsTransaction
    {
        $this->post(route('savings_transactions.store'), [
            'savings_account_id' => $this->account->id, 'transaction_type' => $type, 'amount' => $amount,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        return SavingsTransaction::latest('id')->firstOrFail();
    }

    private function deposit(float $amount = 5000, string $ledger = 'cash'): SavingsTransaction
    {
        $transaction = $this->depositRequest($amount);
        $this->post(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts[$ledger]])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $transaction->fresh()->status);
        return $transaction->fresh();
    }

    private function requestWithdrawal(float $amount): SavingsWithdrawal
    {
        $this->post(route('savings_withdrawals.store'), [
            'savings_account_id' => $this->account->id, 'amount' => $amount,
            'requested_date' => today()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        return SavingsWithdrawal::latest('id')->firstOrFail();
    }

    /** Net credit balance of a ledger account (positive for a liability that grows). */
    private function credit(string $account): float
    {
        return round(-(float) DB::table('journal_entry_lines')->where('chart_of_account_id', $this->accounts[$account])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance'), 2);
    }

    private function memberBalance(): float
    {
        return round((float) $this->account->fresh()->ledger_balance, 2);
    }

    public function test_deposit_needs_a_ledger_account_and_posts_to_cash_and_savings_control(): void
    {
        $transaction = $this->depositRequest(5000);

        // The confirm button offers only cash and bank style accounts.
        $this->get(route('savings_transactions.show', $transaction))->assertOk()->assertSee('Cash on hand')->assertSee('Bank')->assertDontSee('Member savings control</option>', false);

        $this->postJson(route('savings_transactions.confirm', $transaction))->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->postJson(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['control']])
            ->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->postJson(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['income']])
            ->assertStatus(422)->assertJsonValidationErrors('deposit_account_id');
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertEquals(0, $this->memberBalance());
        $this->assertSame(0, JournalEntry::count());

        $this->post(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['cash']])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertEquals($this->accounts['cash'], $transaction->fresh()->deposit_account_id);
        $entry = JournalEntry::sole();
        $this->assertSame('posted', $entry->status);
        $this->assertSame('savings_deposit', $entry->event_type);
        $this->assertSame('savings_transaction:' . $transaction->id . ':confirmed', $entry->posting_key);
        $this->assertSame(SavingsTransaction::class, $entry->source_type);
        $this->assertEquals(5000, $this->credit('register'));
        $this->assertEquals(-5000, $this->credit('cash'));
        $this->assertSame(2, $entry->lines()->where('member_id', $this->account->member_id)->where('savings_account_id', $this->account->id)->count());
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        // Confirming again is refused and nothing is posted twice.
        $this->postJson(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['cash']])->assertStatus(422);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_deposit_confirmation_is_rolled_back_when_it_cannot_be_posted(): void
    {
        $transaction = $this->depositRequest(5000);

        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->postJson(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['cash']])
            ->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertEquals(0, $this->memberBalance());
        $this->assertSame(0, JournalEntry::count());

        DB::table('accounting_periods')->update(['status' => 'open']);
        $this->post(route('savings_transactions.confirm', $transaction), ['deposit_account_id' => $this->accounts['bank']])->assertSessionHasNoErrors();
        $this->assertEquals(5000, $this->memberBalance());
        $this->assertEquals(-5000, $this->credit('bank'));
    }

    public function test_reversing_a_deposit_reverses_the_ledger_entry(): void
    {
        $transaction = $this->deposit(5000);
        $this->postJson(route('savings_transactions.reverse', $transaction), ['description' => 'Wrong account'])->assertOk()->assertJson(['status' => 'success']);

        $this->assertEquals(0, $this->memberBalance());
        $entry = JournalEntry::where('event_type', 'savings_deposit')->sole();
        $this->assertSame('reversed', $entry->status);
        $this->assertSame('posted', JournalEntry::where('reversal_of_id', $entry->id)->sole()->status);
        $this->assertEquals(0, $this->credit('register'));
        $this->assertEquals(0, $this->credit('cash'));
    }

    public function test_withdrawal_posts_when_paid_and_reconciles_with_the_member_balance(): void
    {
        $this->deposit(5000);
        $withdrawal = $this->requestWithdrawal(2000);
        $this->post(route('savings_withdrawals.approve', $withdrawal))->assertSessionHasNoErrors();

        // Approved but not paid: the member balance has fallen, cash has not moved, nothing is posted.
        $this->assertEquals(3000, $this->memberBalance());
        $this->assertSame(1, JournalEntry::count());
        $this->assertEquals(5000, $this->credit('register'));

        $this->get(route('savings_withdrawals.show', $withdrawal))->assertOk()->assertSee('Cash on hand')->assertSee('Bank');
        $pay = fn(array $data) => $this->postJson(route('savings_withdrawals.pay', $withdrawal), $data + ['payment_reference' => 'VOUCHER-1']);
        $pay([])->assertStatus(422)->assertJsonValidationErrors('payment_account_id');
        $pay(['payment_account_id' => $this->accounts['control']])->assertStatus(422)->assertJsonValidationErrors('payment_account_id');
        $this->assertSame('approved', $withdrawal->fresh()->status);

        // No open period: payment fails as a whole.
        DB::table('accounting_periods')->update(['status' => 'closed']);
        $pay(['payment_account_id' => $this->accounts['cash']])->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('approved', $withdrawal->fresh()->status);
        DB::table('accounting_periods')->update(['status' => 'open']);

        $this->post(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'VOUCHER-1', 'payment_account_id' => $this->accounts['cash']])->assertSessionHasNoErrors();
        $this->assertSame('paid', $withdrawal->fresh()->status);
        $this->assertEquals($this->accounts['cash'], $withdrawal->fresh()->payment_account_id);
        $entry = JournalEntry::where('event_type', 'savings_withdrawal')->sole();
        $this->assertSame('savings_withdrawal:' . $withdrawal->id . ':paid', $entry->posting_key);
        $this->assertSame(SavingsWithdrawal::class, $entry->source_type);
        $this->assertSame(2, $entry->lines()->where('member_id', $this->account->member_id)->where('savings_account_id', $this->account->id)->count());
        $this->assertEquals(3000, $this->credit('register'));
        $this->assertEquals(-3000, $this->credit('cash'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        // Paying again is refused and nothing is posted twice.
        $pay(['payment_account_id' => $this->accounts['cash'], 'payment_reference' => 'VOUCHER-2'])->assertStatus(422);
        $this->assertSame(1, JournalEntry::where('event_type', 'savings_withdrawal')->count());
    }

    public function test_reversing_a_paid_withdrawal_restores_cash_and_savings_control(): void
    {
        $this->deposit(5000);
        $withdrawal = $this->requestWithdrawal(2000);
        $this->post(route('savings_withdrawals.approve', $withdrawal))->assertSessionHasNoErrors();
        $this->post(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'VOUCHER-1', 'payment_account_id' => $this->accounts['bank']])->assertSessionHasNoErrors();
        $this->assertEquals(-3000, $this->credit('bank') + $this->credit('cash'));

        $this->postJson(route('savings_transactions.reverse', $withdrawal->savings_transaction_id), ['description' => 'Paid in error'])->assertOk();

        $this->assertEquals(5000, $this->memberBalance());
        $this->assertEquals(5000, $this->credit('register'));
        $this->assertEquals(-5000, $this->credit('cash'));
        $this->assertEquals(0, $this->credit('bank'));
        $entry = JournalEntry::where('event_type', 'savings_withdrawal')->sole();
        $this->assertSame('reversed', $entry->status);
        $this->assertSame(1, JournalEntry::where('reversal_of_id', $entry->id)->count());
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2));
    }

    public function test_reversing_an_approved_but_unpaid_withdrawal_needs_no_ledger_reversal(): void
    {
        $this->deposit(5000);
        $withdrawal = $this->requestWithdrawal(2000);
        $this->post(route('savings_withdrawals.approve', $withdrawal))->assertSessionHasNoErrors();

        $this->postJson(route('savings_transactions.reverse', $withdrawal->savings_transaction_id), ['description' => 'Cancelled'])->assertOk();
        $this->assertEquals(5000, $this->memberBalance());
        $this->assertEquals(5000, $this->credit('register'));
        $this->assertSame(1, JournalEntry::count());
    }

    private function confirmMovement(float $amount, string $type, array $confirm = [], string $direction = null): SavingsTransaction
    {
        $payload = ['savings_account_id' => $this->account->id, 'transaction_type' => $type, 'amount' => $amount,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString(), 'payment_method' => 'system'];
        if ($direction) {
            $payload['direction'] = $direction;
        }
        $this->post(route('savings_transactions.store'), $payload)->assertSessionHasNoErrors();
        $transaction = SavingsTransaction::latest('id')->firstOrFail();
        $this->post(route('savings_transactions.confirm', $transaction), $confirm)->assertSessionHasNoErrors();

        return $transaction->fresh();
    }

    public function test_savings_fee_posts_to_fee_income_and_reverses(): void
    {
        $this->deposit(5000);
        $fee = $this->confirmMovement(100, 'fee');

        $entry = JournalEntry::where('event_type', 'savings_fee')->sole();
        $this->assertSame('savings_transaction:' . $fee->id . ':confirmed', $entry->posting_key);
        $this->assertEquals(4900, $this->credit('register'));
        $this->assertEquals(100, $this->credit('income'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        $this->postJson(route('savings_transactions.reverse', $fee), ['description' => 'Fee waived'])->assertOk();
        $this->assertEquals(5000, $this->credit('register'));
        $this->assertEquals(0, $this->credit('income'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));
    }

    public function test_adjustment_needs_a_valid_offset_account_and_posts_both_directions(): void
    {
        $this->deposit(5000);
        $this->post(route('savings_transactions.store'), ['savings_account_id' => $this->account->id, 'transaction_type' => 'adjustment',
            'direction' => 'credit', 'amount' => 200, 'transaction_date' => today()->toDateString(),
            'value_date' => today()->toDateString(), 'payment_method' => 'system'])->assertSessionHasNoErrors();
        $adjustment = SavingsTransaction::latest('id')->firstOrFail();

        $this->postJson(route('savings_transactions.confirm', $adjustment))->assertStatus(422)->assertJsonValidationErrors('offset_account_id');
        // The register and the control account cannot balance themselves.
        $this->postJson(route('savings_transactions.confirm', $adjustment), ['offset_account_id' => $this->accounts['register']])
            ->assertStatus(422)->assertJsonValidationErrors('offset_account_id');
        $this->postJson(route('savings_transactions.confirm', $adjustment), ['offset_account_id' => $this->accounts['control']])
            ->assertStatus(422)->assertJsonValidationErrors('offset_account_id');
        $this->assertSame('pending', $adjustment->fresh()->status);

        $this->post(route('savings_transactions.confirm', $adjustment), ['offset_account_id' => $this->accounts['expense']])->assertSessionHasNoErrors();
        $this->assertEquals($this->accounts['expense'], $adjustment->fresh()->offset_account_id);
        $this->assertEquals(5200, $this->credit('register'));
        $this->assertEquals(-200, $this->credit('expense'));

        $this->confirmMovement(50, 'adjustment', ['offset_account_id' => $this->accounts['income']], 'debit');
        $this->assertEquals(5150, $this->credit('register'));
        $this->assertEquals(50, $this->credit('income'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        $this->postJson(route('savings_transactions.reverse', $adjustment), ['description' => 'Mistake'])->assertOk();
        $this->assertEquals(4950, $this->credit('register'));
        $this->assertEquals(0, $this->credit('expense'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));
    }

    public function test_scheduled_interest_is_calculated_posted_and_never_credited_twice(): void
    {
        $year = (int) today()->format('Y');
        DB::table('savings_products')->update(['interest_rate' => 12, 'interest_frequency' => 'monthly', 'interest_calculation_method' => 'simple', 'maximum_balance' => null]);

        $this->post(route('savings_transactions.store'), ['savings_account_id' => $this->account->id, 'transaction_type' => 'deposit',
            'amount' => 36500, 'transaction_date' => $year . '-01-01', 'value_date' => $year . '-01-01', 'payment_method' => 'cash'])
            ->assertSessionHasNoErrors();
        $this->post(route('savings_transactions.confirm', SavingsTransaction::latest('id')->firstOrFail()), ['deposit_account_id' => $this->accounts['cash']])
            ->assertSessionHasNoErrors();

        $userId = User::firstOrFail()->id;
        // Not a month end: nothing happens.
        $this->assertSame(0, Artisan::call('savings:credit-interest', ['--date' => $year . '-01-15', '--user' => $userId]));
        $this->assertSame(0, SavingsTransaction::where('transaction_type', 'interest')->count());

        // January: 36,500 x 12% x 31 / 365 = 372.00
        $this->assertSame(0, Artisan::call('savings:credit-interest', ['--date' => $year . '-01-31', '--user' => $userId]), Artisan::output());
        $interest = SavingsTransaction::where('transaction_type', 'interest')->sole();
        $this->assertEquals(372.00, round((float) $interest->amount, 2));
        $this->assertSame('confirmed', $interest->status);
        $this->assertEquals(36872, $this->memberBalance());
        $entry = JournalEntry::where('event_type', 'savings_interest')->sole();
        $this->assertSame('savings_transaction:' . $interest->id . ':confirmed', $entry->posting_key);
        $this->assertEquals(-372, $this->credit('expense'));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        // Running the same period again credits nothing.
        Artisan::call('savings:credit-interest', ['--date' => $year . '-01-31', '--user' => $userId]);
        $this->assertSame(1, SavingsTransaction::where('transaction_type', 'interest')->count());

        // February (simple interest): earned on 36,500 only, not on January interest.
        $february = \Carbon\Carbon::create($year, 2, 1)->endOfMonth();
        Artisan::call('savings:credit-interest', ['--date' => $february->toDateString(), '--user' => $userId]);
        $expected = round(36500 * $february->day * 0.12 / 365, 2);
        $this->assertEquals($expected, round((float) SavingsTransaction::where('transaction_type', 'interest')->latest('id')->firstOrFail()->amount, 2));
        $this->assertEquals($this->memberBalance(), $this->credit('register'));

        // Quarterly products wait for the quarter end.
        DB::table('savings_products')->update(['interest_frequency' => 'quarterly']);
        Artisan::call('savings:credit-interest', ['--date' => $year . '-03-31', '--user' => $userId]);
        $this->assertSame(3, SavingsTransaction::where('transaction_type', 'interest')->count());
    }

    public function test_scheduled_interest_failure_is_reported_and_leaves_balances_untouched(): void
    {
        $year = (int) today()->format('Y');
        DB::table('savings_products')->update(['interest_rate' => 12, 'interest_frequency' => 'monthly']);
        $this->deposit(5000);
        $balance = $this->memberBalance();
        DB::table('accounting_periods')->update(['status' => 'closed']);

        $this->assertSame(1, Artisan::call('savings:credit-interest', ['--date' => $year . '-12-31', '--user' => User::firstOrFail()->id]));
        $this->assertSame(0, SavingsTransaction::where('transaction_type', 'interest')->count());
        $this->assertEquals($balance, $this->memberBalance());
    }

    public function test_scheduler_is_registered(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn($event) => strpos($event->command, 'savings:credit-interest') !== false));
    }

    public function test_savings_product_form_only_accepts_postable_expense_and_income_accounts(): void
    {
        $product = \App\Models\Savings\SavingsProduct::firstOrFail();
        $payload = ['code' => 'SAV2', 'name' => 'Savings Two', 'product_type' => 'voluntary',
            'savings_control_account_id' => $this->accounts['control'], 'interest_expense_account_id' => $this->accounts['expense'],
            'fee_income_account_id' => $this->accounts['income'], 'interest_rate' => 0, 'interest_frequency' => 'annual',
            'interest_calculation_method' => 'simple', 'minimum_balance' => 0, 'maximum_balance' => 100000,
            'minimum_monthly_contribution' => 0, 'allows_withdrawals' => 1, 'withdrawal_notice_days' => 0,
            'can_secure_loan' => 1, 'is_active' => 1, 'allows_premature_withdrawal' => 0, 'auto_rollover' => 0];

        // The form lists the expense and income accounts only.
        $this->get(route('savings_products.create'))->assertOk();

        // A cash account, a zero id and a swapped expense/income pair are all refused.
        $this->postJson(route('savings_products.store'), array_merge($payload, ['interest_expense_account_id' => $this->accounts['cash']]))
            ->assertStatus(422)->assertJsonValidationErrors('interest_expense_account_id');
        $this->postJson(route('savings_products.store'), array_merge($payload, ['fee_income_account_id' => 0]))
            ->assertStatus(422)->assertJsonValidationErrors('fee_income_account_id');
        $this->postJson(route('savings_products.store'), array_merge($payload, [
            'interest_expense_account_id' => $this->accounts['income'], 'fee_income_account_id' => $this->accounts['expense']]))
            ->assertStatus(422)->assertJsonValidationErrors(['interest_expense_account_id', 'fee_income_account_id']);
        $this->assertSame(1, \App\Models\Savings\SavingsProduct::count());

        // Editing an existing product is held to the same rule.
        $this->putJson(route('savings_products.update', $product), array_merge($payload, ['code' => $product->code, 'fee_income_account_id' => $this->accounts['bank']]))
            ->assertStatus(422)->assertJsonValidationErrors('fee_income_account_id');

        $this->post(route('savings_products.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, \App\Models\Savings\SavingsProduct::count());
    }
}
