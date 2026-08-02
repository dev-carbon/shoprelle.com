<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Internal only. Never exposed on any customer-facing endpoint.
            $table->text('body');
            $table->timestamps();

            $table->index(['purchase_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
