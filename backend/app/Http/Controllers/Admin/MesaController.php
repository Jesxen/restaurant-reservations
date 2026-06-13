<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MesaRequest;
use App\Http\Resources\MesaResource;
use App\Models\Mesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MesaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MesaResource::collection(Mesa::orderBy('numero')->get());
    }

    public function store(MesaRequest $request): JsonResponse
    {
        $mesa = Mesa::create($request->validated());

        return (new MesaResource($mesa))->response()->setStatusCode(201);
    }

    public function update(MesaRequest $request, Mesa $mesa): MesaResource
    {
        $mesa->update($request->validated());

        return new MesaResource($mesa);
    }

    public function destroy(Mesa $mesa): JsonResponse
    {
        $mesa->delete();

        return response()->json(['message' => 'Mesa eliminada.']);
    }
}
