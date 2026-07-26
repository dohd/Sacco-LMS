<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareHolds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_holds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_share_account_id');

            $table->unsignedBigInteger('loan_id');

            $table->decimal('amount', 15, 2);

            $table->enum('hold_type', [
                'loan_security',
                'administrative',
                'legal',
            ]);

            $table->date('held_date');
            $table->date('released_date')->nullable();

            $table->enum('status', [
                'active',
                'released',
                'cancelled',
            ])->default('active');

            $table->unsignedBigInteger('created_by');

            $table->unsignedBigInteger('released_by');

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
        Schema::dropIfExists('share_holds');
    }
}
