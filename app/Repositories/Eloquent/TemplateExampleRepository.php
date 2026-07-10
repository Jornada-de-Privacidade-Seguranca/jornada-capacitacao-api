<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\TemplateExampleRepositoryInterface;

class TemplateExampleRepository implements TemplateExampleRepositoryInterface
{
    public function getExampleData(?string $context, bool $includeSteps): array
    {
        // Coloque aqui consultas ao banco, chamadas a Eloquent ou leituras externas.
        // Este template nao consulta o banco de proposito, mas a camada existe para
        // mostrar que Controllers e Services nao devem acessar persistencia direto.
        $layers = [
            [
                'layer' => 'request',
                'responsibility' => 'Validar entrada HTTP.',
                'put_here' => 'Regras de campos, tipos, obrigatoriedade e mensagens de validacao.',
            ],
            [
                'layer' => 'controller',
                'responsibility' => 'Receber a request e devolver a response padronizada.',
                'put_here' => 'Apenas chamadas para Services, Resources e ApiResponse.',
            ],
            [
                'layer' => 'service',
                'responsibility' => 'Orquestrar regra de negocio.',
                'put_here' => 'Fluxos, validacoes de dominio, decisoes e chamadas para repositories.',
            ],
            [
                'layer' => 'repository',
                'responsibility' => 'Encapsular acesso a dados.',
                'put_here' => 'Queries, Eloquent, filtros de persistencia e integracoes de leitura/escrita.',
            ],
            [
                'layer' => 'resource',
                'responsibility' => 'Formatar a entidade ou DTO para JSON.',
                'put_here' => 'Apenas campos de saida em snake_case, sem montar o contrato completo.',
            ],
        ];

        return [
            'route_name' => 'template-example',
            'description' => 'Exemplo executavel de endpoint seguindo MVC + Services + Repositories.',
            'request_context' => $context ?? 'sem contexto informado',
            'layers' => $includeSteps ? $layers : [],
            'repository_note' => 'Esta repository retorna dados estaticos para demonstrar a arquitetura sem depender do banco.',
        ];
    }
}
