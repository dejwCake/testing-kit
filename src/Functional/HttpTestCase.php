<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

abstract class HttpTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // disable csrf protection
        $this->withoutMiddleware(PreventRequestForgery::class);
    }
}
