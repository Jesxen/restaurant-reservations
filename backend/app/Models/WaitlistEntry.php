<?php

namespace App\Models;

use Database\Factories\WaitlistEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    /** @use HasFactory<WaitlistEntryFactory> */
    use HasFactory;

    protected $table = 'waitlist_entries';

    public const ESTADOS = ['esperando', 'notificado', 'convertida', 'cancelada'];

    protected $fillable = [
        'user_id',
        'nombre',
        'email',
        'telefono',
        'fecha',
        'hora',
        'personas',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'personas' => 'integer',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
