<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\ApplicationSubmitted;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\NewApplicationNotification;

class HandleApplicationSubmitted
{
    public function handle(ApplicationSubmitted $event): void
    {
        $application = $event->application->loadMissing(['job.creator', 'candidate']);

        $application->candidate?->notify(new ApplicationStatusNotification(
            $application,
            'Your application was submitted successfully.',
            'application.submitted',
        ));

        $application->job?->creator?->notify(new NewApplicationNotification($application));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::ApplicationSubmitted->value,
            'application-submitted-'.$application->id,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
        );
    }
}
