<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanWitnesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_witnesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_app_id');
            $table->unsignedBigInteger('member_id');
            
            $table->string('name');
            $table->string('national_id');
            $table->string('payroll_number')->nullable();
            $table->string('employer')->nullable();
            $table->string('station')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('signature')->nullable();
            $table->date('signed_at')->nullable();

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
        Schema::dropIfExists('loan_witnesses');
    }
}
