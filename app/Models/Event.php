<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_id',
        'external_id',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Cache de listagem e ranking é invalidado de forma assíncrona por InvalidateEventCachesJob.
     */

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function isCritical(): bool
    {
        return $this->type === 'critical';
    }
}
