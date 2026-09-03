<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiRagRequest extends FormRequest
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
        $min = max(1, (int) config('ai.rag.question_min', 5));
        $max = max($min, (int) config('ai.rag.question_max', 2000));

        return [
            'question' => ['required', 'string', 'min:'.$min, 'max:'.$max],
            'document_id' => ['nullable', 'integer'],
            'conversation_id' => ['nullable', 'integer'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'A question is required.',
            'question.min' => 'The question is too short.',
            'question.max' => 'The question is too long.',
        ];
    }

    public function question(): string
    {
        return trim((string) $this->validated('question'));
    }

    public function documentId(): ?int
    {
        $value = $this->validated('document_id');

        return $value === null ? null : (int) $value;
    }

    public function conversationId(): ?int
    {
        $value = $this->validated('conversation_id');

        return $value === null ? null : (int) $value;
    }

    public function topK(): ?int
    {
        $value = $this->validated('top_k');

        return $value === null ? null : (int) $value;
    }
}
