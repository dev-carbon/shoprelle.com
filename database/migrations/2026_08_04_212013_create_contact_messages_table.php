<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'un visiteur nous écrit depuis l'assistant.
 *
 * La page d'accueil donne une adresse email et rien d'autre — ce qui suppose
 * d'ouvrir sa messagerie, donc de quitter le site, donc de ne pas écrire. Cette
 * table est l'autre chemin : on tape son message là où l'on est déjà.
 *
 * Le moyen de rappel est facultatif et le reste : quelqu'un qui pose une
 * question sans laisser de numéro a quand même posé sa question, et exiger une
 * identité pour l'accepter reviendrait à ne pas la recevoir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            /*
             * Le client, quand la conversation en connaissait un. Nul le reste
             * du temps : parler à l'assistant ne demande aucun compte, et c'est
             * une propriété du service, pas un manque à combler.
             */
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->text('message');

            /* Un numéro ou une adresse, tel qu'il a été écrit. */
            $table->string('reply_to')->nullable();

            $table->string('channel');

            /* Traité, et par qui. Deux colonnes plutôt qu'un booléen : savoir
             * qui a répondu vaut mieux que savoir que quelqu'un l'a fait. */
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['handled_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
