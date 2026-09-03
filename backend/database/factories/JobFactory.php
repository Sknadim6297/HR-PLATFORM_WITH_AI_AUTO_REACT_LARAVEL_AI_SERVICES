<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'created_by' => User::factory()->hr(),
            'title' => $title,
            'slug' => Job::uniqueSlug($title.'-'.fake()->unique()->numerify('###')),
            'department' => fake()->randomElement(['Engineering', 'People', 'Product', 'Sales']),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraph(),
            'responsibilities' => fake()->paragraph(),
            'employment_type' => EmploymentType::FullTime,
            'location' => fake()->city(),
            'salary_min' => 50000,
            'salary_max' => 90000,
            'experience_min' => 2,
            'experience_max' => 5,
            'status' => JobStatus::Draft,
            'published_at' => null,
            'closing_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => JobStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => JobStatus::Closed,
            'published_at' => now()->subWeek(),
            'closing_at' => now(),
        ]);
    }
}
