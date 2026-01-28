<?php

namespace App\Enums;

enum EntityStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Suspended => 'Suspenso',
        };
    }
}
