<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Who to talk back to on the channel the request came from: the
            // Telegram chat id today, a WhatsApp number tomorrow. Null for the
            // web, where the conversation lives in a session that cannot be
            // reached once the visitor has closed the tab.
            $table->string('channel_identifier')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('channel_identifier');
        });
    }
};
