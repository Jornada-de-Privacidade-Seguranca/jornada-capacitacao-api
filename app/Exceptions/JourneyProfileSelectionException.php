<?php

namespace App\Exceptions;

use RuntimeException;

class JourneyProfileSelectionException extends RuntimeException
{
    public const REQUIRED = 'required';

    public const MISMATCH = 'mismatch';

    public const LOCKED = 'locked';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
