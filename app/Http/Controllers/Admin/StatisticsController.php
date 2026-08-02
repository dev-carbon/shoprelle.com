<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    /**
     * Periods the screen offers, in days.
     */
    private const PERIODS = [7, 30, 90];

    public function __construct(
        private StatisticsService $statistics,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $days = (int) $request->integer('period', 30);
        $days = in_array($days, self::PERIODS, strict: true) ? $days : 30;

        return Inertia::render('admin/statistics/index', [
            'statistics' => $this->statistics->forPeriod($days),
            'periods' => self::PERIODS,
        ]);
    }
}
