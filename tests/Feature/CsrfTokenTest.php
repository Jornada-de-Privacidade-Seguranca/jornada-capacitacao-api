<?php

namespace Tests\Feature;

use Tests\TestCase;

class CsrfTokenTest extends TestCase
{
    public function test_it_returns_csrf_token_for_session_requests(): void
    {
        $response = $this->getJson('/api/auth/csrf-token')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token CSRF gerado com sucesso.')
            ->assertJsonStructure([
                'data' => [
                    'csrf_token',
                ],
            ]);

        $this->assertIsString($response->json('data.csrf_token'));
        $this->assertNotSame('', $response->json('data.csrf_token'));
        $response->assertCookie('XSRF-TOKEN');
    }
}
