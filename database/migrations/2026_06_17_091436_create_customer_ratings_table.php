<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_received_id')->nullable();
            
            $table->string('invoice_no', 150)->nullable();
            $table->string('payment_received_no', 150)->nullable();
            
            $table->string('customer_name', 150)->nullable();
            $table->string('phone_number', 30)->nullable();

            $table->tinyInteger('rating_score')->nullable();
            $table->text('rating_comment')->nullable();

            $table->enum('rating_status', [
                'pending_rating',
                'rating_received',
                'pending_comment',
                'comment_received',
                'google_review_requested',
                'complaint_created',
                'closed'
            ])->default('pending_rating');

            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->string('resolution_action', 199)->nullable();

            $table->string('twilio_from', 50)->nullable();
            $table->string('twilio_to', 50)->nullable();
            $table->string('last_message_sid', 100)->nullable();
            $table->tinyInteger('is_opt_out')->default(0);
            $table->tinyInteger('is_opt_back')->default(0);

            $table->dateTime('sent_at')->nullable();
            $table->dateTime('rating_received_at')->nullable();
            $table->dateTime('comment_received_at')->nullable();
            $table->dateTime('opt_out_at')->nullable();
            $table->dateTime('opt_back_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
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
        Schema::dropIfExists('customer_ratings');
    }
}
