<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\OpenApi\OpenApiValidationTrait;

use DejwCake\TestingKit\OpenApi\OpenApiValidationTrait;
use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

final class AssertValidOpenApiResponseForRouteTest extends TestCase
{
    use OpenApiValidationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeOpenApiValidator(__DIR__ . '/../../../Fixtures/openapi.json');
    }

    public function testValidPayloadPassesValidation(): void
    {
        $this->registerItemsRoute([['id' => 1, 'title' => 'Hello']]);

        $response = $this->getJson('/api/items');

        $response->assertOk();
        $this->assertValidOpenApiResponseForRoute('GET', 'api/items/index', $response);
    }

    public function testInvalidPayloadFailsValidation(): void
    {
        $this->registerItemsRoute(['data' => 'not-an-array']);

        $response = $this->getJson('/api/items');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('OpenApi Validation failed');

        $this->assertValidOpenApiResponseForRoute('GET', 'api/items/index', $response);
    }

    public function testMissingOperationFailsWithRouteNotFound(): void
    {
        Route::get('api/unknown', static fn () => ['data' => []])->name('api/unknown/index');

        $response = $this->getJson('/api/unknown');

        $this->expectException(AssertionFailedError::class);

        $this->assertValidOpenApiResponseForRoute('GET', 'api/unknown/index', $response);
    }

    /**
     * @param mixed $payload
     */
    private function registerItemsRoute(mixed $payload): void
    {
        Route::get('api/items', static fn () => is_array($payload) && array_is_list($payload)
            ? ['data' => $payload]
            : $payload)->name('api/items/index');

        $router = $this->app->make(Router::class);
        assert($router instanceof Router);
        $router->getRoutes()->refreshNameLookups();
    }
}
