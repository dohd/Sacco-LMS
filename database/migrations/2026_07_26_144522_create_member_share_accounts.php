<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberShareAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_share_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');

            $table->unsignedBigInteger('share_product_id');

            $table->string('account_number')->unique();

            /*
             * Cached balances.
             * The transaction ledger remains the source of truth.
             */
            $table->unsignedInteger('total_units')->default(0);
            $table->decimal('share_balance', 15, 2)->default(0);
            $table->decimal('held_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2)->default(0);

            $table->date('opened_date');

            $table->enum('status', [
                'active',
                'frozen',
                'closed',
            ])->default('active');

            $table->timestamps();

            $table->unique([
                'member_id',
                'share_product_id',
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
        Schema::dropIfExists('member_share_accounts');
    }
}
