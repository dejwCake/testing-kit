<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Concerns;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;

/**
 * @phpstan-require-extends TestCase
 */
trait FreezesCarbon
{
    protected function freezeCarbon(?Carbon $now = null): void
    {
        Carbon::setTestNow($now ?? Carbon::parse($this->frozenNow()));
    }

    protected function unfreezeCarbon(): void
    {
        Carbon::setTestNow();
    }

    private function frozenNow(): string
    {
        return (string) $this->app->make(Config::class)->get('testing-kit.frozen_now', '2026-01-15 10:30:00');
    }
}
