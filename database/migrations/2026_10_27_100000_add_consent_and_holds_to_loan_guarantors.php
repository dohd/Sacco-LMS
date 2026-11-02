<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsentAndHoldsToLoanGuarantors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_guarantors', function (Blueprint $table) {
            // pending, accepted, declined, held, released, invoked
            $table->string('status', 20)->default('pending')->after('witness_name');
            $table->timestamp('consented_at')->nullable()->after('status');
            $table->unsignedBigInteger('consented_by')->nullable()->after('consented_at');
            $table->text('consent_note')->nullable()->after('consented_by');
            $table->unsignedBigInteger('loan_id')->nullable()->after('consent_note');
            $table->decimal('held_amount', 15, 2)->default(0)->after('loan_id');
            $table->timestamp('released_at')->nullable()->after('held_amount');
            $table->unsignedBigInteger('released_by')->nullable()->after('released_at');
            $table->index('loan_id');
            $table->index(['member_id', 'status']);
        });

        Schema::create('loan_guarantor_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_guarantor_id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('share_account_id');
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('active'); // active, released, invoked
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['loan_guarantor_id', 'status']);
            $table->index(['share_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_guarantor_holds');
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->dropIndex(['loan_id']);
            $table->dropIndex(['member_id', 'status']);
            $table->dropColumn(['status', 'consented_at', 'consented_by', 'consent_note', 'loan_id', 'held_amount', 'released_at', 'released_by']);
        });
    }
}
