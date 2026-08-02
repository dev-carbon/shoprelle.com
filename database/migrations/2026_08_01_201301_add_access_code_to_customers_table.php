<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The secret that turns "Mes demandes" into something a stranger cannot open.
 *
 * Listing a customer's requests used to need only their phone number, and a
 * phone number is not a secret: it sits on a WhatsApp profile. Worse, the
 * references it returned are what "Suivre ma demande" needs, and tracking
 * discloses the quoted amount. One guessable value therefore opened the whole
 * history, money included.
 *
 * Hashed, never stored in clear: a leaked database must not hand over the
 * codes, and bcrypt's cost is a second brake on guessing alongside the rate
 * limiter. The consequence is accepted — a lost code cannot be resent, only
 * replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('access_code_hash')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('access_code_hash');
        });
    }
};
