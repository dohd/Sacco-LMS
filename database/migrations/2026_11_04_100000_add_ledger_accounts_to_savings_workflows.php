<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLedgerAccountsToSavingsWorkflows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            // Cash or bank ledger account that received a deposit (chart_of_accounts.id).
            $table->unsignedBigInteger('deposit_account_id')->nullable()->after('payment_method');
            $table->index('deposit_account_id');
        });

        Schema::table('savings_withdrawals', function (Blueprint $table) {
            // Cash or bank ledger account the withdrawal was paid from (chart_of_accounts.id).
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_method');
            $table->index('payment_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('savings_withdrawals', function (Blueprint $table) {
            $table->dropIndex(['payment_account_id']);
            $table->dropColumn('payment_account_id');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex(['deposit_account_id']);
            $table->dropColumn('deposit_account_id');
        });
    }
}
