<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    private const CSRF_TOKEN = 'test-csrf-token';

    protected function withCsrfToken(): static
    {
        return $this
            ->withSession(['_token' => self::CSRF_TOKEN])
            ->withHeader('X-CSRF-TOKEN', self::CSRF_TOKEN);
    }

    protected function postJsonWithCsrf($uri, array $data = [], array $headers = [], int $options = 0): TestResponse
    {
        return $this->withCsrfToken()->postJson($uri, $data, $headers, $options);
    }

    protected function putJsonWithCsrf($uri, array $data = [], array $headers = [], int $options = 0): TestResponse
    {
        return $this->withCsrfToken()->putJson($uri, $data, $headers, $options);
    }
}
