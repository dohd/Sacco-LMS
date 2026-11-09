<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PreparePilotImportCommit extends Migration
{
    public function __construct()
    {
        // Keep this to allow inspecting tables that already have other enum fields
        DB::connection()
            ->getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->string('place_of_birth')->nullable()->change();
            $table->text('residential_address')->nullable()->change();
            $table->string('next_of_kin_name')->nullable()->change();
            $table->string('next_of_kin_id')->nullable()->change();
            
            // Removed next_of_kin_relationship from here because ->change() doesn't support enum
            
            $table->decimal('monthly_contribution', 10, 2)->default(0)->change();
            $table->date('contribution_start_date')->nullable()->change();
            $table->string('kyc_status')->default('complete');
        });

        // Use a raw SQL statement to alter the enum column safely
        DB::statement("ALTER TABLE member_applications MODIFY COLUMN next_of_kin_relationship ENUM('spouse', 'parent', 'child', 'sibling', 'relative', 'guardian', 'other') NULL");

        Schema::table('members', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->date('admission_date')->nullable()->change();
            $table->string('kyc_status')->default('complete');
        });

        Schema::create('pilot_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('previewed');
            $table->string('membership_path');
            $table->unsignedBigInteger('savings_product_id');
            $table->unsignedBigInteger('share_product_id');
            $table->unsignedBigInteger('loan_product_id');
            $table->unsignedBigInteger('opening_equity_account_id');
            $table->json('preview_report')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pilot_import_batches');
        
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('kyc_status');
        });
        
        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropColumn('kyc_status');
            // If you want to revert the enum to its original state or drop the nullable modifier:
            // DB::statement("ALTER TABLE member_applications MODIFY COLUMN next_of_kin_relationship ENUM('your', 'original', 'values') NOT NULL");
        });
    }
}
