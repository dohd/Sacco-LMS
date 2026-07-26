<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDividendRuns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dividend_runs', function (Blueprint $table) {
            $table->id();

            $table->string('run_number')->unique();
            $table->string('name');

            /*
             * Financial period whose shares qualify.
             */
            $table->date('period_start');
            $table->date('period_end');

            $table->date('declaration_date')->nullable();
            $table->date('payment_date')->nullable();

            /*
             * Example: 10.0000 means a 10% dividend rate.
             */
            $table->decimal('dividend_rate', 8, 4);

            $table->enum('calculation_method', [
                'closing_balance',
                'average_balance',
                'minimum_balance',
                'weighted_average',
            ])->default('weighted_average');

            $table->decimal('total_qualifying_shares', 15, 2)->default(0);
            $table->decimal('gross_dividend_amount', 15, 2)->default(0);
            $table->decimal('total_withholding_tax', 15, 2)->default(0);
            $table->decimal('net_dividend_amount', 15, 2)->default(0);

            $table->enum('status', [
                'draft',
                'calculated',
                'approved',
                'posted',
                'cancelled',
            ])->default('draft');

            $table->unsignedBigInteger('calculated_by');

            $table->unsignedBigInteger('approved_by');

            $table->unsignedBigInteger('posted_by');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

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
        Schema::dropIfExists('dividend_runs');
    }
}
