<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            // Owner (nullable: guests can reserve without an account).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Assigned table (set by admin).
            $table->foreignId('mesa_id')->nullable()->constrained('mesas')->nullOnDelete();
            // Contact snapshot (kept even for guests).
            $table->string('nombre', 100);
            $table->string('email', 150);
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedTinyInteger('personas');
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_show'])
                ->default('pendiente');
            $table->text('notas')->nullable();          // client-facing note
            $table->text('notas_internas')->nullable(); // staff-only note
            $table->timestamps();

            // Speed up listing + availability queries by slot.
            $table->index(['fecha', 'hora']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
