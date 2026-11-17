<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowUnapprovedDividendRuns extends Migration
{
    public function up(): void
    {
        Schema::table('dividend_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->change();
            $table->unsignedBigInteger('posted_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dividend_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable(false)->change();
            $table->unsignedBigInteger('posted_by')->nullable(false)->change();
        });
    }
}
