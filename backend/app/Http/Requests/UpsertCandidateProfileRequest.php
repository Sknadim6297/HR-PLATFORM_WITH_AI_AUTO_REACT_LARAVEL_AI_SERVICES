<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:180'],
            'headline' => ['nullable', 'string', 'max:180'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'current_company' => ['nullable', 'string', 'max:180'],
            'current_role' => ['nullable', 'string', 'max:180'],
            'education_summary' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'array', 'max:50'],
            'skills.*' => ['string', 'max:80'],
        ];
    }
}
