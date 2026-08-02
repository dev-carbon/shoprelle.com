<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace');
            $table->text('product_url');

            // Everything below is supplied by the customer. When automatic product
            // retrieval lands, these columns become the fallback rather than the
            // only source, so they stay nullable by design.
            $table->string('product_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('variant')->nullable();
            $table->decimal('declared_price', 12, 2)->nullable();
            $table->string('declared_currency', 3)->nullable();
            $table->text('comment')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['purchase_request_id', 'position']);
            $table->index('marketplace');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
