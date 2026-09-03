<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\ApplicationShortlisted;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\ApplicationStatusNotification;

class HandleApplicationShortlisted
{
    public function handle(ApplicationShortlisted $event): void
    {
        $application = $event->application->loadMissing(['candidate']);

        $application->candidate?->notify(new ApplicationStatusNotification(
            $application,
            'You have been shortlisted.',
            'application.shortlisted',
        ));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::ApplicationShortlisted->value,
            'application-shortlisted-'.$application->id,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
        );
    }
}
