<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('savings_account_id');
            $table->string('transaction_number')->unique();

            $table->enum('transaction_type', [
                'deposit',
                'withdrawal',
                'interest',
                'fee',
                'transfer_in',
                'transfer_out',
                'adjustment',
                'reversal',
            ]);

            $table->enum('direction', [
                'credit',
                'debit',
            ]);

            $table->decimal('amount', 15, 2);
            $table->decimal('running_balance', 15, 2);

            $table->date('transaction_date'); // actual transaction date
            $table->date('value_date'); // date saved

            $table->enum('payment_method', [
                'cash',
                'mobile_money',
                'bank_transfer',
                'cheque',
                'check_off',
                'internal_transfer',
                'system',
            ])->nullable();

            $table->string('external_reference')->nullable();
            $table->string('receipt_number')->nullable()->unique();

            $table->enum('status', [
                'pending',
                'confirmed',
                'reversed',
            ])->default('confirmed');

            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('reversal_of_id')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('savings_account_id');
            $table->index('transaction_date');
            $table->index('value_date');
            $table->index('status');
            $table->index('transaction_type');
            $table->index('payment_method');
            $table->index([
                'savings_account_id',
                'transaction_date'
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
        Schema::dropIfExists('savings_transactions');
    }
}
