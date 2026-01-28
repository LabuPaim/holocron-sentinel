<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Models\Entity;

class CreateEntityService
{
    public function execute(string $name): Entity
    {
        return Entity::create([
            'name' => $name,
            'status' => EntityStatus::Active,
            'critical_events_count' => 0,
        ]);
    }
}
