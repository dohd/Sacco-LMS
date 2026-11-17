<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_details', function (Blueprint $table) {
            $table->id();
            $table->string('display_name'); // e.g., "Office Supplies"
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']); // Asset, Liability, Equity, Income, Expense, etc.
            $table->string('subtype', 50);
            $table->string('display_subtype', 50);
            $table->text('description')->nullable(); // Purpose or usage notes
            // Audit & metadata
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_details');
    }
}
