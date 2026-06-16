<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * Public list of approved reviews, newest first, with an aggregate
     * (average rating + count) in the collection meta.
     */
    public function index(): AnonymousResourceCollection
    {
        $reviews = Review::query()
            ->where('aprobada', true)
            ->orderByDesc('created_at')
            ->get();

        return ReviewResource::collection($reviews)
            ->additional(['meta' => $this->resumenData()]);
    }

    /**
     * Aggregate-only endpoint (average rating + count over approved reviews).
     */
    public function resumen(): JsonResponse
    {
        return response()->json(['data' => $this->resumenData()]);
    }

    /**
     * Submit a review (authenticated client with a completed reservation).
     * Created unapproved (pending moderation).
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $this->authorize('create', Review::class);

        $user = $request->user();
        $reserva = $request->reservaParaReseñar();

        $review = Review::create([
            'user_id' => $user->id,
            'reserva_id' => $reserva?->id,
            'nombre' => $user->name,
            'rating' => $request->integer('rating'),
            'comentario' => $request->string('comentario'),
            'aprobada' => true,
        ]);

        return (new ReviewResource($review))
            ->additional(['message' => '¡Gracias por tu reseña!'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @return array{rating_medio: float, total: int}
     */
    private function resumenData(): array
    {
        $aprobadas = Review::query()->where('aprobada', true);
        $total = (clone $aprobadas)->count();
        $media = $total > 0 ? round((float) (clone $aprobadas)->avg('rating'), 1) : 0.0;

        return [
            'rating_medio' => $media,
            'total' => $total,
        ];
    }
}
