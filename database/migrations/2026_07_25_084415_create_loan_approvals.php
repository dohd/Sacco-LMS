<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanApprovals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id();

            /*
             * One loan application can have multiple approval
             * or review records.
             */
            $table->unsignedBigInteger('loan_application_id');
            $table->unsignedBigInteger('member_id');

            $table->decimal('approved_amount', 15, 2)->default(0);

            $table->unsignedInteger('repayment_months')->default(0);

            $table->decimal('monthly_installment', 15, 2)->default(0);

            /*
             * Supports rates such as 12.5000%.
             */
            $table->decimal('interest_rate', 8, 4)->default(0);

            $table->enum('decision', [
                'approved',
                'deferred',
                'rejected',
            ]);

            $table->text('reason')->nullable();

            /*
             * The same meeting minute may cover several applications,
             * so this field is indexed but not unique.
             */
            $table->string('minute_number')->nullable()->index();

            $table->date('meeting_date')->nullable();

            /*
             * Assumption: chairman and approving officer are users.
             * Change 'users' to 'employees' or another table where needed.
             */
            $table->unsignedBigInteger('chairman_id')->nullable();
            $table->unsignedBigInteger('approved_by');

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
        Schema::dropIfExists('loan_approvals');
    }
}
