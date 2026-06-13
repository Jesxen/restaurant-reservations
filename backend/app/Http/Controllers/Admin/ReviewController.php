<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * List all reviews (staff), optionally filtered by approval state
     * (?aprobada=0|1), newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reviews = Review::query()
            ->when($request->filled('aprobada'), fn ($q) => $q->where('aprobada', $request->boolean('aprobada')))
            ->orderByDesc('created_at')
            ->get();

        return ReviewResource::collection($reviews);
    }

    /**
     * Approve / unapprove a review.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $this->authorize('moderate', $review);

        $data = $request->validate([
            'aprobada' => ['required', 'boolean'],
        ]);

        $review->update(['aprobada' => $data['aprobada']]);

        return (new ReviewResource($review))
            ->additional(['message' => $review->aprobada ? 'Reseña aprobada.' : 'Reseña ocultada.'])
            ->response();
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['message' => 'Reseña eliminada.']);
    }
}
