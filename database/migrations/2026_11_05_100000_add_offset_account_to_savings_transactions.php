<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOffsetAccountToSavingsTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            // Ledger account that balances a savings adjustment (chart_of_accounts.id).
            $table->unsignedBigInteger('offset_account_id')->nullable()->after('deposit_account_id');
            $table->index('offset_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex(['offset_account_id']);
            $table->dropColumn('offset_account_id');
        });
    }
}
