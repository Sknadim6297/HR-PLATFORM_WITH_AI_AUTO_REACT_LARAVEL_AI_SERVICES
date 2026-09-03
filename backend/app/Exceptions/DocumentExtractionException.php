<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DocumentExtractionException extends RuntimeException
{
    public function __construct(
        string $message = 'Document text extraction failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
