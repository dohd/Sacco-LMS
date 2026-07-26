<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanSecurities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_securities', function (Blueprint $table) {
            $table->id();

            /*
             * One loan application may have multiple securities,
             * such as pledged shares, a motor vehicle or property.
             */
            $table->unsignedBigInteger('loan_app_id');
            $table->unsignedBigInteger('member_id');

            /*
             * Indicates the category of security being provided.
             */
            $table->enum('security_type', [
                'pledged_shares',
                'additional_collateral',
            ]);

            /*
             * A short description or identifying name.
             * Examples:
             * - Member shares
             * - Motor vehicle
             * - Title deed
             * - Equipment
             */
            $table->string('security_name');

            /*
             * Detailed description of the pledged security.
             */
            $table->text('description')->nullable();

            /*
             * Estimated or assessed value of the security.
             */
            $table->decimal('security_value', 15, 2);

            /*
             * Amount of the security value accepted for loan coverage.
             * This may differ from the market value after applying
             * valuation or lending policy limits.
             */
            $table->decimal('accepted_value', 15, 2)->nullable();

            /*
             * Reference details for the pledged asset.
             * Examples:
             * - Share account number
             * - Vehicle registration number
             * - Title deed number
             * - Serial number
             */
            $table->string('reference_number')->nullable();

            /*
             * Optional ownership details for collateral that does not
             * belong directly to the applicant.
             */
            $table->string('owner_name')->nullable();
            $table->string('owner_national_id')->nullable();

            /*
             * Supporting collateral document.
             * Store the file path rather than the actual file.
             */
            $table->string('supporting_document')->nullable();

            /*
             * Indicates whether the security has been verified
             * by an authorized officer.
             */
            $table->boolean('is_verified')->default(false);

            $table->unsignedBigInteger('verified_by');

            $table->timestamp('verified_at')->nullable();

            /*
             * Security can be released after the loan is cleared
             * without deleting its historical record.
             */
            $table->enum('status', [
                'pending',
                'verified',
                'pledged',
                'rejected',
                'released',
            ])->default('pending');

            $table->date('pledged_date')->nullable();
            $table->date('released_date')->nullable();

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
        Schema::dropIfExists('loan_securities');
    }
}
