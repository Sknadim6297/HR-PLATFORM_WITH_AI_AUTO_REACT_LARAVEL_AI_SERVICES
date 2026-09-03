<?php

namespace App\Enums;

enum ScreeningRecommendation: string
{
    case Shortlist = 'shortlist';
    case Interview = 'interview';
    case Reject = 'reject';
    case NeedsReview = 'needs_review';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
