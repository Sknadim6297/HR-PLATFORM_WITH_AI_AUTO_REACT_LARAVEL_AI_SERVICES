<?php

namespace App\Enums;

enum AiTask: string
{
    case Summarize = 'summarize';
    case Generate = 'generate';
    case Classify = 'classify';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
