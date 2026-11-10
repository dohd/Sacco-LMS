<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowPendingMemberApplicationDateOfBirth extends Migration
{
    public function up()
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
        });
    }

    public function down()
    {
        // Pending imported applications can legitimately have no birth date.
        // Keep the column nullable on rollback rather than inventing a date.
    }
}
