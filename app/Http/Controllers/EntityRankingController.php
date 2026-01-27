<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntityRankingController extends Controller
{
    public function critical(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        $days = max(1, min($days, 30));

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        $since = now()->subDays($days);

        $ranking = Entity::query()
            ->select(
                'entities.id',
                'entities.name',
                'entities.status',
                DB::raw('count(*) as critical_events_last_window')
            )
            ->join('events', 'events.entity_id', '=', 'entities.id')
            ->where('events.type', 'critical')
            ->where('events.created_at', '>=', $since)
            ->groupBy('entities.id', 'entities.name', 'entities.status')
            ->orderByDesc('critical_events_last_window')
            ->limit($limit)
            ->get();

        return response()->json($ranking);
    }
}
