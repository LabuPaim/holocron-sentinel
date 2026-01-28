<?php

namespace App\Http\Controllers;

use App\Services\CreateEntityService;
use App\Services\ListEntitiesService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEntityRequest;
use Illuminate\Http\JsonResponse;

class EntityController extends Controller
{
    public function store(
        StoreEntityRequest $request,
        CreateEntityService $service
    ): JsonResponse {
        $entity = $service->execute($request->input('name'));

        $payload = [
            'id' => $entity->id,
            'name' => $entity->name,
            'status' => $entity->status->value,
            'critical_events_count' => $entity->critical_events_count,
            'created_at' => $entity->created_at,
            'updated_at' => $entity->updated_at,
        ];

        // Idempotência: 201 Created se novo, 200 OK se já existia
        $status = $entity->wasRecentlyCreated ? 201 : 200;

        return response()->json($payload, $status);
    }

    public function index(
        Request $request,
        ListEntitiesService $service
    ): JsonResponse {
        $perPage = (int) $request->query('per_page', 10);
        $entities = $service->execute($perPage);

        return response()->json($entities);
    }
}
