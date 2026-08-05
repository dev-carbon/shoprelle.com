<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Des réglages du site que l'équipe change sans déployer — le bandeau de
     * promotion en premier. Une ligne par réglage : une clé, une valeur JSON.
     * Le défaut de chaque réglage vit dans config/shoprelle.php ; une ligne
     * ici est toujours une décision prise depuis le back-office.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
