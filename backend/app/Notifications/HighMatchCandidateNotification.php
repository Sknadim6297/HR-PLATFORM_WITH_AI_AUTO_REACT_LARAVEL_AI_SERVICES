<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class HighMatchCandidateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'job_match.high',
            'message' => 'A high-match candidate is available for review.',
            'application_id' => $this->application->id,
            'job_id' => $this->application->job_id,
            'score' => $this->application->jobMatch?->score,
        ];
    }
}
