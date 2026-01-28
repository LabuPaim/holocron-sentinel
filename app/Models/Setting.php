<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Retorna o valor da configuração (com cache).
     * Retorna $default quando a chave não existir na tabela.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting.{$key}";

        return cache()->remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return static::castValue($key, $setting->value);
        });
    }

    /**
     * Atualiza o valor da configuração e limpa o cache.
     */
    public static function setValue(string $key, mixed $value): void
    {
        $value = is_string($value) ? $value : (string) $value;

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        cache()->forget("setting.{$key}");
    }

    protected static function castValue(string $key, string $value): mixed
    {
        return match ($key) {
            'critical_events_limit' => (int) $value,
            default => $value,
        };
    }
}
