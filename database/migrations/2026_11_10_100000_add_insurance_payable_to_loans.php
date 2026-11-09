<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsurancePayableToLoans extends Migration
{
    public function up()
    {
        Schema::table('loan_products', function (Blueprint $table) {
            // Liability that holds insurance collected for the insurer. Empty = insurance is booked with the other fees as income.
            $table->unsignedBigInteger('insurance_payable_account_id')->nullable();
        });
        Schema::table('loans', function (Blueprint $table) {
            // The part of fees_due that is insurance. Zero on loans booked before the split existed.
            $table->decimal('insurance_due', 15, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('insurance_due');
        });
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn('insurance_payable_account_id');
        });
    }
}
