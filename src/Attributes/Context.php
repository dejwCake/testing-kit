<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Attributes;

use Attribute;

#[Attribute]
final readonly class Context
{
    public function __construct(public ?string $user = null)
    {
    }
}
