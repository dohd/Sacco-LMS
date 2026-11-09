<?php

namespace Tests\Feature;

use App\Models\LoanApplications\LoanProduct;
use App\Models\Savings\SavingsProduct;
use App\Models\Shares\ShareProduct;
use Database\Seeders\PilotProductSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The pilot product seeder, run against an isolated throwaway database. */
class PilotProductSeederTest extends TestCase
{
    private const CODES = [
        '1301' => 'asset', '1302' => 'asset', '1303' => 'asset', '1304' => 'asset',
        '2101' => 'liability', '2206' => 'liability', '2207' => 'liability',
        '3001' => 'equity', '3004' => 'equity',
        '4001' => 'income', '4004' => 'income', '4005' => 'income', '4006' => 'income',
        '5002' => 'expense', '5004' => 'expense',
    ];

    private $database;
    private $originalConnection;
    private $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'pilot_seeder_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config(['database.connections.pilot_seeder_test' => $connection, 'database.default' => 'pilot_seeder_test']);
        DB::setDefaultConnection('pilot_seeder_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'pilot_seeder_test', '--force' => true]), Artisan::output());
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('pilot_seeder_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^pilot_seeder_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function createAccounts(array $except = []): void
    {
        foreach (self::CODES as $code => $type) {
            if (in_array((string) $code, $except, true)) {
                continue;
            }
            $this->ids[$code] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => 'Account ' . $code, 'account_type' => $type,
                'normal_balance' => in_array($type, ['asset', 'expense']) ? 'debit' : 'credit',
                'is_control_account' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_it_creates_the_savings_share_and_loan_products_with_their_accounts(): void
    {
        $this->createAccounts();
        $this->seed(PilotProductSeeder::class);

        $savings = SavingsProduct::where('code', 'BOSA-DEP-01')->sole();
        $this->assertSame('compulsory', $savings->product_type);
        $this->assertEquals(5.07, (float) $savings->interest_rate);
        $this->assertSame('annual', $savings->interest_frequency);
        $this->assertEquals(0, (int) $savings->allows_withdrawals);
        $this->assertSame($this->ids['2101'], (int) $savings->savings_control_account_id);
        $this->assertSame($this->ids['5002'], (int) $savings->interest_expense_account_id);

        $shares = ShareProduct::where('code', 'SC')->sole();
        $this->assertEquals(100, (float) $shares->unit_value);
        $this->assertSame(0, (int) $shares->minimum_units);
        $this->assertSame($this->ids['3001'], (int) $shares->share_capital_account_id);
        $this->assertSame($this->ids['3004'], (int) $shares->share_premium_account_id);

        $standard = LoanProduct::where('code', 'LN-STD')->sole();
        $imported = LoanProduct::where('code', 'LN-IMP')->sole();
        foreach ([$standard, $imported] as $loan) {
            $this->assertEquals(12, (float) $loan->interest_rate);
            $this->assertSame('reducing_balance', $loan->interest_method);
            $this->assertSame('annual', $loan->interest_frequency);
            $this->assertEquals(0, (float) $loan->processing_fee_percentage);
            $this->assertEquals(1, (float) $loan->insurance_fee_percentage);
            $this->assertSame($this->ids['1301'], (int) $loan->loan_principal_account_id);
            $this->assertSame($this->ids['1302'], (int) $loan->interest_receivable_account_id);
            $this->assertSame($this->ids['1303'], (int) $loan->fee_receivable_account_id);
            $this->assertSame($this->ids['1304'], (int) $loan->penalty_receivable_account_id);
            $this->assertSame($this->ids['2207'], (int) $loan->insurance_payable_account_id);
            $this->assertSame($this->ids['5004'], (int) $loan->write_off_expense_account_id);
        }
        // Only the standard product holds interest as unearned.
        $this->assertSame($this->ids['2206'], (int) $standard->unearned_interest_account_id);
        $this->assertNull($imported->unearned_interest_account_id);
    }

    public function test_running_it_again_updates_in_place_without_duplicates(): void
    {
        $this->createAccounts();
        $this->seed(PilotProductSeeder::class);
        SavingsProduct::where('code', 'BOSA-DEP-01')->update(['interest_rate' => 9]);
        $this->seed(PilotProductSeeder::class);

        $this->assertSame(1, SavingsProduct::count());
        $this->assertSame(1, ShareProduct::count());
        $this->assertSame(2, LoanProduct::count());
        $this->assertEquals(5.07, (float) SavingsProduct::first()->interest_rate);
    }

    public function test_it_writes_nothing_and_names_the_missing_accounts(): void
    {
        $this->createAccounts(['2207', '5004']);

        try {
            app(PilotProductSeeder::class)->run();
            $this->fail('The seeder should refuse to run with accounts missing.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('2207, 5004', $e->getMessage());
        }
        $this->assertSame(0, SavingsProduct::count() + ShareProduct::count() + LoanProduct::count());
    }
}
