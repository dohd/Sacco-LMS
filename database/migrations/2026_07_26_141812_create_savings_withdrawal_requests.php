<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsWithdrawalRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_withdrawal_requests', function (Blueprint $table) {
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

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'paid',
                'cancelled',
            ])->default('pending');

            $table->unsignedBigInteger('approved_by');

            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('savings_transaction_id');

            $table->text('reason')->nullable();
            $table->text('decision_reason')->nullable();

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
        Schema::dropIfExists('savings_withdrawal_requests');
    }
}
