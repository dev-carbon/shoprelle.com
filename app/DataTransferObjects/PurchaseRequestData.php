<?php

namespace App\DataTransferObjects;

/**
 * A complete purchase request, ready to be persisted.
 *
 * The chatbot builds this object from a conversation; a future API endpoint or
 * an admin-side manual entry form would build the very same object.
 */
final readonly class PurchaseRequestData
{
    /**
     * @param  list<PurchaseItemData>  $items
     * @param  string|null  $channelIdentifier  who to answer on that channel, when it can be answered
     */
    public function __construct(
        public CustomerData $customer,
        public array $items,
        public string $channel = 'web',
        public ?string $comment = null,
        public ?string $channelIdentifier = null,
    ) {}

    /**
     * The destination is taken from the customer details collected in the same
     * conversation, then snapshotted onto the request.
     */
    public function country(): string
    {
        return $this->customer->country;
    }

    public function city(): string
    {
        return $this->customer->city;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function totalQuantity(): int
    {
        return array_sum(array_map(fn (PurchaseItemData $item): int => $item->quantity, $this->items));
    }
}
