<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\Concerns\AssertsDownload;

use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;

final class AssertDownloadTest extends TestCase
{
    public function testAttachmentWithFilenamePasses(): void
    {
        $response = $this->makeResponse('attachment; filename="dummy.pdf"');

        $this->assertDownload($response, 'dummy.pdf');

        self::assertTrue(true);
    }

    public function testInlineDispositionPasses(): void
    {
        $response = $this->makeResponse('inline; filename="dummy.pdf"');

        $this->assertDownload($response, 'dummy.pdf');

        self::assertTrue(true);
    }

    public function testWrongDispositionTypeFails(): void
    {
        $response = $this->makeResponse('something-else; filename="dummy.pdf"');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Response does not offer a file download');

        $this->assertDownload($response, 'dummy.pdf');
    }

    public function testWrongFilenameFails(): void
    {
        $response = $this->makeResponse('attachment; filename="other.pdf"');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected file [dummy.pdf]');

        $this->assertDownload($response, 'dummy.pdf');
    }

    public function testNoFilenameSkipsFilenameAssertion(): void
    {
        $response = $this->makeResponse('attachment; filename="anything.pdf"');

        $this->assertDownload($response);

        self::assertTrue(true);
    }

    private function makeResponse(string $contentDisposition): TestResponse
    {
        $base = new Response('body');
        $base->headers->set('content-disposition', $contentDisposition);

        return new TestResponse($base);
    }
}
