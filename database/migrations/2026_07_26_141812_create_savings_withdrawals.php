<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsWithdrawals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_withdrawals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('savings_account_id');
            $table->string('request_number')->unique();
            $table->decimal('amount', 15, 2);

            $table->date('requested_date');

            $table->enum('payment_method', [
                'cash',
                'mobile_money',
                'bank_transfer',
                'cheque',
            ]);


            $table->string('mobile_money_number')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();

            $table->unsignedBigInteger('requested_by');

            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();

            $table->string('payment_reference')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'paid',
                'cancelled',
            ])->default('pending');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('savings_transaction_id');

            $table->text('reason')->nullable();
            $table->text('decision_reason')->nullable();

            $table->timestamps();

            $table->index('requested_date');
            $table->index(['status', 'requested_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('savings_withdrawals');
    }
}
