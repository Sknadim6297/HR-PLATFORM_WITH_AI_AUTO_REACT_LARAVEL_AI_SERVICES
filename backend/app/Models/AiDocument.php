<?php

namespace App\Models;

use App\Enums\AiDocumentStatus;
use Database\Factories\AiDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AiDocumentStatus $status
 * @property string|null $extracted_text
 */
class AiDocument extends Model
{
    /** @use HasFactory<AiDocumentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'error_message',
        'extracted_text',
        'processed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'file_path',
        'extracted_text',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'uploaded',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'status' => AiDocumentStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiDocumentChunk::class)->orderBy('chunk_index');
    }

    public static function directoryFor(int $userId): string
    {
        return 'ai-documents/'.$userId;
    }
}
