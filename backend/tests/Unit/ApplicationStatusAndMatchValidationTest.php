<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Services\AI\JobMatchingService;
use ReflectionMethod;
use Tests\TestCase;

class ApplicationStatusAndMatchValidationTest extends TestCase
{
    public function test_allowed_status_transitions(): void
    {
        $this->assertTrue(ApplicationStatus::Applied->canTransitionTo(ApplicationStatus::Screening));
        $this->assertTrue(ApplicationStatus::Screening->canTransitionTo(ApplicationStatus::Shortlisted));
        $this->assertTrue(ApplicationStatus::Screening->canTransitionTo(ApplicationStatus::Rejected));
        $this->assertFalse(ApplicationStatus::Applied->canTransitionTo(ApplicationStatus::Selected));
        $this->assertFalse(ApplicationStatus::Rejected->canTransitionTo(ApplicationStatus::Screening));
    }

    public function test_match_score_is_clamped_between_zero_and_one_hundred(): void
    {
        $service = app(JobMatchingService::class);
        $method = new ReflectionMethod(JobMatchingService::class, 'parse');
        $method->setAccessible(true);

        $parsed = $method->invoke($service, json_encode([
            'score' => 150,
            'matched_skills' => ['PHP'],
            'missing_skills' => [],
            'reasoning' => 'Overstated',
            'confidence' => 'high',
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(100, $parsed['score']);

        $parsedLow = $method->invoke($service, json_encode([
            'score' => -20,
            'matched_skills' => [],
            'missing_skills' => ['PHP'],
            'reasoning' => 'Understated',
            'confidence' => 'low',
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(0, $parsedLow['score']);
    }
}
