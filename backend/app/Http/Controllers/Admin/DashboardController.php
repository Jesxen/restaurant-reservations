<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Setting;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function index(): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $reservasHoy = Reserva::whereDate('fecha', $today)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->count();

        $comensalesHoy = (int) Reserva::whereDate('fecha', $today)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->sum('personas');

        $pendientes = Reserva::where('estado', 'pendiente')->count();

        $capacidadDiaria = max(1, $this->availability->totalCapacity());
        $ocupacionHoy = (int) round(min(100, ($comensalesHoy / $capacidadDiaria) * 100));

        // Reservations per estado (for the breakdown bars).
        $porEstado = Reserva::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $estados = [];
        foreach (Reserva::ESTADOS as $estado) {
            $estados[$estado] = (int) ($porEstado[$estado] ?? 0);
        }

        // Reservations over the next 7 days.
        $proximos = [];
        for ($i = 0; $i < 7; $i++) {
            $dia = Carbon::today()->addDays($i)->toDateString();
            $proximos[] = [
                'fecha' => $dia,
                'reservas' => Reserva::whereDate('fecha', $dia)
                    ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
                    ->count(),
                'comensales' => (int) Reserva::whereDate('fecha', $dia)
                    ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
                    ->sum('personas'),
            ];
        }

        // Estimated revenue today = avg ticket × seated diners today.
        $ticketMedio = (float) Setting::current()->ticket_medio;
        $ingresosEstimadosHoy = round($ticketMedio * $comensalesHoy, 2);

        // Cancellation rate (all-time).
        $totalReservas = array_sum($estados);
        $tasaCancelacion = $totalReservas > 0
            ? (int) round(($estados['cancelada'] / $totalReservas) * 100)
            : 0;

        // Occupancy by time slot (upcoming active reservations).
        $porFranjaRaw = Reserva::select('hora', DB::raw('SUM(personas) as comensales'))
            ->whereDate('fecha', '>=', $today)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();
        $porFranja = $porFranjaRaw->map(fn ($r) => [
            'hora' => substr((string) $r->hora, 0, 5),
            'comensales' => (int) $r->comensales,
        ]);

        return response()->json([
            'reservas_hoy' => $reservasHoy,
            'comensales_hoy' => $comensalesHoy,
            'pendientes' => $pendientes,
            'ocupacion_hoy' => $ocupacionHoy,
            'capacidad_total' => $this->availability->totalCapacity(),
            'ingresos_estimados_hoy' => $ingresosEstimadosHoy,
            'tasa_cancelacion' => $tasaCancelacion,
            'ticket_medio' => $ticketMedio,
            'por_estado' => $estados,
            'proximos_dias' => $proximos,
            'por_franja' => $porFranja,
        ]);
    }
}
