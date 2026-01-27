<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Services\RegisterEventService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class EntityEventController extends Controller
{
    public function store(
        int $entityId,
        StoreEventRequest $request,
        RegisterEventService $service
    ): JsonResponse {
        try {
            $event = $service->execute(
                entityId: $entityId,
                externalId: $request->string('external_id'),
                type: $request->string('type'),
                payload: $request->input('payload', [])
            );

            return response()->json($event, 201);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Entity not found'], 404);
        } catch (DomainException $e) {
            // regra de domínio (ex: suspensa)
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
