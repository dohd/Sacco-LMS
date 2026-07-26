<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRepaymentSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->id();
            /*
             * One loan has multiple repayment schedule entries.
             */
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');

            /*
             * Sequential repayment number, for example 1, 2, 3...
             */
            $table->unsignedInteger('installment_number');

            $table->date('due_date');

            /*
             * Principal balance before this instalment is applied.
             */
            $table->decimal('opening_principal_balance', 15, 2);

            /*
             * Scheduled repayment components.
             */
            $table->decimal('principal_due', 15, 2);
            $table->decimal('interest_due', 15, 2)->default(0);
            $table->decimal('fees_due', 15, 2)->default(0);
            $table->decimal('penalty_due', 15, 2)->default(0);

            /*
             * Total scheduled amount payable for the instalment.
             */
            $table->decimal('total_due', 15, 2);

            /*
             * Expected principal balance after the scheduled
             * principal payment is applied.
             */
            $table->decimal('closing_principal_balance', 15, 2);

            /*
             * Actual amounts allocated to this schedule entry.
             */
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('fees_paid', 15, 2)->default(0);
            $table->decimal('penalty_paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);

            /*
             * Remaining amount for this instalment.
             */
            $table->decimal('outstanding_amount', 15, 2);

            $table->date('fully_paid_date')->nullable();

            $table->enum('status', [
                'pending',
                'partially_paid',
                'paid',
                'overdue',
                'waived',
            ])->default('pending');

            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
             * Prevent duplicate instalment numbers for the same loan.
             */
            $table->unique([
                'loan_id',
                'installment_number',
            ]);

            $table->index([
                'loan_id',
                'due_date',
            ]);

            $table->index([
                'status',
                'due_date',
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
        Schema::dropIfExists('loan_repayment_schedules');
    }
}
