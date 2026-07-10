<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemplateExampleTest extends TestCase
{
    public function test_template_example_endpoint_uses_standard_api_contract(): void
    {
        $this->getJson('/api/template-example?context=swagger&include_steps=true')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Template endpoint retrieved successfully.')
            ->assertJsonPath('data.route_name', 'template-example')
            ->assertJsonPath('data.request_context', 'swagger')
            ->assertJsonPath('data.layers.0.layer', 'request');
    }

    public function test_template_example_validation_uses_standard_error_contract(): void
    {
        $this->getJson('/api/template-example?include_steps=not-a-boolean')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'include_steps',
                ],
                'code',
            ]);
    }

    public function test_template_example_allows_swagger_origin(): void
    {
        $this
            ->withHeader('Origin', 'http://localhost:8080')
            ->getJson('/api/template-example?context=swagger&include_steps=true')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8080');
    }
}
