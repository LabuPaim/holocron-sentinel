<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Services\CreateEntityService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEntityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EntityController extends Controller
{
    public function store(
        StoreEntityRequest $request,
        CreateEntityService $service
    ): JsonResponse {
        $entity = $service->execute($request->input('name'));

        // Retorna apenas os campos necessários para reduzir overhead de serialização
        return response()->json([
            'id' => $entity->id,
            'name' => $entity->name,
            'status' => $entity->status,
            'critical_events_count' => $entity->critical_events_count,
            'created_at' => $entity->created_at,
            'updated_at' => $entity->updated_at,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        // Otimização: Usa uma única query com LEFT JOIN e agregações
        // Os índices [entity_id, created_at] e [type, created_at] são usados automaticamente
        // Esta abordagem é mais eficiente que subqueries correlacionadas
        $entities = Entity::query()
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

        return response()->json($entities);
    }
}
