<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        if (empty($externalId)) {
            throw new DomainException('external_id is required');
        }

        return DB::transaction(function () use ($entityId, $externalId, $type, $payload) {
            $entity = Entity::query()->whereKey($entityId)->lockForUpdate()->firstOrFail();
            
            if (!$entity->isActive()) {
                throw new DomainException('Entity is suspended');
            }

            try {
                $event = Event::query()->create([
                    'entity_id' => $entity->id,
                    'external_id' => $externalId,
                    'type' => $type,
                    'payload' => $payload,
                ]);
            } catch (QueryException $e) {
                
                $existing = Event::query()->where('external_id', $externalId)->first();
                if ($existing) {
                    return $existing;
                }
                throw $e;
            }

            if ($entity->isCritical()) {
                $entity->addCriticalEvent();
                $entity->save();
            }

            return $event;
        });
    }
}