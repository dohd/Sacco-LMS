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

            $table->unsignedBigInteger('loan_repayment_id');
            $table->unsignedBigInteger('loan_repayment_schedule_id');

            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');

            $table->decimal('principal_allocated', 15, 2)->default(0);
            $table->decimal('interest_allocated', 15, 2)->default(0);
            $table->decimal('fees_allocated', 15, 2)->default(0);
            $table->decimal('penalty_allocated', 15, 2)->default(0);
            $table->decimal('total_allocated', 15, 2)->default(0);

            $table->unsignedBigInteger('allocated_by');
            $table->timestamp('allocated_at')->useCurrent();

            $table->enum('status', [
                'active',
                'reversed',
            ])->default('active');

            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            $table->unique([
                'loan_repayment_id',
                'loan_repayment_schedule_id',
            ], 'repayment_schedule_allocation_unique');

            $table->index([
                'loan_repayment_schedule_id',
                'status',
            ], 'schedule_allocation_status_index');

            $table->index([
                'loan_repayment_id',
                'status',
            ], 'repayment_allocation_status_index');
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
