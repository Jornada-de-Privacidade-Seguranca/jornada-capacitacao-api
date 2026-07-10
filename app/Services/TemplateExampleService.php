<?php

namespace App\Services;

use App\Repositories\Contracts\TemplateExampleRepositoryInterface;

class TemplateExampleService
{
    public function __construct(private readonly TemplateExampleRepositoryInterface $templateExampleRepository)
    {
    }

    public function show(?string $context, bool $includeSteps): array
    {
        // Coloque aqui regras de negocio, transformacoes e orquestracao.
        // A Service chama a Repository mesmo neste exemplo sem banco.
        return $this->templateExampleRepository->getExampleData($context, $includeSteps);
    }
}
