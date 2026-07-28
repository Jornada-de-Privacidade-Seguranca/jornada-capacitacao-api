<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class ValidateSessionCsrfToken extends PreventRequestForgery
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
