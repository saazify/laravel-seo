<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Local;

use Saazify\LaravelSeo\Support\Xml;

final class KmlDocument
{
    /**
     * @param  list<array{name: string, description?: string|null, latitude: float|int|string, longitude: float|int|string, address?: string|null}>  $places
     */
    public function render(array $places): string
    {
        $xml = Xml::open('kml', [
            'xmlns' => 'http://www.opengis.net/kml/2.2',
        ]);
        $xml->startElement('Document');

        foreach ($places as $place) {
            if (! is_numeric($place['latitude'] ?? null) || ! is_numeric($place['longitude'] ?? null)) {
                continue;
            }

            $xml->startElement('Placemark');
            Xml::element($xml, 'name', (string) ($place['name'] ?? ''));

            $description = trim((string) ($place['description'] ?? ''));
            $address = trim((string) ($place['address'] ?? ''));

            if ($address !== '') {
                $description = trim($description."\n".$address);
            }

            Xml::element($xml, 'description', $description !== '' ? $description : null);
            $xml->startElement('Point');
            Xml::element($xml, 'coordinates', $place['longitude'].','.$place['latitude'].',0');
            $xml->endElement();
            $xml->endElement();
        }

        $xml->endElement();

        return Xml::close($xml);
    }
}
