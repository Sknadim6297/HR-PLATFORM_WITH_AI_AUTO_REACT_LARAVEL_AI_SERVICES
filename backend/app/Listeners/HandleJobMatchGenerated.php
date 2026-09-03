<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\JobMatchGenerated;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\HighMatchCandidateNotification;

class HandleJobMatchGenerated
{
    public function handle(JobMatchGenerated $event): void
    {
        $application = $event->application->loadMissing(['job.creator', 'jobMatch']);
        $threshold = (int) config('automation.high_match_score', 80);
        $score = (int) ($application->jobMatch?->score ?? 0);

        if ($score < $threshold) {
            return;
        }

        $application->job?->creator?->notify(new HighMatchCandidateNotification($application));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::HighMatchCandidate->value,
            'high-match-'.$application->id.'-'.$score,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'score' => $score,
            ],
        );
    }
}
