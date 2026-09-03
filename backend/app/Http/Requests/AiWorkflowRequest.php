<?php

namespace App\Http\Requests;

use App\Enums\AiTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'task' => ['required', 'string', Rule::enum(AiTask::class)],
            'content' => ['required', 'string', 'max:8000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'task.enum' => 'The selected task is invalid. Allowed tasks: summarize, generate, classify.',
        ];
    }

    public function task(): AiTask
    {
        return $this->enum('task', AiTask::class);
    }

    public function content(): string
    {
        return $this->validated('content');
    }
}
