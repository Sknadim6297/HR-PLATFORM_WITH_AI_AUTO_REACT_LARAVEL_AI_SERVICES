<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Selected = 'selected';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Applied => [self::Screening, self::Withdrawn],
            self::Screening => [self::Shortlisted, self::Rejected, self::Withdrawn],
            self::Shortlisted => [self::Interview, self::Rejected, self::Withdrawn],
            self::Interview => [self::Selected, self::Rejected],
            self::Selected, self::Rejected, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
