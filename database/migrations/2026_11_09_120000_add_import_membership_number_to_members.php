<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportMembershipNumberToMembers extends Migration
{
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('import_membership_number')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['import_membership_number']);
            $table->dropColumn('import_membership_number');
        });
    }
}
