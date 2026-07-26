<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanDisbursements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();

            /*
             * System-generated identifier.
             *
             * Example: DIS-2026-000001
             */
            $table->string('disbursement_number')->unique();

            /*
             * The approved application being disbursed.
             *
             * Financial records should normally be protected
             * from deletion.
             */
            $table->unsignedBigInteger('loan_application_id');

            /*
             * This can be populated after the active loan account
             * has been created.
             */
            $table->unsignedBigInteger('loan_id');

            /*
             * Amount breakdown.
             *
             * gross_amount:
             * Full amount charged to the member's loan account.
             *
             * deductions_amount:
             * Processing fees, insurance or other authorized deductions.
             *
             * net_amount:
             * Actual amount released to the member.
             */
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('deductions_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);

            /*
             * Method used to release the funds.
             */
            $table->enum('disbursement_method', [
                'bank_transfer',
                'mobile_money',
                'cheque',
                'cash',
                'account_credit',
            ]);

            /*
             * External payment reference.
             *
             * Examples:
             * - Bank transfer reference
             * - Mobile-money transaction code
             * - Cheque number
             */
            $table->string('transaction_reference')->nullable();

            /*
             * Cheque-specific details.
             */
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();

            /*
             * Payee or collector details.
             *
             * These fields should only be required where funds
             * are collected physically or paid to a third party.
             */
            $table->string('payee_name')->nullable();
            $table->string('payee_national_id')->nullable();
            $table->string('payee_phone')->nullable();

            /*
             * Date the institution released the funds.
             */
            $table->date('disbursement_date');

            /*
             * Effective banking or accounting date.
             */
            $table->date('value_date')->nullable();

            /*
             * Relevant where a cheque or cash payment was
             * physically collected.
             */
            $table->date('collection_date')->nullable();

            /*
             * Postal or courier delivery reference where a cheque
             * was sent rather than collected.
             */
            $table->string('postal_reference')->nullable();

            /*
             * Disbursement workflow.
             */
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'processing',
                'processed',
                'failed',
                'cancelled',
                'reversed',
            ])->default('draft');

            /*
             * Approval and processing audit trail.
             *
             * Change users to employees if officers are stored
             * in an employees table.
             */
            $table->unsignedBigInteger('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('processed_at')->nullable();

            /*
             * Supporting document such as:
             * - Payment voucher
             * - Cheque acknowledgment
             * - Bank transfer confirmation
             * - Mobile-money confirmation
             */
            $table->string('supporting_document')->nullable();

            /*
             * Reversal information.
             *
             * Processed financial transactions should be reversed,
             * not deleted or overwritten.
             */
            $table->timestamp('reversed_at')->nullable();

            $table->unsignedBigInteger('reversed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reversal_reason')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index([
                'loan_application_id',
                'status',
            ]);

            $table->index([
                'loan_id',
                'status',
            ]);

            $table->index([
                'disbursement_method',
                'transaction_reference',
            ], 'disbursement_and_reference');

            $table->index('disbursement_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_disbursements');
    }
}
