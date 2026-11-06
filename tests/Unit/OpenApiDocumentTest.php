<?php

namespace Tests\Unit;

use Opis\JsonSchema\CompliantValidator;
use Opis\JsonSchema\Errors\ErrorFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * docs/openapi.yaml e montado pelo Swagger, mas nenhum outro ponto da aplicacao le o
 * arquivo. Uma chave duplicada ja quebrou o Swagger (hotfix no PR 45) e voltou a
 * aparecer durante a #152 sem que testes ou CI acusassem. Este teste garante que o
 * arquivo e um YAML valido sem chaves duplicadas, que respeita o JSON Schema oficial
 * do OpenAPI 3.1 e que toda referencia interna ($ref "#/...") aponta para algo
 * existente.
 *
 * O schema oficial fica versionado em tests/Fixtures/openapi para o teste rodar sem
 * rede. Ele nao valida o conteudo dos Schema Objects (so exige objeto ou booleano).
 */
class OpenApiDocumentTest extends TestCase
{
    private const OPENAPI_SCHEMA_ID = 'https://spec.openapis.org/oas/3.1/schema/2022-10-07';

    public function test_openapi_document_is_a_valid_openapi_31_definition(): void
    {
        $this->assertValidOpenApiDocument(dirname(__DIR__, 2).'/docs/openapi.yaml');
    }

    public function test_it_rejects_a_document_with_duplicate_keys(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Duplicate key "200"');

        $this->parseYaml($this->fixturePath('duplicate-key.yaml'));
    }

    public function test_it_rejects_a_document_that_violates_the_openapi_schema(): void
    {
        $errors = $this->schemaErrors($this->parseYaml($this->fixturePath('invalid-structure.yaml')));

        $this->assertArrayHasKey('/info', $errors);
        $this->assertArrayHasKey('/paths/~1exemplo/get/responses/200', $errors);
    }

    public function test_it_rejects_a_document_with_broken_internal_references(): void
    {
        $document = $this->parseYaml($this->fixturePath('broken-ref.yaml'));

        $this->assertSame([], $this->schemaErrors($document));
        $this->assertSame(['#/components/responses/Inexistente'], $this->brokenReferences($document));
    }

    private function assertValidOpenApiDocument(string $path): void
    {
        $document = $this->parseYaml($path);

        $this->assertSame(
            [],
            $this->schemaErrors($document),
            "{$path} nao respeita o schema do OpenAPI 3.1.",
        );
        $this->assertSame(
            [],
            $this->brokenReferences($document),
            "{$path} tem \$ref apontando para componentes inexistentes.",
        );
    }

    /**
     * O parser do Symfony recusa chaves duplicadas com ParseException. Mapas viram objetos
     * para que um "{}" do YAML continue sendo objeto (e nao lista) na validacao.
     */
    private function parseYaml(string $path): mixed
    {
        return Yaml::parseFile($path, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_OBJECT_FOR_MAP);
    }

    /**
     * @return array<string, mixed> erros indexados pelo JSON Pointer do trecho invalido
     */
    private function schemaErrors(mixed $document): array
    {
        $validator = new CompliantValidator(max_errors: 20);

        // O opis/json-schema 2.6 resolve o "$dynamicRef": "#meta" do schema oficial para a
        // raiz do documento em vez do "$dynamicAnchor": "meta" de $defs/schema. Como o
        // schema e um unico recurso, trocar por um $ref direto tem o mesmo significado.
        $schema = str_replace(
            '"$dynamicRef": "#meta"',
            '"$ref": "#/$defs/schema"',
            (string) file_get_contents($this->fixturePath('oas-3.1-schema-2022-10-07.json')),
        );
        $validator->resolver()->registerRaw($schema, self::OPENAPI_SCHEMA_ID);

        $result = $validator->validate($document, self::OPENAPI_SCHEMA_ID);

        if ($result->isValid()) {
            return [];
        }

        return (new ErrorFormatter)->format($result->error());
    }

    /**
     * @return list<string>
     */
    private function brokenReferences(mixed $document): array
    {
        $document = json_decode((string) json_encode($document, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $broken = [];

        array_walk_recursive($document, function (mixed $value, int|string $key) use ($document, &$broken): void {
            if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/') && ! $this->pointerExists($document, $value)) {
                $broken[] = $value;
            }
        });

        return array_values(array_unique($broken));
    }

    private function pointerExists(mixed $document, string $reference): bool
    {
        $node = $document;

        foreach (explode('/', substr($reference, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], rawurldecode($segment));

            if (! is_array($node) || ! array_key_exists($segment, $node)) {
                return false;
            }

            $node = $node[$segment];
        }

        return true;
    }

    private function fixturePath(string $file): string
    {
        return dirname(__DIR__).'/Fixtures/openapi/'.$file;
    }
}
