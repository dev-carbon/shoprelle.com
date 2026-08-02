<?php

namespace App\DataTransferObjects;

use App\Chatbot\Channel;

/**
 * A review as the conversation gathered it.
 *
 * The two identifiers travel as a phone number and a reference rather than as
 * ids: those are the only things a conversation ever knows, and turning them
 * into rows is the service's job, not the engine's.
 */
final readonly class ReviewData
{
    public function __construct(
        public int $rating,
        public Channel $channel,
        public ?string $comment = null,
        public ?string $phone = null,
        public ?string $reference = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'rating' => $this->rating,
            'comment' => $this->comment,
            'channel' => $this->channel,
        ];
    }
}
