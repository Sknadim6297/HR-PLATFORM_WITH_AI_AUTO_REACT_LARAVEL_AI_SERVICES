<?php

namespace App\Enums;

enum AutomationWorkflow: string
{
    case ApplicationSubmitted = 'application_submitted';
    case ApplicationShortlisted = 'application_shortlisted';
    case CandidateSelected = 'candidate_selected';
    case CandidateRejected = 'candidate_rejected';
    case InterviewScheduled = 'interview_scheduled';
    case HighMatchCandidate = 'high_match_candidate';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
