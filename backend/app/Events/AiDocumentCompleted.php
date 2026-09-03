<?php

namespace App\Events;

use App\Models\AiDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiDocumentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public AiDocument $document) {}
}
