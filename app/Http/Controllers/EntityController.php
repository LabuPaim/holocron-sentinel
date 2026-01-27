<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEntityRequest;
use Illuminate\Http\JsonResponse;

class EntityController extends Controller
{
    public function store(StoreEntityRequest $request): JsonResponse
    {
        $entity = Entity::query()->create([
            'name' => $request->input('name'),
            'status' => 'active',
        ]);

        return response()->json($entity, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $entities = Entity::query()
            ->select('id', 'name', 'status', 'critical_events_count')
            ->selectSub(function ($query) {
                $query->from('events')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('events.entity_id', 'entities.id');
            }, 'events_count')
            ->selectSub(function ($query) {
                $query->from('events')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('events.entity_id', 'entities.id')
                    ->where('type', 'critical');
            }, 'critical_events_total')
            ->selectSub(function ($query) {
                $query->from('events')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('events.entity_id', 'entities.id');
            }, 'last_event_at')
            ->orderByDesc('entities.created_at')
            ->paginate($perPage);

        return response()->json($entities);
    }
}
