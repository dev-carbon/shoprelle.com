<?php

use App\Enums\PurchaseRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();

            // Customer-facing identifier, e.g. SHP-2607-4KJ9X2.
            $table->string('reference')->unique();

            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(PurchaseRequestStatus::New->value);

            // Destination is snapshotted on the request: a customer may move, and
            // past requests must keep the address they were shipped to.
            $table->char('country', 2);
            $table->string('city');

            // Conversation channel the request came from. Web today, WhatsApp and
            // Telegram later, without a schema change.
            $table->string('channel')->default('web');

            $table->text('customer_comment')->nullable();

            $table->decimal('quote_items_amount', 12, 2)->nullable();
            $table->decimal('quote_shipping_amount', 12, 2)->nullable();
            $table->decimal('quote_total_amount', 12, 2)->nullable();
            $table->string('quote_currency', 3)->nullable();
            $table->text('quote_notes')->nullable();
            $table->timestamp('quote_sent_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
