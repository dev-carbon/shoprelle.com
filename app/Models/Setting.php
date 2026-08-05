<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Un réglage du site, modifiable depuis le back-office sans déployer.
 *
 * Une clé, une valeur JSON. Le défaut de chaque réglage vit dans
 * config/shoprelle.php : tant que personne n'a enregistré depuis le
 * back-office, la table est vide et c'est la configuration qui parle.
 *
 * @property int $id
 * @property string $key
 * @property array<string, mixed> $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Setting extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * La valeur enregistrée pour une clé, ou le défaut donné.
     *
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public static function valueFor(string $key, array $default = []): array
    {
        return static::query()->where('key', $key)->first()->value ?? $default;
    }
}
