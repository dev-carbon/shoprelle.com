<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');

            // Customers are identified by phone number: it is the one contact
            // detail every buyer has, and the channel the admin follows up on.
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->char('country', 2);
            $table->string('city');
            $table->timestamps();

            $table->index(['country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
