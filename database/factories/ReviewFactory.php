<?php

namespace Database\Factories;

use App\Chatbot\Channel;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Anonymous by default, which is what most reviews are: the
            // assistant asks nobody to identify themselves before listening.
            'customer_id' => null,
            'purchase_request_id' => null,
            'rating' => fake()->numberBetween(Review::MIN_RATING, Review::MAX_RATING),
            'comment' => fake()->optional()->sentence(),
            'channel' => Channel::Web,
            'approved_at' => null,
        ];
    }

    public function rated(int $rating): self
    {
        return $this->state(fn (): array => ['rating' => $rating]);
    }

    public function approved(): self
    {
        return $this->state(fn (): array => ['approved_at' => now()]);
    }
}
