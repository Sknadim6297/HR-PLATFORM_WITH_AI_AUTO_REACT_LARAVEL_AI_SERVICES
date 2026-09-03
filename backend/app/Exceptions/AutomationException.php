<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AutomationException extends RuntimeException
{
    public function __construct(
        string $message = 'Automation request failed.',
        private readonly bool $retryable = true,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
