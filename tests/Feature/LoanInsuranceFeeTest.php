<?php

namespace Tests\Feature;

use App\Models\Accounting\JournalEntry;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanDisbursement;
use App\Models\LoanApplications\LoanProduct;
use App\Models\LoanApplications\LoanRepayment;
use App\Models\Memberships\Member;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Insurance fee held as a liability for the insurer.
 *
 * The loan is KES 12,000 over 12 months at 12% flat with a 1% processing fee
 * and a 1% insurance fee, so 240 of fees are capitalised: 120 processing and
 * 120 insurance. Interest is 1,468.80 and each installment is 1,142.40.
 */
class LoanInsuranceFeeTest extends TestCase
{
    private const INSTALLMENT = 1142.4;

    private $database;
    private $originalConnection;
    private $member;
    private $userId;
    private $loan;
    private $disbursement;
    private $product;
    private $accounts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\AllowIframeFromWebsiteB::class);
        $this->originalConnection = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test uses an isolated MySQL/MariaDB database.');
        }
        $this->database = 'insurance_fee_test_' . bin2hex(random_bytes(8));
        DB::statement("CREATE DATABASE `{$this->database}`");
        $connection = config('database.connections.' . $this->originalConnection);
        $connection['database'] = $this->database;
        $connection['url'] = null;
        config([
            'database.connections.insurance_fee_test' => $connection,
            'database.default' => 'insurance_fee_test',
            'session.driver' => 'array', 'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('insurance_fee_test');
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'insurance_fee_test', '--force' => true]), Artisan::output());

        $this->userId = DB::table('users')->insertGetId(['name' => 'Test Officer', 'email' => 'officer@example.test', 'password' => 'unused']);
        $this->seed(\Database\Seeders\SaccoPermissionSeeder::class);
        $adminRole = DB::table('roles')->where('name', 'sacco_admin')->first();
        DB::table('model_has_roles')->insert(['role_id' => $adminRole->id, 'model_type' => User::class, 'model_id' => $this->userId]);
        $this->actingAs(User::findOrFail($this->userId));

        foreach ([
            'cash' => ['1000', 'Cash on hand', 'asset', 'debit'],
            'principal' => ['1200', 'Loan principal', 'asset', 'debit'],
            'interest' => ['1210', 'Interest receivable', 'asset', 'debit'],
            'fees' => ['1220', 'Fee receivable', 'asset', 'debit'],
            'penalty' => ['4200', 'Penalty income', 'income', 'credit'],
            'interestincome' => ['4000', 'Interest income', 'income', 'credit'],
            'feeincome' => ['4100', 'Processing fee income', 'income', 'credit'],
            'insurance' => ['2207', 'Insurance payable', 'liability', 'credit'],
            'unearned' => ['2206', 'Unearned interest', 'liability', 'credit'],
            'savings' => ['2101', 'Individual savings register', 'liability', 'credit'],
        ] as $key => [$code, $name, $type, $balance]) {
            $this->accounts[$key] = DB::table('chart_of_accounts')->insertGetId([
                'code' => $code, 'name' => $name, 'account_type' => $type, 'normal_balance' => $balance,
                'is_control_account' => false, 'config_key' => $key === 'savings' ? 'individual_savings_register' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('accounting_periods')->insert([
            'name' => 'Current year', 'financial_year' => (int) today()->format('Y'),
            'start_date' => today()->startOfYear()->toDateString(), 'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->member = Member::create([
            'member_application_id' => 1, 'membership_number' => 'BORROWER',
            'first_name' => 'BORROWER', 'last_name' => 'Test', 'date_of_birth' => '1990-01-01',
            'national_id' => 'BORROWER', 'phone' => '0700000000', 'email' => 'borrower@example.test',
            'admission_date' => '2020-01-01', 'approved_by' => $this->userId,
        ]);
        DB::table('share_accounts')->insert([
            'member_id' => $this->member->id, 'share_product_id' => 1, 'account_number' => 'BORROWER',
            'opened_date' => '2020-01-01', 'share_balance' => 50000, 'available_amount' => 50000,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->database && $this->originalConnection) {
            DB::purge('insurance_fee_test');
            DB::setDefaultConnection($this->originalConnection);
            if (preg_match('/^insurance_fee_test_[a-f0-9]{16}$/', $this->database)) {
                DB::statement("DROP DATABASE `{$this->database}`");
            }
        }
        parent::tearDown();
    }

    /** Create a product, then take a KES 12,000 loan on it through the real workflow. */
    private function makeLoan(?string $insuranceAccount, float $processingPct = 1, float $insurancePct = 1): void
    {
        $this->product = LoanProduct::create([
            'code' => 'TEST', 'name' => 'Test Loan', 'minimum_amount' => 1000,
            'maximum_amount' => 100000, 'maximum_repayment_months' => 24,
            'interest_rate' => 12, 'interest_method' => 'flat_rate', 'interest_frequency' => 'annual',
            'requires_guarantors' => false,
            'processing_fee_percentage' => $processingPct, 'insurance_fee_percentage' => $insurancePct,
            'loan_principal_account_id' => $this->accounts['principal'],
            'interest_receivable_account_id' => $this->accounts['interest'],
            'fee_receivable_account_id' => $this->accounts['fees'],
            'interest_income_account_id' => $this->accounts['interestincome'],
            'penalty_income_account_id' => $this->accounts['penalty'],
            'processing_fee_income_account_id' => $this->accounts['feeincome'],
            'insurance_payable_account_id' => $insuranceAccount ? $this->accounts[$insuranceAccount] : null,
        ]);

        $this->post(route('loan_applications.store'), [
            'submission_action' => 'submit', 'member_id' => $this->member->id,
            'loan_product_id' => $this->product->id, 'amount_requested' => 12000,
            'repayment_period_months' => 12, 'payment_mode' => 'cash', 'loan_purpose' => 'School fees',
        ])->assertSessionHasNoErrors();
        $this->patch(route('loan_applications.approve', LoanApplication::firstOrFail()), ['approved_amount' => 12000, 'repayment_period_months' => 12])->assertSessionHasNoErrors();
        $this->loan = Loan::firstOrFail();
        $this->disbursement = LoanDisbursement::where('loan_id', $this->loan->id)->firstOrFail();
    }

    private function disburse(): void
    {
        $this->patch(route('loan_disbursements.update', $this->disbursement), [
            'disbursement_method' => 'cash', 'disbursement_account_id' => $this->accounts['cash'],
            'disbursement_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('loan_disbursements.process', $this->disbursement))->assertSessionHasNoErrors();
        $this->assertSame('processed', $this->disbursement->fresh()->status);
    }

    private function pay(float $amount): LoanRepayment
    {
        $this->post(route('loan_repayments.store'), [
            'loan_id' => $this->loan->id, 'member_id' => $this->member->id,
            'payment_date' => today()->toDateString(), 'amount_paid' => $amount,
            'payment_method' => 'cash', 'deposit_account_id' => $this->accounts['cash'],
        ])->assertSessionHasNoErrors();
        $repayment = LoanRepayment::latest('id')->firstOrFail();
        $this->post(route('loan_repayments.confirm', $repayment))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $repayment->fresh()->status);
        return $repayment->fresh();
    }

    /** Net debit balance of a ledger account. */
    private function balance(string $account): float
    {
        return round((float) DB::table('journal_entry_lines')->where('chart_of_account_id', $this->accounts[$account])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance'), 2);
    }

    private function assertBalanced(string $when): void
    {
        $this->assertEquals(0, round((float) DB::table('journal_entry_lines')->sum(DB::raw('debit - credit')), 2), "$when: trial balance");
    }

    public function test_the_insurance_part_of_the_fees_is_held_as_a_liability(): void
    {
        $this->makeLoan('insurance');
        $this->assertEquals(240, (float) $this->loan->fees_due);
        $this->assertEquals(120, (float) $this->loan->insurance_due);
        $this->disburse();

        $this->assertEquals(240, $this->balance('fees'));
        $this->assertEquals(-120, $this->balance('insurance'));
        $this->assertEquals(-120, $this->balance('feeincome'));
        $this->assertEquals(-1468.8, $this->balance('interestincome'));
        $this->assertBalanced('after disbursement');
    }

    public function test_a_product_with_only_an_insurance_fee_books_it_all_to_the_liability(): void
    {
        $this->makeLoan('insurance', 0, 1);
        $this->assertEquals(120, (float) $this->loan->fees_due);
        $this->disburse();

        $this->assertEquals(-120, $this->balance('insurance'));
        $this->assertEquals(0, $this->balance('feeincome'));
        $this->assertBalanced('after disbursement');
    }

    public function test_without_an_insurance_account_all_fees_stay_income(): void
    {
        $this->makeLoan(null);
        // The loan still records how much of the fees is insurance.
        $this->assertEquals(120, (float) $this->loan->insurance_due);
        $this->disburse();

        $this->assertEquals(-240, $this->balance('feeincome'));
        $this->assertEquals(0, $this->balance('insurance'));
        $this->assertBalanced('after disbursement');
    }

    public function test_loans_booked_before_the_split_are_unchanged(): void
    {
        // A loan approved before this change has no insurance amount, whatever the product says now.
        $this->makeLoan('insurance');
        DB::table('loans')->where('id', $this->loan->id)->update(['insurance_due' => 0]);
        $this->disburse();

        $this->assertEquals(-240, $this->balance('feeincome'));
        $this->assertEquals(0, $this->balance('insurance'));
    }

    public function test_repayments_collect_the_fee_receivable_but_leave_the_insurance_liability(): void
    {
        $this->makeLoan('insurance');
        $this->disburse();
        for ($i = 0; $i < 11; $i++) {
            $this->pay(self::INSTALLMENT);
        }
        $this->pay((float) $this->loan->fresh()->total_outstanding_balance);

        $this->assertSame('fully_paid', $this->loan->fresh()->status);
        $this->assertEquals(0, $this->balance('fees'));
        $this->assertEquals(0, $this->balance('principal'));
        // The member has paid the insurance, but the insurer has not been paid yet.
        $this->assertEquals(-120, $this->balance('insurance'));
        $this->assertEquals(-120, $this->balance('feeincome'));
        $this->assertBalanced('after full repayment');
    }

    public function test_reversing_the_disbursement_returns_the_insurance_liability(): void
    {
        $this->makeLoan('insurance');
        $this->disburse();
        $this->post(route('loan_disbursements.reverse', $this->disbursement), ['reversal_reason' => 'Paid in error'])->assertSessionHasNoErrors();

        foreach (['fees', 'insurance', 'feeincome', 'principal', 'cash'] as $account) {
            $this->assertEquals(0, $this->balance($account), $account);
        }
    }

    public function test_paying_the_insurer_is_a_manual_journal_that_clears_the_liability(): void
    {
        $this->makeLoan('insurance');
        $this->disburse();

        $this->post(route('journal_entries.store'), [
            'accounting_period_id' => DB::table('accounting_periods')->value('id'),
            'transaction_date' => today()->toDateString(), 'posting_date' => today()->toDateString(),
            'description' => 'Insurance premium remitted',
            'lines' => [
                ['account_id' => $this->accounts['insurance'], 'debit' => 120, 'credit' => 0],
                ['account_id' => $this->accounts['cash'], 'debit' => 0, 'credit' => 120],
            ],
        ])->assertSessionHasNoErrors();
        $this->patch(route('journal_entries.post', JournalEntry::where('event_type', 'manual')->latest('id')->firstOrFail()->id))->assertSessionHasNoErrors();

        $this->assertEquals(0, $this->balance('insurance'));
        $this->assertBalanced('after remittance');
    }

    public function test_the_insurance_account_must_be_a_postable_liability_different_from_unearned_interest(): void
    {
        $this->makeLoan(null);
        $product = $this->product;
        $payload = $product->only(['code', 'name', 'minimum_amount', 'maximum_amount', 'interest_rate', 'interest_method',
            'interest_frequency', 'processing_fee_percentage', 'insurance_fee_percentage', 'loan_principal_account_id',
            'interest_receivable_account_id', 'fee_receivable_account_id', 'interest_income_account_id',
            'penalty_income_account_id', 'processing_fee_income_account_id']);
        $payload += ['minimum_repayment_months' => 1, 'maximum_repayment_months' => 24];

        $this->get(route('loan_products.edit', $product))->assertOk()->assertSee('Insurance Payable Account');
        $this->putJson(route('loan_products.update', $product), $payload + ['insurance_payable_account_id' => $this->accounts['feeincome']])
            ->assertStatus(422)->assertJsonValidationErrors('insurance_payable_account_id');
        $this->putJson(route('loan_products.update', $product), $payload + [
            'insurance_payable_account_id' => $this->accounts['insurance'], 'unearned_interest_account_id' => $this->accounts['insurance'],
        ])->assertStatus(422)->assertJsonValidationErrors('insurance_payable_account_id');
        $this->putJson(route('loan_products.update', $product), $payload + ['insurance_payable_account_id' => $this->accounts['insurance']])->assertSessionHasNoErrors();
        $this->assertSame($this->accounts['insurance'], (int) $product->fresh()->insurance_payable_account_id);
    }
}
