<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PageVisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une journée de trafic sur les pages publiques, en deux compteurs.
 *
 * C'est toute la mesure d'audience du site : pas de service tiers, pas de
 * cookie de plus que la session déjà là — la page de confidentialité promet
 * exactement ça. Une ligne par jour, incrémentée au passage par le middleware
 * `RecordPageVisit`, lue par l'écran Statistiques.
 *
 * @property int $id
 * @property CarbonImmutable $day
 * @property int $views
 * @property int $visitors
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class PageVisit extends Model
{
    /** @use HasFactory<PageVisitFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'immutable_date',
            'views' => 'integer',
            'visitors' => 'integer',
        ];
    }
}
