<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioReserva;
use App\Models\Reserva;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reservas:recordatorios';

    /**
     * @var string
     */
    protected $description = 'Envía recordatorios por correo a los clientes con reservas para mañana.';

    public function handle(SmsService $sms): int
    {
        $manana = Carbon::tomorrow()->toDateString();

        $reservas = Reserva::query()
            ->whereDate('fecha', $manana)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->with('user')
            ->get();

        foreach ($reservas as $reserva) {
            Mail::to($reserva->email)->send(new RecordatorioReserva($reserva));

            // Best-effort SMS reminder when the customer's account has a phone.
            if (! empty($reserva->user?->phone)) {
                $hora = substr((string) $reserva->hora, 0, 5);
                $sms->send(
                    $reserva->user->phone,
                    "Recordatorio: tu reserva {$reserva->localizador} es mañana a las {$hora}. ¡Te esperamos!",
                );
            }
        }

        $this->info("Recordatorios enviados: {$reservas->count()} (reservas para {$manana}).");

        return self::SUCCESS;
    }
}
