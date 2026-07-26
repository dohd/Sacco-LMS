<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJournalEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('accounting_period_id');

            $table->string('entry_number')->unique();

            /*
             * Prevents the same operational event from being posted twice.
             *
             * Examples:
             * loan_repayment:184:confirmed
             * savings_transaction:524:confirmed
             * dividend_run:15:posted
             */
            $table->string('posting_key')->unique();

            $table->date('transaction_date');
            $table->date('posting_date');

            $table->string('reference_number')->nullable();

            $table->string('event_type');

            /*
             * Links the journal to its operational source.
             */
            $table->nullableMorphs('source');

            $table->text('description');

            $table->enum('status', [
                'draft',
                'posted',
                'reversed',
            ])->default('draft');

            $table->unsignedBigInteger('created_by');

            $table->unsignedBigInteger('posted_by');

            $table->timestamp('posted_at')->nullable();

            /*
             * Reversal information.
             */
            $table->unsignedBigInteger('reversal_of_id');

            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            $table->index([
                'transaction_date',
                'status',
            ]);

            $table->index([
                'accounting_period_id',
                'status',
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
        Schema::dropIfExists('journal_entries');
    }
}
