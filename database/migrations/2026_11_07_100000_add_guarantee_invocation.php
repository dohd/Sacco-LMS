<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddGuaranteeInvocation extends Migration
{
    public function up()
    {
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->timestamp('invoked_at')->nullable();
            $table->unsignedBigInteger('invoked_by')->nullable();
            $table->decimal('invoked_amount', 15, 2)->default(0);
            $table->text('invocation_reason')->nullable();
        });

        // Repayments funded by a guarantor's shares and the share redemptions that fund them.
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->unsignedBigInteger('loan_guarantor_id')->nullable();
        });
        Schema::table('share_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('loan_guarantor_id')->nullable();
        });

        DB::statement("ALTER TABLE loan_repayments MODIFY payment_method ENUM('cash','bank_transfer','mobile_money','cheque','standing_order','check_off','post_dated_cheque','account_credit','guarantee') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE loan_repayments MODIFY payment_method ENUM('cash','bank_transfer','mobile_money','cheque','standing_order','check_off','post_dated_cheque','account_credit') NOT NULL");

        Schema::table('share_transactions', function (Blueprint $table) {
            $table->dropColumn('loan_guarantor_id');
        });
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropColumn('loan_guarantor_id');
        });
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->dropColumn(['invoked_at', 'invoked_by', 'invoked_amount', 'invocation_reason']);
        });
    }
}
