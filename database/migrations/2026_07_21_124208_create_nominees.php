<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNominees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nominees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nomination_id');
            $table->unsignedBigInteger('member_application_id');

            $table->string('full_name');
            $table->string('national_id');
            $table->string('postal_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('relationship');
            $table->decimal('percentage', 5, 2);

            $table->boolean('is_minor')->default(false);
            $table->date('date_of_birth')->nullable();

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
        Schema::dropIfExists('nominees');
    }
}
