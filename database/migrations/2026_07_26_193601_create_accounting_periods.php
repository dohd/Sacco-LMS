<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingPeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->unsignedInteger('financial_year');

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', [
                'open',
                'closed',
                'locked',
            ])->default('open');

            $table->unsignedBigInteger('closed_by');

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'financial_year',
                'start_date',
                'end_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_periods');
    }
}
