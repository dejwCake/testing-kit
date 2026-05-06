<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use DejwCake\TestingKit\OpenApi\OpenApiValidationTrait;

abstract class ApiTestCase extends HttpTestCase
{
    use OpenApiValidationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeOpenApiValidator(
            (string) config('testing-kit.openapi.spec_path'),
            (bool) config('testing-kit.openapi.regenerate_on_init', true),
            true,
        );
    }

    /**
     * @return array<string, list<array<string, string>>|string>
     */
    protected function createApiErrorViolation(string $property, string ...$violations): array
    {
        $errorResponse = [
            'propertyPath' => $property,
            'errors' => [],
        ];

        foreach ($violations as $violation) {
            $errorResponse['errors'][] = ['translation' => $violation];
        }

        return $errorResponse;
    }
}
