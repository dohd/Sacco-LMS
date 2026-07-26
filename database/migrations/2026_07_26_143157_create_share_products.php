<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_products', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();

            // General ledger mapping
            $table->unsignedBigInteger('share_capital_account_id');
            $table->unsignedBigInteger('share_premium_account_id');

            /*
             * Nominal value of one share unit.
             * Example: one share unit = KES 100.
             */
            $table->decimal('unit_value', 15, 2);

            $table->unsignedInteger('minimum_units')->default(1);
            $table->unsignedInteger('maximum_units')->nullable();

            /*
             * Whether this share product qualifies for dividends.
             */
            $table->boolean('dividend_eligible')->default(true);

            /*
             * Whether shares can be transferred or refunded.
             */
            $table->boolean('allows_transfer')->default(false);
            $table->boolean('allows_redemption')->default(false);

            $table->boolean('can_secure_loan')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('share_products');
    }
}
