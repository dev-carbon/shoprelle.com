<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // Denormalised request id so attachments can be authorised and listed
            // without loading every item of the request.
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index('purchase_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
