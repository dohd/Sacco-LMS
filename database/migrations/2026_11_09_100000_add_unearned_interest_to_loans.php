<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnearnedInterestToLoans extends Migration
{
    public function up()
    {
        Schema::table('loan_products', function (Blueprint $table) {
            // Liability that holds interest charged but not yet due. Empty = recognise interest in full at disbursement.
            $table->unsignedBigInteger('unearned_interest_account_id')->nullable();
        });
        Schema::table('loans', function (Blueprint $table) {
            // True when this loan's interest was booked to unearned interest and is released as installments fall due.
            $table->boolean('interest_deferred')->default(false);
        });
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->date('interest_released_on')->nullable();
            $table->index(['loan_id', 'interest_released_on']);
        });
    }

    public function down()
    {
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->dropIndex(['loan_id', 'interest_released_on']);
            $table->dropColumn('interest_released_on');
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('interest_deferred');
        });
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn('unearned_interest_account_id');
        });
    }
}
