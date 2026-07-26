<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_share_account_id');

            $table->string('transaction_number')->unique();

            $table->enum('transaction_type', [
                'purchase',
                'transfer_in',
                'transfer_out',
                'redemption',
                'bonus_issue',
                'adjustment',
                'reversal',
            ]);

            $table->enum('direction', [
                'credit',
                'debit',
            ]);

            $table->unsignedInteger('units');

            /*
             * Snapshot of the share unit value when the transaction occurred.
             */
            $table->decimal('unit_value', 15, 2);
            $table->decimal('amount', 15, 2);

            $table->unsignedInteger('running_units');
            $table->decimal('running_balance', 15, 2);

            $table->date('transaction_date');
            $table->date('value_date');

            $table->enum('payment_method', [
                'cash',
                'mobile_money',
                'bank_transfer',
                'cheque',
                'check_off',
                'internal_transfer',
                'system',
            ])->nullable();

            $table->string('payment_reference')->nullable();
            $table->string('receipt_number')->nullable()->unique();

            $table->enum('status', [
                'pending',
                'confirmed',
                'reversed',
            ])->default('confirmed');

            $table->unsignedBigInteger('recorded_by');

            $table->unsignedBigInteger('reversal_of_id');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index([
                'member_share_account_id',
                'value_date',
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
        Schema::dropIfExists('share_transactions');
    }
}
