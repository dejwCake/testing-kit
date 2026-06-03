<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Feature;

use DejwCake\TestingKit\Concerns\FreezesCarbon;
use DejwCake\TestingKit\Concerns\MocksTranslator;
use DejwCake\TestingKit\Snapshot\HtmlDriver;
use DejwCake\TestingKit\Snapshot\SnapshotAsserts;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends BaseTestCase
{
    use FreezesCarbon;
    use MatchesSnapshots;
    use MocksTranslator;
    use RefreshDatabase;
    use SnapshotAsserts;

    protected Config $config;

    public function assertMatchesHtmlSnapshot(string $actual, ?string $id = null): void
    {
        $this->assertMatchesSnapshot($actual, new HtmlDriver(), $id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->app->make(Config::class);
        $this->app->setLocale((string) $this->config->get('testing-kit.default_locale', 'en'));

        $this->mockTranslator();
    }
}
