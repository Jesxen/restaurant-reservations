<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_restaurante')->default('Restaurante La Laguna');
            $table->unsignedInteger('aforo')->nullable(); // null => derive from active tables
            $table->time('apertura_comida')->default('13:00');
            $table->time('cierre_comida')->default('16:00');
            $table->time('apertura_cena')->default('20:00');
            $table->time('cierre_cena')->default('23:30');
            $table->unsignedSmallInteger('duracion_turno')->default(120); // minutes
            $table->decimal('ticket_medio', 8, 2)->default(35.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
