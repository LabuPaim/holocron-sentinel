<?php

namespace App\Services;

use App\Exceptions\ExternalIdConflictException;
use App\Jobs\InvalidateEventCachesJob;
use App\Models\Entity;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use DomainException;

class RegisterEventService
{
    /**
     * Registra um evento dentro de uma única transação.
     * Idempotência por external_id: mesmo entity + external_id → 200 + evento existente;
     * external_id em outra entidade → 409.
     * Job de cache só é disparado após commit (afterCommit).
     *
     * @return array{event: Event, created: bool}
     */
    public function execute(
        int $entityId,
        string $externalId,
        string $type,
        array $payload
    ): array {
        $externalId = trim($externalId);
        $type = strtolower(trim($type));

        if ($externalId === '') {
            throw new DomainException('external_id is required');
        }

        return DB::transaction(function () use ($entityId, $externalId, $type, $payload): array {
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
                $sqlState = $e->errorInfo[0] ?? '';
                if ($sqlState !== '23505') {
                    throw $e;
                }

                $existing = Event::query()
                    ->where('external_id', $externalId)
                    ->first();

                if (! $existing) {
                    throw $e;
                }

                if ((int) $existing->entity_id !== (int) $entityId) {
                    throw new ExternalIdConflictException('external_id already used by another entity');
                }

                return ['event' => $existing, 'created' => false];
            }

            if ($type === 'critical') {
                $entity->addCriticalEvent();
                $entity->save();
            }

            InvalidateEventCachesJob::dispatch($entityId, $type === 'critical')
                ->afterCommit();

            return ['event' => $event, 'created' => true];
        });
    }
}
