<?php

namespace App\Listeners;

use App\Enums\AutomationWorkflow;
use App\Events\CandidateSelected;
use App\Jobs\TriggerN8nWorkflow;
use App\Notifications\ApplicationStatusNotification;

class HandleCandidateSelected
{
    public function handle(CandidateSelected $event): void
    {
        $application = $event->application->loadMissing(['candidate']);

        $application->candidate?->notify(new ApplicationStatusNotification(
            $application,
            'Congratulations! You have been selected.',
            'application.selected',
        ));

        TriggerN8nWorkflow::dispatch(
            AutomationWorkflow::CandidateSelected->value,
            'candidate-selected-'.$application->id,
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
        );
    }
}
