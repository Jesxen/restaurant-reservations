<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Booking configuration.
            $table->unsignedSmallInteger('intervalo_slots')->default(30)->after('duracion_turno'); // minutes between bookable slots
            $table->unsignedSmallInteger('antelacion_min_horas')->default(1)->after('intervalo_slots'); // min lead time (hours)
            $table->unsignedTinyInteger('max_personas_online')->default(20)->after('antelacion_min_horas');
            $table->json('dias_cierre')->nullable()->after('max_personas_online'); // weekday numbers 0=Sun..6=Sat

            // Branding.
            $table->string('logo_url')->nullable();
            $table->string('color_primario', 9)->nullable(); // hex e.g. #1a2b3c
            $table->string('color_acento', 9)->nullable();

            // Contact.
            $table->string('email_contacto')->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Social.
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('tiktok_url')->nullable();

            // Gallery.
            $table->json('galeria')->nullable(); // array of image URLs
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'intervalo_slots',
                'antelacion_min_horas',
                'max_personas_online',
                'dias_cierre',
                'logo_url',
                'color_primario',
                'color_acento',
                'email_contacto',
                'telefono',
                'direccion',
                'ciudad',
                'lat',
                'lng',
                'instagram_url',
                'facebook_url',
                'tiktok_url',
                'galeria',
            ]);
        });
    }
};
