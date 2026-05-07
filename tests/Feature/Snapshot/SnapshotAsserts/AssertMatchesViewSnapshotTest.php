<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Snapshot\SnapshotAsserts;

use DejwCake\TestingKit\Snapshot\SnapshotAsserts;
use DejwCake\TestingKit\Tests\TestCase;

final class AssertMatchesViewSnapshotTest extends TestCase
{
    use SnapshotAsserts;

    public function testNormalisesUuidIdAndCsrfBeforeSnapshotting(): void
    {
        $html = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <head>
                <title>Snapshot</title>
                <script src="http://localhost/build/assets/app-abc123.js"></script>
                <link rel="stylesheet" href="http://localhost/build/assets/app-abc123.css">
            </head>
            <body>
                <!-- a comment that should be stripped -->
                <div id="abc123def" data-id='{"id":"xyz789abc"}'>
                    <input type="hidden" name="_token" value="abcdefghijklmno">
                    <p>request id 9b7e7df0-3d80-4b85-9f12-7d1a1c5a6f01</p>
                    <a href="/things?id=zzz999">Link</a>
                </div>
            </body>
            </html>
            HTML;

        $this->assertMatchesViewSnapshot($html);
    }
}
