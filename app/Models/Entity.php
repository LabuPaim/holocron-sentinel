<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $fillable = [
        'name',
        'status',
        'critical_events_count',
    ];

    protected $casts = [
        'status' => EntityStatus::class,
        'critical_events_count' => 'integer',
    ];

    /**
     * Cache de listagem é invalidado de forma assíncrona por InvalidateEventCachesJob
     * (disparado após criação de evento). Outras atualizações de entidade podem
     * disparar o mesmo job onde fizer sentido.
     */

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function suspend(): void
    {
        $this->status = EntityStatus::Suspended;
    }

    /**
     * Incrementa o contador de eventos críticos de forma atômica no banco
     * e aplica suspensão se atingir o limite. Deve ser chamado dentro de transação
     */
    public function addCriticalEvent(): void
    {
        $this->newQuery()
            ->whereKey($this->getKey())
            ->increment('critical_events_count');

        $this->refresh();

        if ($this->critical_events_count >= config('holocron.critical_events_limit')) {
            $this->suspend();
            $this->save();
        }
    }
}
