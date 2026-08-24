<?php

namespace Tests\Feature;

use Tests\TestCase;

class CompetencyTest extends TestCase
{
    public function test_competency_scaffold_routes_are_not_registered(): void
    {
        foreach ([
            ['GET', '/api/competencies'],
            ['POST', '/api/competencies'],
            ['GET', '/api/competencies/1'],
            ['PUT', '/api/competencies/1'],
            ['PATCH', '/api/competencies/1'],
            ['DELETE', '/api/competencies/1'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertNotFound();
        }
    }
}
