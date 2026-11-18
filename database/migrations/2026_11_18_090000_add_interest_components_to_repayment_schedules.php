<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInterestComponentsToRepaymentSchedules extends Migration
{
    public function up()
    {
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->decimal('deferred_interest_due', 15, 2)->default(0)->after('interest_due');
            
            // NULL distinguishes legacy/original schedules from a restructure
            // schedule whose future interest may correctly be zero.
            $table->decimal('future_interest_due', 15, 2)->nullable()->after('deferred_interest_due');
        });

        DB::table('loan_repayment_schedules')
            ->whereIn('loan_id', DB::table('loans')->where('interest_deferred', true)->select('id'))
            ->update(['deferred_interest_due' => DB::raw('interest_due')]);
    }

    public function down()
    {
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->dropColumn(['deferred_interest_due', 'future_interest_due']);
        });
    }
}
