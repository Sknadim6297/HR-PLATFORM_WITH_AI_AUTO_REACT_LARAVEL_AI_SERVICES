<?php

namespace App\Providers;

use App\Contracts\AI\LlmProviderInterface;
use App\Contracts\AI\VectorStoreInterface;
use App\Events\AiDocumentCompleted;
use App\Events\ApplicationShortlisted;
use App\Events\ApplicationSubmitted;
use App\Events\CandidateRejected;
use App\Events\CandidateSelected;
use App\Events\InterviewScheduled;
use App\Events\JobMatchGenerated;
use App\Events\ResumeAnalysisCompleted;
use App\Listeners\HandleAiDocumentCompleted;
use App\Listeners\HandleApplicationShortlisted;
use App\Listeners\HandleApplicationSubmitted;
use App\Listeners\HandleCandidateRejected;
use App\Listeners\HandleCandidateSelected;
use App\Listeners\HandleInterviewScheduled;
use App\Listeners\HandleJobMatchGenerated;
use App\Listeners\HandleResumeAnalysisCompleted;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Policies\CandidateProfilePolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\JobPolicy;
use App\Services\AI\MySqlVectorStore;
use App\Services\AI\OpenAiLlmProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VectorStoreInterface::class, function ($app) {
            $driver = (string) config('ai.vector_search.driver', 'mysql');

            return match ($driver) {
                'mysql' => $app->make(MySqlVectorStore::class),
                default => throw new InvalidArgumentException(
                    "Unsupported vector store driver [{$driver}]."
                ),
            };
        });

        $this->app->bind(LlmProviderInterface::class, function ($app) {
            $provider = (string) config('ai.llm.provider', 'openai');

            return match ($provider) {
                'openai' => $app->make(OpenAiLlmProvider::class),
                default => throw new InvalidArgumentException(
                    "Unsupported LLM provider [{$provider}]."
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(CandidateProfile::class, CandidateProfilePolicy::class);

        Event::listen(ApplicationSubmitted::class, HandleApplicationSubmitted::class);
        Event::listen(ResumeAnalysisCompleted::class, HandleResumeAnalysisCompleted::class);
        Event::listen(JobMatchGenerated::class, HandleJobMatchGenerated::class);
        Event::listen(ApplicationShortlisted::class, HandleApplicationShortlisted::class);
        Event::listen(InterviewScheduled::class, HandleInterviewScheduled::class);
        Event::listen(CandidateSelected::class, HandleCandidateSelected::class);
        Event::listen(CandidateRejected::class, HandleCandidateRejected::class);
        Event::listen(AiDocumentCompleted::class, HandleAiDocumentCompleted::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
