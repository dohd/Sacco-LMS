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
            
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('loan_product_id')->nullable();

            $table->string('application_number')->unique();
            $table->decimal('amount_requested',15,2)->default(0);
            $table->decimal('amount_approved',15,2)->default(0);
            $table->text('amount_in_words')->nullable();

            $table->unsignedInteger('repayment_period_months')->default(0);
            $table->decimal('monthly_installment',15,2)->default(0);

            // Keep the requested terms separate from the final approved terms.
            $table->unsignedInteger('approved_repayment_months')->default(0);
            $table->decimal('approved_monthly_installment', 15, 2)->default(0);
            $table->decimal('approved_interest_rate', 8, 4)->default(0);
            $table->enum('approved_interest_method', ['flat_rate', 'reducing_balance'])->nullable();
            $table->enum('approved_interest_frequency', ['annual', 'monthly', 'one_time'])->nullable();

            $table->date('required_date')->nullable();

            $table->enum('payment_mode',[
                'standing_order',
                'check_off',
                'post_dated_cheques',
                'cash'
            ])->nullable();

            $table->text('loan_purpose')->nullable();
            $table->decimal('purpose_amount',15,2)->default(0);

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
            ])->default('draft');

            $table->unsignedBigInteger('drafted_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('deferred_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();

            $table->dateTime('drafted_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('deferred_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->text('defer_note')->nullable();
            $table->text('rejection_note')->nullable();

            $table->timestamps();
            $table->index(['member_id', 'status'], 'loan_applications_member_status_index');
        });

        // Child tables are created earlier; all referenced tables exist here.
        foreach ($this->loanRelationships() as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $parent) {
                    $table->foreign($column)->references('id')->on($parent)->onDelete('restrict');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove incoming constraints before dropping the application table.
        foreach (array_reverse($this->loanRelationships(), true) as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $parent) {
                    $table->dropForeign([$column]);
                }
            });
        }

        Schema::dropIfExists('loan_applications');
    }

    private function loanRelationships(): array
    {
        return [
            'loan_applications' => [
                'member_id' => 'members',
                'loan_product_id' => 'loan_products',
                'drafted_by' => 'users',
                'submitted_by' => 'users',
                'reviewed_by' => 'users',
                'approved_by' => 'users',
                'deferred_by' => 'users',
                'rejected_by' => 'users',
                'closed_by' => 'users',
            ],
            'loan_guarantors' => ['loan_application_id' => 'loan_applications', 'member_id' => 'members'],
            'loan_witnesses' => ['loan_application_id' => 'loan_applications', 'member_id' => 'members'],
            'loan_securities' => [
                'loan_application_id' => 'loan_applications',
                'member_id' => 'members',
                'verified_by' => 'users',
            ],
            'loan_approvals' => [
                'loan_application_id' => 'loan_applications',
                'member_id' => 'members',
                'chairman_id' => 'users',
                'approved_by' => 'users',
            ],
        ];
    }
}
