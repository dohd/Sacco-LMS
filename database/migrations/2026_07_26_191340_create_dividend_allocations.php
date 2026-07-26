<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDividendAllocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dividend_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('dividend_run_id');

            $table->unsignedBigInteger('member_id');

            $table->unsignedBigInteger('member_share_account_id');

            /*
             * Share balance used when calculating the dividend.
             */
            $table->decimal('qualifying_share_balance', 15, 2);

            $table->decimal('dividend_rate', 8, 4);

            $table->decimal('gross_dividend', 15, 2);
            $table->decimal('withholding_tax', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net_dividend', 15, 2);

            $table->enum('payment_option', [
                'cash',
                'bank_transfer',
                'mobile_money',
                'credit_savings',
                'purchase_shares',
                'offset_loan',
            ])->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'paid',
                'withheld',
                'cancelled',
            ])->default('pending');

            $table->date('paid_date')->nullable();
            $table->string('payment_reference')->nullable();

            $table->timestamps();

            $table->unique([
                'dividend_run_id',
                'member_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dividend_allocations');
    }
}
