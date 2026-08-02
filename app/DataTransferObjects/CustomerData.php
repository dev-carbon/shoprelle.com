<?php

namespace App\DataTransferObjects;

/**
 * Customer identity and destination collected during a conversation.
 */
final readonly class CustomerData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $phone,
        public ?string $email,
        public string $country,
        public string $city,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, phone: string, email?: string|null, country: string, city: string}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            firstName: trim($attributes['first_name']),
            lastName: trim($attributes['last_name']),
            phone: trim($attributes['phone']),
            email: isset($attributes['email']) ? trim($attributes['email']) ?: null : null,
            country: strtoupper(trim($attributes['country'])),
            city: trim($attributes['city']),
        );
    }

    /**
     * @return array{first_name: string, last_name: string, phone: string, email: string|null, country: string, city: string}
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
        ];
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
