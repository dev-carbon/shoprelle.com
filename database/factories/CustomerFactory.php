<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /** The default plaintext behind {@see withAccessCode()}. */
    public const TEST_ACCESS_CODE = 'K4M9PZ';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+2376'.fake()->unique()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'country' => 'CM',
            'city' => fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda']),
        ];
    }

    /**
     * Indicate that the customer did not share an email address.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
        ]);
    }

    /**
     * Give the customer a known access code, so a test can type it back.
     *
     * The code is hashed exactly as the application stores it; only the test
     * knows the plaintext, which is the whole point of the mechanism.
     */
    public function withAccessCode(string $code = self::TEST_ACCESS_CODE): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_code_hash' => Hash::make($code),
        ]);
    }

    /**
     * Place the customer in a specific destination country.
     */
    public function inCountry(string $country): static
    {
        return $this->state(fn (array $attributes): array => [
            'country' => $country,
        ]);
    }
}
