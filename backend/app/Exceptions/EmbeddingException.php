<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class EmbeddingException extends RuntimeException
{
    public function __construct(
        string $message = 'Embedding generation failed.',
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
