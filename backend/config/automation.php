<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automation / n8n
    |--------------------------------------------------------------------------
    |
    | Only approved workflow identifiers may be triggered. Arbitrary webhook
    | URLs are never accepted from request input.
    |
    */

    'enabled' => (bool) env('N8N_ENABLED', false),

    'base_url' => rtrim((string) env('N8N_BASE_URL', ''), '/'),

    'timeout' => (int) env('N8N_TIMEOUT', 15),

    'high_match_score' => (int) env('N8N_HIGH_MATCH_SCORE', 80),

    /*
    | Approved workflow keys mapped to webhook path suffixes.
    | Example full URL: {base_url}/{path}
    */
    'workflows' => [
        'application_submitted' => env('N8N_WF_APPLICATION_SUBMITTED', 'webhook/application-submitted'),
        'application_shortlisted' => env('N8N_WF_APPLICATION_SHORTLISTED', 'webhook/application-shortlisted'),
        'candidate_selected' => env('N8N_WF_CANDIDATE_SELECTED', 'webhook/candidate-selected'),
        'candidate_rejected' => env('N8N_WF_CANDIDATE_REJECTED', 'webhook/candidate-rejected'),
        'interview_scheduled' => env('N8N_WF_INTERVIEW_SCHEDULED', 'webhook/interview-scheduled'),
        'high_match_candidate' => env('N8N_WF_HIGH_MATCH_CANDIDATE', 'webhook/high-match-candidate'),
    ],

];
