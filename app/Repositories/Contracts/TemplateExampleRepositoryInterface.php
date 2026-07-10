<?php

namespace App\Repositories\Contracts;

interface TemplateExampleRepositoryInterface
{
    public function getExampleData(?string $context, bool $includeSteps): array;
}
