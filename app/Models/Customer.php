<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * A person who submits purchase requests. Customers are not application users:
 * they are created from the chatbot conversation and identified by phone number.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $access_code_hash
 * @property string $country
 * @property string $city
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read Collection<int, PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count populated by withCount()
 * @property-read string|null $purchase_requests_max_created_at populated by withMax()
 */
#[Fillable(['first_name', 'last_name', 'phone', 'email', 'country', 'city'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * Characters an access code is drawn from.
     *
     * Zero, one, O, I and L are left out: the code is read off a screen and
     * typed back, often from a photo of it, and a customer who cannot tell a
     * zero from an O will blame the service rather than the font.
     */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private const LENGTH = 6;

    /**
     * The code in clear, populated only on the request that generated it.
     *
     * Declared as a real property rather than an attribute so Eloquent never
     * sees it and can never write it to a column. It exists for exactly one
     * hop: from generation to the message that shows it to the customer.
     */
    public ?string $plainAccessCode = null;

    /**
     * @return HasMany<PurchaseRequest, $this>
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * The configured country name, falling back to the raw ISO code.
     */
    public function countryName(): string
    {
        return config('shoprelle.countries.'.$this->country, $this->country);
    }

    /**
     * Give the customer an access code, unless they already have one.
     *
     * Returning customers keep theirs: it is the key to their whole history, so
     * a second request must not silently invalidate the code they saved after
     * the first. Does not save — the caller is inside a transaction and decides
     * when to persist.
     */
    public function ensureAccessCode(): void
    {
        if ($this->access_code_hash !== null) {
            return;
        }

        $this->plainAccessCode = self::generateAccessCode();
        $this->access_code_hash = Hash::make($this->plainAccessCode);
    }

    /**
     * Whether a customer-supplied code opens this account.
     *
     * Normalises before comparing, because the code is shown grouped and typed
     * back with whatever spacing and case the customer feels like.
     */
    public function matchesAccessCode(string $code): bool
    {
        if ($this->access_code_hash === null) {
            return false;
        }

        return Hash::check(static::normalizeAccessCode($code), $this->access_code_hash);
    }

    /**
     * The code as the customer is shown it, split for readability.
     */
    public static function formatAccessCode(string $code): string
    {
        return implode('-', str_split($code, 3));
    }

    /**
     * Strip the formatting a customer may have typed back.
     */
    public static function normalizeAccessCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private static function generateAccessCode(): string
    {
        $alphabet = self::ALPHABET;
        $code = '';

        for ($index = 0; $index < self::LENGTH; $index++) {
            // random_int rather than rand: this is a credential.
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
