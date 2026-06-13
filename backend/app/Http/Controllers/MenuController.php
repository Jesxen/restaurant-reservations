<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    /**
     * Public menu: active categories with their available dishes.
     */
    public function index(): AnonymousResourceCollection
    {
        $categorias = Categoria::query()
            ->where('activa', true)
            ->with(['platos' => fn ($q) => $q->where('disponible', true)->orderBy('nombre')])
            ->orderBy('orden')
            ->get();

        return CategoriaResource::collection($categorias);
    }
}
