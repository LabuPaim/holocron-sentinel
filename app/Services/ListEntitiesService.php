<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ListEntitiesService
{
    /**
     * Tempo de cache em segundos (5 minutos)
     */
    private const CACHE_TTL = 300;

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

        // Gera chave de cache baseada nos parâmetros
        $cacheKey = "entities_list:per_page:{$perPage}";

        // Tenta buscar do cache primeiro
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
            return $this->fetchEntities($perPage);
        });
    }

    /**
     * Busca entidades do banco de dados
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    private function fetchEntities(int $perPage): LengthAwarePaginator
    {
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

    /**
     * Limpa o cache de listagem de entidades
     *
     * Deve ser chamado quando entidades ou eventos são criados/atualizados.
     * Usado pelo job assíncrono InvalidateEventCachesJob.
     */
    public function clearCache(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget("entities_list:per_page:{$i}");
        }
    }

    /**
     * Limpa todo o cache de ranking crítico (days 1-30, limit 1-100).
     *
     * Usado pelo job assíncrono InvalidateEventCachesJob após evento crítico.
     */
    public function clearRankingCache(): void
    {
        for ($days = 1; $days <= 30; $days++) {
            for ($limit = 1; $limit <= 100; $limit++) {
                Cache::forget("ranking_critical:days:{$days}:limit:{$limit}");
            }
        }
    }
}
