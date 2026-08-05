<?php

namespace Database\Factories;

use App\Models\PageVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageVisit>
 */
class PageVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $views = fake()->numberBetween(1, 200);

        return [
            'day' => today(),
            'views' => $views,
            'visitors' => fake()->numberBetween(1, $views),
        ];
    }
}
