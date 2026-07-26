<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRepayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();

            /*
             * The loan account receiving the repayment.
             *
             * Restrict deletion to preserve financial transaction history.
             */
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');

            /*
             * System-generated repayment transaction number.
             *
             * Example: RP-2026-000001
             */
            $table->string('repayment_number')->unique();

            /*
             * Optional receipt issued to the member.
             */
            $table->string('receipt_number')
                ->nullable()
                ->unique();

            /*
             * Date the payment was received and the date it became
             * effective in the loan account.
             */
            $table->date('payment_date');
            $table->date('value_date')->nullable();

            /*
             * Total amount received from the member.
             * amount_paid =
                principal_amount
                + interest_amount
                + penalty_amount
                + fees_amount
                + unallocated_amount
             */
            $table->decimal('amount_paid', 15, 2);

            /*
             * Allocation of the repayment amount.
             */
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->decimal('fees_amount', 15, 2)->default(0);

            /*
             * Amount not yet allocated to principal, interest,
             * penalties, fees or repayment schedule entries.
             */
            $table->decimal('unallocated_amount', 15, 2)->default(0);

            /*
             * Actual method used to make the repayment.
             */
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'mobile_money',
                'cheque',
                'standing_order',
                'check_off',
                'post_dated_cheque',
                'account_credit',
            ]);

            /*
             * External reference such as:
             * - M-Pesa transaction code
             * - Bank transaction reference
             * - Cheque number
             * - Standing order reference
             */
            $table->string('transaction_reference')->nullable();

            /*
             * Optional payer details where payment was made
             * by a third party.
             */
            $table->string('payer_name')->nullable();
            $table->string('payer_phone')->nullable();

            /*
             * Officer or user who recorded the repayment.
             */
            $table->unsignedBigInteger('recorded_by');

            /*
             * Transaction processing status.
             */
            $table->enum('status', [
                'pending',
                'confirmed',
                'failed',
                'reversed',
            ])->default('confirmed');

            /*
             * Reversal information is retained instead of deleting
             * an incorrect repayment transaction.
             */
            $table->timestamp('reversed_at')->nullable();

            $table->unsignedBigInteger('reversed_by');

            $table->text('reversal_reason')->nullable();

            /*
             * Supporting document such as a deposit slip,
             * cheque image or bank confirmation.
             */
            $table->string('supporting_document')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index([
                'loan_id',
                'payment_date',
            ]);

            $table->index([
                'loan_id',
                'status',
            ]);

            $table->index([
                'payment_method',
                'transaction_reference',
            ]);

            $table->index('value_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_repayments');
    }
}
