<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            $table->string('message_sid')->unique();
            $table->string('sms_sid')->nullable();
            $table->string('sms_message_sid')->nullable();

            $table->string('messaging_service_sid')->nullable();
            $table->string('message_type')->nullable();

            $table->string('sms_status')->nullable();

            $table->text('body')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_payload')->nullable();

            $table->string('from')->nullable();
            $table->string('to')->nullable();

            $table->string('wa_id')->nullable();
            $table->string('profile_name')->nullable();

            $table->string('external_user_id')->nullable();

            $table->integer('num_media')->default(0);
            $table->integer('num_segments')->default(1);
            $table->integer('referral_num_media')->default(0);

            $table->boolean('forwarded')->nullable();
            $table->boolean('frequently_forwarded')->nullable();            

            $table->string('original_replied_message_sid')->nullable();
            $table->string('original_replied_message_sender')->nullable();

            $table->json('channel_metadata')->nullable();

            $table->string('account_sid')->nullable();
            $table->string('api_version')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}

