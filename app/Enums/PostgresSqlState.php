<?php

namespace App\Enums;

enum PostgresSqlState: string
{
    case UNIQUE_VIOLATION = '23505';
    case FOREIGN_KEY_VIOLATION = '23503';
    case NOT_NULL_VIOLATION = '23502';

    /**
     * Indica se o errorInfo do PDO corresponde a violação de unique em qualquer driver suportado.
     * PostgreSQL: 23505 | MySQL: 23000 + 1062 | SQLite: 23000 + 2067
     *
     * @param  array<int, mixed>|null  $errorInfo  errorInfo da QueryException/PDOException
     */
    public static function isUniqueViolationFromErrorInfo(?array $errorInfo): bool
    {
        if ($errorInfo === null || $errorInfo === []) {
            return false;
        }

        $sqlState = $errorInfo[0] ?? null;
        $driverCode = isset($errorInfo[1]) ? (int) $errorInfo[1] : null;

        $postgres = self::tryFrom($sqlState ?? '');
        if ($postgres?->isUniqueViolation()) {
            return true;
        }

        if ($sqlState === '23000') {
            if ($driverCode === 1062) {
                return true; // MySQL duplicate key
            }
            if ($driverCode === 2067) {
                return true; // SQLite SQLITE_CONSTRAINT_UNIQUE
            }
        }

        return false;
    }

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
        };
    }
}
