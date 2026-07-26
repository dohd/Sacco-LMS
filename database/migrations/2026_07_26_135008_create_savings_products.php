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
