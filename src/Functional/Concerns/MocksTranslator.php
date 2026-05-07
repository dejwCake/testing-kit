<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional\Concerns;

use DejwCake\TestingKit\Functional\TestCase;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Mockery;

/**
 * @phpstan-require-extends TestCase
 */
trait MocksTranslator
{
    protected Translator|Mockery\MockInterface $translatorMocker;

    private function mockTranslator(): void
    {
        $this->translatorMocker = Mockery::mock(
            $this->app['translator'],
            static function (Mockery\MockInterface $mock): void {
                $mock->makePartial();

                $mock->shouldReceive('choice')
                    ->andReturnUsing(static fn ($key) => $key);

                $mock->shouldReceive('get')
                    ->andReturnUsing(static fn ($key) => $key);
            },
        );

        $this->instance('translator', $this->translatorMocker);

        // we need to extend validator factory to set our new mocked translator to it
        $this->app->singleton('validator', static function ($app) {
            // same code as in ValidationServiceProvider::registerValidationFactory() method
            $validator = new Factory($app['translator'], $app);

            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
