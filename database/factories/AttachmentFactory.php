<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'purchase_item_id' => null,
            'disk' => config('shoprelle.attachments.disk'),
            'path' => 'purchase-requests/'.fake()->uuid().'.jpg',
            'original_name' => 'capture.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(20_000, 800_000),
        ];
    }
}
