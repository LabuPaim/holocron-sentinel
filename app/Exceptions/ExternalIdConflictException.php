<?php

namespace App\Exceptions;

use Exception;

class ExternalIdConflictException extends Exception
{
    public function __construct(string $message = 'external_id already used by another entity')
    {
        parent::__construct($message);
    }
}
