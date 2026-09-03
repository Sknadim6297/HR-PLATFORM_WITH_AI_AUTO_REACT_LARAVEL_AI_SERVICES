<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\InterviewScheduled;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\ApplicationStatusNotification;

class HandleInterviewScheduled
{
    public function handle(InterviewScheduled $event): void
    {
        $application = $event->application->loadMissing(['candidate']);

        $application->candidate?->notify(new ApplicationStatusNotification(
            $application,
            'Your application moved to the interview stage.',
            'application.interview',
        ));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::InterviewScheduled->value,
            'interview-scheduled-'.$application->id,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
        );
    }
}
