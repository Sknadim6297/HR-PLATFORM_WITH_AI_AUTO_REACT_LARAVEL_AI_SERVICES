<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Job::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'department' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'employment_type' => ['required', Rule::in(EmploymentType::values())],
            'location' => ['nullable', 'string', 'max:180'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:salary_min'],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:50'],
            'experience_max' => ['nullable', 'integer', 'min:0', 'max:50', 'gte:experience_min'],
            'closing_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->validated();
    }
}
