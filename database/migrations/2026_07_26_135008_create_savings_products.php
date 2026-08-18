<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->enum('product_type', [
                'compulsory',
                'voluntary',
                'fixed_deposit',
            ]);

            // General ledger mapping
            $table->unsignedBigInteger('savings_control_account_id');
            $table->unsignedBigInteger('interest_expense_account_id');
            $table->unsignedBigInteger('fee_income_account_id');

            $table->decimal('interest_rate', 8, 4)->default(0);

            $table->enum('interest_frequency', [
                'annual',
                'monthly',
                'quarterly',
                'semi_annual',
                'at_maturity',
            ])
            ->default('at_maturity');

            $table->enum('interest_calculation_method', [
                'simple',
                'compound',
            ])
            ->default('simple');

            /*
             * Deposit Term
             */
            $table->unsignedInteger('minimum_term_months')
                ->default(0);

            $table->unsignedInteger('maximum_term_months')
                ->nullable();

            /*
             * Premature Withdrawal
             */
            $table->boolean('allows_premature_withdrawal')
                ->default(false);

            $table->decimal('premature_withdrawal_penalty_percentage', 8, 4)
            ->default(0);

            /*
             * Automatic Maturity Processing
             */
            $table->boolean('auto_rollover')
                ->default(false);

            $table->enum('rollover_option', [
                'principal_only',
                'principal_and_interest',
            ])
            ->nullable();

            /*
             * Product Limits
             */
            $table->decimal('maximum_balance', 15, 2)
            ->nullable();

            /*
             * Fixed Deposit Eligibility
             */
            $table->boolean('allows_partial_withdrawals')
                ->default(false);

            $table->decimal('minimum_balance', 15, 2)->default(0);
            $table->decimal('minimum_monthly_contribution', 15, 2)->default(0);

            $table->boolean('allows_withdrawals')->default(true);
            $table->unsignedInteger('withdrawal_notice_days')->default(0);

            $table->boolean('can_secure_loan')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('savings_products');
    }
}
