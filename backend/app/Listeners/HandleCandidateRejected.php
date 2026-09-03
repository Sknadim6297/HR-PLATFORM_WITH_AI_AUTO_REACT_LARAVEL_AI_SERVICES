<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\CandidateRejected;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\ApplicationStatusNotification;

class HandleCandidateRejected
{
    public function handle(CandidateRejected $event): void
    {
        $application = $event->application->loadMissing(['candidate']);

        $application->candidate?->notify(new ApplicationStatusNotification(
            $application,
            'Your application was not successful this time.',
            'application.rejected',
        ));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::CandidateRejected->value,
            'candidate-rejected-'.$application->id,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
        );
    }
}
