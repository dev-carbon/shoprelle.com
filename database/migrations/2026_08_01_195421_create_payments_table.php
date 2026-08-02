<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();

            $table->string('method');

            // The wallet or bank behind the method, e.g. "Orange Money". Free
            // text rather than an enum: the list differs by country and changes
            // faster than a deployment.
            $table->string('provider')->nullable();

            // Signed on purpose. A refund is a negative line, so cancelling a
            // paid request will not need a schema change; the admin form only
            // accepts positive amounts for now.
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);

            // The transaction id the customer quotes back, used to reconcile
            // against the operator's statement.
            $table->string('provider_reference')->nullable();

            // When the money actually arrived, which is not when it was keyed
            // in: payments are often recorded the morning after.
            $table->timestamp('received_at');

            // Nulled rather than cascaded: deleting an employee must never
            // erase the record that money was received.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_request_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
