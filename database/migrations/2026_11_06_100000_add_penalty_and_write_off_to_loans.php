<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenaltyAndWriteOffToLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_products', function (Blueprint $table) {
            // One-off penalty, as a percentage of the unpaid principal, interest and fees
            // of an installment that is still unpaid after the grace period.
            $table->decimal('penalty_rate', 8, 4)->default(0)->after('grace_period_days');
            // Assessed penalties are held here until paid (chart_of_accounts.id).
            $table->unsignedBigInteger('penalty_receivable_account_id')->nullable()->after('penalty_income_account_id');
            // Expense that absorbs a written-off loan (chart_of_accounts.id).
            $table->unsignedBigInteger('write_off_expense_account_id')->nullable()->after('penalty_receivable_account_id');
        });

        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->date('penalty_assessed_on')->nullable()->after('penalty_due');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->date('written_off_date')->nullable()->after('closure_reason');
            $table->unsignedBigInteger('written_off_by')->nullable()->after('written_off_date');
            $table->text('write_off_reason')->nullable()->after('written_off_by');
            $table->decimal('written_off_principal', 15, 2)->default(0)->after('write_off_reason');
            $table->decimal('written_off_interest', 15, 2)->default(0)->after('written_off_principal');
            $table->decimal('written_off_fees', 15, 2)->default(0)->after('written_off_interest');
            $table->decimal('written_off_penalty', 15, 2)->default(0)->after('written_off_fees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['written_off_date', 'written_off_by', 'write_off_reason',
                'written_off_principal', 'written_off_interest', 'written_off_fees', 'written_off_penalty']);
        });

        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->dropColumn('penalty_assessed_on');
        });

        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn(['penalty_rate', 'penalty_receivable_account_id', 'write_off_expense_account_id']);
        });
    }
}
