<?php

namespace App\Models;

use Database\Factories\MesaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesa extends Model
{
    /** @use HasFactory<MesaFactory> */
    use HasFactory;

    protected $fillable = ['numero', 'capacidad', 'activa'];

    protected $casts = [
        'capacidad' => 'integer',
        'activa' => 'boolean',
    ];

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}
