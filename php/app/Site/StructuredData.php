<?php

namespace App\Site;

use App\Content\Values;

/**
 * Gestructureerde gegevens (schema.org, JSON-LD) voor zoekmachines: het bedrijf op de vaste locatie
 * (adres, ligging, werkgebied), Steyn als persoon, de diensten met prijzen en per pagina de veelgestelde vragen.
 * Alles komt uit de website-teksten, zodat het gelijk blijft met wat bezoekers zien.
 */
final class StructuredData
{
    /** Ligging van Gymbase, Overtoom 371-w (OpenStreetMap). Alleen gebruikt zolang dat het adres is. */
    private const GYMBASE_GEO = ['street' => 'Overtoom 371-w', 'latitude' => 52.3595173, 'longitude' => 4.8630131];

    /** Buurten rond de Overtoom waar klanten vandaan komen (naast heel Amsterdam en online). */
    private const NEARBY = ['Oud-West', 'De Baarsjes', 'Oud-Zuid', 'Bos en Lommer', 'Overtoomse Veld'];

    /**
     * @param  list<array{q: string, a: string}>|null  $faq
     */
    public static function graph(string $canonical, string $title, string $description, ?array $faq = null): array
    {
        $base = Site::url();
        $ids = ['business' => "{$base}/#bedrijf", 'person' => "{$base}/#steyn", 'website' => "{$base}/#website"];
        $locatie = Texts::get('algemeen')['locatie'];

        $page = [
            '@type' => $faq ? ['WebPage', 'FAQPage'] : 'WebPage',
            '@id' => $canonical,
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'inLanguage' => 'nl-NL',
            'isPartOf' => ['@id' => $ids['website']],
            'about' => ['@id' => $ids['business']],
        ];
        if ($faq) {
            $page['mainEntity'] = array_values(array_map(fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ], array_filter($faq, fn ($item) => ($item['q'] ?? '') !== '' && ($item['a'] ?? '') !== '')));
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $ids['website'],
                    'url' => "{$base}/",
                    'name' => 'SteynPT',
                    'inLanguage' => 'nl-NL',
                    'publisher' => ['@id' => $ids['business']],
                ],
                self::business($base, $ids, $locatie),
                self::person($base, $ids, $locatie),
                $page,
            ],
        ];
    }

    private static function business(string $base, array $ids, array $locatie): array
    {
        $address = ['@type' => 'PostalAddress', 'streetAddress' => $locatie['street'], 'addressCountry' => 'NL'];
        // "1054 JN Amsterdam" → postcode en plaats
        if (preg_match('/^\s*(\d{4}\s?[A-Za-z]{2})\s+(.+?)\s*$/', $locatie['city'], $m)) {
            $address['postalCode'] = strtoupper($m[1]);
            $address['addressLocality'] = $m[2];
        } else {
            $address['addressLocality'] = $locatie['city'];
        }
        $address['addressRegion'] = 'Noord-Holland';

        $business = [
            '@type' => 'LocalBusiness',
            '@id' => $ids['business'],
            'name' => 'SteynPT',
            'description' => Texts::get('home')['seo']['description'],
            'url' => "{$base}/",
            'logo' => "{$base}/brand/steynpt-logo-black.png",
            'image' => ["{$base}/images/steyn-glimlach.jpg", "{$base}/images/steyn-headshot.jpg"],
            'address' => $address,
        ];
        if (trim($locatie['street']) === self::GYMBASE_GEO['street']) {
            $business['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => self::GYMBASE_GEO['latitude'], 'longitude' => self::GYMBASE_GEO['longitude']];
            $business['hasMap'] = Site::mapsUrl($locatie['street'], $locatie['city']);
        }
        $business['areaServed'] = [
            ['@type' => 'City', 'name' => 'Amsterdam'],
            ...array_map(fn (string $name) => ['@type' => 'Place', 'name' => "{$name}, Amsterdam"], self::NEARBY),
            ['@type' => 'Country', 'name' => 'Nederland'],
        ];
        $business['founder'] = ['@id' => $ids['person']];
        $business['employee'] = ['@id' => $ids['person']];
        $business['priceRange'] = '€€';
        if (($locatie['instagramUrl'] ?? '') !== '') {
            $business['sameAs'] = [$locatie['instagramUrl']];
        }
        $business['hasOfferCatalog'] = [
            '@type' => 'OfferCatalog',
            'name' => 'Diensten van SteynPT',
            'itemListElement' => self::offers($base),
        ];

        return $business;
    }

    private static function person(string $base, array $ids, array $locatie): array
    {
        $person = [
            '@type' => 'Person',
            '@id' => $ids['person'],
            'name' => 'Steyn van Leeuwen',
            'jobTitle' => 'Personal trainer en orthomoleculair voedingstherapeut',
            'url' => "{$base}/over-steyn",
            'image' => "{$base}/images/steyn-headshot.jpg",
            'worksFor' => ['@id' => $ids['business']],
            'knowsAbout' => ['Personal training', 'Krachttraining', 'Voedingscoaching', 'Orthomoleculaire voeding', 'Ademcoaching', 'Topsport'],
        ];
        if (($locatie['instagramUrl'] ?? '') !== '') {
            $person['sameAs'] = [$locatie['instagramUrl']];
        }

        return $person;
    }

    /** Diensten met de prijzen zoals ze op de site staan (prijzen die geen getal zijn, worden weggelaten). */
    private static function offers(string $base): array
    {
        $offer = function (string $name, string $path, string $description, ?string $price, ?string $unit) use ($base) {
            $item = [
                '@type' => 'Offer',
                'itemOffered' => ['@type' => 'Service', 'name' => $name, 'description' => $description, 'url' => $base.$path, 'provider' => ['@id' => "{$base}/#bedrijf"]],
            ];
            $number = $price !== null ? Values::priceNumber($price) : NAN;
            if (! is_nan($number)) {
                $item['priceSpecification'] = array_filter([
                    '@type' => 'UnitPriceSpecification',
                    'price' => $number,
                    'priceCurrency' => 'EUR',
                    'unitText' => $unit ?: null,
                ], fn ($v) => $v !== null);
            }

            return $item;
        };

        $offers = [];
        foreach (Texts::ptPrices() as $card) {
            $offers[] = $offer("Personal training – {$card['name']}", '/personal-training', '1-op-1 personal training bij Gymbase in Amsterdam Oud-West.', $card['price'], $card['unit']);
        }
        foreach (Texts::onlinePlans() as $plan) {
            $offers[] = $offer("Online coaching {$plan['name']}", '/online-coaching', $plan['tagline'] ?? 'Online coaching door Steyn van Leeuwen.', $plan['price'], 'per maand');
        }
        $adem = Texts::breathworkPrice();
        $offers[] = $offer('Ademcoaching 1-op-1', '/ademcoaching', 'Een 1-op-1 ademsessie in Amsterdam.', $adem['price'], $adem['unit']);
        $offers[] = $offer('Voedingscoaching', '/voedingscoaching', 'Voedingscoaching door een orthomoleculair voedingstherapeut.', null, null);

        return $offers;
    }

    /** Als JSON voor een <script type="application/ld+json">; < en > worden ge-escaped (teksten zijn bewerkbaar). */
    public static function json(array $graph): string
    {
        return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }
}
