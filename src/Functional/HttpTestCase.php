<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

abstract class HttpTestCase extends TestCase
{
    use TestCaseContext;

    protected function setUp(): void
    {
        parent::setUp();

        // disable csrf protection
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->resolveContextFromAttributes();
    }
}
