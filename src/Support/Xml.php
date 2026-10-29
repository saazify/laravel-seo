<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Support;

final class Xml
{
    /**
     * @param  array<string, string>  $attributes
     */
    public static function open(string $root, array $attributes): \XMLWriter
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startElement($root);

        foreach ($attributes as $name => $value) {
            $xml->writeAttribute($name, $value);
        }

        return $xml;
    }

    public static function element(\XMLWriter $xml, string $name, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $xml->writeElement($name, $value);
    }

    public static function close(\XMLWriter $xml): string
    {
        $xml->endElement();
        $xml->endDocument();

        return (string) $xml->outputMemory();
    }
}
