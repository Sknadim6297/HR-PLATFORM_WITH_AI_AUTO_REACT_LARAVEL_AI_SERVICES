<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    protected $model = CandidateProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->candidate(),
            'phone' => fake()->numerify('+1#########'),
            'location' => fake()->city(),
            'headline' => fake()->sentence(4),
            'years_of_experience' => fake()->numberBetween(0, 15),
            'current_company' => fake()->company(),
            'current_role' => fake()->jobTitle(),
            'education_summary' => fake()->sentence(),
            'skills' => ['Laravel', 'PHP', 'MySQL'],
        ];
    }
}
