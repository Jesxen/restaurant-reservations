<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Stripe deposit configuration (admin-editable).
            $table->boolean('deposito_activo')->default(false)->after('galeria');
            $table->decimal('deposito_por_persona', 8, 2)->default(0)->after('deposito_activo');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['deposito_activo', 'deposito_por_persona']);
        });
    }
};
