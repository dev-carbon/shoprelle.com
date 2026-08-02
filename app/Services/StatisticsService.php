<?php

namespace App\Services;

use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates for the back-office statistics screen.
 *
 * Every figure is a single grouped query rather than a loop over models, so the
 * page cost stays flat as the request volume grows.
 */
class StatisticsService
{
    /**
     * @return array<string, mixed>
     */
    public function forPeriod(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        return [
            'period_days' => $days,
            'headline' => $this->headline($since),
            'daily' => $this->daily($since, $days),
            'funnel' => $this->funnel(),
            'by_status' => $this->byStatus(),
            'top_marketplaces' => $this->topMarketplaces(),
            'top_cities' => $this->topCities(),
        ];
    }

    /**
     * The numbers that answer "how is the business doing" at a glance.
     *
     * @return array<string, mixed>
     */
    private function headline(CarbonImmutable $since): array
    {
        $inPeriod = PurchaseRequest::query()->where('created_at', '>=', $since);

        $quoted = PurchaseRequest::query()->whereNotNull('quote_sent_at');
        $quotedCount = (clone $quoted)->count();
        $quotedTotal = (float) (clone $quoted)->sum('quote_total_amount');

        return [
            'requests_in_period' => (clone $inPeriod)->count(),
            'customers_total' => Customer::query()->count(),
            'new_customers_in_period' => Customer::query()->where('created_at', '>=', $since)->count(),
            'items_total' => (int) PurchaseItem::query()->sum('quantity'),
            'quoted_total' => number_format($quotedTotal, 2, '.', ''),
            'average_quote' => number_format(
                $quotedCount > 0 ? $quotedTotal / $quotedCount : 0.0,
                2,
                '.',
                '',
            ),
            'currency' => config('shoprelle.quote_currency'),
        ];
    }

    /**
     * Requests per day, with empty days filled in so the series has no gaps.
     *
     * @return list<array{date: string, label: string, count: int}>
     */
    private function daily(CarbonImmutable $since, int $days): array
    {
        /** @var array<string, int> $counts */
        $counts = PurchaseRequest::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->pluck('aggregate', 'day')
            ->all();

        $series = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $since->addDays($offset);
            $key = $date->format('Y-m-d');

            $series[] = [
                'date' => $key,
                'label' => $date->translatedFormat('j M'),
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * How far requests get, as a funnel.
     *
     * Each stage counts requests that reached it *or went past it*, which is
     * what makes the drop-off between stages meaningful. Cancelled requests
     * still count for the stages they cleared before being cancelled.
     *
     * @return list<array{label: string, count: int, share: float}>
     */
    private function funnel(): array
    {
        $total = PurchaseRequest::query()->count();

        $stages = [
            'Demandes reçues' => PurchaseRequest::query(),
            'Devis envoyés' => PurchaseRequest::query()->whereNotNull('quote_sent_at'),
            'Paiements reçus' => $this->reached(PurchaseRequestStatus::PaymentReceived),
            'Achats effectués' => $this->reached(PurchaseRequestStatus::Purchased),
            'Expédiées' => $this->reached(PurchaseRequestStatus::Shipped),
            'Livrées' => $this->reached(PurchaseRequestStatus::Delivered),
        ];

        $funnel = [];

        foreach ($stages as $label => $query) {
            $count = $query->count();

            $funnel[] = [
                'label' => $label,
                'count' => $count,
                'share' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
            ];
        }

        return $funnel;
    }

    /**
     * Requests whose history shows they reached a given status, whatever their
     * status is today.
     *
     * @return Builder<PurchaseRequest>
     */
    private function reached(PurchaseRequestStatus $status): Builder
    {
        return PurchaseRequest::query()->whereHas(
            'statusHistories',
            fn ($query) => $query->where('to_status', $status),
        );
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function byStatus(): array
    {
        /** @var array<string, int> $counts */
        $counts = PurchaseRequest::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return array_map(fn (PurchaseRequestStatus $status): array => [
            'label' => $status->label(),
            'count' => (int) ($counts[$status->value] ?? 0),
        ], PurchaseRequestStatus::cases());
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function topMarketplaces(): array
    {
        /** @var array<string, int> $counts */
        $counts = PurchaseItem::query()
            ->select('marketplace', DB::raw('count(*) as aggregate'))
            ->groupBy('marketplace')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'marketplace')
            ->all();

        $rows = [];

        foreach ($counts as $value => $count) {
            $marketplace = Marketplace::tryFrom((string) $value);

            $rows[] = [
                'label' => $marketplace?->label() ?? (string) $value,
                'count' => (int) $count,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function topCities(): array
    {
        $rows = [];

        // Queried through the query builder: these are aggregate rows, not
        // purchase requests, and hydrating partial models would only invite
        // someone to call a method on them later.
        $cities = DB::table('purchase_requests')
            ->select('city', 'country', DB::raw('count(*) as aggregate'))
            ->groupBy('city', 'country')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();

        foreach ($cities as $city) {
            $country = config('shoprelle.countries.'.$city->country, $city->country);

            $rows[] = [
                'label' => $city->city.' · '.$country,
                'count' => (int) $city->aggregate,
            ];
        }

        return $rows;
    }
}
