<?php

namespace App\Events;

use App\Models\JobApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResumeAnalysisCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public JobApplication $application) {}
}
