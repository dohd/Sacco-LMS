<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PilotImportService;
use Database\Seeders\PilotProductSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberImportOnboardingSimulationTest extends TestCase
{
    private $database;
    private $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') $this->markTestSkipped('The import simulation requires the isolated MySQL/MariaDB test database.');

        $this->database = 'member_onboarding_sim_' . bin2hex(random_bytes(6));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config(['database.connections.onboarding_sim' => $connection, 'database.default' => 'onboarding_sim']);
        DB::setDefaultConnection('onboarding_sim');

        try {
            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'onboarding_sim', '--force' => true]), Artisan::output());
        } catch (\Throwable $e) {
            DB::purge('onboarding_sim');
            DB::setDefaultConnection($this->originalConnection);
            DB::statement("DROP DATABASE `{$this->database}`");
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('pilot-imports');
        if ($this->database && $this->originalConnection) {
            DB::purge('onboarding_sim');
            DB::setDefaultConnection($this->originalConnection);
            DB::statement("DROP DATABASE `{$this->database}`");
        }
        parent::tearDown();
    }

    public function test_three_imported_members_can_sign_in_and_update_credentials(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $accountTypes = [
            '1301' => 'asset', '1302' => 'asset', '1303' => 'asset', '1304' => 'asset',
            '2101' => 'liability', '2206' => 'liability', '2207' => 'liability',
            '3001' => 'equity', '3004' => 'equity', '4001' => 'income', '4004' => 'income',
            '4005' => 'income', '4006' => 'income', '5002' => 'expense', '5004' => 'expense',
        ];
        foreach ($accountTypes as $code => $type) {
            DB::table('chart_of_accounts')->insert([
                'code' => $code, 'name' => 'Simulation ' . $code, 'account_type' => $type,
                'normal_balance' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit',
                'is_control_account' => false, 'is_postable' => true, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->seed(PilotProductSeeder::class);
        DB::table('accounting_periods')->insert([
            'name' => 'Onboarding simulation', 'financial_year' => (int) date('Y'),
            'start_date' => date('Y-01-01'), 'end_date' => date('Y-12-31'), 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $admin = User::create(['name' => 'Simulation Importer', 'email' => 'importer@example.test', 'password' => 'simulation-password']);
        Role::create(['name' => 'member', 'key_name' => 'member', 'guard_name' => 'web', 'is_active' => true]);

        Storage::fake('local');
        $headers = ['import_membership_number', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'national_id', 'phone', 'email', 'admission_date', 'shares', 'deposit_balance', 'loan_balance', 'interest_accrued', 'status'];
        $rows = [
            ['', 'Amina', '', 'Otieno', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', '0', '0', '0', 'active'],
            ['', 'Brian', '', 'Kiptoo', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', '0', '0', '0', 'active'],
            ['', 'Cecilia', '', 'Njeri', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', '0', '0', '0', 'active'],
        ];
        $csv = implode(',', $headers) . "\n" . implode("\n", array_map(fn ($row) => implode(',', $row), $rows));
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('members.csv', $csv);
        $ids = [
            'savings_product_id' => DB::table('savings_products')->where('code', 'BOSA-DEP-01')->value('id'),
            'shares_product_id' => DB::table('share_products')->where('code', 'SC')->value('id'),
            'loans_product_id' => DB::table('loan_products')->where('code', 'LN-IMP')->value('id'),
            'opening_equity_account_id' => DB::table('chart_of_accounts')->where('code', '3004')->value('id'),
        ];
        $imports = app(PilotImportService::class);
        $report = $imports->preview($file, $ids, date('Y-m-d'));
        $this->assertSame([], $report['errors']);
        $batch = $imports->stage($file, $ids, $admin->id, date('Y-m-d'));
        $result = $imports->commit($batch->token, $admin->id);
        $this->assertSame(3, $result['created_members']);
        $this->assertSame(3, $result['member_logins_created']);

        $members = DB::table('members')->orderBy('id')->get();
        $this->assertCount(3, $members);
        $emails = ['amina@example.test', 'brian@example.test', 'cecilia@example.test'];
        foreach ($members as $index => $member) {
            $this->assertSame($member->membership_number, $member->import_membership_number);
            $response = $this->post('/login', ['email' => $member->import_membership_number, 'password' => $member->import_membership_number]);
            $response->assertRedirect(route('member.credentials.show'));
            $response = $this->post(route('member.credentials.update'), [
                'email' => $emails[$index], 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password',
            ]);
            $response->assertRedirect(route('member.kyc.show'));
            $this->assertDatabaseHas('users', ['id' => $member->user_id, 'email' => $emails[$index], 'member_login_setup_required' => 0]);
            $this->assertDatabaseHas('members', ['id' => $member->id, 'email' => $emails[$index], 'kyc_status' => 'pending']);
            $this->assertDatabaseHas('member_applications', ['id' => $member->member_application_id, 'email' => $emails[$index]]);
            $this->get(route('member.kyc.show'))->assertOk();
            $this->post(route('logout'));
        }
    }
}
