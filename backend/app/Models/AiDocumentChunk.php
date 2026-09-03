<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $chunk_index
 * @property string $content
 * @property int|null $token_count
 * @property array<string, mixed>|null $metadata
 */
class AiDocumentChunk extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_document_id',
        'chunk_index',
        'content',
        'token_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }
}
