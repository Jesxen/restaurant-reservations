<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WaitlistController extends Controller
{
    /**
     * List waitlist entries (staff), optionally filtered by fecha and estado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $entries = WaitlistEntry::query()
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha', $request->date('fecha')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('created_at')
            ->get();

        return WaitlistEntryResource::collection($entries);
    }

    /**
     * Remove a waitlist entry.
     */
    public function destroy(WaitlistEntry $entry): JsonResponse
    {
        $entry->delete();

        return response()->json(['message' => 'Entrada de lista de espera eliminada.']);
    }
}
