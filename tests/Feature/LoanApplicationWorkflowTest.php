<?php

namespace Tests\Feature;

use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanGuarantor;
use App\Models\LoanApplications\LoanProduct;
use App\Models\Memberships\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanApplicationWorkflowTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $uploadRoot;
    private $member;
    private $guarantor;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();
        // This browser-only middleware calls PHP header() directly under CLI.
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This workflow dry run uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'loan_workflow_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        $this->uploadRoot = sys_get_temp_dir() . '/' . $this->database;
        config([
            'database.connections.loan_workflow_test' => $connection,
            'database.default' => 'loan_workflow_test',
            'session.driver' => 'array', 'cache.default' => 'array',
            'filesystems.disks.private.root' => $this->uploadRoot,
        ]);
        DB::setDefaultConnection('loan_workflow_test');
        Storage::forgetDisk('private');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'loan_workflow_test', '--force' => true]), Artisan::output());

        $userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $this->actingAs(User::findOrFail($userId));
        $this->member = $this->makeMember('APPLICANT', $userId);
        $this->guarantor = $this->makeMember('GUARANTOR', $userId);
        $this->product = LoanProduct::create([
            'code' => 'TEST', 'name' => 'Test Loan', 'minimum_amount' => 1000,
            'maximum_amount' => 100000, 'maximum_repayment_months' => 24,
            'interest_rate' => 12, 'interest_method' => 'flat_rate', 'interest_frequency' => 'annual',
            'requires_guarantors' => true, 'minimum_guarantors' => 1, 'maximum_guarantors' => 2,
            'loan_principal_account_id' => 0, 'interest_receivable_account_id' => 0,
            'interest_income_account_id' => 0, 'fee_receivable_account_id' => 0,
            'penalty_income_account_id' => 0, 'processing_fee_income_account_id' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            Storage::disk('private')->deleteDirectory('loan_applications'); Storage::disk('private')->deleteDirectory('loose');
            if (is_dir($this->uploadRoot)) {
                rmdir($this->uploadRoot);
            }
            DB::purge('loan_workflow_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^loan_workflow_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    private function makeMember(string $number, int $userId): Member
    {
        $member = Member::create([
            'member_application_id' => 1, 'membership_number' => $number,
            'first_name' => $number, 'last_name' => 'Test', 'date_of_birth' => '1990-01-01',
            'national_id' => $number, 'phone' => '0700000000',
            'email' => strtolower($number) . '@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => $userId,
        ]);
        DB::table('share_accounts')->insert([
            'member_id' => $member->id, 'share_product_id' => 1, 'account_number' => $number,
            'opened_date' => '2020-01-01', 'share_balance' => 50000, 'available_amount' => 50000,
        ]);
        return $member;
    }

    private function payload(string $action = 'submit'): array
    {
        return [
            'submission_action' => $action, 'member_id' => $this->member->id,
            'loan_product_id' => $this->product->id, 'amount_requested' => 12000,
            'repayment_period_months' => 12, 'payment_mode' => 'cash', 'loan_purpose' => 'School fees',
            'guarantors' => [['member_id' => $this->guarantor->id, 'shares_offered' => 12000]],
            'witnesses' => [['name' => 'External Witness', 'national_id' => 'WITNESS']],
        ];
    }

    public function test_draft_edit_submit_review_verify_and_approve(): void
    {
        $this->get(route('loan_applications.create'))->assertOk()->assertSee('formnovalidate', false);
        $this->post(route('loan_applications.store'), ['submission_action' => 'draft'])->assertSessionHasNoErrors();
        $application = LoanApplication::firstOrFail();
        $this->assertSame('draft', $application->status);
        $this->get(route('loan_applications.show', $application))->assertOk();
        $this->get(route('loan_applications.edit', $application))->assertOk()->assertSee('name="_method" value="PATCH"', false);
        $this->get(route('loan_applications.index'))->assertOk()->assertSee($application->application_number);
        $this->patchJson(route('loan_applications.workflow', $application), ['action' => 'submit'])->assertStatus(422);

        $payload = $this->payload('draft');
        $payload['applicant_signature'] = UploadedFile::fake()->image('applicant.jpg');
        $payload['guarantors'][0]['signature'] = UploadedFile::fake()->image('guarantor.jpg');
        $payload['securities'] = [['security_type' => 'additional_collateral', 'security_name' => 'Equipment',
            'security_value' => 15000, 'supporting_document' => UploadedFile::fake()->image('equipment.jpg')]];
        $this->patch(route('loan_applications.update', $application), $payload)->assertSessionHasNoErrors()->assertRedirect(route('loan_applications.show', $application));
        $application->refresh();
        $signature = $application->applicant_signature;
        $guarantor = $application->guarantors()->firstOrFail();
        $security = $application->securities()->firstOrFail();
        $witness = $application->witnesses()->firstOrFail();
        $this->assertNull($witness->member_id);
        $this->assertSame($this->guarantor->full_name, $guarantor->guarantor_name);
        $this->assertEquals(1120, $application->monthly_installment);
        $this->assertEquals(50000, $application->total_share_contribution);

        // Preserve existing files and IDs while updating the draft a second time.
        unset($payload['applicant_signature'], $payload['guarantors'][0]['signature'], $payload['securities'][0]['supporting_document']);
        $payload['guarantors'][0]['id'] = $guarantor->id;
        $payload['securities'][0]['id'] = $security->id;
        $payload['witnesses'][0]['id'] = $witness->id;
        $this->patch(route('loan_applications.update', $application), $payload)->assertSessionHasNoErrors();
        $this->assertSame($signature, $application->fresh()->applicant_signature);
        $this->assertSame($guarantor->signature, $guarantor->fresh()->signature);
        Storage::disk('private')->assertExists($signature);
        Storage::disk('public')->assertMissing($signature);
        $this->get(route('loan_applications.edit', $application))->assertOk()->assertSee('School fees');

        $this->patch(route('loan_applications.workflow', $application), ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->patch(route('loan_applications.workflow', $application), ['action' => 'review'])->assertSessionHasNoErrors();
        $this->assertSame('under_review', $application->fresh()->status);

        $approval = ['approved_amount' => 10000, 'repayment_period_months' => 10];
        $this->patchJson(route('loan_applications.approve', $application), $approval)->assertStatus(422)->assertJsonValidationErrors('securities');
        $this->patch(route('loan_applications.verify_security', $application), ['security_id' => $security->id, 'accepted_value' => 10000])->assertSessionHasNoErrors();
        // The guarantor has not yet accepted, so approval is blocked.
        $this->patchJson(route('loan_applications.approve', $application), $approval)->assertStatus(422)->assertJsonValidationErrors('guarantors');
        $this->patch(route('loan_applications.guarantor_consent', [$application, $guarantor]), ['decision' => 'accepted'])->assertSessionHasNoErrors();
        $this->patch(route('loan_applications.approve', $application), $approval)->assertSessionHasNoErrors()->assertRedirect(route('loan_applications.show', $application));
        $this->assertSame('held', $guarantor->fresh()->status);
        $this->assertEquals(12000, $guarantor->fresh()->held_amount);
        $this->assertSame(Loan::sole()->id, $guarantor->fresh()->loan_id);
        $this->assertEquals(12000, DB::table('share_accounts')->where('member_id', $this->guarantor->id)->value('held_amount'));
        $this->assertEquals(38000, DB::table('share_accounts')->where('member_id', $this->guarantor->id)->value('available_amount'));
        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertEquals(12000, $application->amount_requested);
        $this->assertEquals(12, $application->repayment_period_months);
        $this->assertEquals(10000, $application->amount_approved);
        $this->assertEquals(10, $application->approved_repayment_months);
        $this->assertEquals(1100, $application->approved_monthly_installment);
        $this->assertSame('flat_rate', $application->approved_interest_method);
        $this->assertSame('pledged', $security->fresh()->status);
        $this->assertSame(1, DB::table('loan_approvals')->count());
        $this->get(route('loan_applications.show', $application))->assertOk()->assertSee('Approved Terms')->assertSee('10,000.00');
        $this->patchJson(route('loan_applications.approve', $application), $approval)->assertStatus(422);
        $this->patchJson(route('loan_applications.update', $application), $this->payload('draft'))->assertStatus(422);
        $this->deleteJson(route('loan_applications.destroy', $application))->assertStatus(422);
        $this->assertSame(1, DB::table('loan_approvals')->count());
    }

    public function test_direct_submission_defer_resubmit_and_reject(): void
    {
        $this->post(route('loan_applications.store'), $this->payload())->assertSessionHasNoErrors();
        $application = LoanApplication::firstOrFail();
        $this->assertSame('submitted', $application->status);
        $this->patchJson(route('loan_applications.workflow', $application), ['action' => 'defer'])->assertStatus(422);
        $this->patch(route('loan_applications.workflow', $application), ['action' => 'defer', 'note' => 'Correct purpose'])->assertSessionHasNoErrors();
        $this->get(route('loan_applications.edit', $application))->assertOk();
        $this->patch(route('loan_applications.update', $application), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNull($application->fresh()->defer_note);
        $this->patch(route('loan_applications.workflow', $application), ['action' => 'reject', 'note' => 'Not eligible'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertSame('Not eligible', $application->fresh()->rejection_note);
        $this->patchJson(route('loan_applications.workflow', $application), ['action' => 'review'])->assertStatus(422);
    }

    public function test_draft_deletion_cleans_up_children_and_uploads(): void
    {
        $payload = $this->payload('draft');
        $payload['applicant_signature'] = UploadedFile::fake()->image('signature.jpg');
        $this->post(route('loan_applications.store'), $payload)->assertSessionHasNoErrors();
        $application = LoanApplication::firstOrFail();
        $path = $application->applicant_signature;
        $this->delete(route('loan_applications.destroy', $application))->assertRedirect(route('loan_applications.index'));
        $this->assertSame(0, LoanApplication::count());
        $this->assertSame(0, DB::table('loan_guarantors')->count());
        $this->assertSame(0, DB::table('loan_witnesses')->count());
        Storage::disk('private')->assertMissing($path);
    }

    public function test_uploaded_documents_are_private_and_served_only_to_authorised_users(): void
    {
        $payload = $this->payload('draft');
        $payload['applicant_signature'] = UploadedFile::fake()->image('signature.jpg');
        $this->post(route('loan_applications.store'), $payload)->assertSessionHasNoErrors();
        $path = LoanApplication::firstOrFail()->applicant_signature;

        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->get(route('loan_applications.show', LoanApplication::firstOrFail()))
            ->assertOk()->assertSee(route('documents.show', ['path' => $path]), false);

        // Authenticated, but neither staff nor the owning member (the first user is seeded as admin).
        $admin = auth()->user();
        $this->get(route('documents.show', ['path' => $path]))->assertOk();
        $user = User::findOrFail(DB::table('users')->insertGetId(['name' => 'Plain User', 'email' => 'plain@example.test', 'password' => 'unused']));
        $this->actingAs($user);
        $this->get(route('documents.show', ['path' => $path]))->assertForbidden();

        // The owning member can open their own document.
        $this->member->update(['user_id' => $user->id]);
        $this->get(route('documents.show', ['path' => $path]))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->member->update(['user_id' => null]);
        $this->get(route('documents.show', ['path' => $path]))->assertForbidden();

        // Staff with view-members can open any document.
        $user->givePermissionTo('view-members');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->get(route('documents.show', ['path' => $path]))->assertOk();

        // Unknown, unreferenced and traversal paths never resolve to a file.
        Storage::disk('private')->put('loose/orphan.txt', 'x');
        foreach (['', 'loose/orphan.txt', '../.env', 'loan_applications/../../.env', '/etc/passwd', 'C:\windows\win.ini'] as $bad) {
            $this->get(route('documents.show', ['path' => $bad]))->assertNotFound();
        }

        $this->app['auth']->forgetGuards();
        $this->get(route('documents.show', ['path' => $path]))->assertRedirect(route('login'));
    }

    /**
     * Submit, review and approve a loan for $applicant guaranteed by $guarantor.
     */
    private function approveGuaranteedLoan(Member $applicant, Member $guarantor, float $offered = 12000, bool $consent = true): LoanApplication
    {
        $payload = $this->payload();
        $payload['member_id'] = $applicant->id;
        $payload['guarantors'] = [['member_id' => $guarantor->id, 'shares_offered' => $offered,
            'signature' => UploadedFile::fake()->image('guarantor.jpg')]];
        $this->post(route('loan_applications.store'), $payload)->assertSessionHasNoErrors();
        $application = LoanApplication::where('member_id', $applicant->id)->firstOrFail();
        if ($consent) {
            $this->patch(route('loan_applications.guarantor_consent', [$application, $application->guarantors()->first()]), ['decision' => 'accepted'])->assertSessionHasNoErrors();
        }
        $this->patch(route('loan_applications.approve', $application), ['approved_amount' => 12000, 'repayment_period_months' => 12])->assertSessionHasNoErrors();
        return $application->fresh();
    }

    private function guarantorAvailable(Member $member): float
    {
        return (float) DB::table('share_accounts')->where('member_id', $member->id)->value('available_amount');
    }

    public function test_declined_or_changed_guarantee_needs_fresh_consent_before_approval(): void
    {
        $this->post(route('loan_applications.store'), $this->payload())->assertSessionHasNoErrors();
        $application = LoanApplication::firstOrFail();
        $guarantor = $application->guarantors()->firstOrFail();
        $this->assertSame('pending', $guarantor->status);

        // Acceptance needs a signed form; refusal needs a reason.
        $this->patchJson(route('loan_applications.guarantor_consent', [$application, $guarantor]), ['decision' => 'accepted'])->assertStatus(422)->assertJsonValidationErrors('signature');
        $this->patchJson(route('loan_applications.guarantor_consent', [$application, $guarantor]), ['decision' => 'declined'])->assertStatus(422)->assertJsonValidationErrors('note');
        $this->patch(route('loan_applications.guarantor_consent', [$application, $guarantor]), ['decision' => 'accepted', 'signature' => UploadedFile::fake()->image('signed.jpg')])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $guarantor->fresh()->status);

        // Changing the guarantee amount on a deferred application resets consent.
        $this->patch(route('loan_applications.workflow', $application), ['action' => 'defer', 'note' => 'Adjust guarantee'])->assertSessionHasNoErrors();
        $payload = $this->payload();
        $payload['guarantors'][0]['id'] = $guarantor->id;
        $payload['guarantors'][0]['shares_offered'] = 13000;
        $this->patch(route('loan_applications.update', $application), $payload)->assertSessionHasNoErrors();
        $this->assertSame('pending', $guarantor->fresh()->status);
        $this->assertNull($guarantor->fresh()->consented_at);
        $this->patchJson(route('loan_applications.approve', $application), ['approved_amount' => 12000, 'repayment_period_months' => 12])
            ->assertStatus(422)->assertJsonValidationErrors('guarantors');
        $this->assertSame(0, Loan::count());
        $this->assertEquals(0, DB::table('share_accounts')->where('member_id', $this->guarantor->id)->value('held_amount'));
    }

    public function test_held_shares_cannot_back_a_second_loan_or_be_withdrawn(): void
    {
        $this->approveGuaranteedLoan($this->member, $this->guarantor);
        $this->assertEquals(38000, $this->guarantorAvailable($this->guarantor));

        // 40,000 offered but only 38,000 free.
        $other = $this->makeMember('APPLICANT2', User::first()->id);
        $payload = $this->payload();
        $payload['member_id'] = $other->id;
        $payload['guarantors'] = [['member_id' => $this->guarantor->id, 'shares_offered' => 40000]];
        $this->postJson(route('loan_applications.store'), $payload)->assertStatus(422)->assertJsonValidationErrors('guarantors');

        // Withdrawing into the held amount is refused; withdrawing free shares is allowed.
        \App\Models\Shares\ShareProduct::create(['code' => 'SH', 'name' => 'Shares', 'unit_value' => 100,
            'share_capital_account_id' => 0, 'share_premium_account_id' => 0]);
        DB::table('share_accounts')->where('member_id', $this->guarantor->id)->update(['total_units' => 500]);
        $account = DB::table('share_accounts')->where('member_id', $this->guarantor->id)->first();
        $redeem = fn(int $units) => $this->postJson(route('share_transactions.store'), [
            'share_account_id' => $account->id, 'transaction_type' => 'redemption', 'units' => $units,
            'transaction_date' => today()->toDateString(), 'value_date' => today()->toDateString()]);
        $redeem(450)->assertStatus(422)->assertJsonValidationErrors('units');
        $this->assertSame(0, DB::table('share_transactions')->count());
        $redeem(300)->assertRedirect();
        $this->assertSame(1, DB::table('share_transactions')->count());
    }

    public function test_guarantor_shares_are_released_when_loan_is_fully_repaid(): void
    {
        $application = $this->approveGuaranteedLoan($this->member, $this->guarantor);
        $loan = Loan::sole();
        $service = app(\App\Services\LoanGuarantorService::class);

        // Release is keyed to loan completion; reinstatement restores the hold.
        DB::transaction(fn() => $service->releaseForLoan($loan));
        $guarantor = $application->guarantors()->firstOrFail();
        $this->assertSame('released', $guarantor->status);
        $this->assertEquals(0, $guarantor->held_amount);
        $this->assertEquals(50000, $this->guarantorAvailable($this->guarantor));
        $this->assertEquals(0, DB::table('share_accounts')->where('member_id', $this->guarantor->id)->value('held_amount'));
        $this->assertSame(0, DB::table('loan_guarantor_holds')->where('status', 'active')->count());

        DB::transaction(fn() => $service->reinstateForLoan($loan));
        $this->assertSame('held', $guarantor->fresh()->status);
        $this->assertEquals(38000, $this->guarantorAvailable($this->guarantor));

        // A disbursement reversal closes the loan and frees the guarantors too.
        DB::transaction(fn() => $service->releaseForLoan($loan));
        $this->assertEquals(50000, $this->guarantorAvailable($this->guarantor));
    }

    public function test_guarantor_can_be_replaced_on_a_running_loan(): void
    {
        $application = $this->approveGuaranteedLoan($this->member, $this->guarantor);
        $old = $application->guarantors()->firstOrFail();
        $replacement = $this->makeMember('REPLACEMENT', User::first()->id);

        // Applicant and an already-used guarantor are rejected; coverage must still be met.
        $replace = fn(array $data) => $this->postJson(route('loan_applications.guarantor_replace', [$application, $old]),
            $data + ['signature' => UploadedFile::fake()->image('new.jpg')]);
        $replace(['member_id' => $this->member->id, 'shares_offered' => 12000])->assertStatus(422)->assertJsonValidationErrors('member_id');
        $replace(['member_id' => $this->guarantor->id, 'shares_offered' => 12000])->assertStatus(422)->assertJsonValidationErrors('member_id');
        $replace(['member_id' => $replacement->id, 'shares_offered' => 5000])->assertStatus(422)->assertJsonValidationErrors('shares_offered');
        $this->assertSame('held', $old->fresh()->status);

        $replace(["member_id" => $replacement->id, "shares_offered" => 12000])->assertRedirect();
        $this->assertSame('replaced', $old->fresh()->status);
        $this->assertEquals(50000, $this->guarantorAvailable($this->guarantor));
        $this->assertEquals(38000, $this->guarantorAvailable($replacement));
        $new = LoanGuarantor::where('member_id', $replacement->id)->firstOrFail();
        $this->assertSame('held', $new->status);
        $this->assertSame(Loan::sole()->id, $new->loan_id);
        $this->assertEquals(12000, $application->fresh()->guarantor_security);

        // Held guarantees are listed for staff; none are in arrears yet.
        $this->get(route('loan_guarantees.index'))->assertOk()->assertSee($replacement->full_name)->assertDontSee($this->guarantor->full_name);
        $this->get(route('loan_guarantees.index', ['filter' => 'in_arrears']))->assertOk()->assertDontSee($replacement->full_name);
        $this->get(route('loan_applications.show', $application))->assertOk()->assertSee('Replaced');
    }

    public function test_invalid_input_cannot_create_records_or_reuse_another_applications_children(): void
    {
        $invalid = $this->payload();
        $invalid['guarantors'][] = $invalid['guarantors'][0];
        $this->postJson(route('loan_applications.store'), $invalid)->assertStatus(422);
        $invalid = $this->payload();
        $invalid['guarantors'][0]['member_id'] = $this->member->id;
        $this->postJson(route('loan_applications.store'), $invalid)->assertStatus(422);
        $this->assertSame(0, LoanApplication::count());

        $this->post(route('loan_applications.store'), $this->payload('draft'))->assertSessionHasNoErrors();
        $first = LoanApplication::firstOrFail();
        $this->post(route('loan_applications.store'), ['submission_action' => 'draft'])->assertSessionHasNoErrors();
        $second = LoanApplication::latest('id')->first();
        $payload = $this->payload('draft');
        $payload['guarantors'][0]['id'] = $first->guarantors()->first()->id;
        $this->patchJson(route('loan_applications.update', $second), $payload)->assertStatus(422);
        $this->assertSame(1, $first->guarantors()->count());
        $this->get(route('loan_applications.show', 999999))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get(route('loan_applications.index'))->assertRedirect(route('login'));
    }
}
