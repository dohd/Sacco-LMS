<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRepaymentAllocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_repayment_allocations', function (Blueprint $table) {
            $table->id();

            /*
             * The actual repayment transaction being allocated.
             */
            $table->unsignedBigInteger('loan_repayment_id');

            /*
             * The scheduled instalment receiving the allocation.
             */
            $table->unsignedBigInteger('loan_repayment_schedule_id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');
            
            /*
             * Amounts allocated to each repayment component.
             */
            $table->decimal('principal_allocated', 15, 2)->default(0);
            $table->decimal('interest_allocated', 15, 2)->default(0);
            $table->decimal('fees_allocated', 15, 2)->default(0);
            $table->decimal('penalty_allocated', 15, 2)->default(0);

            /*
             * Officer who performed or confirmed the allocation.
             *
             * Change users to employees where applicable.
             */
            $table->unsignedBigInteger('allocated_by');
            

            $table->timestamp('allocated_at')->useCurrent();

            /*
             * Financial allocations should be reversed rather than deleted.
             */
            $table->enum('status', [
                'active',
                'reversed',
            ])->default('active');

            $table->timestamp('reversed_at')->nullable();

            $table->unsignedBigInteger('reversed_by');

            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            /*
             * One repayment should allocate to a particular schedule
             * entry only once.
             */
            $table->unique(
                [
                    'loan_repayment_id',
                    'loan_repayment_schedule_id',
                ],
                'repayment_schedule_allocation_unique'
            );

            $table->index(
                [
                    'loan_repayment_schedule_id',
                    'status',
                ],
                'schedule_allocation_status_index'
            );

            $table->index(
                [
                    'loan_repayment_id',
                    'status',
                ],
                'repayment_allocation_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_repayment_allocations');
    }
}
