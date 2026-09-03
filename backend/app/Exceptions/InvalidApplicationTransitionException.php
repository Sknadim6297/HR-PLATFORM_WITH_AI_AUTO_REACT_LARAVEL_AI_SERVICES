<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidApplicationTransitionException extends RuntimeException
{
    public function __construct(string $message = 'Invalid application status transition.')
    {
        parent::__construct($message);
    }
}
