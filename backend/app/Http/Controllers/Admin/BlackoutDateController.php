<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlackoutDateRequest;
use App\Http\Resources\BlackoutDateResource;
use App\Models\BlackoutDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlackoutDateController extends Controller
{
    /**
     * List blackout dates (upcoming first).
     */
    public function index(): AnonymousResourceCollection
    {
        $fechas = BlackoutDate::query()->orderBy('fecha')->get();

        return BlackoutDateResource::collection($fechas);
    }

    public function store(BlackoutDateRequest $request): JsonResponse
    {
        $blackout = BlackoutDate::create($request->validated());

        return (new BlackoutDateResource($blackout))
            ->additional(['message' => 'Fecha bloqueada creada.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(BlackoutDateRequest $request, BlackoutDate $blackoutDate): BlackoutDateResource
    {
        $blackoutDate->update($request->validated());

        return new BlackoutDateResource($blackoutDate);
    }

    public function destroy(BlackoutDate $blackoutDate): JsonResponse
    {
        $blackoutDate->delete();

        return response()->json(['message' => 'Fecha bloqueada eliminada.']);
    }
}
