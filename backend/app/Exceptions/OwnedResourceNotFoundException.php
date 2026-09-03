<?php

namespace App\Exceptions;

use RuntimeException;

class OwnedResourceNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message);
    }
}
