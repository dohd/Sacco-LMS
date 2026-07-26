<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDividendDeductions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dividend_deductions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('dividend_allocation_id');

            $table->enum('deduction_type', [
                'withholding_tax',
                'loan_offset',
                'contribution_arrears',
                'fee',
                'other',
            ]);

            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();

            $table->nullableMorphs('source');

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
        Schema::dropIfExists('dividend_deductions');
    }
}
