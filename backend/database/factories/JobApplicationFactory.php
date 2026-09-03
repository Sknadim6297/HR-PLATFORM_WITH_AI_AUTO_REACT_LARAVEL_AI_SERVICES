<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory()->published(),
            'candidate_id' => User::factory()->candidate(),
            'resume_document_id' => null,
            'cover_letter' => fake()->paragraph(),
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ];
    }

    public function screening(): static
    {
        return $this->state(fn (): array => [
            'status' => ApplicationStatus::Screening,
        ]);
    }

    public function shortlisted(): static
    {
        return $this->state(fn (): array => [
            'status' => ApplicationStatus::Shortlisted,
        ]);
    }
}
