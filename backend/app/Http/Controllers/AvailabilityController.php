<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Seats left for a given date/time slot.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(Reserva::VENTANA_RESERVA_DIAS)->toDateString()],
            'hora' => ['required', 'date_format:H:i'],
        ], [
            'fecha.required' => 'Debes indicar una fecha.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'fecha.before_or_equal' => 'Sólo consultamos disponibilidad con hasta '.Reserva::VENTANA_RESERVA_DIAS.' días de antelación.',
            'hora.required' => 'Debes indicar una hora.',
            'hora.date_format' => 'La hora no es válida.',
        ]);

        $left = $this->availability->seatsLeft($validated['fecha'], $validated['hora']);

        return response()->json([
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'capacidad_total' => $this->availability->totalCapacity(),
            'plazas_disponibles' => $left,
            'disponible' => $left > 0,
        ]);
    }
}
