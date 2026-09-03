<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('job')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:180'],
            'department' => ['nullable', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'min:20', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'employment_type' => ['sometimes', 'required', Rule::in(EmploymentType::values())],
            'location' => ['nullable', 'string', 'max:180'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:salary_min'],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:50'],
            'experience_max' => ['nullable', 'integer', 'min:0', 'max:50', 'gte:experience_min'],
            'closing_at' => ['nullable', 'date'],
        ];
    }
}
