<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChartOfAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('account_code', 50)->unique();
            $table->string('account_name');

            $table->unsignedBigInteger('parent_id');

            $table->enum('account_type', [
                'asset',
                'liability',
                'equity',
                'income',
                'expense',
            ]);

            $table->enum('normal_balance', [
                'debit',
                'credit',
            ]);

            /*
             * Control accounts connect the GL to detailed subledgers.
             *
             * Examples:
             * - Member savings control
             * - Loan principal control
             * - Share capital control
             */
            $table->boolean('is_control_account')->default(false);

            /*
             * Header accounts cannot receive journal postings.
             */
            $table->boolean('is_postable')->default(true);

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index([
                'account_type',
                'is_active',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_of_accounts');
    }
}
