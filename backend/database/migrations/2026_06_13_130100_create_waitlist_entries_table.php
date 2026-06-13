<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('email', 150);
            $table->string('telefono', 40)->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedTinyInteger('personas');
            $table->enum('estado', ['esperando', 'notificado', 'convertida', 'cancelada'])
                ->default('esperando');
            $table->timestamps();

            // Promotion scan: earliest waiting entry for a given slot.
            $table->index(['fecha', 'hora', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
