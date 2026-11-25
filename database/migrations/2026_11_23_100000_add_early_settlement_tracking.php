<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEarlySettlementTracking extends Migration
{
    public function up()
    {
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->boolean('is_early_settlement')->default(false)->after('amount_paid');
            $table->decimal('interest_waived', 15, 2)->default(0)->after('interest_amount');
        });

        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->decimal('interest_waived', 15, 2)->default(0)->after('interest_due');
        });
    }

    public function down()
    {
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->dropColumn('interest_waived');
        });

        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropColumn(['is_early_settlement', 'interest_waived']);
        });
    }
}
