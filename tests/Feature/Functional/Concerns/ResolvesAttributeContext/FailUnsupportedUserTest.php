<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\Concerns\ResolvesAttributeContext;

use DejwCake\TestingKit\Attributes\Context;
use DejwCake\TestingKit\Tests\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

final class FailUnsupportedUserTest extends TestCase
{
    #[Context(user: 'bogus')]
    public function testUnsupportedUserFailsWithListOfAllowedActors(): void
    {
        // setUp already fired and dispatched the failure, so we re-trigger it
        // here for a clean exception assertion.
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Allowed are: anonymous|customer|admin-user');

        $this->resolveContextFromAttributes();
    }

    /**
     * Override the failure path's runtime visibility so setUp() can complete
     * and let the test method assert on the exception itself.
     */
    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (Throwable) {
            // Swallow the setUp failure — the assertion is performed inside
            // the test method by calling resolveContextFromAttributes again.
        }
    }
}
