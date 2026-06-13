<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReservaRequest;
use App\Http\Resources\ReservaResource;
use App\Mail\ReservaActualizada;
use App\Models\Reserva;
use App\Models\ReservaEvento;
use App\Services\SmsService;
use App\Services\WaitlistService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservaController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly WaitlistService $waitlist,
    ) {}

    /**
     * List reservations with optional filters (fecha, estado, q search).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservas = $this->filtered($request)->with('mesa')->get();

        return ReservaResource::collection($reservas);
    }

    /**
     * Reservation detail with table, owner and status history.
     */
    public function show(Reserva $reserva): ReservaResource
    {
        return new ReservaResource($reserva->load(['mesa', 'eventos.user']));
    }

    /**
     * Update estado / table / notes; record a history event on status change.
     */
    public function update(UpdateReservaRequest $request, Reserva $reserva): ReservaResource
    {
        $data = $request->validated();
        $estadoAnterior = $reserva->estado;

        $reserva->update($data);

        if (array_key_exists('estado', $data) && $data['estado'] !== $estadoAnterior) {
            ReservaEvento::create([
                'reserva_id' => $reserva->id,
                'user_id' => $request->user()->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $data['estado'],
            ]);

            // Notify the customer only for the transitions they care about.
            if (in_array($data['estado'], ['confirmada', 'cancelada'], true)) {
                Mail::to($reserva->email)->send(new ReservaActualizada($reserva));
            }

            // SMS confirmation (best-effort, only when the customer has a phone).
            if ($data['estado'] === 'confirmada') {
                $this->enviarSmsConfirmacion($reserva);
            }

            // Seat freed up (declined / cancelled / no-show): promote the
            // earliest fitting waitlist entry.
            if (in_array($data['estado'], ['cancelada', 'no_show'], true)) {
                $this->waitlist->promoteForFreedSlot($reserva);
            }
        }

        return new ReservaResource($reserva->load(['mesa', 'eventos.user']));
    }

    /**
     * Send a short Spanish SMS confirmation when the reservation's customer has
     * a phone on their linked account. No-ops silently when SMS is unconfigured.
     */
    private function enviarSmsConfirmacion(Reserva $reserva): void
    {
        $telefono = $reserva->user?->phone;

        if (empty($telefono)) {
            return;
        }

        $fecha = $reserva->fecha?->format('d/m/Y');
        $hora = substr((string) $reserva->hora, 0, 5);

        $this->sms->send(
            $telefono,
            "Tu reserva {$reserva->localizador} está confirmada para el {$fecha} a las {$hora}. ¡Te esperamos!",
        );
    }

    /**
     * Export the (filtered) reservations as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $reservas = $this->filtered($request)->get();
        $filename = 'reservas_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($reservas) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Nombre', 'Email', 'Fecha', 'Hora', 'Personas', 'Estado', 'Mesa']);
            foreach ($reservas as $r) {
                fputcsv($out, [
                    $r->id, $r->nombre, $r->email, $r->fecha?->format('Y-m-d'),
                    substr((string) $r->hora, 0, 5), $r->personas, $r->estado, $r->mesa_id,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Shared query builder for index + export.
     */
    private function filtered(Request $request)
    {
        return Reserva::query()
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha', $request->date('fecha')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('fecha')
            ->orderBy('hora');
    }
}
