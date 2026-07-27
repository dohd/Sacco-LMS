<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            /*
             * Each approved loan application should create
             * only one active loan account.
             */
            $table->unsignedBigInteger('loan_application_id')->unique();
            $table->unsignedBigInteger('member_id');

            /*
             * Identifies the approval record whose terms
             * were used to create this loan account.
             */
            $table->unsignedBigInteger('loan_approval_id');

            /*
             * System-generated loan account number.
             */
            $table->string('loan_number')->unique();

            /*
             * Approved and disbursed loan values.
             */
            $table->decimal('approved_amount', 15, 2);
            $table->decimal('amount_disbursed', 15, 2);

            /*
             * Snapshot of the approved loan terms.
             */
            $table->unsignedInteger('repayment_period_months');
            $table->decimal('interest_rate', 8, 4);
            $table->decimal('monthly_installment', 15, 2);

            /*
             * Interest calculation method.
             */
            $table->enum('interest_method', [
                'flat_rate',
                'reducing_balance',
            ])->default('reducing_balance');

            /*
             * Repayment schedule dates.
             */
            $table->date('disbursement_date');
            $table->date('first_repayment_date');
            $table->date('maturity_date');

            /*
             * Payment method inherited from the application
             * or selected during disbursement.
             */
            $table->enum('payment_mode', [
                'standing_order',
                'check_off',
                'post_dated_cheques',
                'cash',
            ]);

            /*
             * Running loan balances.
             */
            $table->decimal('principal_balance', 15, 2);
            $table->decimal('interest_balance', 15, 2)->default(0);
            $table->decimal('penalty_balance', 15, 2)->default(0);
            $table->decimal('total_outstanding_balance', 15, 2);

            /*
             * Cumulative amounts collected against the loan.
             */
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('penalties_paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);

            /*
             * Disbursement tracking information.
             */
            $table->enum('disbursement_method', [
                'bank_transfer',
                'mobile_money',
                'cheque',
                'cash',
                'account_credit',
            ])->nullable();

            $table->string('disbursement_reference')->nullable();
            $table->unsignedBigInteger('disbursed_by');

            /*
             * Loan account status.
             */
            $table->enum('status', [
                'active',
                'in_arrears',
                'restructured',
                'fully_paid',
                'written_off',
                'closed',
            ])->default('active');

            /*
             * Arrears tracking.
             */
            $table->decimal('arrears_amount', 15, 2)->default(0);
            $table->unsignedInteger('days_in_arrears')->default(0);

            /*
             * Closure information is retained for audit purposes.
             */
            $table->date('closed_date')->nullable();
            $table->unsignedBigInteger('closed_by');

            $table->text('closure_reason')->nullable();
            $table->text('remarks')->nullable();

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
        Schema::dropIfExists('loans');
    }
}
