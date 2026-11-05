<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisbursementAccountToLoanDisbursements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_disbursements', function (Blueprint $table) {
            // Cash or bank ledger account the loan is paid from (chart_of_accounts.id).
            // Null for account_credit disbursements, which post to the savings control account.
            $table->unsignedBigInteger('disbursement_account_id')->nullable()->after('disbursement_method');
            $table->index('disbursement_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loan_disbursements', function (Blueprint $table) {
            $table->dropIndex(['disbursement_account_id']);
            $table->dropColumn('disbursement_account_id');
        });
    }
}
