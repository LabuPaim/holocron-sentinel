<?php

namespace App\Services;

use App\Enums\PostgresSqlState;
use App\Models\Entity;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use DomainException;

class RegisterEventService
{
    public function execute(
        int $entityId,
        string $externalId,
        string $type,
        array $payload
    ): Event {
        $externalId = trim($externalId);
        $type = strtolower(trim($type));

        if ($externalId === '') {
            throw new DomainException('external_id is required');
        }

        return DB::transaction(function () use ($entityId, $externalId, $type, $payload) {
            $entity = Entity::query()
                ->whereKey($entityId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $entity->isActive()) {
                throw new DomainException('Entity is suspended');
            }

            try {
                $event = Event::query()->create([
                    'entity_id'   => $entity->id,
                    'external_id' => $externalId,
                    'type'        => $type,
                    'payload'     => $payload,
                ]);
            } catch (QueryException $e) {
                // errorInfo()[0] retorna o código SQLSTATE do PostgreSQL
                $errorInfo = $e->errorInfo ?? [];
                $sqlState = $errorInfo[0] ?? null;

                try {
                    $postgresError = PostgresSqlState::from($sqlState);
                } catch (\ValueError) {
                    // Código SQLSTATE não reconhecido, re-lança a exceção
                    throw $e;
                }

                if (! $postgresError->isUniqueViolation()) {
                    throw $e;
                }

                $existing = Event::query()
                    ->where('external_id', $externalId)
                    ->first();

                // Se caiu aqui, era pra existir. Se não existir, algo muito estranho rolou.
                if (! $existing) {
                    throw $e;
                }

                // Protege contra external_id "global" sendo reaproveitado por outra entidade
                if ((int) $existing->entity_id !== (int) $entityId) {
                    throw new DomainException('external_id already used by another entity');
                }

                return $existing;
            }

            if ($type === 'critical') {
                $entity->addCriticalEvent();
                $entity->save();
            }

            return $event;
        });
    }
}
