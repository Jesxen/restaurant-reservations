<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'nombre_restaurante',
        'aforo',
        'apertura_comida',
        'cierre_comida',
        'apertura_cena',
        'cierre_cena',
        'duracion_turno',
        'ticket_medio',
        // Booking config.
        'intervalo_slots',
        'antelacion_min_horas',
        'max_personas_online',
        'dias_cierre',
        // Branding.
        'logo_url',
        'color_primario',
        'color_acento',
        // Contact.
        'email_contacto',
        'telefono',
        'direccion',
        'ciudad',
        'lat',
        'lng',
        // Social.
        'instagram_url',
        'facebook_url',
        'tiktok_url',
        // Gallery.
        'galeria',
    ];

    protected $casts = [
        'aforo' => 'integer',
        'duracion_turno' => 'integer',
        'ticket_medio' => 'decimal:2',
        'intervalo_slots' => 'integer',
        'antelacion_min_horas' => 'integer',
        'max_personas_online' => 'integer',
        'dias_cierre' => 'array',
        'galeria' => 'array',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /** Singleton row — create defaults on first access. */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Weekday numbers (0=Sunday .. 6=Saturday) on which the restaurant is closed.
     *
     * @return array<int, int>
     */
    public function diasCierre(): array
    {
        return array_map('intval', $this->dias_cierre ?? []);
    }

    /** Slot interval in minutes (defensive default). */
    public function intervaloSlots(): int
    {
        return $this->intervalo_slots ?: 30;
    }

    /** Minimum lead time in hours before a slot can be booked. */
    public function antelacionMinHoras(): int
    {
        return (int) ($this->antelacion_min_horas ?? 1);
    }
}
