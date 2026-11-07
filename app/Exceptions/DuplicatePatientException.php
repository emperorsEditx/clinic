<?php

namespace App\Exceptions;

use Illuminate\Support\Collection;
use RuntimeException;

class DuplicatePatientException extends RuntimeException
{
    public function __construct(public readonly Collection $matches)
    {
        parent::__construct('A patient with matching details is already registered.');
    }
}
