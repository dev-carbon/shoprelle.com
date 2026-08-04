<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Marketplace;
use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La sélection de produits montrée sur la vitrine, tenue à la main.
 *
 * Tout tient sur un écran : la liste et le formulaire. Il n'y a jamais qu'une
 * poignée de produits mis en avant, et une page par produit ferait cliquer
 * trois fois pour corriger un prix.
 */
class ProductController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->imageUrl(),
                'marketplace' => $product->marketplace->value,
                'marketplace_label' => $product->marketplace->label(),
                'category' => $product->category->value,
                'category_label' => $product->category->label(),
                'product_url' => $product->product_url,
                'price' => $product->price,
                'currency' => $product->currency,
                'is_featured' => $product->is_featured,
                'position' => $product->position,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'marketplaces' => array_map(
                fn (Marketplace $marketplace): array => [
                    'value' => $marketplace->value,
                    'label' => $marketplace->label(),
                ],
                Marketplace::cases(),
            ),
            'categories' => ProductCategory::options(),
            'currency' => config('shoprelle.quote_currency'),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        Product::create([
            ...$this->attributes($request),
            'image_path' => $request->file('image')?->store('products', 'public'),
        ]);

        return back()->with('status', 'Produit ajouté.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $attributes = $this->attributes($request);

        /*
         * L'ancienne photo n'est supprimée qu'une fois la nouvelle écrite :
         * l'inverse laisse un produit sans image si le dépôt échoue.
         */
        if ($request->hasFile('image')) {
            $previous = $product->image_path;
            $attributes['image_path'] = $request->file('image')->store('products', 'public');

            if ($previous !== null) {
                Storage::disk('public')->delete($previous);
            }
        }

        $product->update($attributes);

        return back()->with('status', 'Produit modifié.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->image_path !== null) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('status', 'Produit supprimé.');
    }

    /**
     * Les champs communs à l'ajout et à la modification.
     *
     * La devise n'est pas demandée : les prix de la vitrine sont indicatifs et
     * affichés dans la monnaie des devis. La faire saisir reviendrait à laisser
     * une page mélanger deux monnaies sans le dire.
     *
     * @return array<string, mixed>
     */
    private function attributes(ProductRequest $request): array
    {
        return [
            'name' => $request->string('name')->toString(),
            'marketplace' => $request->enum('marketplace', Marketplace::class),
            'category' => $request->enum('category', ProductCategory::class),
            'product_url' => $request->string('product_url')->toString(),
            'price' => $request->input('price'),
            'currency' => $request->input('price') === null
                ? null
                : config('shoprelle.quote_currency'),
            'is_featured' => $request->boolean('is_featured'),
            'position' => $request->integer('position'),
        ];
    }
}
