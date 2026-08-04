<?php

namespace App\Http\Requests\Admin;

use App\Enums\Marketplace;
use App\Enums\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ce qu'on accepte pour un produit de la sélection, à l'ajout comme à la
 * modification.
 *
 * Une seule classe pour les deux : les règles sont les mêmes à une près — la
 * photo, obligatoire quand le produit n'existe pas encore et facultative
 * ensuite, puisqu'on doit pouvoir corriger un prix sans redéposer l'image.
 *
 * L'autorisation est laissée au contrôleur, qui interroge `ProductPolicy` comme
 * les autres écrans d'administration.
 */
class ProductRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreating = $this->route('product') === null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'marketplace' => ['required', Rule::enum(Marketplace::class)],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'product_url' => ['required', 'url:http,https', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'is_featured' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:9999'],
            /*
             * 4 Mo, et des formats que tout navigateur sait afficher. C'est la
             * seule entrée de fichier de l'administration, et elle mérite
             * d'être bornée serré.
             */
            'image' => [
                $isCreating ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'marketplace' => 'plateforme',
            'category' => 'catégorie',
            'product_url' => 'lien du produit',
            'price' => 'prix',
            'image' => 'photo',
        ];
    }
}
