<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListEntitiesService
{
    /**
     * Lista entidades com agregações de eventos
     *
     * @param int $perPage Número de itens por página (1-100)
     * @return LengthAwarePaginator
     */
    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        // Normaliza perPage entre 1 e 100
        $perPage = max(1, min($perPage, 100));

        // Otimização: Usa uma única query com LEFT JOIN e agregações
        // Os índices [entity_id, created_at] e [type, created_at] são usados automaticamente
        // Esta abordagem é mais eficiente que subqueries correlacionadas
        return Entity::query()
            ->select(
                'entities.id',
                'entities.name',
                'entities.status',
                'entities.critical_events_count',
                'entities.created_at',
                DB::raw('COALESCE(COUNT(events.id), 0) as events_count'),
                DB::raw('COALESCE(SUM(CASE WHEN events.type = \'critical\' THEN 1 ELSE 0 END), 0) as critical_events_total'),
                DB::raw('MAX(events.created_at) as last_event_at')
            )
            ->leftJoin('events', 'events.entity_id', '=', 'entities.id')
            ->groupBy(
                'entities.id',
                'entities.name',
                'entities.status',
                'entities.critical_events_count',
                'entities.created_at'
            )
            ->orderByDesc('entities.created_at')
            ->paginate($perPage);
    }
}
