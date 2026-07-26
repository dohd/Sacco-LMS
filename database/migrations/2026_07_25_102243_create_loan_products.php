<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            /*
             * Basic product information.
             *
             * Examples:
             * DEV  - Development Loan
             * EMG  - Emergency Loan
             * SCH  - School Fees Loan
             */
            $table->string('code', 50)->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();

            // General ledger mapping
            $table->unsignedBigInteger('loan_principal_account_id');
            $table->unsignedBigInteger('interest_receivable_account_id');
            $table->unsignedBigInteger('interest_income_account_id');
            $table->unsignedBigInteger('penalty_receivable_account_id');
            $table->unsignedBigInteger('penalty_income_account_id');
            $table->unsignedBigInteger('processing_fee_income_account_id');

            /*
             * Loan amount limits.
             */
            $table->decimal('minimum_amount', 15, 2)->default(0);
            $table->decimal('maximum_amount', 15, 2)->nullable();

            /*
             * Repayment-period limits.
             */
            $table->unsignedInteger('minimum_repayment_months')
                ->default(1);

            $table->unsignedInteger('maximum_repayment_months');

            /*
             * Interest configuration.
             *
             * Supports rates such as 12.5000%.
             */
            $table->decimal('interest_rate', 8, 4);

            $table->enum('interest_method', [
                'flat_rate',
                'reducing_balance',
            ])->default('reducing_balance');

            /*
             * Allows the product rate to be interpreted as annual,
             * monthly or one-off.
             */
            $table->enum('interest_frequency', [
                'annual',
                'monthly',
                'one_time',
            ])->default('annual');

            /*
             * Guarantor requirements.
             */
            $table->boolean('requires_guarantors')->default(true);

            $table->unsignedInteger('minimum_guarantors')
                ->default(1);

            $table->unsignedInteger('maximum_guarantors')
                ->nullable();

            /*
             * Percentage of the approved amount that must be covered
             * by guarantor security.
             *
             * Example: 100.0000 means full loan coverage.
             */
            $table->decimal(
                'minimum_guarantor_coverage_percentage',
                8,
                4
            )->default(100);

            /*
             * Member eligibility requirements.
             */
            $table->unsignedInteger('minimum_membership_months')
                ->default(0);

            $table->decimal(
                'minimum_share_contribution',
                15,
                2
            )->default(0);

            $table->decimal(
                'minimum_monthly_contribution',
                15,
                2
            )->default(0);

            /*
             * Maximum loan amount as a multiple of the member's
             * qualifying shares or contributions.
             *
             * Example: 3.00 means up to three times the member's shares.
             */
            $table->decimal(
                'share_multiplier',
                8,
                2
            )->nullable();

            /*
             * Controls whether members with existing active loans
             * may apply for this product.
             */
            $table->unsignedInteger('maximum_active_loans')
                ->nullable();

            $table->boolean('allows_top_up')->default(false);
            $table->boolean('allows_refinancing')->default(false);

            /*
             * Minimum percentage of an existing loan that must have
             * been repaid before a top-up is allowed.
             */
            $table->decimal(
                'minimum_repaid_percentage_for_top_up',
                8,
                4
            )->nullable();

            /*
             * Product-specific configurable rules.
             *
             * Example:
             * {
             *   "minimum_age": 18,
             *   "maximum_age_at_maturity": 65,
             *   "allowed_employment_types": [
             *      "permanent",
             *      "contract"
             *   ],
             *   "requires_salary_checkoff": false
             * }
             */
            $table->json('eligibility_rules')->nullable();

            /*
             * Optional product charges.
             */
            $table->decimal(
                'application_fee',
                15,
                2
            )->default(0);

            $table->decimal(
                'processing_fee_percentage',
                8,
                4
            )->default(0);

            $table->decimal(
                'insurance_fee_percentage',
                8,
                4
            )->default(0);

            /*
             * Grace-period configuration.
             */
            $table->unsignedInteger('grace_period_days')
                ->default(0);

            /*
             * Product lifecycle.
             *
             * Products should normally be deactivated rather than
             * deleted because historical loans may reference them.
             */
            $table->boolean('is_active')->default(true);

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->timestamps();

            $table->index([
                'is_active',
                'name',
            ]);

            $table->index(
                ['minimum_repayment_months', 'maximum_repayment_months'], 
                'loan_products_repayment_range_index' // Shorter custom name (36 chars)
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_products');
    }
}
