<?php

namespace Tests\Feature;

use App\Models\LoanApplications\{Loan, LoanApplication, LoanProduct, LoanDisbursement, LoanRepayment};
use App\Models\Memberships\{Member, MemberApplication};
use App\Models\Shares\{ShareProduct, ShareAccount, ShareTransaction};
use App\Models\User;
use Illuminate\Support\Facades\{Artisan, DB, Hash};
use Tests\TestCase;

/** Real HTTP workflow; business failures are collected without repairing application data. */
class SaccoLifecycleAuditTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $createdDatabase = false;
    private $officer;
    private $findings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue($this->app->environment('testing'));
        // The production migrations use MySQL-specific DDL. Never migrate the configured DB.
        $this->originalConnection = DB::getDefaultConnection();
        $connection = config('database.connections.' . $this->originalConnection);
        $this->assertSame('mysql', $connection['driver'], 'Use MySQL/MariaDB with CREATE/DROP DATABASE privileges.');
        // Read only role reference data from the current DB; never copy users or assignments.
        $rolesTable = config('permission.table_names.roles', 'roles');
        $roles = DB::connection($this->originalConnection)->table($rolesTable)
            ->get()->map(function ($role) { return (array) $role; })->all();
        $this->assertNotEmpty($roles, 'The current database must contain roles for this audit.');
        $this->database = 'sacco_audit_' . bin2hex(random_bytes(8));
        DB::connection($this->originalConnection)->statement("CREATE DATABASE `{$this->database}`");
        $this->createdDatabase = true;
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config(['database.connections.sacco_audit' => $connection, 'database.default' => 'sacco_audit',
            'session.driver' => 'array', 'cache.default' => 'array', 'mail.default' => 'array']);
        DB::setDefaultConnection('sacco_audit');
        $this->assertSame($this->database, DB::connection()->getDatabaseName());
        $result = Artisan::call('migrate', ['--database' => 'sacco_audit', '--force' => true]);
        $this->assertSame(0, $result, Artisan::output());
        // Keep state between lifecycle stages; roll back only after the whole test.
        DB::beginTransaction();
        DB::connection('sacco_audit')->table($rolesTable)->insert($roles);
        $this->assertTrue(DB::connection('sacco_audit')->table($rolesTable)
            ->where('key_name', 'member')->exists(), 'The current database must contain the member role.');
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->officer = User::create(['name' => 'QA Officer', 'email' => 'officer@example.test',
            'password' => 'Officer-password-123']);
        $adminRole = DB::table('roles')->where('key_name', 'sacco_admin')->orWhere('name', 'sacco_admin')->first();
        if (!$adminRole) {
            $roleId = DB::table('roles')->insertGetId(['name' => 'sacco_admin', 'guard_name' => 'web',
                'key_name' => 'sacco_admin', 'description' => 'Test administrator', 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now()]);
            $adminRole = DB::table('roles')->find($roleId);
        }
        if ($adminRole) {
            DB::table('model_has_roles')->insert(['role_id' => $adminRole->id,
                'model_type' => User::class, 'model_id' => $this->officer->id]);
        }
    }

    protected function tearDown(): void
    {
        try {
            if ($this->createdDatabase) {
                if (DB::getDefaultConnection() === 'sacco_audit') {
                    while (DB::connection()->transactionLevel() > 0) {
                        DB::rollBack();
                    }
                }
                DB::purge('sacco_audit');
                DB::setDefaultConnection($this->originalConnection);
                if (preg_match('/^sacco_audit_[a-f0-9]{16}$/D', $this->database)) {
                    DB::connection($this->originalConnection)->statement("DROP DATABASE `{$this->database}`");
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    private function success($response): void
    {
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
    }

    private function expectValue($expected, $actual, string $label): void
    {
        if ($expected !== $actual) {
            $this->findings[] = $label . ': expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true);
        }
    }

    private function money(string $expected, $actual, string $label): void
    {
        $this->expectValue($expected, number_format((float) $actual, 2, '.', ''), $label);
    }

    private function registerMember(): Member
    {
        $this->actingAs($this->officer);
        $this->success($this->post(route('memberships.store'), [
            'submission_action' => 'submit', 'first_name' => 'Lifecycle', 'last_name' => 'Member',
            'date_of_birth' => '1990-01-01', 'place_of_birth' => 'Nairobi', 'national_id' => 'QA123456',
            'phone' => '0700000001', 'email' => 'member@example.test', 'residential_address' => 'Nairobi',
            'next_of_kin_name' => 'QA Kin', 'next_of_kin_id' => 'QA654321',
            'next_of_kin_relationship' => 'sibling', 'monthly_contribution' => '1000.00',
            'contribution_start_date' => today()->toDateString(), 'agreed_to_terms' => 1,
        ]));
        $application = MemberApplication::sole();
        $this->assertSame('pending', $application->status);
        $this->success($this->patch(route('memberships.review', $application)));
        $this->success($this->patch(route('memberships.approve', $application)));
        $this->assertSame('approved', $application->fresh()->status);
        $member = Member::sole();
        $this->assertSame((int) $application->id, (int) $member->member_application_id);
        $this->assertSame('active', $member->status);
        $this->get(route('memberships.show', $application))->assertOk();
        return $member;
    }

    public function test_membership_approval_provisions_a_member_login(): void
    {
        $member = $this->registerMember();
        $this->assertDatabaseHas('users', ['email' => $member->email],
            'sacco_audit');
        $this->assertEquals(User::where('email', $member->email)->sole()->id, $member->user_id);
    }

    public function paymentMethods(): array
    {
        return ['internal payment' => ['account_credit', false], 'bank settlement' => ['bank_transfer', false],
            'reversal before repayment' => ['account_credit', true]];
    }

    /** @dataProvider paymentMethods */
    public function test_registration_shares_approval_disbursement_and_repayment(string $paymentMethod, bool $reverse): void
    {
        // 1. Exercise the existing public registration and real login endpoints.
        $password = 'Lifecycle-password-123';
        $this->success($this->post(route('register'), ['name' => 'Lifecycle Member',
            'email' => 'member@example.test', 'password' => $password, 'password_confirmation' => $password]));
        $user = User::where('email', 'member@example.test')->sole();
        $this->expectValue(true, Hash::check($password, $user->password), 'Registration password is usable');
        $this->post(route('logout'))->assertRedirect();
        $login = $this->postJson(route('login'), ['email' => $user->email, 'password' => $password]);
        $this->expectValue(204, $login->getStatusCode(), 'Real member login');
        $this->expectValue(true, $user->fresh()->last_login_at !== null, 'last_login_at recorded');
        // Staff-assisted MVP endpoints; actingAs is not evidence that member login works.
        $member = $this->registerMember();

        $this->assertSame(1, User::where('email', $user->email)->count());
        $this->assertEquals($user->id, $member->user_id);
        $this->assertSame($user->password, $user->fresh()->password);

        // Reference configuration only: all member/financial mutations below use HTTP.
        $accountId = DB::table('chart_of_accounts')->insertGetId(['account_code' => 'QA100',
            'account_name' => 'QA control', 'parent_id' => 0, 'account_type' => 'asset', 'normal_balance' => 'debit']);
        $shares = ShareProduct::create(['code' => 'QA-SH', 'name' => 'QA Shares', 'unit_value' => 100,
            'share_capital_account_id' => $accountId, 'share_premium_account_id' => $accountId]);
        $product = LoanProduct::create(['code' => 'QA-LN', 'name' => 'QA Loan',
            'minimum_amount' => 1000, 'maximum_amount' => 100000, 'maximum_repayment_months' => 12,
            'interest_rate' => 12, 'interest_method' => 'flat_rate', 'interest_frequency' => 'annual',
            'requires_guarantors' => false, 'minimum_share_contribution' => 10000,
            'loan_principal_account_id' => $accountId, 'interest_receivable_account_id' => $accountId,
            'interest_income_account_id' => $accountId, 'penalty_receivable_account_id' => $accountId,
            'penalty_income_account_id' => $accountId, 'processing_fee_income_account_id' => $accountId]);

        // 2. 500 units x KES 100, confirmed through the actual posting workflow.
        $this->success($this->post(route('share_accounts.store'), ['member_id' => $member->id,
            'share_product_id' => $shares->id, 'opened_date' => today()->toDateString()]));
        $account = ShareAccount::sole();
        $this->success($this->post(route('share_transactions.store'), ['share_account_id' => $account->id,
            'transaction_type' => 'purchase', 'units' => 500, 'transaction_date' => today()->toDateString(),
            'value_date' => today()->toDateString(), 'payment_method' => 'cash', 'receipt_number' => 'QA-SH-1']));
        $transaction = ShareTransaction::sole();
        $this->assertSame('pending', $transaction->status);
        $this->assertEquals(0, $account->fresh()->share_balance);
        $this->success($this->post(route('share_transactions.confirm', $transaction)));
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertEquals(500, $account->fresh()->total_units);
        $this->assertEquals(50000, $account->fresh()->share_balance);
        $this->assertEquals(50000, $account->fresh()->available_amount);
        $this->patchJson(route('share_accounts.update', $account), ['status' => 'closed'])->assertStatus(422);
        $this->postJson(route('share_transactions.store'), ['unit_value' => 1])->assertStatus(422);
        $this->success($this->post(route('share_accounts.transition', $account), ['action' => 'freeze']));
        $this->assertSame('frozen', $account->fresh()->status);
        $this->postJson(route('share_accounts.transition', $account), ['action' => 'freeze'])->assertStatus(422);
        $this->success($this->post(route('share_accounts.transition', $account), ['action' => 'unfreeze']));
        $this->postJson(route('share_accounts.close', $account), ['closure_reason' => 'Has funds'])->assertStatus(422);
        $this->assertSame('active', $account->fresh()->status);


        // 3. Approve KES 12,000 for 12 months at 12% annual flat interest.
        $this->success($this->post(route('loan_applications.store'), ['submission_action' => 'submit',
            'member_id' => $member->id, 'loan_product_id' => $product->id, 'amount_requested' => 12000,
            'repayment_period_months' => 12, 'payment_mode' => 'cash', 'loan_purpose' => 'School fees']));
        $application = LoanApplication::sole();
        $this->assertSame('submitted', $application->status);
        // Direct approval from submitted is supported; review is an optional separate workflow.
        $this->success($this->patch(route('loan_applications.approve', $application),
            ['approved_amount' => 12000, 'repayment_period_months' => 12]));
        $this->assertSame('approved', $application->fresh()->status);
        $loan = Loan::sole();
        $this->assertEquals($member->id, $loan->member_id);
        $this->assertEquals(12000, $loan->approved_amount);
        $this->assertEquals(1120, $loan->monthly_installment);
        $this->assertDatabaseCount('loan_approvals', 1);

        // 4. Approval creates the approved record; confirmation processes that same record.
        $disbursement = LoanDisbursement::where('loan_id', $loan->id)->sole();
        $this->assertDatabaseCount('loan_disbursements', 1);
        $this->assertSame('approved', $disbursement->status);
        $this->assertEquals($application->id, $disbursement->loan_application_id);
        $this->assertNull($disbursement->processed_at);
        $this->assertEquals(0, $loan->fresh()->amount_disbursed);
        $this->postJson(route('loan_repayments.store'), ['loan_id' => $loan->id,
            'member_id' => $member->id, 'payment_date' => today()->toDateString(),
            'amount_paid' => 1120, 'payment_method' => 'cash'])->assertStatus(422);
        $this->assertDatabaseCount('loan_repayments', 0);
        $this->postJson(route('loan_disbursements.store'), [])->assertStatus(405);
        $this->postJson(route('loan_disbursements.approve', $disbursement))->assertStatus(405);
        $this->postJson(route('loan_disbursements.submit', $disbursement))->assertStatus(405);
        $this->get(route('loan_disbursements.create'))->assertStatus(405);
        $this->patchJson(route('loan_disbursements.update', $disbursement), ['gross_amount' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('gross_amount');
        $this->get(route('loan_disbursements.edit', $disbursement))->assertOk()
            ->assertDontSee('name="gross_amount"', false);
        $scheduleId = DB::table('loan_repayment_schedules')->where('loan_id', $loan->id)->value('id');
        $this->postJson(route('loan_repayment_schedules.store'), [])->assertStatus(405);
        $this->patchJson(route('loan_repayment_schedules.update', $scheduleId), [])->assertStatus(405);
        $this->deleteJson(route('loan_repayment_schedules.destroy', $scheduleId))->assertStatus(405);
        $this->get(route('loan_repayment_schedules.edit', $scheduleId))->assertStatus(405);
        $this->get(route('loan_repayment_schedules.show', $scheduleId))->assertOk();
        $this->success($this->patch(route('loan_disbursements.update', $disbursement), [
            'disbursement_method' => $paymentMethod, 'disbursement_date' => today()->toDateString(),
            'transaction_reference' => 'QA-PAYMENT-1', 'bank_name' => 'Test Bank']));
        $disbursement->refresh();

        $expectedStatus = in_array($disbursement->disbursement_method, ['bank_transfer', 'mobile_money'])
            ? 'processing' : 'processed';
        $this->success($this->post(route('loan_disbursements.process', $disbursement)));
        $this->assertSame($expectedStatus, $disbursement->fresh()->status);
        if ($expectedStatus === 'processing') {
            $this->assertEquals(0, $loan->fresh()->amount_disbursed);
            $this->success($this->post(route('loan_disbursements.process', $disbursement)));
            $this->assertSame('processed', $disbursement->fresh()->status);
        }
        $this->postJson(route('loan_disbursements.process', $disbursement))->assertStatus(422);
        $this->patchJson(route('loan_disbursements.update', $disbursement), [
            'disbursement_method' => 'cash', 'disbursement_date' => today()->toDateString()])->assertStatus(422);
        $this->assertDatabaseCount('loan_disbursements', 1);

        $this->assertNotNull($disbursement->fresh()->processed_at);
        $this->assertEquals($this->officer->id, $disbursement->fresh()->processed_by);
        $this->assertEquals(12000, $disbursement->fresh()->net_amount);
        $loan->refresh();
        $this->money('12000.00', $loan->amount_disbursed, 'Disbursed amount credited to loan');
        $this->money('12000.00', $loan->principal_balance, 'Disbursement principal balance');
        $this->money('13440.00', $loan->total_outstanding_balance, 'Disbursement total with flat interest');
        $this->expectValue(12, DB::table('loan_repayment_schedules')->where('loan_id', $loan->id)->count(), 'Generated installments');
        $beforeRepayment = (float) $loan->total_outstanding_balance;
        if ($reverse) {
            $this->success($this->post(route('loan_disbursements.reverse', $disbursement),
                ['reversal_reason' => 'Payment cancelled before any repayments']));
            $this->assertSame('reversed', $disbursement->fresh()->status);
            $this->assertSame('closed', $loan->fresh()->status);
            $this->assertEquals(0, $loan->fresh()->amount_disbursed);
            $this->assertEquals(0, $loan->fresh()->total_outstanding_balance);
            $this->assertEquals(0, DB::table('loan_repayment_schedules')->where('loan_id', $loan->id)->sum('outstanding_amount'));
            $this->postJson(route('loan_disbursements.reverse', $disbursement), ['reversal_reason' => 'Duplicate'])->assertStatus(422);
            $this->assertSame([], $this->findings);
            return;
        }


        // 5. First installment: KES 1,000 principal + KES 120 interest.
        $this->success($this->post(route('loan_repayments.store'), ['loan_id' => $loan->id,
            'member_id' => $member->id, 'payment_date' => today()->toDateString(),
            'amount_paid' => 1120, 'payment_method' => 'cash', 'receipt_number' => 'QA-RP-1']));
        $repayment = LoanRepayment::sole();
        $this->assertSame('pending', $repayment->status);
        $this->postJson(route('loan_repayments.confirm', $repayment), ['principal_amount' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('principal_amount');
        $this->assertSame('pending', $repayment->fresh()->status);

        $this->assertEquals($beforeRepayment, $loan->fresh()->total_outstanding_balance);
        $this->success($this->post(route('loan_repayments.confirm', $repayment)));
        $repayment->refresh();
        $loan->refresh();
        $this->assertSame('confirmed', $repayment->status);
        $this->money('1000.00', $repayment->principal_amount, 'Repayment principal allocation');
        $this->money('120.00', $repayment->interest_amount, 'Repayment interest allocation');
        $this->money('0.00', $repayment->unallocated_amount, 'Unallocated repayment');
        $this->money('11000.00', $loan->principal_balance, 'Remaining principal');
        $this->money('12320.00', $loan->total_outstanding_balance, 'Remaining total balance');
        $this->money('1120.00', $beforeRepayment - (float) $loan->total_outstanding_balance, 'Balance reduction');
        $this->money('1120.00', $loan->total_paid, 'Loan paid total');
        $this->money('1120.00', DB::table('loan_repayment_allocations')->where('loan_repayment_id', $repayment->id)->sum('total_allocated'), 'Allocation reconciliation');
        $this->expectValue('active', $loan->status, 'Partially repaid loan remains active');
        $this->postJson(route('loan_disbursements.reverse', $disbursement), ['reversal_reason' => 'Invalid reversal after repayment'])
            ->assertStatus(422);
        $this->assertSame('processed', $disbursement->fresh()->status);

        $this->postJson(route('loan_repayments.confirm', $repayment))->assertStatus(422);
        $this->assertEquals($loan->total_outstanding_balance, $loan->fresh()->total_outstanding_balance);
        $this->assertEquals(50000, $account->fresh()->share_balance);

        $this->assertSame([], $this->findings, "Lifecycle audit failed:\n- " . implode("\n- ", $this->findings));
    }
}
