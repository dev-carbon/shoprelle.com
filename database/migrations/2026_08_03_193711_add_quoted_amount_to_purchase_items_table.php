<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // What the whole line is billed at, quantity included, in the quote
            // currency. Null until the request is quoted; the sum of these lines
            // is what the quote charges for the goods.
            $table->decimal('quoted_amount', 12, 2)->nullable()->after('declared_currency');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('quoted_amount');
        });
    }
};
