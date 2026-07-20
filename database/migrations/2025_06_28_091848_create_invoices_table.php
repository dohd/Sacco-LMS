<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('record_num');
            $table->bigInteger('customer_id');
            $table->string('customer_name');
            $table->string('order_number',50)->nullable();
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('payment_terms', 50);
            $table->string('payment_terms_label', 50);
            $table->bigInteger('payment_terms_id');
            $table->date('due_date');
            $table->bigInteger('salesperson_id')->nullable();
            $table->string('salesperson_name')->nullable();
            $table->string('salesperson_email',50)->nullable();
            $table->bigInteger('location_id')->nullable();
            $table->bigInteger('currency_id');
            $table->string('currency_code', 20);
            $table->decimal('currency_rate', 16, 4)->default(0);
            $table->text('notes')->nullable();
            $table->decimal('taxable', 16, 4)->default(0);
            $table->decimal('tax', 16, 4)->default(0);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('total', 16, 4)->default(0);
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->bigInteger('zoho_invoice_id')->nullable();
            $table->string('zoho_invoice_number',50)->nullable();
            $table->string('zoho_status',20)->nullable();
            $table->decimal('zoho_exchange_rate', 16, 4)->default(0);
            $table->decimal('zoho_balance', 16, 4)->default(0);
            $table->string('zoho_invoice_url')->nullable();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->bigInteger('item_id');
            $table->integer('row_index')->default(0);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit',50)->nullable();
            $table->decimal('quantity', 16, 4)->default(0);
            $table->decimal('rate', 16, 4)->default(0);
            $table->decimal('amount', 16, 4)->default(0);
            $table->bigInteger('tax_id')->nullable();
            $table->decimal('tax_percentage', 16, 4)->default(0);
            $table->decimal('item_tax', 16, 4)->default(0);
            $table->string('product_type',50)->nullable(); // goods, service
            $table->string('sku',50)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

            $table->bigInteger('zoho_line_item_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_items');
    }
}
