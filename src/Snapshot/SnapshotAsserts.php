<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Snapshot;

use DOMDocument;
use Illuminate\Contracts\Config\Repository as Config;

use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;

/**
 * @phpstan-require-extends \DejwCake\TestingKit\Functional\TestCase
 */
trait SnapshotAsserts
{
    public function removeWhitespaceInEmptyRows(string $mainContent): string
    {
        $tmp = explode("\n", $mainContent);
        $tmp = array_map(static fn (string $line) => rtrim($line), $tmp);

        return implode("\n", $tmp);
    }

    protected function assertMatchesViewSnapshot(string $htmlResponse): void
    {
        //remove html comments
        $htmlResponse = preg_replace('/<!--(.|\s)*?-->/', '', $htmlResponse);

        $appUrl = $this->app->make(Config::class)->get('app.url');
        assert(is_string($appUrl));

        $mainContent = $this->isFullHtmlDocument($htmlResponse)
            ? $this->normaliseFullDocument($htmlResponse, $appUrl)
            : $htmlResponse;

        //replace random uuids
        $mainContent = $this->change(
            $mainContent,
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/',
            'uuid-replaced-in-snapshot-%d',
        );

        //replace random ids
        $mainContent = $this->change($mainContent, '/id="([0-9a-zA-Z]*)"/', 'id="id%d"');
        $mainContent = $this->change($mainContent, '/\?id=([0-9a-zA-Z]*)"/', '?id=id%d"');
        $mainContent = $this->change($mainContent, '/"id":"([0-9a-zA-Z]*)"/', '"id":"id%d"');
        $mainContent = $this->change($mainContent, '/"checksum":"([0-9a-zA-Z]*)"/', '"checksum":"checksum%d"');
        $mainContent = $this->change($mainContent, '/"htmlHash":"([0-9a-zA-Z]*)"/', '"htmlHash":"htmlHash%d"');

        // replace database auto-increment ids rendered as "#N" in text content (e.g. "Inquiry ID: #6")
        $mainContent = preg_replace('/(>\s*)#\d+(\s*[<])/', '$1#db-id-replaced-in-snapshot$2', $mainContent);

        // replace copyright year (rendered via PHP date('Y') which Carbon::setTestNow does not override)
        $mainContent = preg_replace('/(&copy;\s+)\d{4}/', '$1YEAR-replaced-in-snapshot', $mainContent);
        $mainContent = $this->remove($mainContent, '#<script(.*)\@vite(.*)></script>#');
        $mainContent = $this->remove($mainContent, '#<link(.*)%5B::%5D(.*)>#');
        $mainContent = $this->remove($mainContent, '#<script(.*)/build/(.*)></script>#');
        $mainContent = $this->remove($mainContent, '#<link(.*)/build/(.*)>#');
        $mainContent = $this->removeWhitespaceInEmptyRows($mainContent);

        //replace csrf tokens
        $mainContent = preg_replace(
            '/<input type="hidden" name="_token" value="[0-9a-zA-Z]*">/',
            '<input type="hidden" name="_token" value="csrf-replaced-in-snapshot">',
            $mainContent,
        );

        $this->assertMatchesHtmlSnapshot($mainContent);
    }

    private function isFullHtmlDocument(string $html): bool
    {
        return stripos($html, '<html') !== false && stripos($html, '<head') !== false;
    }

    private function normaliseFullDocument(string $html, string $baseHref): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);

        $head = $dom->getElementsByTagName('head')->item(0);
        if ($head !== null) {
            $baseElement = $dom->createElement('base');
            $baseElement->setAttribute('href', $baseHref);
            $head->appendChild($baseElement);
        }

        $content = $dom->getElementsByTagName('html')->item(0);

        return $dom->saveHTML($content);
    }

    private function change(string $mainContent, string $pattern, string $format): string
    {
        $found = [];
        if (preg_match_all($pattern, $mainContent, $found) !== false) {
            foreach ($found[0] as $index => $id) {
                $mainContent = str_replace(
                    sprintf('%s', $id),
                    sprintf($format, $index + 1),
                    $mainContent,
                );
            }
        }

        return $mainContent;
    }

    private function remove(string $mainContent, string $pattern): string
    {
        return preg_replace($pattern, '', $mainContent);
    }
}
