<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Exceptions\LlmProviderException;
use App\Jobs\AnalyzeCandidateResume;
use App\Jobs\TriggerN8nWorkflow;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\AiJobMatch;
use App\Models\AiResumeAnalysis;
use App\Models\AuditLog;
use App\Models\AutomationEvent;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\AI\JobMatchingService;
use App\Services\AI\ResumeAnalysisService;
use App\Services\Automation\N8nService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecruitmentDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4.1-mini',
            'automation.enabled' => false,
        ]);
    }

    public function test_admin_and_hr_can_create_jobs_but_candidate_cannot(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson('/api/jobs', $this->jobPayload(['title' => 'Admin Backend Engineer']))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Admin Backend Engineer');

        Sanctum::actingAs(User::factory()->hr()->create());
        $this->postJson('/api/jobs', $this->jobPayload(['title' => 'HR Backend Engineer']))
            ->assertCreated();

        Sanctum::actingAs(User::factory()->candidate()->create());
        $this->postJson('/api/jobs', $this->jobPayload())
            ->assertForbidden();
    }

    public function test_publish_close_and_candidate_only_sees_published_jobs(): void
    {
        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->create(['status' => JobStatus::Draft]);

        Sanctum::actingAs($hr);
        $this->postJson("/api/jobs/{$job->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        Sanctum::actingAs($candidate);
        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $draft = Job::factory()->for($hr, 'creator')->create(['status' => JobStatus::Draft]);
        $this->getJson("/api/jobs/{$draft->id}")
            ->assertNotFound();

        Sanctum::actingAs($hr);
        $this->postJson("/api/jobs/{$job->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'job.published',
            'auditable_id' => $job->id,
        ]);
    }

    public function test_job_filtering_and_pagination(): void
    {
        $hr = User::factory()->hr()->create();
        Job::factory()->for($hr, 'creator')->count(3)->create([
            'department' => 'Engineering',
            'status' => JobStatus::Draft,
        ]);
        Job::factory()->for($hr, 'creator')->create([
            'department' => 'Sales',
            'title' => 'Account Executive',
        ]);

        Sanctum::actingAs($hr);
        $this->getJson('/api/jobs?department=Engineering&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_candidate_profile_ownership(): void
    {
        $candidate = User::factory()->candidate()->create();
        $other = User::factory()->candidate()->create();
        $hr = User::factory()->hr()->create();

        Sanctum::actingAs($candidate);
        $this->putJson('/api/candidate/profile', [
            'headline' => 'Laravel Developer',
            'skills' => ['Laravel', 'PHP'],
            'years_of_experience' => 5,
        ])->assertOk()->assertJsonPath('data.headline', 'Laravel Developer');

        $profile = CandidateProfile::query()->where('user_id', $candidate->id)->firstOrFail();

        Sanctum::actingAs($other);
        $this->getJson("/api/candidate/profiles/{$profile->id}")->assertNotFound();

        Sanctum::actingAs($hr);
        $this->getJson("/api/candidate/profiles/{$profile->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id);
    }

    public function test_candidate_applies_and_duplicate_is_rejected(): void
    {
        Notification::fake();
        Queue::fake([TriggerN8nWorkflow::class, AnalyzeCandidateResume::class]);

        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();

        Sanctum::actingAs($candidate);
        $this->postJson("/api/jobs/{$job->id}/applications", [
            'cover_letter' => 'I am excited to apply.',
        ])->assertCreated()->assertJsonPath('data.status', 'applied');

        $this->postJson("/api/jobs/{$job->id}/applications", [
            'cover_letter' => 'Again',
        ])->assertStatus(409);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.submitted',
        ]);
    }

    public function test_application_visibility_and_status_transitions(): void
    {
        Notification::fake();
        Queue::fake([TriggerN8nWorkflow::class]);

        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $other = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();
        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => ApplicationStatus::Applied,
        ]);

        Sanctum::actingAs($other);
        $this->getJson("/api/applications/{$application->id}")->assertNotFound();

        Sanctum::actingAs($candidate);
        $this->getJson('/api/applications')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson("/api/applications/{$application->id}/status", [
            'status' => 'shortlisted',
        ])->assertNotFound();

        Sanctum::actingAs($hr);
        $this->getJson("/api/jobs/{$job->id}/applications")->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/applications/{$application->id}/status", [
            'status' => 'selected',
        ])->assertStatus(422);

        $this->patchJson("/api/applications/{$application->id}/status", [
            'status' => 'screening',
        ])->assertOk()->assertJsonPath('data.status', 'screening');

        $this->patchJson("/api/applications/{$application->id}/status", [
            'status' => 'shortlisted',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.status_changed',
            'auditable_id' => $application->id,
        ]);
    }

    public function test_resume_document_ownership_and_analysis_dispatch(): void
    {
        Queue::fake([AnalyzeCandidateResume::class, TriggerN8nWorkflow::class]);
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $other = User::factory()->candidate()->create();
        $hr = User::factory()->hr()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();

        $foreignDoc = AiDocument::factory()->create([
            'user_id' => $other->id,
            'status' => AiDocumentStatus::Completed,
        ]);

        Sanctum::actingAs($candidate);
        $this->postJson("/api/jobs/{$job->id}/applications", [
            'resume_document_id' => $foreignDoc->id,
        ])->assertNotFound();

        $ownDoc = AiDocument::factory()->create([
            'user_id' => $candidate->id,
            'status' => AiDocumentStatus::Completed,
        ]);

        $this->postJson("/api/jobs/{$job->id}/applications", [
            'resume_document_id' => $ownDoc->id,
            'cover_letter' => 'Please review my resume.',
        ])->assertCreated();

        Queue::assertPushed(AnalyzeCandidateResume::class);
    }

    public function test_resume_analysis_and_job_match_pipeline_with_fakes(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [1, 0], 'index' => 0]],
                'model' => 'text-embedding-3-small',
            ], 200),
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'summary' => 'Strong Laravel background.',
                                'skills' => ['Laravel', 'PHP'],
                                'experience' => ['5 years backend'],
                                'education' => ['BSc CS'],
                                'strengths' => ['API design'],
                                'gaps' => ['Limited Go'],
                                'confidence' => 'high',
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ]],
                ], 200)
                ->push([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'score' => 87,
                                'matched_skills' => ['Laravel', 'PHP'],
                                'missing_skills' => ['Go'],
                                'reasoning' => 'Strong overlap with requirements.',
                                'confidence' => 'high',
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ]],
                ], 200),
        ]);

        $candidate = User::factory()->candidate()->create();
        $hr = User::factory()->hr()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create([
            'requirements' => 'Laravel and PHP experience required.',
        ]);
        $document = AiDocument::factory()->create([
            'user_id' => $candidate->id,
            'status' => AiDocumentStatus::Completed,
        ]);
        AiDocumentChunk::query()->create([
            'ai_document_id' => $document->id,
            'chunk_index' => 0,
            'content' => 'Five years of Laravel and PHP experience building APIs.',
            'token_count' => 20,
            'metadata' => ['char_count' => 60],
            'embedding' => [1, 0],
            'embedding_model' => 'text-embedding-3-small',
            'embedded_at' => now(),
        ]);

        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'resume_document_id' => $document->id,
        ]);

        /** @var ResumeAnalysisService $analysisService */
        $analysisService = app(ResumeAnalysisService::class);
        $analysis = $analysisService->analyze($application);

        $this->assertSame('high', $analysis->confidence);
        $this->assertContains('Laravel', $analysis->skills);

        $application->refresh();
        /** @var JobMatchingService $matchService */
        $matchService = app(JobMatchingService::class);
        $match = $matchService->match($application->fresh(['resumeAnalysis', 'job', 'candidate']));

        $this->assertSame(87, $match->score);
        $this->assertContains('Laravel', $match->matched_skills);

        // Idempotency
        $again = $analysisService->analyze($application->fresh(['resumeAnalysis', 'resumeDocument', 'job', 'candidate']));
        $this->assertSame($analysis->id, $again->id);
        $this->assertSame(1, AiResumeAnalysis::query()->count());
        $this->assertSame(1, AiJobMatch::query()->count());
    }

    public function test_malformed_match_score_and_screening_are_handled(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"score":"nope"}']]],
            ], 200),
        ]);

        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();
        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
        ]);

        AiResumeAnalysis::query()->create([
            'application_id' => $application->id,
            'summary' => 'Experienced Laravel developer.',
            'skills' => ['Laravel'],
            'experience' => [],
            'education' => [],
            'strengths' => [],
            'gaps' => [],
            'confidence' => 'medium',
            'analyzed_at' => now(),
        ]);

        $this->expectException(LlmProviderException::class);
        app(JobMatchingService::class)->match($application->fresh([
            'jobMatch', 'resumeAnalysis', 'job', 'candidate.candidateProfile',
        ]));
    }

    public function test_ai_screening_is_advisory_and_authorized(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'model' => 'gpt-4.1-mini',
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'recommendation' => 'shortlist',
                            'score' => 88,
                            'reasoning' => 'Skills align with the role.',
                            'confidence' => 'high',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ]],
            ], 200),
        ]);

        $application = $this->applicationWithAnalysisAndMatch();
        $candidate = $application->candidate;
        $hr = $application->job->creator;

        Sanctum::actingAs($candidate);
        $this->postJson("/api/applications/{$application->id}/ai-screen")
            ->assertNotFound();

        Sanctum::actingAs($hr);
        $this->postJson("/api/applications/{$application->id}/ai-screen")
            ->assertOk()
            ->assertJsonPath('data.recommendation', 'shortlist')
            ->assertJsonPath('meta.decision_support_only', true);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Applied, $application->status);
    }

    public function test_automation_event_idempotency(): void
    {
        config(['automation.enabled' => false]);

        Queue::fake();
        Notification::fake();

        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();
        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => ApplicationStatus::Screening,
        ]);

        Sanctum::actingAs($hr);
        $this->patchJson("/api/applications/{$application->id}/status", [
            'status' => 'shortlisted',
        ])->assertOk();

        // Listener queues TriggerN8nWorkflow; process it twice for idempotency of event_key.
        Queue::assertPushed(TriggerN8nWorkflow::class, function (TriggerN8nWorkflow $job) use ($application): bool {
            return $job->eventKey === 'application-shortlisted-'.$application->id;
        });

        $workflowJob = new TriggerN8nWorkflow(
            'application_shortlisted',
            'application-shortlisted-'.$application->id,
            ['application_id' => $application->id],
        );
        $workflowJob->handle(app(N8nService::class));
        $workflowJob->handle(app(N8nService::class));

        $this->assertSame(1, AutomationEvent::query()->count());
    }

    public function test_audit_log_does_not_store_sensitive_fields(): void
    {
        $hr = User::factory()->hr()->create();
        Sanctum::actingAs($hr);

        $this->postJson('/api/jobs', $this->jobPayload([
            'title' => 'Secure Role',
            'description' => str_repeat('Detailed job description. ', 5),
        ]))->assertCreated();

        $log = AuditLog::query()->where('action', 'job.created')->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertArrayNotHasKey('embedding', $log->new_values ?? []);
        $this->assertArrayNotHasKey('extracted_text', $log->new_values ?? []);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Backend Engineer',
            'description' => 'Build and maintain Laravel APIs for recruitment workflows.',
            'requirements' => 'PHP, Laravel, MySQL',
            'responsibilities' => 'Own backend services',
            'employment_type' => 'full_time',
            'department' => 'Engineering',
            'location' => 'Remote',
            'salary_min' => 60000,
            'salary_max' => 90000,
            'experience_min' => 2,
            'experience_max' => 6,
        ], $overrides);
    }

    private function applicationWithAnalysisAndMatch(): JobApplication
    {
        $hr = User::factory()->hr()->create();
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->for($hr, 'creator')->published()->create();
        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => ApplicationStatus::Applied,
        ]);

        AiResumeAnalysis::query()->create([
            'application_id' => $application->id,
            'summary' => 'Experienced Laravel developer.',
            'skills' => ['Laravel', 'PHP'],
            'experience' => ['Backend'],
            'education' => ['BSc'],
            'strengths' => ['APIs'],
            'gaps' => ['Go'],
            'confidence' => 'high',
            'analyzed_at' => now(),
        ]);

        AiJobMatch::query()->create([
            'application_id' => $application->id,
            'score' => 85,
            'matched_skills' => ['Laravel'],
            'missing_skills' => ['Go'],
            'reasoning' => 'Good overlap',
            'confidence' => 'high',
            'generated_at' => now(),
        ]);

        return $application->fresh(['job.creator', 'candidate', 'resumeAnalysis', 'jobMatch']);
    }
}
