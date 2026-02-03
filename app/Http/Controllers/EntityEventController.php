<?php

namespace App\Http\Controllers;

use App\Exceptions\ExternalIdConflictException;
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
            $result = $service->execute(
                entityId: $entityId,
                externalId: $request->string('external_id'),
                type: $request->string('type'),
                payload: $request->input('payload', [])
            );

            $status = $result['created'] ? 201 : 200;

            return response()->json($result['event'], $status);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Entity not found'], 404);
        } catch (ExternalIdConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
