<?php

namespace Tests\Feature;

use App\Models\Accounting\JournalEntry;
use App\Models\Memberships\Member;
use App\Models\Memberships\MemberApplication;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareProduct;
use App\Models\Shares\ShareTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Ledger postings for share purchases, redemptions, bonus issues and adjustments. */
class SharePostingTest extends TestCase
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
        $this->database = 'share_posting_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.share_posting_test' => $connection,
            'database.default' => 'share_posting_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('share_posting_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'share_posting_test', '--force' => true]), Artisan::output());

        $userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        DB::table('model_has_roles')->insert(['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $userId]);
        $this->actingAs(User::findOrFail($userId));

        foreach ([
            'cash' => ['1000', 'Cash on hand', 'asset', 'debit', true],
            'bank' => ['1010', 'Bank', 'asset', 'debit', true],
            'capital' => ['3000', 'Share capital', 'equity', 'credit', true],
            'premium' => ['3010', 'Share premium', 'equity', 'credit', true],
            'reserve' => ['3100', 'General reserve', 'equity', 'credit', true],
            'header' => ['3999', 'Equity header', 'equity', 'credit', false],
        ] as $key => [$code, $name, $type, $balance, $postable]) {
            $this->accounts[$key] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => $name, 'account_type' => $type, 'normal_balance' => $balance,
                'is_postable' => $postable, 'is_control_account' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('accounting_periods')->insert([
            'name' => 'Current year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $application = MemberApplication::create(['application_number' => 'SH-1',
            'first_name' => 'Share', 'last_name' => 'Member', 'email' => 'share@example.test',
            'national_id' => 'SH-ID', 'status' => 'approved', 'date_of_birth' => '1990-01-01',
            'place_of_birth' => 'Nairobi', 'phone' => '0700000003', 'residential_address' => 'Nairobi',
            'next_of_kin_name' => 'Kin', 'next_of_kin_id' => 'SH-KIN', 'next_of_kin_relationship' => 'sibling',
            'monthly_contribution' => 1000, 'contribution_start_date' => today()->toDateString()]);
        $member = Member::create(['member_application_id' => $application->id,
            'membership_number' => 'SH-1', 'first_name' => 'Share', 'last_name' => 'Member',
            'email' => 'share@example.test', 'national_id' => 'SH-ID', 'phone' => '0700000003',
            'date_of_birth' => '1990-01-01', 'admission_date' => today()->toDateString(),
            'approved_by' => $userId, 'status' => 'active', 'is_active' => true]);
        $this->post(route('share_products.store'), ['code' => 'ORD', 'name' => 'Ordinary shares',
            'share_capital_account_id' => $this->accounts['capital'], 'share_premium_account_id' => $this->accounts['premium'],
            'unit_value' => 100, 'minimum_units' => 1, 'maximum_units' => 100000,
            'dividend_eligible' => 1, 'allows_transfer' => 1, 'allows_redemption' => 1, 'can_secure_loan' => 1, 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $this->post(route('share_accounts.store'), ['member_id' => $member->id,
            'share_product_id' => ShareProduct::firstOrFail()->id, 'opened_date' => today()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->account = ShareAccount::firstOrFail();
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('share_posting_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^share_posting_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function request(string $type, int $units, ?string $direction = null): ShareTransaction
    {
        $payload = ['share_account_id' => $this->account->id, 'transaction_type' => $type, 'units' => $units,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString(), 'payment_method' => 'cash'];
        if ($direction) {
            $payload['direction'] = $direction;
        }
        $this->post(route('share_transactions.store'), $payload)->assertSessionHasNoErrors();

        return ShareTransaction::latest('id')->firstOrFail();
    }

    private function confirm(string $type, int $units, string $ledger, ?string $direction = null): ShareTransaction
    {
        $transaction = $this->request($type, $units, $direction);
        $this->post(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts[$ledger]])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $transaction->fresh()->status);

        return $transaction->fresh();
    }

    /** Net credit balance of a ledger account (positive when it grows as a credit). */
    private function credit(string $account): float
    {
        return round(-(float) DB::table('journal_entry_lines')->where('chart_of_account_id', $this->accounts[$account])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance'), 2);
    }

    private function memberBalance(): float
    {
        return round((float) $this->account->fresh()->share_balance, 2);
    }

    public function test_purchase_needs_a_cash_or_bank_account_and_posts_to_share_capital(): void
    {
        $transaction = $this->request('purchase', 50);

        $this->get(route('share_transactions.show', $transaction))->assertOk()->assertSee('Cash on hand')->assertSee('Bank')->assertDontSee('General reserve');

        // No account, a non cash account and a header account are all refused.
        $this->postJson(route('share_transactions.confirm', $transaction))->assertStatus(422)->assertJsonValidationErrors('ledger_account_id');
        $this->postJson(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['reserve']])
            ->assertStatus(422)->assertJsonValidationErrors('ledger_account_id');
        $this->postJson(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['header']])
            ->assertStatus(422)->assertJsonValidationErrors('ledger_account_id');
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(0, JournalEntry::count());

        $this->post(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['cash']])->assertSessionHasNoErrors();
        $this->assertEquals($this->accounts['cash'], $transaction->fresh()->ledger_account_id);
        $entry = JournalEntry::sole();
        $this->assertSame('share_purchase', $entry->event_type);
        $this->assertSame('share_transaction:' . $transaction->id . ':confirmed', $entry->posting_key);
        $this->assertEquals(5000, $this->credit('capital'));
        $this->assertEquals(-5000, $this->credit('cash'));
        $this->assertSame(2, $entry->lines()->where('member_id', $this->account->member_id)->where('share_account_id', $this->account->id)->count());
        $this->assertEquals($this->memberBalance(), $this->credit('capital'));

        // Confirming again is refused and nothing is posted twice.
        $this->postJson(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['cash']])->assertStatus(422);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_purchase_confirmation_is_rolled_back_when_it_cannot_be_posted(): void
    {
        $transaction = $this->request('purchase', 50);

        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->postJson(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['cash']])
            ->assertStatus(422)->assertJsonValidationErrors('ledger');
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertEquals(0, $this->memberBalance());
        $this->assertSame(0, JournalEntry::count());

        DB::table('accounting_periods')->update(['status' => 'open']);
        $this->post(route('share_transactions.confirm', $transaction), ['ledger_account_id' => $this->accounts['bank']])->assertSessionHasNoErrors();
        $this->assertEquals(5000, $this->memberBalance());
        $this->assertEquals(-5000, $this->credit('bank'));
    }

    public function test_redemption_pays_out_of_cash_and_reverses(): void
    {
        $this->confirm('purchase', 50, 'cash');
        $redemption = $this->confirm('redemption', 20, 'bank');

        $entry = JournalEntry::where('event_type', 'share_redemption')->sole();
        $this->assertSame('share_transaction:' . $redemption->id . ':confirmed', $entry->posting_key);
        $this->assertEquals(3000, $this->credit('capital'));
        $this->assertEquals(2000, $this->credit('bank'));
        $this->assertEquals($this->memberBalance(), $this->credit('capital'));

        $this->post(route('share_transactions.reverse', $redemption), ['description' => 'Paid in error'])->assertSessionHasNoErrors();
        $this->assertSame('reversed', $redemption->fresh()->status);
        $this->assertEquals(5000, $this->credit('capital'));
        $this->assertEquals(0, $this->credit('bank'));
        $this->assertEquals($this->memberBalance(), $this->credit('capital'));
    }

    public function test_reversing_a_purchase_reverses_the_ledger_entry(): void
    {
        $purchase = $this->confirm('purchase', 50, 'cash');
        $this->post(route('share_transactions.reverse', $purchase), ['description' => 'Wrong member'])->assertSessionHasNoErrors();

        $this->assertEquals(0, $this->credit('capital'));
        $this->assertEquals(0, $this->credit('cash'));
        $this->assertEquals(0, $this->memberBalance());
        $this->assertSame('reversed', JournalEntry::where('posting_key', 'share_transaction:' . $purchase->id . ':confirmed')->value('status'));
        $this->assertSame(2, JournalEntry::count());
    }

    public function test_bonus_issue_and_adjustments_post_against_a_chosen_equity_account(): void
    {
        $this->confirm('purchase', 50, 'cash');

        // A bonus issue capitalises a reserve: Dr reserve, Cr share capital.
        $bonus = $this->request('bonus_issue', 10);
        $this->postJson(route('share_transactions.confirm', $bonus))->assertStatus(422)->assertJsonValidationErrors('ledger_account_id');
        $this->post(route('share_transactions.confirm', $bonus), ['ledger_account_id' => $this->accounts['reserve']])->assertSessionHasNoErrors();
        $this->assertEquals(6000, $this->credit('capital'));
        $this->assertEquals(-1000, $this->credit('reserve'));

        // A debit adjustment is the reverse.
        $this->confirm('adjustment', 5, 'reserve', 'debit');
        $this->assertEquals(5500, $this->credit('capital'));
        $this->assertEquals(-500, $this->credit('reserve'));
        $this->assertEquals($this->memberBalance(), $this->credit('capital'));

        // Share capital cannot balance itself.
        $adjustment = $this->request('adjustment', 1, 'credit');
        $this->postJson(route('share_transactions.confirm', $adjustment), ['ledger_account_id' => $this->accounts['capital']])
            ->assertStatus(422)->assertJsonValidationErrors('ledger_account_id');
        $this->assertSame('pending', $adjustment->fresh()->status);
    }

    public function test_transfers_are_not_posted(): void
    {
        $this->confirm('purchase', 50, 'cash');
        $transfer = $this->request('transfer_out', 10);
        $this->post(route('share_transactions.confirm', $transfer))->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $transfer->fresh()->status);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_share_product_form_only_accepts_postable_ledger_accounts(): void
    {
        $payload = ['code' => 'PREF', 'name' => 'Preference shares',
            'share_capital_account_id' => $this->accounts['header'], 'share_premium_account_id' => $this->accounts['premium'],
            'unit_value' => 100, 'minimum_units' => 1, 'maximum_units' => 100000, 'is_active' => 1];

        $this->postJson(route('share_products.store'), $payload)->assertStatus(422)->assertJsonValidationErrors('share_capital_account_id');
        $this->assertSame(1, ShareProduct::count());
    }
}
