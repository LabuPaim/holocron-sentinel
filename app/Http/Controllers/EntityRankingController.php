<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EntityRankingController extends Controller
{
    /**
     * Tempo de cache em segundos (2 minutos)
     * Ranking muda mais frequentemente que a listagem geral
     */
    private const CACHE_TTL = 120;

    public function critical(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        $days = max(1, min($days, 30));

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        // Gera chave de cache baseada nos parâmetros
        $cacheKey = "ranking_critical:days:{$days}:limit:{$limit}";

        // Tenta buscar do cache primeiro
        $ranking = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($days, $limit) {
            return $this->fetchRanking($days, $limit);
        });

        return response()->json($ranking);
    }

    /**
     * Busca ranking do banco de dados
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function fetchRanking(int $days, int $limit)
    {
        $since = now()->subDays($days);

        return Entity::query()
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
    }
}
