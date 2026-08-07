<?php

namespace App\Exceptions;

use RuntimeException;

class IncompleteJourneyException extends RuntimeException
{
    /**
     * @param  array<int, string>  $missingFields
     */
    public function __construct(private readonly array $missingFields)
    {
        parent::__construct('Completed journey has incomplete required data.');
    }

    /**
     * @return array<int, string>
     */
    public function missingFields(): array
    {
        return $this->missingFields;
    }
}
