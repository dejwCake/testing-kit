<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\OpenApi;

use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

trait OpenApiValidationTrait
{
    private ?ResponseValidator $validator = null;

    protected function initializeOpenApiValidator(
        string $pathToSpecs,
        bool $regenerateSchema = false,
        bool $forceRecreate = false,
    ): void {
        if ($this->validator !== null && !$forceRecreate) {
            return;
        }

        if ($regenerateSchema) {
            $command = config('testing-kit.openapi.regenerate_command', 'l5-swagger:generate');
            $this->artisan($command);
        }

        $this->validator = (new ValidatorBuilder())->fromJsonFile($pathToSpecs)->getResponseValidator();
    }

    /**
     * @param string[][] $data
     */
    protected function assertValidOpenApiResponseForRoute(
        string $method,
        string $routeName,
        TestResponse $response,
        ?array $data = null,
        ?string $message = null,
    ): void {
        $router = $this->app->make(Router::class);
        assert($router instanceof Router);
        $route = $router->getRoutes()->getByName($routeName);
        if ($route === null) {
            self::fail(sprintf('Route for `%s` not found.', $routeName));
        }

        $paths = Util::determinePossiblePaths($route->uri());
        if (count($paths) === 0) {
            self::fail(sprintf('Operation path for route `%s` not found.', $routeName));
        }

        $path = str_starts_with($paths[0], '/') ? $paths[0] : '/' . $paths[0];

        $this->assertResponseValidOpenApi($method, $path, $response, $data, $message);
    }

    /**
     * @param string[][] $data
     */
    protected function assertResponseValidOpenApi(
        string $method,
        string $path,
        TestResponse $response,
        ?array $data = null,
        ?string $message = null,
    ): void {
        $operation = new OperationAddress($path, strtolower($method));

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrResponse = $psrHttpFactory->createResponse($response->baseResponse);

        $this->validateResponseOpenApi($operation, $psrResponse, $message);

        if ($data !== null) {
            $responseData = $response->json()['data'];
            (new Collection($data))->each(
                static function ($value, $field) use ($responseData): void {
                    self::assertEquals($value, $responseData[$field]);
                },
            );
        }
    }

    private function validateResponseOpenApi(
        OperationAddress $operation,
        ResponseInterface $response,
        ?string $message = null,
    ): bool {
        assert($this->validator instanceof ResponseValidator);

        try {
            $this->validator->validate($operation, $response);

            return true;
        } catch (ValidationFailed $validationFailed) {
            $errors = '';
            $previousException = $validationFailed->getPrevious();
            if ($previousException instanceof KeywordMismatch) {
                $errors = $previousException->getMessage() . ' ' . (new Collection(
                    $previousException->dataBreadCrumb()->buildChain(),
                ))->map(
                    static fn ($field) => $field . ': ' . $previousException->keyword(),
                )->implode(', ');
            }
            self::fail(
                $message ?: sprintf(
                    "OpenApi Validation failed. %s. Previous error: %s",
                    $validationFailed->getMessage(),
                    $errors,
                ),
            );
        }
    }
}
