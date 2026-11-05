<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLedgerAccountToShareTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('share_transactions', function (Blueprint $table) {
            // Cash/bank account for purchases and redemptions, or the account
            // that balances a bonus issue or adjustment (chart_of_accounts.id).
            $table->unsignedBigInteger('ledger_account_id')->nullable()->after('payment_method');
            $table->index('ledger_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('share_transactions', function (Blueprint $table) {
            $table->dropIndex(['ledger_account_id']);
            $table->dropColumn('ledger_account_id');
        });
    }
}
