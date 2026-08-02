<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewListResource;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * Every review left from a conversation, newest first.
     */
    public function __invoke(): Response
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::query()
            ->with(['customer', 'purchaseRequest'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reviews/index', [
            'reviews' => ReviewListResource::collection($reviews),
            'summary' => [
                'total' => Review::count(),
                // Rounded to one decimal here rather than in the browser: the
                // average is a server fact, and formatting it twice is how the
                // two end up disagreeing.
                'average' => round((float) Review::avg('rating'), 1),
            ],
        ]);
    }
}
