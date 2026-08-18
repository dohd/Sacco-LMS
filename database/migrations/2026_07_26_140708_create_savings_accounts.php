<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('savings_product_id');

            $table->string('account_number')->unique();

            $table->decimal('ledger_balance', 15, 2)->default(0);
            $table->decimal('held_balance', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->default(0);

            $table->date('opened_date');
            $table->unsignedBigInteger('opened_by');

            $table->date('closed_date')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->text('closure_reason')->nullable();

            $table->date('last_transaction_date')->nullable();

            $table->enum('status', [
                'active',
                'frozen',
                'closed',
            ])->default('active');

            $table->timestamps();

            $table->index('member_id');
            $table->index('savings_product_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('savings_accounts');
    }
}
