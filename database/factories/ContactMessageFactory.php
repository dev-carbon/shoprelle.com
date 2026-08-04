<?php

namespace Database\Factories;

use App\Chatbot\Channel;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'message' => fake()->sentence(12),
            'reply_to' => fake()->boolean() ? fake()->e164PhoneNumber() : null,
            'channel' => Channel::Web,
            'handled_at' => null,
            'handled_by' => null,
        ];
    }

    /** Un message auquel l'équipe a déjà répondu. */
    public function handled(): static
    {
        return $this->state(fn (): array => ['handled_at' => now()]);
    }
}
