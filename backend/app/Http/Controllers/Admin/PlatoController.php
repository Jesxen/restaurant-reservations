<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlatoRequest;
use App\Http\Resources\PlatoResource;
use App\Models\Plato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlatoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PlatoResource::collection(Plato::orderBy('categoria_id')->orderBy('nombre')->get());
    }

    public function store(PlatoRequest $request): JsonResponse
    {
        $plato = Plato::create($request->validated());

        return (new PlatoResource($plato->refresh()))->response()->setStatusCode(201);
    }

    public function update(PlatoRequest $request, Plato $plato): PlatoResource
    {
        $plato->update($request->validated());

        return new PlatoResource($plato);
    }

    public function destroy(Plato $plato): JsonResponse
    {
        $plato->delete();

        return response()->json(['message' => 'Plato eliminado.']);
    }
}
