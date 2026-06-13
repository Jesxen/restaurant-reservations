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
            'nombre_restaurante' => $s->nombre_restaurante,
            'horarios' => [
                'comida' => substr((string) $s->apertura_comida, 0, 5).'–'.substr((string) $s->cierre_comida, 0, 5),
                'cena' => substr((string) $s->apertura_cena, 0, 5).'–'.substr((string) $s->cierre_cena, 0, 5),
            ],
        ]);
    }

    /**
     * Full settings (admin).
     */
    public function show(): SettingResource
    {
        return new SettingResource(Setting::current());
    }

    public function update(SettingRequest $request): SettingResource
    {
        $setting = Setting::current();
        $setting->update($request->validated());

        return new SettingResource($setting);
    }
}
