<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNomineeWitnesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nominee_witnesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nomination_id');
            $table->unsignedBigInteger('member_id');

            $table->string('full_name');
            $table->string('national_id');
            $table->string('signature')->nullable();
            
            $table->softDeletes();
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
        Schema::dropIfExists('nominee_witnesses');
    }
}
