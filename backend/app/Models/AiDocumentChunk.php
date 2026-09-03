<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $chunk_index
 * @property string $content
 * @property int|null $token_count
 * @property array<string, mixed>|null $metadata
 * @property list<float|int>|null $embedding
 * @property string|null $embedding_model
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
        'embedding',
        'embedding_model',
        'embedded_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'content',
        'embedding',
        'embedding_model',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'metadata' => 'array',
            'embedding' => 'array',
            'embedded_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }

    public function isEmbedded(): bool
    {
        return is_array($this->embedding)
            && $this->embedding !== []
            && $this->embedded_at !== null;
    }
}
