<?php

namespace Tests\Feature;

use App\Models\Memberships\{Member, MemberApplication};
use App\Models\User;
use Illuminate\Support\Facades\{Artisan, DB};
use App\Models\Savings\{SavingsProduct, SavingsAccount, SavingsTransaction, SavingsWithdrawal};
use Tests\TestCase;

/** Phase 2: real savings endpoints on an isolated MySQL database per test. */
class SavingsPhaseTwoAuditTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $createdDatabase = false;
    private $officer;

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
            \Carbon\Carbon::setTestNow();
            parent::tearDown();
        }
    }

    private function success($response): void
    {
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
    }

    private function openAccount(): SavingsAccount
    {
        $this->actingAs($this->officer);
        // Phase 1 membership is a prerequisite fixture, not a savings mutation.
        $application = MemberApplication::create(['application_number' => 'P2-MEMBER',
            'first_name' => 'Savings', 'last_name' => 'Member', 'email' => 'savings@example.test',
            'national_id' => 'P2-ID', 'status' => 'approved', 'date_of_birth' => '1990-01-01',
            'place_of_birth' => 'Nairobi', 'phone' => '0700000002', 'residential_address' => 'Nairobi',
            'next_of_kin_name' => 'QA Kin', 'next_of_kin_id' => 'P2-KIN', 'next_of_kin_relationship' => 'sibling',
            'monthly_contribution' => 1000, 'contribution_start_date' => today()->toDateString()]);
        $member = Member::create(['member_application_id' => $application->id,
            'membership_number' => 'P2-MEMBER', 'first_name' => 'Savings', 'last_name' => 'Member',
            'email' => 'savings@example.test', 'national_id' => 'P2-ID', 'phone' => '0700000002',
            'date_of_birth' => '1990-01-01', 'admission_date' => today()->toDateString(),
            'approved_by' => $this->officer->id, 'status' => 'active', 'is_active' => true]);
        $ids = [];
        foreach (['liability', 'expense', 'income'] as $type) {
            $ids[] = DB::table('chart_of_accounts')->insertGetId(['account_code' => 'P2-' . $type,
                'account_name' => 'Savings ' . $type, 'parent_id' => 0, 'account_type' => $type,
                'normal_balance' => $type === 'expense' ? 'debit' : 'credit']);
        }
        $payload = ['code' => 'P2-SAVE', 'name' => 'Phase Two Savings', 'product_type' => 'voluntary',
            'savings_control_account_id' => $ids[0], 'interest_expense_account_id' => $ids[1],
            'fee_income_account_id' => $ids[2], 'interest_rate' => 0, 'interest_frequency' => 'annual',
            'interest_calculation_method' => 'simple', 'minimum_balance' => 1000,
            'maximum_balance' => 20000, 'minimum_monthly_contribution' => 0,
            'allows_withdrawals' => 1, 'withdrawal_notice_days' => 0, 'can_secure_loan' => 1,
            'is_active' => 1, 'allows_premature_withdrawal' => 0, 'auto_rollover' => 0];
        $this->success($this->post(route('savings_products.store'), $payload));
        $product = SavingsProduct::sole();
        $this->assertEquals(1000, $product->minimum_balance);
        $this->postJson(route('savings_products.store'), $payload)->assertStatus(422);
        $this->assertDatabaseCount('savings_products', 1);
        $accountPayload = ['member_id' => $member->id, 'savings_product_id' => $product->id,
            'opened_date' => today()->toDateString()];
        $this->success($this->post(route('savings_accounts.store'), $accountPayload));
        $this->postJson(route('savings_accounts.store'), $accountPayload)->assertStatus(422);
        $this->assertDatabaseCount('savings_accounts', 1);
        $account = SavingsAccount::sole();
        $this->assertEquals($member->id, $account->member_id);
        $this->assertSame('active', $account->status);
        $this->balance($account, 0);
        return $account;
    }

    private function balance(SavingsAccount $account, float $expected): void
    {
        $account->refresh();
        $this->assertEquals($expected, $account->ledger_balance, 'Ledger balance');
        $this->assertEquals($expected, $account->available_balance, 'Available balance');
        $this->assertEquals(0, $account->held_balance);
    }

    private function deposit(SavingsAccount $account, int $amount = 10000): SavingsTransaction
    {
        $this->success($this->post(route('savings_transactions.store'), [
            'savings_account_id' => $account->id, 'transaction_type' => 'deposit', 'amount' => $amount,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString(),
            'payment_method' => 'cash', 'receipt_number' => 'P2-DEPOSIT']));
        $transaction = SavingsTransaction::where('receipt_number', 'P2-DEPOSIT')->sole();
        $this->assertSame('pending', $transaction->status);
        $this->balance($account, 0);
        $this->success($this->post(route('savings_transactions.confirm', $transaction)));
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertEquals($amount, $transaction->fresh()->running_balance);
        $this->balance($account, $amount);
        return $transaction;
    }

    private function withdrawalPayload(SavingsAccount $account, float $amount): array
    {
        return ['savings_account_id' => $account->id, 'amount' => $amount,
            'requested_date' => today()->toDateString(), 'payment_method' => 'cash', 'reason' => 'Phase 2 audit'];
    }

    private function requestWithdrawal(SavingsAccount $account, float $amount = 3000): SavingsWithdrawal
    {
        $this->success($this->post(route('savings_withdrawals.store'), $this->withdrawalPayload($account, $amount)));
        $withdrawal = SavingsWithdrawal::latest('id')->firstOrFail();
        $this->assertSame('pending', $withdrawal->status);
        $this->assertDatabaseHas('savings_transactions', ['id' => $withdrawal->savings_transaction_id,
            'savings_account_id' => $account->id, 'transaction_type' => 'withdrawal',
            'direction' => 'debit', 'amount' => $amount, 'status' => 'pending']);
        return $withdrawal;
    }

    public function test_product_account_deposit_withdrawal_approval_and_payment(): void
    {
        $account = $this->openAccount();
        $deposit = $this->deposit($account);
        $this->postJson(route('savings_transactions.confirm', $deposit))->assertStatus(422);
        $this->balance($account, 10000);
        $withdrawal = $this->requestWithdrawal($account);
        $this->balance($account, 10000);
        $this->postJson(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'P2-EARLY'])->assertStatus(422);
        $this->balance($account, 10000);
        $this->success($this->post(route('savings_withdrawals.approve', $withdrawal)));
        $this->assertSame('approved', $withdrawal->fresh()->status);
        $this->assertEquals($this->officer->id, $withdrawal->fresh()->approved_by);
        $this->assertNotNull($withdrawal->fresh()->approved_at);
        $confirmed = SavingsTransaction::findOrFail($withdrawal->savings_transaction_id);
        $this->assertEquals($this->officer->id, $confirmed->confirmed_by);
        $this->assertNotNull($confirmed->confirmed_at);
        $this->assertEquals((string) $withdrawal->fresh()->approved_at, (string) $confirmed->confirmed_at);
        $this->balance($account, 7000);
        $this->assertDatabaseHas('savings_transactions', ['id' => $withdrawal->savings_transaction_id,
            'status' => 'confirmed', 'running_balance' => 7000]);
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        $this->postJson(route('savings_withdrawals.pay', $withdrawal), [])->assertStatus(422);
        $this->success($this->post(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'P2-CASH']));
        $this->assertSame('paid', $withdrawal->fresh()->status);
        $this->assertEquals($this->officer->id, $withdrawal->fresh()->paid_by);
        $this->assertNotNull($withdrawal->fresh()->paid_at);
        $this->assertSame('P2-CASH', $withdrawal->fresh()->payment_reference);
        $this->assertEquals((string) $confirmed->confirmed_at, (string) $confirmed->fresh()->confirmed_at);
        $this->assertEquals($confirmed->confirmed_by, $confirmed->fresh()->confirmed_by);
        $this->balance($account, 7000);
        $this->postJson(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'P2-REPLAY'])->assertStatus(422);
        $this->balance($account, 7000);
        $this->assertDatabaseCount('savings_transactions', 2);
        $this->assertEquals(7000, SavingsTransaction::where('direction', 'credit')->where('status', 'confirmed')->sum('amount')
            - SavingsTransaction::where('direction', 'debit')->where('status', 'confirmed')->sum('amount'));
        foreach (['savings_products' => $account->savings_product_id, 'savings_accounts' => $account->id,
            'savings_transactions' => $deposit->id, 'savings_withdrawals' => $withdrawal->id] as $resource => $id) {
            $this->get(route($resource . '.index'))->assertOk();
            $this->get(route($resource . '.show', $id))->assertOk();
        }
    }

    public function test_insufficient_funds_and_rejection_leave_balances_unchanged(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $this->postJson(route('savings_withdrawals.store'), $this->withdrawalPayload($account, 11000))->assertStatus(422);
        $this->assertDatabaseCount('savings_withdrawals', 0);
        $this->assertDatabaseCount('savings_transactions', 1);
        $withdrawal = $this->requestWithdrawal($account);
        $this->success($this->post(route('savings_withdrawals.reject', $withdrawal), ['decision_reason' => 'Member cancelled']));
        $this->assertSame('rejected', $withdrawal->fresh()->status);
        $this->assertDatabaseHas('savings_transactions', ['id' => $withdrawal->savings_transaction_id, 'status' => 'reversed']);
        $this->postJson(route('savings_transactions.confirm', $withdrawal->savings_transaction_id))->assertStatus(422);
        $this->balance($account, 10000);
    }

    public function test_withdrawal_cannot_bypass_approval_using_transaction_confirmation(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $withdrawal = $this->requestWithdrawal($account);
        $response = $this->postJson(route('savings_transactions.confirm', $withdrawal->savings_transaction_id));
        $this->assertSame([422, '10000.00', 'pending', 'pending'], [$response->getStatusCode(),
            number_format($account->fresh()->ledger_balance, 2, '.', ''), $withdrawal->fresh()->status,
            SavingsTransaction::findOrFail($withdrawal->savings_transaction_id)->status],
            'Direct confirmation must reject the withdrawal and leave both records and balances unchanged.');
    }

    public function test_withdrawal_cannot_reduce_balance_below_product_minimum(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        // Request may be accepted for review; approval must enforce the product minimum.
        $withdrawal = $this->requestWithdrawal($account, 9500);
        $response = $this->postJson(route('savings_withdrawals.approve', $withdrawal));
        $this->assertSame([422, '10000.00', 'pending'], [$response->getStatusCode(),
            number_format($account->fresh()->ledger_balance, 2, '.', ''), $withdrawal->fresh()->status],
            'KES 9,500 withdrawal must not breach the KES 1,000 minimum on a KES 10,000 account.');
    }

    public function test_minimum_balance_boundary_and_direct_withdrawal_creation(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $this->postJson(route('savings_transactions.store'), [
            'savings_account_id' => $account->id, 'transaction_type' => 'withdrawal', 'amount' => 100,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()])->assertStatus(422);
        $this->assertDatabaseCount('savings_transactions', 1);
        $tooMuch = $this->requestWithdrawal($account, 9000.01);
        $this->postJson(route('savings_withdrawals.approve', $tooMuch))->assertStatus(422);
        $this->assertSame('pending', $tooMuch->fresh()->status);
        $this->assertDatabaseHas('savings_transactions', ['id' => $tooMuch->savings_transaction_id, 'status' => 'pending']);
        $this->balance($account, 10000);
        $exact = $this->requestWithdrawal($account, 9000);
        $this->success($this->post(route('savings_withdrawals.approve', $exact)));
        $this->balance($account, 1000);
    }

    public function test_transfer_debit_respects_minimum_and_held_balance(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        // Existing hold is a prerequisite fixture; confirmation must use ledger minus holds.
        $account->update(['held_balance' => 2000, 'available_balance' => 8000]);
        $withdrawal = $this->requestWithdrawal($account, 7000.01);
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        $this->assertEquals(10000, $account->fresh()->ledger_balance);
        foreach ([7000.01, 7000] as $amount) {
            $this->success($this->post(route('savings_transactions.store'), [
                'savings_account_id' => $account->id, 'transaction_type' => 'transfer_out', 'amount' => $amount,
                'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()]));
            $transaction = SavingsTransaction::latest('id')->firstOrFail();
            $response = $this->postJson(route('savings_transactions.confirm', $transaction));
            if ($amount > 7000) {
                $response->assertStatus(422);
                $this->assertSame('pending', $transaction->fresh()->status);
                $this->assertEquals(8000, $account->fresh()->available_balance);
            } else {
                $this->success($response);
                $this->assertSame('confirmed', $transaction->fresh()->status);
                $this->assertEquals(3000, $account->fresh()->ledger_balance);
                $this->assertEquals(1000, $account->fresh()->available_balance);
                $this->assertEquals(2000, $account->fresh()->held_balance);
            }
        }
    }

    public function test_maximum_balance_rejection_rolls_back_confirmation(): void
    {
        $account = $this->openAccount();
        $this->success($this->post(route('savings_transactions.store'), [
            'savings_account_id' => $account->id, 'transaction_type' => 'deposit', 'amount' => 21000,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()]));
        $transaction = SavingsTransaction::sole();
        $this->postJson(route('savings_transactions.confirm', $transaction))->assertStatus(422);
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->balance($account, 0);
    }

    public function test_product_change_before_approval_is_rechecked_but_does_not_strand_approved_payment(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $withdrawal = $this->requestWithdrawal($account);
        $product = SavingsProduct::findOrFail($account->savings_product_id);
        $product->update(['allows_withdrawals' => false]);
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        $this->balance($account, 10000);
        $this->assertSame('pending', $withdrawal->fresh()->status);
        $this->assertDatabaseHas('savings_transactions', ['id' => $withdrawal->savings_transaction_id,
            'status' => 'pending', 'confirmed_by' => null, 'confirmed_at' => null]);
        // Retirement prevents enrollment, but does not freeze this existing account.
        $product->update(['allows_withdrawals' => true, 'is_active' => false]);
        $newApplication = MemberApplication::create([
            'application_number' => 'P2-RET-' . $product->id,
            'first_name' => 'New', 'last_name' => 'Applicant', 'date_of_birth' => '1991-01-01',
            'place_of_birth' => 'Nairobi', 'national_id' => 'P2-RET-' . $product->id,
            'phone' => '0700000099', 'email' => 'retired-' . $product->id . '@example.test',
            'residential_address' => 'Nairobi', 'next_of_kin_name' => 'Kin', 'next_of_kin_id' => 'P2-RET-KIN-' . $product->id,
            'next_of_kin_relationship' => 'sibling', 'monthly_contribution' => 1000,
            'contribution_start_date' => today()->toDateString(), 'status' => 'approved',
        ]);
        $newMember = Member::create([
            'member_application_id' => $newApplication->id, 'membership_number' => 'P2-RET-' . $product->id,
            'first_name' => 'New', 'last_name' => 'Applicant', 'date_of_birth' => '1991-01-01',
            'place_of_birth' => 'Nairobi', 'national_id' => 'P2-RET-M-' . $product->id,
            'phone' => '0700000099', 'email' => 'retired-member-' . $product->id . '@example.test',
            'residential_address' => 'Nairobi', 'admission_date' => today()->toDateString(),
            'approved_by' => $this->officer->id, 'status' => 'active', 'is_active' => true,
        ]);
        $this->postJson(route('savings_accounts.store'), [
            'member_id' => $newMember->id, 'savings_product_id' => $product->id,
            'opened_date' => today()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('savings_product_id');
        $this->assertDatabaseCount('savings_accounts', 1);
        $this->success($this->post(route('savings_withdrawals.approve', $withdrawal)));
        $product->update(['allows_withdrawals' => false]);
        $this->success($this->post(route('savings_withdrawals.pay', $withdrawal), ['payment_reference' => 'P2-APPROVED']));
        $this->balance($account, 7000);
        $this->assertSame('paid', $withdrawal->fresh()->status);
    }

    public function test_notice_period_uses_submission_timestamp_and_allows_exact_boundary(): void
    {
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-15 10:00:00'));
        $account = $this->openAccount();
        $this->deposit($account);
        SavingsProduct::findOrFail($account->savings_product_id)->update(['withdrawal_notice_days' => 2]);
        $payload = $this->withdrawalPayload($account, 3000);
        $payload['requested_date'] = '2026-01-01';
        $this->success($this->post(route('savings_withdrawals.store'), $payload));
        $withdrawal = SavingsWithdrawal::sole();
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-17 09:59:59'));
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        $this->balance($account, 10000);
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-17 10:00:00'));
        $this->success($this->post(route('savings_withdrawals.approve', $withdrawal)));
        $this->balance($account, 7000);
    }

    public function test_transfer_rechecks_withdrawal_permission_and_notice_without_restricting_fees(): void
    {
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-15 10:00:00'));
        $account = $this->openAccount();
        $this->deposit($account);
        $payload = ['savings_account_id' => $account->id, 'transaction_type' => 'transfer_out', 'amount' => 1000,
            'transaction_date' => '2026-01-01', 'value_date' => '2026-01-01'];
        $this->success($this->post(route('savings_transactions.store'), $payload));
        $transfer = SavingsTransaction::latest('id')->firstOrFail();
        $product = SavingsProduct::findOrFail($account->savings_product_id);
        $product->update(['allows_withdrawals' => false]);
        $this->postJson(route('savings_transactions.confirm', $transfer))->assertStatus(422);
        $payload['transaction_type'] = 'fee';
        $this->success($this->post(route('savings_transactions.store'), $payload));
        $fee = SavingsTransaction::latest('id')->firstOrFail();
        $this->success($this->post(route('savings_transactions.confirm', $fee)));
        $product->update(['allows_withdrawals' => true, 'withdrawal_notice_days' => 1]);
        $this->postJson(route('savings_transactions.confirm', $transfer))->assertStatus(422);
        $this->assertSame('pending', $transfer->fresh()->status);
        $this->balance($account, 9000);
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-16 10:00:00'));
        $this->success($this->post(route('savings_transactions.confirm', $transfer)));
        $this->balance($account, 8000);
    }

    public function test_fixed_deposit_opening_switching_and_withdrawal_are_not_available(): void
    {
        $account = $this->openAccount();
        $product = SavingsProduct::findOrFail($account->savings_product_id)->replicate();
        $product->fill(['code' => 'P2-FIXED', 'name' => 'Fixed Deposit', 'product_type' => 'fixed_deposit'])->save();
        $this->postJson(route('savings_accounts.store'), ['member_id' => $account->member_id,
            'savings_product_id' => $product->id, 'opened_date' => today()->toDateString()])->assertStatus(422);
        $this->patchJson(route('savings_accounts.update', $account), [
            'savings_product_id' => $product->id])->assertStatus(422);
        $this->assertDatabaseCount('savings_accounts', 1);
        $this->deposit($account);
        $withdrawal = $this->requestWithdrawal($account);
        // Simulate an existing product changed after the withdrawal was requested.
        SavingsProduct::findOrFail($account->savings_product_id)->update(['product_type' => 'fixed_deposit']);
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertStatus(422);
        $this->balance($account, 10000);
    }

    public function test_frozen_account_rejects_deposits_and_withdrawals(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $this->success($this->post(route('savings_accounts.transition', $account), ['action' => 'freeze']));
        $this->assertSame('frozen', $account->fresh()->status);
        $this->postJson(route('savings_transactions.store'), [
            'savings_account_id' => $account->id, 'transaction_type' => 'deposit', 'amount' => 100,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()])->assertStatus(422);
        $this->postJson(route('savings_withdrawals.store'), $this->withdrawalPayload($account, 1000))->assertStatus(422);
        $this->assertDatabaseCount('savings_transactions', 1);
        $this->assertDatabaseCount('savings_withdrawals', 0);
        $this->balance($account, 10000);
    }

    public function test_generated_fields_and_account_transitions_are_protected(): void
    {
        $account = $this->openAccount();
        $this->patchJson(route('savings_accounts.update', $account), ['ledger_balance' => 999])
            ->assertStatus(422)->assertJsonValidationErrors('ledger_balance');
        $this->patchJson(route('savings_accounts.update', $account), ['status' => 'closed'])
            ->assertStatus(422)->assertJsonValidationErrors('status');
        $this->postJson(route('savings_transactions.store'), ['confirmed_by' => $this->officer->id])
            ->assertStatus(422)->assertJsonValidationErrors('confirmed_by');
        $this->success($this->post(route('savings_accounts.transition', $account), ['action' => 'freeze']));
        $this->assertSame('frozen', $account->fresh()->status);
        $this->postJson(route('savings_accounts.transition', $account), ['action' => 'freeze'])->assertStatus(422);
        $this->success($this->post(route('savings_accounts.transition', $account), ['action' => 'unfreeze']));
        $this->deposit($account);
        $this->postJson(route('savings_accounts.transition', $account), ['action' => 'close', 'closure_reason' => 'Test'])
            ->assertStatus(422);
        $this->assertSame('active', $account->fresh()->status);
        $this->assertEquals(10000, $account->fresh()->ledger_balance);
    }

    public function test_member_cannot_post_to_another_members_savings_or_choose_adjustments(): void
    {
        $account = $this->openAccount();
        $user = $this->rbacMember();
        foreach (['create-savings-transaction', 'request-savings-withdrawal'] as $name) {
            $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user);
        $this->postJson(route('savings_transactions.store'), ['savings_account_id' => $account->id,
            'transaction_type' => 'deposit'])->assertForbidden();
        $this->postJson(route('savings_withdrawals.store'), ['savings_account_id' => $account->id])->assertForbidden();
        Member::findOrFail($account->member_id)->update(['user_id' => $user->id]);
        $this->postJson(route('savings_transactions.store'), ['savings_account_id' => $account->id,
            'transaction_type' => 'adjustment'])->assertForbidden();
        $this->assertDatabaseCount('savings_transactions', 0);
    }

    private function rbacMember(): User
    {
        $user = User::create(['name' => 'RBAC Member', 'email' => 'rbac-member@example.test',
            'password' => 'Member-password-123']);
        $user->assignRole('member');
        $permission = \Spatie\Permission\Models\Permission::findOrCreate('view-own-savings', 'web');
        $user->roles()->firstOrFail()->givePermissionTo($permission);
        return $user;
    }

    public function test_rbac_member_cannot_access_user_management(): void
    {
        $this->actingAs($this->rbacMember());
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.show', $this->officer))->assertForbidden();
        $this->get(route('users.edit', $this->officer))->assertForbidden();
        $this->postJson(route('users.store'), [])->assertForbidden();
        $this->patchJson(route('users.update', $this->officer), [])->assertForbidden();
        $this->deleteJson(route('users.destroy', $this->officer))->assertForbidden();
        $this->postJson(route('users.activate', $this->officer->id))->assertForbidden();
        $this->postJson(route('users.deactivate', $this->officer->id))->assertForbidden();
        $this->postJson(route('users.update_active_profile', $this->officer), [])->assertForbidden();
        $this->postJson(route('users.delete_profile_pic', $this->officer))->assertForbidden();
    }

    public function test_rbac_member_cannot_read_another_members_savings_account(): void
    {
        $account = $this->openAccount();
        $this->actingAs($this->rbacMember());
        $this->get(route('savings_accounts.show', $account))->assertForbidden();
        $this->get(route('savings_accounts.index'))->assertOk()
            ->assertViewHas('savingsAccounts', fn ($accounts) => $accounts->isEmpty());
    }

    public function test_rbac_member_can_read_own_savings_but_cannot_manage_accounts(): void
    {
        $account = $this->openAccount();
        $user = $this->rbacMember();
        Member::findOrFail($account->member_id)->update(['user_id' => $user->id]);
        $this->actingAs($user);
        $this->get(route('savings_accounts.show', $account))->assertOk();
        $this->get(route('savings_accounts.index'))->assertOk()
            ->assertViewHas('savingsAccounts', fn ($accounts) => $accounts->count() === 1
                && $accounts->first()->id === $account->id);
        $this->get(route('savings_accounts.create'))->assertForbidden();
        $this->get(route('savings_accounts.edit', $account))->assertForbidden();
        $this->postJson(route('savings_accounts.store'), [])->assertForbidden();
        $this->patchJson(route('savings_accounts.update', $account), [])->assertForbidden();
        $this->deleteJson(route('savings_accounts.destroy', $account))->assertForbidden();
        $this->assertSame('active', $account->fresh()->status);
    }

    public function test_rbac_admin_can_access_user_management_and_savings(): void
    {
        $account = $this->openAccount();
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('savings_accounts.index'))->assertOk();
        $this->get(route('savings_accounts.show', $account))->assertOk();
        $this->get(route('savings_accounts.edit', $account))->assertOk();
    }

    public function test_rbac_direct_permission_allows_withdrawal_approval(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $withdrawal = $this->requestWithdrawal($account);
        $user = $this->rbacMember();
        $permission = \Spatie\Permission\Models\Permission::findOrCreate('approve-savings-withdrawal', 'web');
        $user->givePermissionTo($permission);
        $this->assertDatabaseHas('model_has_permissions', ['permission_id' => $permission->id,
            'model_type' => $user->getMorphClass(), 'model_id' => $user->id]);
        $this->actingAs($user);
        $this->success($this->postJson(route('savings_withdrawals.approve', $withdrawal)));
        $this->assertSame('approved', $withdrawal->fresh()->status);
    }

    public function test_member_cannot_approve_a_withdrawal(): void
    {
        $account = $this->openAccount();
        $this->deposit($account);
        $withdrawal = $this->requestWithdrawal($account);
        $this->patchJson(route('savings_transactions.update', $withdrawal->savings_transaction_id), [
            'transaction_type' => 'deposit', 'amount' => 500,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()])
            ->assertStatus(422);
        $this->assertSame('withdrawal', SavingsTransaction::findOrFail($withdrawal->savings_transaction_id)->transaction_type);
        $memberUser = User::create(['name' => 'Member User', 'email' => 'member-rbac@example.test',
            'password' => 'Member-password-123']);
        $memberRole = DB::table('roles')->where('key_name', 'member')->orWhere('name', 'member')->first();
        if (!$memberRole) {
            $roleId = DB::table('roles')->insertGetId(['name' => 'member', 'guard_name' => 'web',
                'key_name' => 'member', 'description' => 'Test member', 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now()]);
            $memberRole = DB::table('roles')->find($roleId);
        }
        DB::table('model_has_roles')->insert(['role_id' => $memberRole->id,
            'model_type' => User::class, 'model_id' => $memberUser->id]);
        $this->actingAs($memberUser);
        $this->postJson(route('savings_withdrawals.approve', $withdrawal))->assertForbidden();
        $this->assertSame('pending', $withdrawal->fresh()->status);
        $this->assertSame('pending', SavingsTransaction::findOrFail($withdrawal->savings_transaction_id)->status);
        $this->assertEquals(10000, $account->fresh()->ledger_balance);
    }
}
