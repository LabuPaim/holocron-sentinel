<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\PostgresSqlState;
use App\Models\Entity;
use Illuminate\Database\QueryException;

class CreateEntityService
{
    /**
     * Cria ou retorna a entidade com o nome informado (idempotente por name, case-insensitive).
     *
     * Espera nome já validado e normalizado pela camada de Request (trim + lowercase).
     * Em concorrência, se outra requisição criar primeiro, retorna a existente.
     */
    public function execute(string $name): Entity
    {
        $name = strtolower(trim($name));

        try {
            return Entity::firstOrCreate(
                ['name' => $name],
                [
                    'status' => EntityStatus::Active,
                    'critical_events_count' => 0,
                ]
            );
        } catch (QueryException $e) {
            $errorInfo = $e->errorInfo ?? [];
            $sqlState = $errorInfo[0] ?? null;

            try {
                $postgresError = PostgresSqlState::from($sqlState);
            } catch (\ValueError) {
                throw $e;
            }

            if (! $postgresError->isUniqueViolation()) {
                throw $e;
            }

            // Concorrência: outra requisição criou a entidade primeiro
            return Entity::query()->where('name', $name)->firstOrFail();
        }
    }
}
