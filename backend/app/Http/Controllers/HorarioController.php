<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function __construct(private readonly SlotService $slots) {}

    /**
     * Bookable time slots for a given date.
     *
     * GET /api/horarios?fecha=YYYY-MM-DD
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(Reserva::VENTANA_RESERVA_DIAS)->toDateString(),
            ],
        ], [
            'fecha.required' => 'Debes indicar una fecha.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'fecha.before_or_equal' => 'Sólo aceptamos reservas con hasta '.Reserva::VENTANA_RESERVA_DIAS.' días de antelación.',
        ]);

        $fecha = $validated['fecha'];
        $motivo = $this->slots->motivoCierre($fecha);

        return response()->json([
            'fecha' => $fecha,
            'abierto' => $motivo === null,
            'motivo_cierre' => $motivo,
            'slots' => $this->slots->slotsParaFecha($fecha),
        ]);
    }
}
