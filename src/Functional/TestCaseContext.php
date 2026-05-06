<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use DejwCake\TestingKit\Attributes\Context;
use Illuminate\Auth\AuthManager;
use Override;
use ReflectionAttribute;
use ReflectionClass;

trait TestCaseContext
{
    private ?Context $context = null;

    /**
     * @return array{0: ReflectionAttribute[], 1: ReflectionAttribute[]}
     */
    protected function getTestCaseAttributes(): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $classAttributes = $reflectionClass->getAttributes();
        $reflectionMethod = $reflectionClass->getMethod($this->name());
        $methodAttributes = $reflectionMethod->getAttributes();

        return [$classAttributes, $methodAttributes];
    }

    #[Override]
    protected function getName(bool $withDataSet = true): string
    {
        if ($withDataSet) {
            return $this->nameWithDataSet();
        }

        return $this->name();
    }

    private function resolveContextFromAttributes(): void
    {
        [$classAttributes, $methodAttributes] = $this->getTestCaseAttributes();

        $this->context = new Context();
        $this->setContextFromAttributes($classAttributes);
        $this->setContextFromAttributes($methodAttributes);

        $this->setUserFromContext($this->context);
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     */
    private function setContextFromAttributes(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() !== Context::class) {
                continue;
            }

            $context = $attribute->newInstance();
            assert($context instanceof Context);
            if ($context->user !== null) {
                $this->context = new Context(user: $context->user);
            }
        }
    }

    private function setUserFromContext(Context $context): bool
    {
        if ($context->user === null) {
            return false;
        }

        return match ($context->user) {
            'customer' => $this->applyCustomerContext(),
            'admin-user' => $this->applyAdminUserContext(),
            'anonymous' => $this->applyAnonymousContext(),
            default => $this->failUnsupportedUser($context->user),
        };
    }

    private function applyCustomerContext(): bool
    {
        $this->actingAsCustomer();

        return true;
    }

    private function applyAdminUserContext(): bool
    {
        $this->actingAsAdminUser();

        return true;
    }

    private function applyAnonymousContext(): bool
    {
        $auth = $this->app->make(AuthManager::class);
        assert($auth instanceof AuthManager);

        if ($auth->guard()->check()) {
            $auth->guard()->logoutCurrentDevice();
        }

        return true;
    }

    private function failUnsupportedUser(string $user): bool
    {
        $this->fail(
            sprintf(
                'Unsupported user. [%s] Allowed are: anonymous|customer|admin-user',
                $user,
            ),
        );
    }
}
