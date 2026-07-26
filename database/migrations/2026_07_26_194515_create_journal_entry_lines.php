<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJournalEntryLines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('journal_entry_id');

            $table->unsignedBigInteger('chart_of_account_id');

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->text('description')->nullable();

            /*
             * Optional subledger references.
             */
            $table->unsignedBigInteger('member_id');

            $table->unsignedBigInteger('loan_id');

            $table->unsignedBigInteger('savings_account_id');

            $table->unsignedBigInteger('member_share_account_id');

            $table->timestamps();

            $table->index([
                'chart_of_account_id',
                'journal_entry_id',
            ]);

            $table->index([
                'member_id',
                'chart_of_account_id',
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
        Schema::dropIfExists('journal_entry_lines');
    }
}
