<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('payment_intent_id')->nullable()->after('notas_internas');
            $table->enum('deposito_estado', ['no_aplica', 'pendiente', 'pagado', 'reembolsado'])
                ->default('no_aplica')->after('payment_intent_id');
            $table->decimal('deposito_importe', 8, 2)->nullable()->after('deposito_estado');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['payment_intent_id', 'deposito_estado', 'deposito_importe']);
        });
    }
};
