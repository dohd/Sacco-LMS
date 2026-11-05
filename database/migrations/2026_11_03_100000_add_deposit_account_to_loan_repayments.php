<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepositAccountToLoanRepayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_repayments', function (Blueprint $table) {
            // Cash or bank ledger account that receives the money (chart_of_accounts.id).
            // Null for account_credit repayments, which post to the savings control account.
            $table->unsignedBigInteger('deposit_account_id')->nullable()->after('payment_method');
            $table->index('deposit_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropIndex(['deposit_account_id']);
            $table->dropColumn('deposit_account_id');
        });
    }
}
