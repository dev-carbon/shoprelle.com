<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();

            // Null when the request was just created, or when the transition was
            // performed by the system rather than an administrator.
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['purchase_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
