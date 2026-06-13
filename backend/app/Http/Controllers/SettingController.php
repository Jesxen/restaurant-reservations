<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\SettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Public subset: name + opening hours (for landing/footer).
     */
    public function publicShow(): JsonResponse
    {
        $s = Setting::current();

        return response()->json([
            // --- Existing keys (kept for backwards compatibility) -----------
            'nombre_restaurante' => $s->nombre_restaurante,
            'horarios' => [
                'comida' => substr((string) $s->apertura_comida, 0, 5).'–'.substr((string) $s->cierre_comida, 0, 5),
                'cena' => substr((string) $s->apertura_cena, 0, 5).'–'.substr((string) $s->cierre_cena, 0, 5),
            ],

            // --- New public-safe fields (v2) --------------------------------
            'horarios_detalle' => [
                'apertura_comida' => substr((string) $s->apertura_comida, 0, 5),
                'cierre_comida' => substr((string) $s->cierre_comida, 0, 5),
                'apertura_cena' => substr((string) $s->apertura_cena, 0, 5),
                'cierre_cena' => substr((string) $s->cierre_cena, 0, 5),
            ],
            'dias_cierre' => $s->diasCierre(),
            'reservas' => [
                'intervalo_slots' => $s->intervaloSlots(),
                'antelacion_min_horas' => $s->antelacionMinHoras(),
                'max_personas_online' => $s->max_personas_online ?: \App\Models\Reserva::MAX_PERSONAS,
                'ventana_dias' => \App\Models\Reserva::VENTANA_RESERVA_DIAS,
            ],
            'branding' => [
                'logo_url' => $s->logo_url,
                'color_primario' => $s->color_primario,
                'color_acento' => $s->color_acento,
            ],
            'contacto' => [
                'email' => $s->email_contacto,
                'telefono' => $s->telefono,
                'direccion' => $s->direccion,
                'ciudad' => $s->ciudad,
            ],
            'coords' => [
                'lat' => $s->lat !== null ? (float) $s->lat : null,
                'lng' => $s->lng !== null ? (float) $s->lng : null,
            ],
            'social' => [
                'instagram' => $s->instagram_url,
                'facebook' => $s->facebook_url,
                'tiktok' => $s->tiktok_url,
            ],
            'galeria' => $s->galeria ?? [],
        ]);
    }

    /**
     * Full settings (admin).
     */
    public function show(): JsonResponse
    {
        return (new SettingResource(Setting::current()))->response()->setStatusCode(200);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $v = $request->validated();
        $setting = Setting::current();

        $setting->update([
            'nombre_restaurante' => $v['nombre_restaurante'],
            'aforo' => $v['aforo'] ?? null,
            'ticket_medio' => $v['ticket_medio'],

            'apertura_comida' => $v['horarios_detalle']['apertura_comida'],
            'cierre_comida' => $v['horarios_detalle']['cierre_comida'],
            'apertura_cena' => $v['horarios_detalle']['apertura_cena'],
            'cierre_cena' => $v['horarios_detalle']['cierre_cena'],

            'intervalo_slots' => $v['reservas']['intervalo_slots'],
            'antelacion_min_horas' => $v['reservas']['antelacion_min_horas'],
            'max_personas_online' => $v['reservas']['max_personas_online'],
            'dias_cierre' => $v['dias_cierre'] ?? [],

            'logo_url' => $v['branding']['logo_url'] ?? null,
            'color_primario' => $v['branding']['color_primario'] ?? null,
            'color_acento' => $v['branding']['color_acento'] ?? null,

            'email_contacto' => $v['contacto']['email'] ?? null,
            'telefono' => $v['contacto']['telefono'] ?? null,
            'direccion' => $v['contacto']['direccion'] ?? null,
            'ciudad' => $v['contacto']['ciudad'] ?? null,

            'lat' => $v['coords']['lat'] ?? null,
            'lng' => $v['coords']['lng'] ?? null,

            'instagram_url' => $v['social']['instagram'] ?? null,
            'facebook_url' => $v['social']['facebook'] ?? null,
            'tiktok_url' => $v['social']['tiktok'] ?? null,

            'galeria' => $v['galeria'] ?? [],
        ]);

        return (new SettingResource($setting))->response()->setStatusCode(200);
    }
}
