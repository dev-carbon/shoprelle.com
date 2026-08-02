<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer reviews, left from a conversation with the assistant.
 *
 * Both attributions are nullable and both survive a delete. A review is left by
 * whoever is talking to the bot, and that is often somebody the conversation
 * cannot name: no account is required to talk to Shoprelle, so a visitor with no
 * request behind them can still have an opinion worth keeping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('channel', 20);

            /*
             * Nothing published today reads this, and that is the point: a
             * review reaches the public only once somebody decides it should.
             * Adding the column now costs one line; adding it after the first
             * review goes out on the landing page costs an incident.
             */
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['approved_at', 'created_at']);
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
