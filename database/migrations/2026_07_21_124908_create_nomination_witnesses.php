<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNominationWitnesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nomination_witnesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nomination_id');
            $table->unsignedBigInteger('member_application_id');

            $table->string('full_name');
            $table->string('national_id');
            $table->string('signature')->nullable();
            
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
        Schema::dropIfExists('nomination_witnesses');
    }
}
