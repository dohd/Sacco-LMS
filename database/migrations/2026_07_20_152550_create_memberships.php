<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberships extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            // Personal Details
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('first_name');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('national_id')->unique();
            $table->string('phone');
            $table->text('current_address')->nullable();
            $table->text('residential_address');

            // Employment
            $table->string('employer_name')->nullable();
            $table->string('working_station')->nullable();
            $table->string('designation')->nullable();
            $table->text('employer_address')->nullable();
            $table->string('employer_phone')->nullable();
            $table->string('employment_terms')->nullable();

            // Business
            $table->string('business_name')->nullable();
            $table->string('business_nature')->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->text('business_location')->nullable();

            // Next of Kin
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_id');
            $table->string('next_of_kin_relationship');

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
                'pending',
                'under_review',
                'approved',
                'rejected'
            ])->default('pending');

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
        Schema::dropIfExists('memberships');
    }
}
