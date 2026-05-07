<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Snapshot\SnapshotAsserts;

use DejwCake\TestingKit\Snapshot\SnapshotAsserts;
use DejwCake\TestingKit\Tests\TestCase;

final class RemoveWhitespaceInEmptyRowsTest extends TestCase
{
    public function testTrailingSpacesAreStripped(): void
    {
        $fixture = new class () {
            use SnapshotAsserts;
        };

        $input = "first line   \n   \nthird line";
        $expected = "first line\n\nthird line";

        self::assertSame($expected, $fixture->removeWhitespaceInEmptyRows($input));
    }

    public function testInnerSpacesArePreserved(): void
    {
        $fixture = new class () {
            use SnapshotAsserts;
        };

        $input = "a   b\nc   d";

        self::assertSame($input, $fixture->removeWhitespaceInEmptyRows($input));
    }
}
