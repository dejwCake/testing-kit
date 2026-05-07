<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional\Concerns;

use DejwCake\TestingKit\Attributes\Context;
use DejwCake\TestingKit\Functional\TestCase;
use Illuminate\Auth\AuthManager;
use ReflectionAttribute;
use ReflectionClass;

/**
 * @phpstan-require-extends TestCase
 */
trait ResolvesAttributeContext
{
    protected function resolveContextFromAttributes(): void
    {
        [$classAttributes, $methodAttributes] = $this->getTestCaseAttributes();

        $context = new Context();
        $context = $this->mergeContextFromAttributes($context, $classAttributes);
        $context = $this->mergeContextFromAttributes($context, $methodAttributes);

        $this->setUserFromContext($context);
    }

    /**
     * @return array{0: ReflectionAttribute[], 1: ReflectionAttribute[]}
     */
    private function getTestCaseAttributes(): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $classAttributes = $reflectionClass->getAttributes();
        $reflectionMethod = $reflectionClass->getMethod($this->name());
        $methodAttributes = $reflectionMethod->getAttributes();

        return [$classAttributes, $methodAttributes];
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     */
    private function mergeContextFromAttributes(Context $context, array $attributes): Context
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() !== Context::class) {
                continue;
            }

            $newContext = $attribute->newInstance();
            assert($newContext instanceof Context);
            if ($newContext->user !== null) {
                $context = new Context(user: $newContext->user);
            }
        }

        return $context;
    }

    private function setUserFromContext(Context $context): void
    {
        if ($context->user === null) {
            return;
        }

        match ($context->user) {
            'customer' => $this->applyCustomerContext(),
            'admin-user' => $this->applyAdminUserContext(),
            'anonymous' => $this->applyAnonymousContext(),
            default => $this->failUnsupportedUser($context->user),
        };
    }

    private function applyCustomerContext(): void
    {
        $this->actingAsCustomer();
    }

    private function applyAdminUserContext(): void
    {
        $this->actingAsAdminUser();
    }

    private function applyAnonymousContext(): void
    {
        $auth = $this->app->make(AuthManager::class);
        assert($auth instanceof AuthManager);

        if ($auth->guard()->check()) {
            $auth->guard()->logoutCurrentDevice();
        }
    }

    private function failUnsupportedUser(string $user): never
    {
        $this->fail(
            sprintf(
                'Unsupported user. [%s] Allowed are: anonymous|customer|admin-user',
                $user,
            ),
        );
    }
}
