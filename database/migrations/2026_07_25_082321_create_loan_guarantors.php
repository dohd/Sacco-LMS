<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanGuarantors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_application_id');
            $table->unsignedBigInteger('member_id');

            $table->string('guarantor_name');
            $table->string('member_number');
            $table->decimal('shares_offered',15,2)->default(0);

            $table->string('national_id');
            $table->string('signature')->nullable();
            $table->string('witness_name')->nullable();

            $table->timestamps();
            $table->unique(['loan_application_id', 'member_id'], 'loan_guarantors_application_member_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_guarantors');
    }
}
