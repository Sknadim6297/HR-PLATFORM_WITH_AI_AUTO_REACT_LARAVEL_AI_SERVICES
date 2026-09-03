<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DocumentChunkingException extends RuntimeException
{
    public function __construct(
        string $message = 'Document chunking failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
