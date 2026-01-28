<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Lista todas as configurações (chave/valor).
     */
    public function index(): JsonResponse
    {
        $settings = Setting::query()
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $this->castForResponse($s->key, $s->value)]);

        return response()->json($settings);
    }

    /**
     * Exibe uma configuração específica.
     */
    public function show(string $key): JsonResponse
    {
        $value = Setting::getValue($key);

        if ($value === null && ! Setting::query()->where('key', $key)->exists()) {
            return response()->json(['message' => 'Configuração não encontrada.'], 404);
        }

        return response()->json(['key' => $key, 'value' => $value]);
    }

    /**
     * Atualiza uma configuração.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required'],
        ]);

        $allowedKeys = ['critical_events_limit'];
        if (! in_array($key, $allowedKeys, true)) {
            return response()->json(['message' => 'Configuração não editável.'], 422);
        }

        $value = $validated['value'];
        if ($key === 'critical_events_limit') {
            $value = (int) $value;
            if ($value < 1) {
                return response()->json(['message' => 'O limite deve ser pelo menos 1.'], 422);
            }
        }

        Setting::setValue($key, (string) $value);

        return response()->json(['key' => $key, 'value' => Setting::getValue($key)]);
    }

    private function castForResponse(string $key, string $value): mixed
    {
        return Setting::getValue($key);
    }
}
