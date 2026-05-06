<?php

declare(strict_types=1);

namespace DejwCake\TestingKit;

use Illuminate\Support\ServiceProvider;
use Override;

final class TestingKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/testing-kit.php' => $this->app->configPath('testing-kit.php'),
            ], 'testing-kit-config');
        }
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/testing-kit.php', 'testing-kit');
    }
}
