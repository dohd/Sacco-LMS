<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAdjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_adjs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reason');
            $table->text('description');
            $table->string('adjustment_type',50);
            $table->date('date');
            $table->bigInteger('location_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->bigInteger('zoho_inventory_adjustment_id')->nullable();
        });


        Schema::create('stock_adj_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_adj_id');
            $table->bigInteger('item_id');
            $table->decimal('quantity_adjusted', 16, 4)->default(0);
            $table->decimal('item_total', 16, 4)->default(0);
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign('stock_adj_id')->references('id')->on('stock_adjs')->onDelete('cascade');

            $table->bigInteger('zoho_line_item_id')->nullable();
            $table->bigInteger('zoho_item_total')->nullable();
            $table->string('zoho_item_name')->nullable();
            $table->string('zoho_adjustment_account_id')->nullable();
            $table->string('zoho_adjustment_account_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_adjs');
        Schema::dropIfExists('stock_adj_items');
    }
}
