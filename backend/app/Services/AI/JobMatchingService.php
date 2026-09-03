<?php

namespace App\Services\AI;

use App\Contracts\AI\LlmProviderInterface;
use App\Events\JobMatchGenerated;
use App\Exceptions\LlmProviderException;
use App\Models\AiJobMatch;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Log;

class JobMatchingService
{
    public function __construct(
        private readonly LlmProviderInterface $llmProvider,
    ) {}

    public function match(JobApplication $application): AiJobMatch
    {
        $existing = $application->jobMatch;

        if ($existing !== null && $existing->generated_at !== null) {
            return $existing;
        }

        $application->loadMissing(['job', 'resumeAnalysis', 'candidate.candidateProfile']);

        $analysis = $application->resumeAnalysis;

        if ($analysis === null || ! $analysis->isComplete()) {
            throw new LlmProviderException(
                'Resume analysis is required before job matching.',
                retryable: false,
            );
        }

        $systemPrompt = <<<'PROMPT'
You are an HR job-matching assistant providing decision support only.
Score relevance between a candidate and a job from 0 to 100 using skills, experience, education, and qualifications only.
Never consider protected attributes (religion, race, ethnicity, gender, sexual orientation, disability, medical information, political affiliation).
Do not invent missing skills.
Return JSON only.
PROMPT;

        $userPrompt = json_encode([
            'job' => [
                'title' => $application->job->title,
                'requirements' => $application->job->requirements,
                'responsibilities' => $application->job->responsibilities,
                'experience_min' => $application->job->experience_min,
                'experience_max' => $application->job->experience_max,
            ],
            'candidate_profile' => [
                'years_of_experience' => $application->candidate->candidateProfile?->years_of_experience,
                'skills' => $application->candidate->candidateProfile?->skills,
                'headline' => $application->candidate->candidateProfile?->headline,
            ],
            'resume_analysis' => [
                'summary' => $analysis->summary,
                'skills' => $analysis->skills,
                'experience' => $analysis->experience,
                'education' => $analysis->education,
                'strengths' => $analysis->strengths,
                'gaps' => $analysis->gaps,
            ],
            'response_schema' => [
                'score' => 'integer 0-100',
                'matched_skills' => [],
                'missing_skills' => [],
                'reasoning' => 'string',
                'confidence' => 'high|medium|low',
            ],
        ], JSON_THROW_ON_ERROR);

        $completion = $this->llmProvider->generate($systemPrompt, $userPrompt, [
            'json' => true,
            'temperature' => 0.1,
        ]);

        $parsed = $this->parse($completion['content']);

        $match = AiJobMatch::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'score' => $parsed['score'],
                'matched_skills' => $parsed['matched_skills'],
                'missing_skills' => $parsed['missing_skills'],
                'reasoning' => $parsed['reasoning'],
                'confidence' => $parsed['confidence'],
                'model' => $completion['model'],
                'generated_at' => now(),
            ],
        );

        Log::info('Job match generated.', [
            'application_id' => $application->id,
            'score' => $match->score,
            'confidence' => $match->confidence,
            'model' => $completion['model'],
        ]);

        JobMatchGenerated::dispatch($application->fresh(['jobMatch']) ?? $application);

        return $match;
    }

    /**
     * @return array{
     *     score: int,
     *     matched_skills: list<mixed>,
     *     missing_skills: list<mixed>,
     *     reasoning: string,
     *     confidence: string
     * }
     */
    private function parse(string $raw): array
    {
        $trimmed = trim($raw);

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $trimmed = $matches[0];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new LlmProviderException(
                'The language model returned an invalid response.',
                retryable: false,
                previous: $exception,
            );
        }

        if (! array_key_exists('score', $decoded) || ! is_numeric($decoded['score'])) {
            throw new LlmProviderException(
                'The language model returned an invalid match score.',
                retryable: false,
            );
        }

        $score = (int) round((float) $decoded['score']);
        $score = max(0, min(100, $score));

        $confidence = strtolower((string) ($decoded['confidence'] ?? 'low'));

        if (! in_array($confidence, ['high', 'medium', 'low'], true)) {
            $confidence = 'low';
        }

        return [
            'score' => $score,
            'matched_skills' => array_values((array) ($decoded['matched_skills'] ?? [])),
            'missing_skills' => array_values((array) ($decoded['missing_skills'] ?? [])),
            'reasoning' => (string) ($decoded['reasoning'] ?? ''),
            'confidence' => $confidence,
        ];
    }
}
