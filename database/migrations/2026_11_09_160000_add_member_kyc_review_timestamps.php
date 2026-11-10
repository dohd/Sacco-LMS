<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberKycReviewTimestamps extends Migration
{
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('kyc_submitted_at')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->unsignedBigInteger('kyc_verified_by')->nullable();
        });
    }

    public function down()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['kyc_submitted_at', 'kyc_verified_at', 'kyc_verified_by']);
        });
    }
}
