<?php

namespace App\Jobs;

use App\Services\ListEntitiesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job assíncrono para invalidar caches de listagem e ranking após criação de evento.
 *
 * Não contém regras de negócio: apenas limpa caches para que a próxima leitura
 * recalcule agregados. Mantém a API rápida (sem 100+ invalidações na requisição).
 */
class InvalidateEventCachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $entityId,
        public bool $isCritical
    ) {
    }

    public function handle(ListEntitiesService $listEntitiesService): void
    {
        $listEntitiesService->clearCache();

        if ($this->isCritical) {
            $listEntitiesService->clearRankingCache();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('InvalidateEventCachesJob: falha ao invalidar caches', [
            'entity_id' => $this->entityId,
            'is_critical' => $this->isCritical,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
