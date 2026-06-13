<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackoutDate extends Model
{
    protected $table = 'blackout_dates';

    protected $fillable = [
        'fecha',
        'motivo',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];
}
