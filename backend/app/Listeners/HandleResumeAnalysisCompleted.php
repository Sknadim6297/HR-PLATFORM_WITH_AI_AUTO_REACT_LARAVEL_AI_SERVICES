<?php

namespace App\Listeners;

use App\Events\ResumeAnalysisCompleted;
use App\Notifications\ResumeAnalysisReadyNotification;

class HandleResumeAnalysisCompleted
{
    public function handle(ResumeAnalysisCompleted $event): void
    {
        $application = $event->application->loadMissing(['job.creator']);

        $application->job?->creator?->notify(new ResumeAnalysisReadyNotification($application));
    }
}
