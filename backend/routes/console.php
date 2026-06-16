<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily reminder emails for next-day reservations.
Schedule::command('reservas:recordatorios')->dailyAt('10:00');

// Purge access tokens that expired more than a day ago so the table stays small.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
