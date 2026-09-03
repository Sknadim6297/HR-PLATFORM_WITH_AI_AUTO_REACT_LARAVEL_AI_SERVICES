<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSemanticSearchRequest extends FormRequest
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
            'query' => ['required', 'string', 'max:2000'],
            'document_id' => ['nullable', 'integer'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'query.required' => 'A search query is required.',
            'top_k.min' => 'top_k must be at least 1.',
            'top_k.max' => 'top_k may not be greater than 20.',
        ];
    }

    public function queryText(): string
    {
        return trim((string) $this->validated('query'));
    }

    public function documentId(): ?int
    {
        $value = $this->validated('document_id');

        return $value === null ? null : (int) $value;
    }

    public function topK(): ?int
    {
        $value = $this->validated('top_k');

        return $value === null ? null : (int) $value;
    }
}
