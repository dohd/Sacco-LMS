<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddJournalNumberSequences extends Migration
{
    public function up()
    {
        // One row per year holds the last journal entry number issued.
        Schema::create('journal_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        // Manual journal posting keys follow the same type:id:state convention as every other entry.
        DB::statement("UPDATE journal_entries SET posting_key = CONCAT('manual_journal:', id, ':posted') WHERE event_type = 'manual'");
        DB::statement("UPDATE journal_entries SET posting_key = CONCAT('manual_journal:', reversal_of_id, ':reversed') WHERE event_type = 'manual_reversal' AND reversal_of_id IS NOT NULL");
    }

    public function down()
    {
        DB::statement("UPDATE journal_entries SET posting_key = CONCAT('manual:', entry_number) WHERE event_type = 'manual'");
        DB::statement("UPDATE journal_entries SET posting_key = CONCAT('manual_reversal:', reversal_of_id) WHERE event_type = 'manual_reversal' AND reversal_of_id IS NOT NULL");
        Schema::dropIfExists('journal_number_sequences');
    }
}
