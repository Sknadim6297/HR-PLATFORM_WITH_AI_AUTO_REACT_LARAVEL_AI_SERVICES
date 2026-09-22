<?php

namespace App\Listeners;

use App\Enums\AiDocumentStatus;
use App\Events\AiDocumentCompleted;
use App\Jobs\AnalyzeCandidateResume;
use App\Jobs\GenerateJobMatch;
use App\Models\JobApplication;

class HandleAiDocumentCompleted
{
    public function handle(AiDocumentCompleted $event): void
    {
        if ($event->document->status !== AiDocumentStatus::Completed) {
            return;
        }

        JobApplication::query()
            ->where('resume_document_id', $event->document->id)
            ->with(['resumeAnalysis', 'jobMatch'])
            ->get()
            ->each(function (JobApplication $application): void {
                if ($application->resumeAnalysis?->isComplete()) {
                    if ($application->jobMatch?->generated_at === null) {
                        GenerateJobMatch::dispatch($application->id);
                    }

                    return;
                }

                AnalyzeCandidateResume::dispatch($application->id);
            });
    }
}
