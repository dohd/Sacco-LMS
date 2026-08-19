<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            /*
             * The approved membership application from which
             * this member record was created.
             *
             * One application can create only one member.
             *
             * Change 'membership_applications' if your application
             * table uses a different name.
             */
            $table->unsignedBigInteger('member_id');

            /*
             * System-generated membership number.
             *
             * Example: MEM-2026-000001
             */
            $table->string('membership_number')->unique();

            /*
             * Optional link to the system user account.
             *
             * A member may be registered before receiving
             * access to the system.
             */
            $table->unsignedBigInteger('user_id')->nullable();            

            /*
             * Core member details copied from the approved application.
             *
             * Keeping these fields on the member record provides a
             * stable operational record even when the original
             * application is archived.
             */
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();

            $table->string('national_id')->unique();
            $table->string('phone');
            $table->string('email')->nullable();

            $table->text('current_address')->nullable();
            $table->text('residential_address')->nullable();

            /*
             * Member admission information.
             */
            $table->date('admission_date');

            $table->unsignedBigInteger('approved_by');            

            $table->timestamp('approved_at')->nullable();

            /*
             * Current membership lifecycle status.
             */
            $table->enum('status', [
                'active',
                'dormant',
                'suspended',
                'withdrawn',
                'deceased',
                'closed',
            ])->default('active');

            /*
             * Allows operational filtering while retaining
             * historical member records.
             */
            $table->boolean('is_active')->default(true);

            /*
             * Membership exit or closure information.
             */
            $table->date('closure_date')->nullable();

            $table->unsignedBigInteger('closed_by')->nullable();

            $table->text('closure_reason')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'status',
                'is_active',
            ]);

            $table->index([
                'last_name',
                'first_name',
            ]);

            $table->index('admission_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('members');
    }
}
