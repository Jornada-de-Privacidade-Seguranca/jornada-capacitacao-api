<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about', function (): void {
    $this->comment('Jornada de Capacitacao API');
})->purpose('Display basic application information');
