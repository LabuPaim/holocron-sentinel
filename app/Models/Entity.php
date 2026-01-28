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

    public function addCriticalEvent(): void
    {
        $this->critical_events_count++;

        if ($this->critical_events_count >= config('holocron.critical_events_limit')) {
            $this->suspend();
        }
    }
}
