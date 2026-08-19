<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberApplications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_applications', function (Blueprint $table) {
            $table->id();

            $table->string('application_number')->unique();

            // Application source
            $table->enum('application_channel', [
                'web',
                'mobile',
                'office',
                'agent',
                'import',
            ])->default('web');

            // Personal Details
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('first_name');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('national_id')->unique();
            $table->string('phone');
            $table->string('email')->nullable()->index();
            $table->text('current_address')->nullable();
            $table->text('residential_address');

            // Employment
            $table->string('employer_name')->nullable();
            $table->string('working_station')->nullable();
            $table->string('designation')->nullable();
            $table->text('employer_address')->nullable();
            $table->string('employer_phone')->nullable();
            $table->enum('employment_terms', [
                'permanent',
                'contract',
                'temporary',
                'casual',
                'seasonal',
                'self_employed',
            ])->nullable();

            // Business
            $table->string('business_name')->nullable();
            $table->string('business_nature')->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->text('business_location')->nullable();

            // Next of Kin
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_id');
            $table->enum('next_of_kin_relationship', [
                'spouse',
                'parent',
                'child',
                'sibling',
                'relative',
                'guardian',
                'other'
            ]);

            // Contributions
            $table->decimal('monthly_contribution', 10, 2);
            $table->date('contribution_start_date');

            // Documents
            $table->string('national_id_front')->nullable();
            $table->string('national_id_back')->nullable();
            $table->string('passport_photo_1')->nullable();
            $table->string('passport_photo_2')->nullable();
            $table->string('nominee_form')->nullable();

            // Declaration
            $table->boolean('agreed_to_terms')->default(false);
            $table->string('applicant_signature')->nullable();
            $table->date('application_date')->nullable();

            // Workflow
            $table->enum('status', [
                'draft',
                'pending',
                'under_review',
                'approved',
                'rejected',
                'withdrawn',
                'cancelled'
            ])->default('pending');


            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('rejected_by')->nullable();

            $table->timestamp('rejected_at')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('application_date');
            $table->index([
                'last_name',
                'first_name',
            ]);
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_applications');
    }
}
