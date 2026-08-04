<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La sélection de produits montrée sur la vitrine.
 *
 * Ce n'est pas un catalogue : rien ici ne se vend, rien ne se met au panier. Ce
 * sont des exemples tenus à la main — un produit, sa photo, son prix indicatif
 * et le lien vers la plateforme où il se trouve — dont le rôle est de répondre
 * à « qu'est-ce que je peux commander ? » avant que le visiteur n'ait à le
 * demander.
 *
 * D'où ce que la table ne contient pas : ni stock, ni variantes, ni prix
 * calculé. Le prix affiché est indicatif et daté par sa colonne `updated_at` ;
 * le seul prix qui engage le service reste celui du devis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            /*
             * Le chemin de la photo sur le disque public, jamais une URL
             * distante : afficher l'image hébergée par la plateforme reviendrait
             * à lui laisser décider quand notre page se casse.
             */
            $table->string('image_path')->nullable();

            $table->string('marketplace');
            $table->string('category');
            $table->text('product_url');

            /*
             * Indicatif, et affiché comme tel. Nullable parce qu'un produit
             * peut être mis en avant avant qu'on ait relevé son prix.
             */
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            /*
             * `is_featured` décide de la présence sur la vitrine, `position` de
             * l'ordre. Deux colonnes plutôt qu'une : retirer un produit de la
             * page sans perdre sa place dans la liste est exactement ce qu'on
             * veut pouvoir faire.
             */
            $table->boolean('is_featured')->default(true);
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamps();

            $table->index(['is_featured', 'position']);
            $table->index('category');
            $table->index('marketplace');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
