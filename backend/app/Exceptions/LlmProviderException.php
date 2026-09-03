<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class LlmProviderException extends RuntimeException
{
    public function __construct(
        string $message = 'The language model is temporarily unavailable.',
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
