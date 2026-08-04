<?php

namespace App\Models;

use App\Chatbot\Channel;
use Carbon\CarbonImmutable;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message écrit depuis l'assistant, à destination de l'équipe.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $message
 * @property string|null $reply_to
 * @property Channel $channel
 * @property CarbonImmutable|null $handled_at
 * @property int|null $handled_by
 * @property CarbonImmutable|null $created_at
 */
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'handled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Les messages qui attendent encore une réponse.
     *
     * @param  Builder<ContactMessage>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('handled_at');
    }
}
