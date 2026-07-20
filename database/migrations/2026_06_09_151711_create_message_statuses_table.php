<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('message_statuses', function (Blueprint $table) {
            $table->id();

            $table->string('channel_prefix')->nullable();
            $table->string('api_version')->nullable();

            // sent, delivered, read, failed, undelivered
            $table->string('message_status')->nullable();
            $table->string('sms_status')->nullable();

            $table->string('sms_sid')->nullable();
            $table->string('message_sid')->nullable();

            $table->string('channel_install_sid')->nullable();
            $table->string('to')->nullable();
            $table->string('from')->nullable();

            $table->boolean('structured_message')->nullable();

            $table->string('account_sid')->nullable();
            $table->string('channel_to_address')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_statuses');
    }
}