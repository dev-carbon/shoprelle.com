<?php

namespace App\Chatbot;

use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\PurchaseItemData;
use App\DataTransferObjects\PurchaseRequestData;
use Illuminate\Support\Str;

/**
 * The whole conversation, held server side.
 *
 * The state is a plain, array-serialisable structure rather than a graph of
 * models: it has to survive a round trip through the cache and, later, through
 * a WhatsApp or Telegram webhook.
 */
final class ConversationState
{
    /**
     * @param  list<array<string, mixed>>  $items  completed product lines
     * @param  array<string, mixed>  $draft  the product line being described
     * @param  array<string, mixed>  $customer  identity and destination
     * @param  list<ChatMessage>  $messages  transcript shown to the customer
     * @param  array<string, mixed>  $tracking  answers gathered while tracking a request
     * @param  string|null  $knownPhone  the number this conversation has proven itself to own
     */
    public function __construct(
        public string $id,
        public Step $step,
        public array $items = [],
        public array $draft = [],
        public array $customer = [],
        public array $messages = [],
        public ?string $reference = null,
        public Channel $channel = Channel::Web,
        public bool $wantsMoreItems = false,
        public ?Intent $intent = null,
        public array $tracking = [],
        public ?string $knownPhone = null,
    ) {}

    public static function start(Channel $channel = Channel::Web): self
    {
        return new self(
            id: Str::uuid()->toString(),
            step: Step::Menu,
            channel: $channel,
        );
    }

    public function pushBotMessage(string $text): void
    {
        $this->messages[] = ChatMessage::fromBot($text);
    }

    public function pushCustomerMessage(string $text): void
    {
        $this->messages[] = ChatMessage::fromCustomer($text);
    }

    public function isCompleted(): bool
    {
        return $this->step === Step::Completed;
    }

    /**
     * Product lines already confirmed, plus the one in progress when it is far
     * enough along to be shown in the summary.
     *
     * @return list<array<string, mixed>>
     */
    public function allItems(): array
    {
        return $this->items;
    }

    /**
     * Fold the finished draft into the item list and start a fresh one.
     */
    public function commitDraft(): void
    {
        if ($this->draft !== []) {
            $this->items[] = $this->draft;
            $this->draft = [];
        }
    }

    /**
     * Build the persistable representation of the conversation.
     *
     * The identifier is passed in rather than held on the state: the
     * conversation key already carries it, and one source is enough.
     */
    public function toPurchaseRequestData(?string $channelIdentifier = null): PurchaseRequestData
    {
        return new PurchaseRequestData(
            customer: CustomerData::fromArray([
                'first_name' => $this->customer['first_name'] ?? '',
                'last_name' => $this->customer['last_name'] ?? '',
                'phone' => $this->customer['phone'] ?? '',
                'email' => $this->customer['email'] ?? null,
                'country' => $this->customer['country'] ?? '',
                'city' => $this->customer['city'] ?? '',
            ]),
            items: array_map(PurchaseItemData::fromArray(...), $this->items),
            channel: $this->channel->value,
            channelIdentifier: $channelIdentifier,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'step' => $this->step->value,
            'items' => $this->items,
            'draft' => $this->draft,
            'customer' => $this->customer,
            'messages' => array_map(fn (ChatMessage $message): array => $message->toArray(), $this->messages),
            'reference' => $this->reference,
            'channel' => $this->channel->value,
            'wants_more_items' => $this->wantsMoreItems,
            'intent' => $this->intent?->value,
            'tracking' => $this->tracking,
            'known_phone' => $this->knownPhone,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        /** @var list<array{id: string, author: string, text: string, at: string}> $messages */
        $messages = $attributes['messages'] ?? [];

        return new self(
            id: $attributes['id'],
            step: Step::from($attributes['step']),
            items: $attributes['items'] ?? [],
            draft: $attributes['draft'] ?? [],
            customer: $attributes['customer'] ?? [],
            messages: array_map(ChatMessage::fromArray(...), $messages),
            reference: $attributes['reference'] ?? null,
            channel: Channel::tryFrom($attributes['channel'] ?? '') ?? Channel::Web,
            wantsMoreItems: $attributes['wants_more_items'] ?? false,
            intent: isset($attributes['intent']) ? Intent::from($attributes['intent']) : null,
            tracking: $attributes['tracking'] ?? [],
            knownPhone: $attributes['known_phone'] ?? null,
        );
    }
}
