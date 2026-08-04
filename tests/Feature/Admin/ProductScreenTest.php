<?php

use App\Enums\Marketplace;
use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');

    $this->admin = User::factory()->admin()->create();
});

/** Ce qu'un formulaire valide envoie, sans la photo. */
function productPayload(array $overrides = []): array
{
    return [
        'name' => 'Veste matelassée',
        'marketplace' => Marketplace::Shein->value,
        'category' => ProductCategory::Mode->value,
        'product_url' => 'https://www.shein.com/veste-p-1234.html',
        'price' => 19500,
        'position' => 0,
        'is_featured' => true,
        ...$overrides,
    ];
}

it('lists the selection for an administrator', function () {
    Product::factory()->create(['name' => 'Veste matelassée']);

    $this->actingAs($this->admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/index')
            ->has('products', 1)
            ->where('products.0.name', 'Veste matelassée')
            ->has('categories')
            ->has('marketplaces')
        );
});

it('keeps the selection away from a guest', function () {
    $this->get(route('admin.products.index'))->assertRedirect(route('login'));
});

it('keeps the selection away from a signed-in customer', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.products.index'))
        ->assertForbidden();
});

it('adds a product with its photo', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            ...productPayload(),
            'image' => UploadedFile::fake()->image('veste.jpg'),
        ])
        ->assertRedirect();

    $product = Product::sole();

    expect($product->name)->toBe('Veste matelassée')
        ->and($product->marketplace)->toBe(Marketplace::Shein)
        ->and($product->category)->toBe(ProductCategory::Mode)
        ->and($product->is_featured)->toBeTrue()
        ->and($product->currency)->toBe(config('shoprelle.quote_currency'));

    Storage::disk('public')->assertExists((string) $product->image_path);
});

it('refuses a product without a photo', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), productPayload())
        ->assertSessionHasErrors('image');

    expect(Product::count())->toBe(0);
});

it('refuses a link that is not a url', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            ...productPayload(['product_url' => 'shein point com']),
            'image' => UploadedFile::fake()->image('veste.jpg'),
        ])
        ->assertSessionHasErrors('product_url');
});

it('edits a product without asking for its photo again', function () {
    $product = Product::factory()->create([
        'image_path' => 'products/original.webp',
        'name' => 'Ancien nom',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product), productPayload([
            'name' => 'Nouveau nom',
        ]))
        ->assertRedirect();

    expect($product->refresh()->name)->toBe('Nouveau nom')
        ->and($product->image_path)->toBe('products/original.webp');
});

it('replaces the photo and drops the previous file', function () {
    Storage::disk('public')->put('products/original.webp', 'binaire');

    $product = Product::factory()->create(['image_path' => 'products/original.webp']);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product), [
            ...productPayload(),
            'image' => UploadedFile::fake()->image('nouvelle.jpg'),
        ])
        ->assertRedirect();

    expect($product->refresh()->image_path)->not->toBe('products/original.webp');

    Storage::disk('public')->assertMissing('products/original.webp');
    Storage::disk('public')->assertExists((string) $product->image_path);
});

it('removes a product and its photo', function () {
    Storage::disk('public')->put('products/original.webp', 'binaire');

    $product = Product::factory()->create(['image_path' => 'products/original.webp']);

    $this->actingAs($this->admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect();

    expect(Product::count())->toBe(0);

    Storage::disk('public')->assertMissing('products/original.webp');
});
