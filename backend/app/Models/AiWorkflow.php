<?php

namespace App\Models;

use App\Enums\AiTask;
use App\Enums\AiWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AiTask $task
 * @property AiWorkflowStatus $status
 * @property array<string, mixed>|null $result
 */
class AiWorkflow extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'task',
        'content',
        'status',
        'result',
        'error_message',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'task' => AiTask::class,
            'status' => AiWorkflowStatus::class,
            'result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
