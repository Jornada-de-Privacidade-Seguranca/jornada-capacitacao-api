<?php

namespace App\Support;

class SessionMetadata
{
    public static function toArray(): array
    {
        return [
            'session' => [
                'lifetime_minutes' => config('session.lifetime'),
            ],
        ];
    }
}
