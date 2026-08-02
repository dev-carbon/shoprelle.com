<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records what a quote costs Shoprelle, next to what it charges the customer.
 *
 * The quote is billed in the destination currency while the goods are bought
 * abroad in another, so the margin lives in the gap between the two plus the
 * exchange rate applied on the day. Without these three columns that gap is
 * unknowable after the fact, since rates move.
 *
 * All three are nullable: they are a management figure never shown to the
 * customer, and a quote is still valid without them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->decimal('quote_cost_amount', 12, 2)->nullable()->after('quote_currency');
            $table->char('quote_cost_currency', 3)->nullable()->after('quote_cost_amount');

            // Units of the quote currency bought with one unit of the cost
            // currency, snapshotted at quote time. Six decimals because the
            // pair can be worth hundreds to one.
            $table->decimal('quote_exchange_rate', 16, 6)->nullable()->after('quote_cost_currency');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'quote_cost_amount',
                'quote_cost_currency',
                'quote_exchange_rate',
            ]);
        });
    }
};
