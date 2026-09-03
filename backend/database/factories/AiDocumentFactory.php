<?php

namespace Database\Factories;

use App\Enums\AiDocumentStatus;
use App\Models\AiDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiDocument>
 */
class AiDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_name' => 'resume.pdf',
            'file_path' => 'ai-documents/1/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 123456,
            'status' => AiDocumentStatus::Uploaded,
        ];
    }
}
