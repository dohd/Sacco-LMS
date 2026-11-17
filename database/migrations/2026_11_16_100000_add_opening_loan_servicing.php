<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpeningLoanServicing extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(false)->after('status');
        });

        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->string('loan_status_before')->nullable()->after('status');
        });

        Schema::create('loan_restructures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');
            $table->date('effective_date');
            $table->date('first_repayment_date');
            $table->unsignedInteger('remaining_months');
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->decimal('principal_balance', 15, 2)->default(0);
            $table->decimal('interest_balance', 15, 2)->default(0);
            $table->decimal('fees_balance', 15, 2)->default(0);
            $table->decimal('penalty_balance', 15, 2)->default(0);
            $table->string('previous_status', 30)->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();
            $table->index(['loan_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_restructures');
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropColumn('loan_status_before');
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('is_opening_balance');
        });
    }
}
