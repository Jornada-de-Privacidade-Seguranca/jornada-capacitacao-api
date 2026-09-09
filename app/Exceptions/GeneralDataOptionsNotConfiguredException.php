<?php

namespace App\Exceptions;

use RuntimeException;

class GeneralDataOptionsNotConfiguredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('General data options are not configured.');
    }
}
