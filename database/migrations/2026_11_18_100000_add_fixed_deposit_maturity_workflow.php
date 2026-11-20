<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFixedDepositMaturityWorkflow extends Migration
{
    public function up()
    {
        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->unsignedInteger('fixed_deposit_term_months')->nullable()->after('opened_date');
            $table->decimal('fixed_deposit_locked_principal', 15, 2)->default(0)->after('fixed_deposit_term_months');
            $table->decimal('fixed_deposit_interest_rate', 8, 4)->nullable()->after('fixed_deposit_locked_principal');
            $table->enum('fixed_deposit_calculation_method', ['simple', 'compound'])->nullable()->after('fixed_deposit_interest_rate');
            $table->boolean('fixed_deposit_auto_rollover')->nullable()->after('fixed_deposit_calculation_method');
            $table->enum('fixed_deposit_rollover_option', ['principal_only', 'principal_and_interest'])->nullable()->after('fixed_deposit_auto_rollover');
            $table->date('fixed_deposit_term_start_date')->nullable()->after('fixed_deposit_rollover_option');
            $table->date('fixed_deposit_maturity_date')->nullable()->after('fixed_deposit_term_start_date');
            $table->date('fixed_deposit_matured_at')->nullable()->after('fixed_deposit_maturity_date');
            $table->date('fixed_deposit_terminated_at')->nullable()->after('fixed_deposit_matured_at');
            $table->index(['fixed_deposit_maturity_date', 'fixed_deposit_matured_at'], 'savings_fixed_deposit_maturity_index');
        });

        Schema::table('savings_withdrawals', function (Blueprint $table) {
            $table->decimal('penalty_amount', 15, 2)->default(0)->after('amount');
        });

        Schema::create('savings_fixed_deposit_maturities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('savings_account_id');
            $table->date('maturity_date');
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->string('rollover_option', 30)->nullable();
            $table->decimal('rolled_over_amount', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->date('next_maturity_date')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();
            $table->unique(['savings_account_id', 'maturity_date'], 'savings_fd_account_maturity_unique');
            $table->index('maturity_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('savings_fixed_deposit_maturities');
        Schema::table('savings_withdrawals', function (Blueprint $table) {
            $table->dropColumn('penalty_amount');
        });
        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->dropIndex('savings_fixed_deposit_maturity_index');
            $table->dropColumn([
                'fixed_deposit_term_months', 'fixed_deposit_locked_principal',
                'fixed_deposit_interest_rate', 'fixed_deposit_calculation_method',
                'fixed_deposit_auto_rollover', 'fixed_deposit_rollover_option',
                'fixed_deposit_term_start_date', 'fixed_deposit_maturity_date',
                'fixed_deposit_matured_at', 'fixed_deposit_terminated_at',
            ]);
        });
    }
}
