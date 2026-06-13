<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioReserva;
use App\Models\Reserva;
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

    public function handle(): int
    {
        $manana = Carbon::tomorrow()->toDateString();

        $reservas = Reserva::query()
            ->whereDate('fecha', $manana)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->get();

        foreach ($reservas as $reserva) {
            Mail::to($reserva->email)->send(new RecordatorioReserva($reserva));
        }

        $this->info("Recordatorios enviados: {$reservas->count()} (reservas para {$manana}).");

        return self::SUCCESS;
    }
}
