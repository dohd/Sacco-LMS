<?php

namespace Tests\Feature;

use App\Models\Accounting\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Manual journals share the posting rules of every other ledger entry.
 */
class ManualJournalTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $accounts = [];
    private $periodId;
    private $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'manual_journal_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.manual_journal_test' => $connection,
            'database.default' => 'manual_journal_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('manual_journal_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'manual_journal_test', '--force' => true]), Artisan::output());

        $this->userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        DB::table('model_has_roles')->insert(['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $this->userId]);
        $this->actingAs(User::findOrFail($this->userId));

        foreach ([
            'cash' => ['1000', 'Cash on hand', 'asset', 'debit', null],
            'rent' => ['5000', 'Rent expense', 'expense', 'debit', null],
            'register' => ['2101', 'Individual savings register', 'liability', 'credit', 'individual_savings_register'],
            'capital' => ['3000', 'Share capital', 'equity', 'credit', null],
            'income' => ['4000', 'Income', 'income', 'credit', null],
        ] as $key => [$code, $name, $type, $balance, $config]) {
            $this->accounts[$key] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => $name, 'account_type' => $type, 'normal_balance' => $balance,
                'is_control_account' => false, 'config_key' => $config, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('share_products')->insert([
            'code' => 'ORD', 'name' => 'Ordinary shares', 'share_capital_account_id' => $this->accounts['capital'],
            'share_premium_account_id' => $this->accounts['income'], 'unit_value' => 100, 'minimum_units' => 1,
            'dividend_eligible' => 1, 'allows_transfer' => 1, 'allows_redemption' => 1, 'can_secure_loan' => 1,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->periodId = DB::table('accounting_periods')->insertGetId([
            'name' => 'Current year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('manual_journal_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^manual_journal_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function payload(string $debit = 'rent', string $credit = 'cash', float $amount = 500): array
    {
        return [
            'accounting_period_id' => $this->periodId, 'transaction_date' => today()->toDateString(),
            'posting_date' => today()->toDateString(), 'description' => 'Office rent',
            'lines' => [
                ['account_id' => $this->accounts[$debit], 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->accounts[$credit], 'debit' => 0, 'credit' => $amount],
            ],
        ];
    }

    private function draft(array $payload = null): JournalEntry
    {
        $this->post(route('journal_entries.store'), $payload ?: $this->payload())->assertSessionHasNoErrors();
        return JournalEntry::latest('id')->firstOrFail();
    }

    private function balance(string $account): float
    {
        return round((float) DB::table('journal_entry_lines')->where('chart_of_account_id', $this->accounts[$account])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance'), 2);
    }

    public function test_a_manual_journal_is_drafted_posted_and_reversed(): void
    {
        $entry = $this->draft();
        $this->assertSame('draft', $entry->status);
        $this->patch(route('journal_entries.post', $entry->id))->assertSessionHasNoErrors();
        $entry = $entry->fresh();
        $this->assertSame('posted', $entry->status);
        $this->assertEquals($this->userId, $entry->posted_by);
        $this->assertEquals(500, $this->balance('rent'));

        $this->post(route('journal_entries.reverse', $entry->id), ['reversal_reason' => 'Wrong month'])->assertSessionHasNoErrors();
        $this->assertSame('reversed', $entry->fresh()->status);
        $reversal = JournalEntry::where('reversal_of_id', $entry->id)->sole();
        $this->assertSame('manual_journal:' . $entry->id . ':posted', $entry->posting_key);
        $this->assertSame('manual_journal:' . $entry->id . ':reversed', $reversal->posting_key);
        $this->assertSame('posted', $reversal->status);
        $this->assertEquals(0, $this->balance('rent'));

        // Reversing twice changes nothing.
        $this->postJson(route('journal_entries.reverse', $entry->id), ['reversal_reason' => 'Again'])->assertStatus(422);
        $this->assertSame(1, JournalEntry::where('reversal_of_id', $entry->id)->count());
    }

    public function test_posting_applies_the_standard_rules(): void
    {
        // Unbalanced.
        $payload = $this->payload();
        $payload['lines'][1]['credit'] = 400;
        $this->postJson(route('journal_entries.store'), $payload)->assertStatus(422)->assertJsonValidationErrors('lines');

        // Inactive account found at posting time.
        $entry = $this->draft();
        DB::table('chart_of_accounts')->where('id', $this->accounts['rent'])->update(['is_active' => false]);
        $this->patchJson(route('journal_entries.post', $entry->id))->assertStatus(422)->assertJsonValidationErrors('journal');
        DB::table('chart_of_accounts')->where('id', $this->accounts['rent'])->update(['is_active' => true]);

        // Closed period.
        DB::table('accounting_periods')->update(['status' => 'closed']);
        $this->patchJson(route('journal_entries.post', $entry->id))->assertStatus(422)->assertJsonValidationErrors('journal');
        DB::table('accounting_periods')->update(['status' => 'open']);
        $this->assertSame('draft', $entry->fresh()->status);

        $this->patch(route('journal_entries.post', $entry->id))->assertSessionHasNoErrors();
        // Posted entries cannot be posted again.
        $this->patchJson(route('journal_entries.post', $entry->id))->assertStatus(422);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_subledger_controlled_accounts_are_refused(): void
    {
        $this->postJson(route('journal_entries.store'), $this->payload('cash', 'register'))
            ->assertStatus(422)->assertJsonValidationErrors('lines');
        $this->postJson(route('journal_entries.store'), $this->payload('cash', 'capital'))
            ->assertStatus(422)->assertJsonValidationErrors('lines');
        $this->assertSame(0, JournalEntry::count());

        // A draft saved before the account became controlled is refused at posting.
        $entry = $this->draft($this->payload('cash', 'income'));
        DB::table('chart_of_accounts')->where('id', $this->accounts['income'])->update(['config_key' => 'individual_savings_register']);
        DB::table('chart_of_accounts')->where('id', $this->accounts['register'])->update(['config_key' => null]);
        $this->patchJson(route('journal_entries.post', $entry->id))->assertStatus(422)->assertJsonValidationErrors('lines');
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_transaction_entries_cannot_be_reversed_by_hand(): void
    {
        $system = JournalEntry::create([
            'accounting_period_id' => $this->periodId, 'entry_number' => 'JE-SYS-1', 'posting_key' => 'loan_repayment:1:confirmed',
            'transaction_date' => today(), 'posting_date' => today(), 'event_type' => 'loan_repayment',
            'description' => 'System entry', 'status' => 'posted', 'created_by' => $this->userId,
        ]);
        $this->postJson(route('journal_entries.reverse', $system->id), ['reversal_reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('journal');
        $this->assertSame('posted', $system->fresh()->status);
        $this->assertSame(0, JournalEntry::where('reversal_of_id', $system->id)->count());
    }

    public function test_entry_numbers_stay_unique_after_a_draft_is_deleted(): void
    {
        $first = $this->draft();
        $second = $this->draft();
        $third = $this->draft();
        $this->assertSame(['000001', '000002', '000003'], [substr($first->entry_number, -6), substr($second->entry_number, -6), substr($third->entry_number, -6)]);

        // The old count-based generator looped forever here: two entries were left, so the next number was already taken.
        $this->delete(route('journal_entries.destroy', $second->id))->assertSessionHasNoErrors();
        $fourth = $this->draft();
        $fifth = $this->draft();
        $this->assertSame('000004', substr($fourth->entry_number, -6));
        $this->assertSame('000005', substr($fifth->entry_number, -6));
        $this->assertSame(JournalEntry::count(), JournalEntry::distinct('entry_number')->count('entry_number'));

        // Every key follows type:id:state.
        foreach (JournalEntry::all() as $entry) {
            $this->assertSame('manual_journal:' . $entry->id . ':posted', $entry->posting_key);
        }
    }

    public function test_numbering_continues_after_the_highest_number_already_issued(): void
    {
        JournalEntry::create([
            'accounting_period_id' => $this->periodId, 'entry_number' => 'JE-' . now()->format('Y') . '-000050',
            'posting_key' => 'loan_repayment:9:confirmed', 'transaction_date' => today(), 'posting_date' => today(),
            'event_type' => 'loan_repayment', 'description' => 'Existing', 'status' => 'posted', 'created_by' => $this->userId,
        ]);
        $this->assertSame('000051', substr($this->draft()->entry_number, -6));
        $this->assertSame('000052', substr($this->draft()->entry_number, -6));
        $this->assertSame(52, (int) DB::table('journal_number_sequences')->where('year', (int) now()->format('Y'))->value('last_number'));
    }

    public function test_users_without_permission_cannot_post_or_reverse(): void
    {
        $entry = $this->draft();
        $other = User::create(['name' => 'Clerk', 'email' => 'clerk@example.test', 'password' => 'unused']);
        $this->actingAs($other);
        $this->patch(route('journal_entries.post', $entry->id))->assertStatus(403);
        $this->post(route('journal_entries.reverse', $entry->id), ['reversal_reason' => 'x'])->assertStatus(403);
        $this->post(route('journal_entries.store'), $this->payload())->assertStatus(403);
        $this->assertSame('draft', $entry->fresh()->status);
    }
}
