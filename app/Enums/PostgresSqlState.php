<?php

namespace App\Enums;

enum PostgresSqlState: string
{
    case UNIQUE_VIOLATION = '23505';
    case FOREIGN_KEY_VIOLATION = '23503';
    case NOT_NULL_VIOLATION = '23502';
    
    public function isUniqueViolation(): bool
    {
        return $this === self::UNIQUE_VIOLATION;
    }


    public function isConstraintViolation(): bool
    {
        return in_array($this, [
            self::UNIQUE_VIOLATION,
            self::FOREIGN_KEY_VIOLATION,
            self::NOT_NULL_VIOLATION,
        ]);
    }

    public function description(): string
    {
        return match ($this) {
            self::UNIQUE_VIOLATION => 'Unique constraint violation',
            self::FOREIGN_KEY_VIOLATION => 'Foreign key violation',
            self::NOT_NULL_VIOLATION => 'Not null violation',
            default => 'PostgreSQL error',
        };
    }
}
