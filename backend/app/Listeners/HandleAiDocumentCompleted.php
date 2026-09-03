<?php

namespace App\Listeners;

use App\Enums\AiDocumentStatus;
use App\Events\AiDocumentCompleted;
use App\Jobs\AnalyzeCandidateResume;
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
            ->whereDoesntHave('resumeAnalysis', function ($query): void {
                $query->whereNotNull('analyzed_at');
            })
            ->pluck('id')
            ->each(fn (int $applicationId) => AnalyzeCandidateResume::dispatch($applicationId));
    }
}
