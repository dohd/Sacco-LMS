<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountingWorkflowToDividends extends Migration
{
    public function up(): void
    {
        Schema::table('dividend_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('declaration_account_id')->nullable()->after('dividend_rate');
            $table->unsignedBigInteger('payable_account_id')->nullable()->after('declaration_account_id');
            $table->unsignedBigInteger('withholding_account_id')->nullable()->after('payable_account_id');
        });
        Schema::table('dividend_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_option');
        });
        Schema::table('dividend_deductions', function (Blueprint $table) {
            $table->unsignedBigInteger('offset_account_id')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('dividend_deductions', fn (Blueprint $table) => $table->dropColumn('offset_account_id'));
        Schema::table('dividend_allocations', fn (Blueprint $table) => $table->dropColumn('payment_account_id'));
        Schema::table('dividend_runs', fn (Blueprint $table) => $table->dropColumn(['declaration_account_id', 'payable_account_id', 'withholding_account_id']));
    }
}
