<?php

namespace Database\Factories;

use App\Models\AdminNote;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNote>
 */
class AdminNoteFactory extends Factory
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
            'user_id' => User::factory()->admin(),
            'body' => fake()->sentence(12),
        ];
    }
}
