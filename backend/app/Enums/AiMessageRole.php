<?php

namespace App\Enums;

enum AiMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
