<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->nullOnDelete();
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->text('comentario');
            $table->boolean('aprobada')->default(false);
            $table->timestamps();

            // Public listing filters by approval, newest first.
            $table->index(['aprobada', 'created_at']);
            // One review per user per reservation (when both present).
            $table->unique(['user_id', 'reserva_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
