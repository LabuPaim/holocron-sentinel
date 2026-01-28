<?php

namespace App\Services;

use App\Models\Entity;
use DomainException;

class CreateEntityService
{
    public function execute(string $name): Entity
    {
        $name = trim($name);

        if ($name === '') {
            throw new DomainException('Entity name is required');
        }

        return Entity::create([
            'name' => $name,
            'status' => 'active',
        ]);
    }
}
