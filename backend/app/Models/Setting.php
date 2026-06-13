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
    ];

    protected $casts = [
        'aforo' => 'integer',
        'duracion_turno' => 'integer',
        'ticket_medio' => 'decimal:2',
    ];

    /** Singleton row — create defaults on first access. */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
