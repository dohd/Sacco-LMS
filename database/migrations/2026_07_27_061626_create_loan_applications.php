<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanApplications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('loan_product_id');

            $table->string('application_number')->unique();
            $table->decimal('amount_requested',15,2);
            $table->text('amount_in_words')->nullable();

            $table->unsignedInteger('repayment_period_months');
            $table->decimal('monthly_installment',15,2);

            $table->date('required_date')->nullable();

            $table->enum('payment_mode',[
                'standing_order',
                'check_off',
                'post_dated_cheques',
                'cash'
            ]);

            $table->text('loan_purpose');
            $table->decimal('purpose_amount',15,2)->nullable();

            // Employment
            $table->string('employer_name')->nullable();
            $table->enum('employment_type',[
                'permanent',
                'seasonal',
                'contract',
                'self_employed'
            ])->nullable();

            $table->string('work_station')->nullable();
            $table->string('employer_postal_address')->nullable();

            // Business
            $table->string('business_name')->nullable();
            $table->string('business_postal_address')->nullable();

            // Financial Position
            $table->decimal('total_share_contribution',15,2)->default(0);
            $table->decimal('outstanding_loan_balance',15,2)->default(0);
            $table->decimal('monthly_share_contribution',15,2)->default(0);

            // Security
            $table->decimal('security_shares',15,2)->default(0);
            $table->decimal('guarantor_security',15,2)->default(0);

            $table->string('applicant_signature')->nullable();
            $table->date('declaration_date')->nullable();

            $table->enum('status',[
                'draft',
                'submitted',
                'under_review',
                'approved',
                'deferred',
                'rejected',
                'disbursed',
                'closed'
            ])->default('submitted');

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
        Schema::dropIfExists('loan_applications');
    }
}
