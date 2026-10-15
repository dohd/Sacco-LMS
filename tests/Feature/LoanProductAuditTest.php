<?php

namespace Tests\Feature;

use App\Models\LoanApplications\LoanProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Regression tests; remaining defect tests document intentionally deferred issues. */
class LoanProductAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'product_audit', 'database.connections.product_audit' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::setDefaultConnection('product_audit');
        require_once database_path('migrations/2026_07_25_102243_create_loan_products.php');
        (new \CreateLoanProducts)->up();
        Schema::create('chart_of_accounts', function ($table) {
            $table->id(); $table->string('account_code'); $table->string('account_name');
        });
        DB::table('chart_of_accounts')->insert(['id' => 1, 'account_code' => '100', 'account_name' => 'Audit Account']);
        $user = new User;
        $user->forceFill(['id' => 1, 'name' => 'Audit User']);
        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
    }

    protected function tearDown(): void
    {
        DB::purge('product_audit');
        parent::tearDown();
    }

    private function payload(): array
    {
        return ['code' => 'AUDIT', 'name' => 'Audit Loan', 'minimum_amount' => 100,
            'maximum_amount' => 10000, 'minimum_repayment_months' => 1, 'maximum_repayment_months' => 12,
            'interest_rate' => 12, 'interest_method' => 'flat_rate', 'interest_frequency' => 'annual',
            'loan_principal_account_id' => 1, 'interest_receivable_account_id' => 1,
            'interest_income_account_id' => 1, 'penalty_receivable_account_id' => 1,
            'penalty_income_account_id' => 1, 'processing_fee_income_account_id' => 1];
    }

    public function test_create_read_update_and_explicit_status_work(): void
    {
        $this->post(route('loan_products.store'), $this->payload())->assertRedirect(route('loan_products.index'))->assertSessionHasNoErrors();
        $product = LoanProduct::firstOrFail();
        $this->assertTrue($product->is_active);
        $this->get(route('loan_products.index'))->assertOk()->assertSee('Audit Loan');
        $this->get(route('loan_products.show', $product))->assertOk()->assertSee('Audit Loan');
        $this->get(route('loan_products.edit', $product))->assertOk();
        $this->patch(route('loan_products.update', $product), array_replace($this->payload(), ['name' => 'Updated Loan']))->assertRedirect(route('loan_products.show', $product));
        $this->assertSame('Updated Loan', $product->fresh()->name);
        $this->patch(route('loan_products.status', $product), ['is_active' => 1])->assertRedirect();
        $this->assertTrue($product->fresh()->is_active);
        $this->patch(route('loan_products.status', $product), ['is_active' => 0])->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_defect_delete_returns_success_without_deleting(): void
    {
        $product = LoanProduct::create($this->payload());
        $this->delete(route('loan_products.destroy', $product))->assertOk();
        $this->assertNotNull($product->fresh());
    }

    public function test_defect_account_options_empty_and_zero_ids_accepted(): void
    {
        $this->get(route('loan_products.create'))->assertOk()->assertViewHas('accounts', function ($accounts) { return $accounts->isEmpty(); });
        $payload = $this->payload();
        foreach ($payload as $key => $value) { if (substr($key, -11) === '_account_id') { $payload[$key] = 0; } }
        $this->post(route('loan_products.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(0, (int) LoanProduct::firstOrFail()->loan_principal_account_id);
    }

    public function test_missing_or_null_accounts_return_field_validation_errors(): void
    {
        $product = LoanProduct::create($this->payload());
        foreach (array_keys($this->payload()) as $key) {
            if (substr($key, -11) !== '_account_id') { continue; }
            foreach ([false, true] as $explicitNull) {
                $payload = array_replace($this->payload(), ['code' => 'NEW', 'name' => 'New Loan']);
                unset($payload[$key]);
                if ($explicitNull) { $payload[$key] = null; }
                $this->postJson(route('loan_products.store'), $payload)->assertStatus(422)->assertJsonValidationErrors($key);
                $this->patchJson(route('loan_products.update', $product), $payload)->assertStatus(422)->assertJsonValidationErrors($key);
            }
        }
        $this->assertSame(1, LoanProduct::count());
        $this->assertSame('Audit Loan', $product->fresh()->name);
    }

    public function test_rules_and_dates_render_on_edit_and_survive_save(): void
    {
        $extra = ['eligibility_rules' => ['minimum_age' => 18], 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31'];
        $product = LoanProduct::create(array_merge($this->payload(), $extra));
        $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('&quot;minimum_age&quot;: 18', false)
            ->assertSee('value="2026-01-01"', false)->assertSee('value="2026-12-31"', false);
        $extra['eligibility_rules'] = json_encode($extra['eligibility_rules']);
        $this->patch(route('loan_products.update', $product), array_merge($this->payload(), $extra))->assertSessionHasNoErrors();
        $this->assertSame(['minimum_age' => 18], $product->fresh()->eligibility_rules);
    }

    public function test_create_preserves_failed_input_and_errors(): void
    {
        $payload = $this->payload(); $payload['name'] = '';
        $this->from(route('loan_products.create'))->post(route('loan_products.store'), $payload)->assertSessionHasErrors('name');
        $this->get(route('loan_products.create'))->assertOk()->assertSee('value="AUDIT"', false)
            ->assertSee('The name field is required.');
    }

    public function test_edit_preserves_failed_input(): void
    {
        $product = LoanProduct::create($this->payload());
        $payload = array_replace($this->payload(), ['name' => 'Unsaved correction', 'interest_rate' => -1]);
        $this->from(route('loan_products.edit', $product))->patch(route('loan_products.update', $product), $payload)->assertSessionHasErrors('interest_rate');
        $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('value="Unsaved correction"', false)
            ->assertSee('value="-1"', false);
    }

    public function test_form_submission_preserves_status(): void
    {
        $product = LoanProduct::create(array_merge($this->payload(), ['is_active' => true]));
        $this->get(route('loan_products.edit', $product))->assertOk()->assertDontSee('name="is_active"', false);
        $this->patch(route('loan_products.update', $product), $this->payload())->assertRedirect();
        $this->assertTrue($product->fresh()->is_active);
        $product->update(['is_active' => false]);
        $this->patch(route('loan_products.update', $product), $this->payload())->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_failed_edit_preserves_unchecked_switches_and_array_rules(): void
    {
        $product = LoanProduct::create(array_merge($this->payload(), [
            'requires_guarantors' => true, 'allows_top_up' => true, 'allows_refinancing' => true,
        ]));
        $payload = array_replace($this->payload(), ['interest_rate' => -1, 'requires_guarantors' => 0,
            'allows_top_up' => 0, 'allows_refinancing' => 0, 'eligibility_rules' => ['minimum_age' => 21]]);
        $this->from(route('loan_products.edit', $product))->patch(route('loan_products.update', $product), $payload)
            ->assertSessionHasErrors('interest_rate');
        $response = $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('&quot;minimum_age&quot;: 21', false);
        foreach (['requires_guarantors', 'allows_top_up', 'allows_refinancing'] as $field) {
            $response->assertSee('type="hidden" name="' . $field . '" value="0"', false);
            $this->assertSame(1, preg_match('/<input type="checkbox"[^>]*id="' . $field . '"[^>]*>/', $response->getContent(), $matches));
            $this->assertStringNotContainsString('checked', $matches[0]);
        }
    }

    public function test_validation_rejects_duplicates_and_invalid_ranges(): void
    {
        $product = LoanProduct::create($this->payload());
        $this->postJson(route('loan_products.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
        foreach ([['maximum_amount' => 50], ['maximum_repayment_months' => 0], ['eligibility_rules' => '{invalid'], ['requires_guarantors' => 1, 'minimum_guarantors' => 0]] as $invalid) {
            $this->patchJson(route('loan_products.update', $product), array_replace($this->payload(), $invalid))->assertStatus(422);
        }
        $this->get(route('loan_products.show', 999999))->assertNotFound();
    }
}
