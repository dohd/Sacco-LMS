<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The demo reset clears business data, products and member portal users,
 * keeps the configuration, and refuses to run anywhere but a switched-on demo.
 */
class DemoResetTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $adminId;
    private $staffId;
    private $memberUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'demo_reset_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.demo_reset_test' => $connection,
            'database.default' => 'demo_reset_test',
            'session.driver' => 'array', 'cache.default' => 'array',
            'demo.reset_enabled' => true,
        ]);
        DB::setDefaultConnection('demo_reset_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'demo_reset_test', '--force' => true]), Artisan::output());
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);

        Storage::fake('private');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('demo_reset_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^demo_reset_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function fill(): void
    {
        $this->adminId = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x']);
        $this->staffId = DB::table('users')->insertGetId(['name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'x']);
        $this->memberUserId = DB::table('users')->insertGetId(['name' => 'Member', 'email' => 'member@example.test', 'password' => 'x']);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        $accountantRole = DB::table('roles')->where('name', 'accountant')->first();
        DB::table('model_has_roles')->insert([
            ['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $this->adminId],
            ['role_id' => $accountantRole->id, 'model_type' => User::class, 'model_id' => $this->staffId],
            ['role_id' => $accountantRole->id, 'model_type' => User::class, 'model_id' => $this->memberUserId],
        ]);

        $account = DB::table('chart_of_accounts')->insertGetId([
            'code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit',
            'is_control_account' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('accounting_periods')->insert([
            'name' => 'Year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'closed', 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('settings')->insert(['key' => 'KEEP_ME', 'value' => 'yes']);
        DB::table('share_products')->insert([
            'code' => 'ORD', 'name' => 'Ordinary shares', 'share_capital_account_id' => $account,
            'share_premium_account_id' => $account, 'unit_value' => 100, 'minimum_units' => 1,
            'dividend_eligible' => 1, 'allows_transfer' => 1, 'allows_redemption' => 1, 'can_secure_loan' => 1,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('journal_number_sequences')->insert(['year' => (int) today()->format('Y'), 'last_number' => 7]);
        DB::table('failed_jobs')->insert(['uuid' => 'abc', 'connection' => 'x', 'queue' => 'x', 'payload' => '{}', 'exception' => 'x']);
        DB::table('share_accounts')->insert([
            'member_id' => 1, 'share_product_id' => 1, 'account_number' => 'A1', 'opened_date' => '2020-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Storage::disk('private')->put('memberships/APP-1/id/front.png', 'x');
        Storage::disk('public')->put('nomination-signatures/members/a.png', 'x');
        Storage::disk('public')->put('templates/employees.xlsx', 'x');
        Storage::disk('public')->put('images/users/photo.png', 'x');
    }

    private function counts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'share_accounts' => DB::table('share_accounts')->count(),
            'share_products' => DB::table('share_products')->count(),
        ];
    }

    public function test_it_clears_data_products_and_member_users_and_keeps_the_configuration(): void
    {
        $this->fill();
        // The member portal user is the one linked to a member row.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('members')->insert([
            'member_application_id' => 1, 'membership_number' => 'M1', 'first_name' => 'A', 'last_name' => 'B',
            'date_of_birth' => '1990-01-01', 'national_id' => 'N1', 'phone' => '0700', 'email' => 'm@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => $this->adminId, 'user_id' => $this->memberUserId,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertSame(0, Artisan::call('demo:reset', ['--force' => true]), Artisan::output());

        $this->assertSame(0, DB::table('members')->count());
        $this->assertSame(0, DB::table('share_accounts')->count());
        $this->assertSame(0, DB::table('share_products')->count());
        $this->assertSame(0, DB::table('journal_number_sequences')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        // Member portal user and its role are gone; admin and staff stay.
        $this->assertNull(DB::table('users')->find($this->memberUserId));
        $this->assertNotNull(DB::table('users')->find($this->adminId));
        $this->assertNotNull(DB::table('users')->find($this->staffId));
        $this->assertSame(2, DB::table('model_has_roles')->count());

        // Configuration is kept and a closed period is reopened.
        $this->assertSame(1, DB::table('chart_of_accounts')->count());
        $this->assertSame('yes', DB::table('settings')->where('key', 'KEEP_ME')->value('value'));
        $this->assertGreaterThan(20, DB::table('permissions')->count());
        $this->assertGreaterThan(0, DB::table('roles')->count());
        $this->assertSame('open', DB::table('accounting_periods')->value('status'));

        // Uploaded documents go; templates and user photos stay.
        Storage::disk('private')->assertMissing('memberships/APP-1/id/front.png');
        Storage::disk('public')->assertMissing('nomination-signatures/members/a.png');
        Storage::disk('public')->assertExists('templates/employees.xlsx');
        Storage::disk('public')->assertExists('images/users/photo.png');
    }

    public function test_an_admin_linked_to_a_member_is_never_deleted(): void
    {
        $this->fill();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('members')->insert([
            'member_application_id' => 1, 'membership_number' => 'M1', 'first_name' => 'A', 'last_name' => 'B',
            'date_of_birth' => '1990-01-01', 'national_id' => 'N1', 'phone' => '0700', 'email' => 'm@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => $this->adminId, 'user_id' => $this->adminId,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertSame(0, Artisan::call('demo:reset', ['--force' => true]));
        $this->assertNotNull(DB::table('users')->find($this->adminId));
    }

    public function test_it_refuses_to_run_when_switched_off_or_in_production(): void
    {
        $this->fill();
        $before = $this->counts();

        config(['demo.reset_enabled' => false]);
        $this->assertSame(1, Artisan::call('demo:reset', ['--force' => true]));
        $this->assertSame($before, $this->counts());

        config(['demo.reset_enabled' => true]);
        $this->app['env'] = 'production';
        $this->assertSame(1, Artisan::call('demo:reset', ['--force' => true]));
        $this->assertSame($before, $this->counts());
        Storage::disk('private')->assertExists('memberships/APP-1/id/front.png');

        // The same setup runs once the environment is an allowed one.
        $this->app['env'] = 'demo';
        $this->assertSame(0, Artisan::call('demo:reset', ['--force' => true]));
        $this->assertSame(0, DB::table('share_products')->count());
        Storage::disk('private')->assertMissing('memberships/APP-1/id/front.png');
    }

    public function test_the_schedule_is_registered_only_when_switched_on(): void
    {
        $registered = function (bool $enabled) {
            config(['demo.reset_enabled' => $enabled]);
            $schedule = new \Illuminate\Console\Scheduling\Schedule();
            $kernel = new class($this->app, $this->app['events']) extends \App\Console\Kernel {
                public function define(\Illuminate\Console\Scheduling\Schedule $schedule)
                {
                    $this->schedule($schedule);
                }
            };
            $kernel->define($schedule);
            return collect($schedule->events())->filter(fn($event) => strpos($event->command, 'demo:reset --force') !== false);
        };

        $this->assertCount(0, $registered(false));
        $demo = $registered(true);
        $this->assertCount(1, $demo);
        $this->assertSame('0 3 * * *', $demo->first()->expression);
    }
}
