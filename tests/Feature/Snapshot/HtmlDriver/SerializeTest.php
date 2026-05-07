<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Snapshot\HtmlDriver;

use DejwCake\TestingKit\Snapshot\HtmlDriver;
use DejwCake\TestingKit\Tests\TestCase;

final class SerializeTest extends TestCase
{
    public function testEmptyStringReturnsEmpty(): void
    {
        $driver = new HtmlDriver();

        self::assertSame('', $driver->serialize(''));
    }

    public function testValidHtmlIsSerialized(): void
    {
        $driver = new HtmlDriver();

        $html = '<!DOCTYPE html><html><body><p>Hello</p></body></html>';
        $serialized = $driver->serialize($html);

        self::assertStringContainsString('<p>Hello</p>', $serialized);
    }

    public function testNonEmptyStringIsAlwaysReturnedAsString(): void
    {
        $driver = new HtmlDriver();

        $result = $driver->serialize('<unknown-tag>x</unknown-tag>');

        self::assertNotSame('', $result);
        self::assertStringContainsString('x', $result);
    }
}
