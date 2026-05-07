<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional\Concerns;

use DejwCake\TestingKit\Functional\TestCase;
use Illuminate\Testing\TestResponse;

/**
 * @phpstan-require-extends TestCase
 */
trait AssertsDownload
{
    public function assertDownload(TestResponse $response, ?string $filename = null): void
    {
        $contentDisposition = explode(';', (string) $response->headers->get('content-disposition'));

        $this->assertContentDispositionType($contentDisposition[0]);

        if ($filename !== null) {
            $this->assertContentDispositionFilename($contentDisposition, $filename);
        }
    }

    private function assertContentDispositionType(string $type): void
    {
        $type = trim($type);
        if ($type !== 'attachment' && $type !== 'inline') {
            self::fail(
                'Response does not offer a file download.' . PHP_EOL .
                sprintf('Disposition [%s] found in header, [attachment] expected.', $type),
            );
        }
    }

    /**
     * @param array<int, string> $contentDisposition
     */
    private function assertContentDispositionFilename(array $contentDisposition, string $filename): void
    {
        if (!isset($contentDisposition[1])) {
            self::fail(sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename));
        }

        $parts = explode('=', $contentDisposition[1]);
        $key = trim($parts[0]);

        if ($key !== 'filename') {
            self::fail(
                'Unsupported Content-Disposition header provided.' . PHP_EOL .
                sprintf('Disposition [%s] found in header, [filename] expected.', $key),
            );
        }

        $actualFilename = isset($parts[1]) ? trim($parts[1], " \"'") : '';

        self::assertSame(
            $filename,
            $actualFilename,
            sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename),
        );
    }
}
