<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Snapshot;

use DOMDocument;

trait SnapshotAsserts
{
    protected function assertMatchesViewSnapshot(string $htmlResponse): void
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        //remove html comments
        $htmlResponse = preg_replace('/<!--(.|\s)*?-->/', '', $htmlResponse);

        //find main content block
        $dom->loadHTML($htmlResponse, \LIBXML_NONET | \LIBXML_NOWARNING | \LIBXML_NOERROR);

        $baseElement = $dom->createElement('base');
        $baseElement->setAttribute('href', 'http://home-accounting.dev');
        $dom->getElementsByTagName('head')->item(0)->appendChild($baseElement);

        $content = $dom->getElementsByTagName('html')->item(0);

        //convert main content block back to string
        $mainContent = $dom->saveHTML($content);

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

    public function removeWhitespaceInEmptyRows(string $mainContent): string
    {
        $tmp = explode("\n", $mainContent);
        $tmp = array_map(static fn (string $line) => rtrim($line), $tmp);

        return implode("\n", $tmp);
    }
}
