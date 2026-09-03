<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobApplication::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resume_document_id' => ['nullable', 'integer', 'exists:ai_documents,id'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
