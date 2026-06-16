<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speed up the availability lookups, which filter active reservations by
     * date (and scan their hora to compute slot overlap).
     */
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->index(['fecha', 'estado', 'hora'], 'reservas_fecha_estado_hora_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex('reservas_fecha_estado_hora_idx');
        });
    }
};
