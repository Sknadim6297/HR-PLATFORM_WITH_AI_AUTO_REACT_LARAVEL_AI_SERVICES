<?php

namespace App\Listeners;

use App\Events\ResumeAnalysisCompleted;

class HandleResumeAnalysisCompleted
{
    public function handle(ResumeAnalysisCompleted $event): void
    {
        // The complete report notification is sent after job matching finishes.
    }
}
