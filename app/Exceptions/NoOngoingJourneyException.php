<?php

namespace App\Exceptions;

use RuntimeException;

class NoOngoingJourneyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ongoing journey not found.');
    }
}
