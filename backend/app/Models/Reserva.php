<?php

namespace App\Models;

use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    protected $table = 'reservas';

    public const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_show'];

    // Estados that still occupy a seat for availability purposes.
    public const ESTADOS_ACTIVOS = ['pendiente', 'confirmada'];

    // How many days ahead a reservation may be booked.
    public const VENTANA_RESERVA_DIAS = 90;

    // Maximum party size handled online; larger groups are asked to call.
    public const MAX_PERSONAS = 20;

    protected $fillable = [
        'user_id',
        'mesa_id',
        'nombre',
        'email',
        'fecha',
        'hora',
        'personas',
        'estado',
        'notas',
        'notas_internas',
        'payment_intent_id',
        'deposito_estado',
        'deposito_importe',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'personas' => 'integer',
        'deposito_importe' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Assign a unique reference code on creation if one wasn't provided.
        static::creating(function (Reserva $reserva): void {
            if (empty($reserva->localizador)) {
                $reserva->localizador = static::generarLocalizador();
            }
        });
    }

    /**
     * Generate a unique, human-friendly reference code (e.g. LL-XK4P9).
     * Excludes ambiguous characters (0/O, 1/I) for easier dictation.
     */
    public static function generarLocalizador(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $codigo = 'LL-';
            for ($i = 0; $i < 5; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (static::where('localizador', $codigo)->exists());

        return $codigo;
    }

    public function cancelable(): bool
    {
        return in_array($this->estado, ['pendiente', 'confirmada'], true);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Mesa, $this> */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /** @return HasMany<ReservaEvento, $this> */
    public function eventos(): HasMany
    {
        return $this->hasMany(ReservaEvento::class)->orderByDesc('created_at');
    }
}
