<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Automatische waarden: {code} in een tekst wordt vervangen door een waarde die op één plek wordt ingesteld.
 */
final class Vars
{
    /** Alle automatische waarden en waar ze worden ingesteld (pagina en onderdeel in het beheer). */
    public const VARS = [
        ['key' => 'actie', 'label' => 'Naam van de vriendenactie', 'page' => 'algemeen', 'section' => 'vriendenactie'],
        ['key' => 'vriendkorting', 'label' => 'Wat de vriend krijgt', 'page' => 'algemeen', 'section' => 'vriendenactie'],
        ['key' => 'jouwkorting', 'label' => 'Wat de uitnodiger krijgt', 'page' => 'algemeen', 'section' => 'vriendenactie'],
        ['key' => 'ademprijs', 'label' => 'Prijs ademsessie 1-op-1', 'page' => 'pakketten', 'section' => 'adem'],
        ['key' => 'ademduur', 'label' => 'Duur ademsessie 1-op-1', 'page' => 'pakketten', 'section' => 'adem'],
        ['key' => 'online-vanaf', 'label' => 'Laagste prijs online coaching', 'page' => 'pakketten', 'section' => 'online'],
    ];

    /** De codes uit VARS, in dezelfde volgorde. */
    public const VAR_KEYS = ['actie', 'vriendkorting', 'jouwkorting', 'ademprijs', 'ademduur', 'online-vanaf'];

    // Hele euro's zonder decimalen, anders twee decimalen met een komma: 79 of 59,50.
    private static function formatPrice(float $n): string
    {
        return floor($n) == $n ? Js::numberToString($n) : (string) preg_replace('/\./', ',', Js::toFixed($n, 2), 1);
    }

    /**
     * De waarden van de codes, uit de teksten van 'algemeen' en 'pakketten' (zoals Values::resolvePage ze geeft).
     *
     * @return array<string, string>
     */
    public static function computeVars(array $shared, array $prices): array
    {
        $online = [];
        foreach ($prices['online']['plans'] as $plan) {
            $n = Values::priceNumber((string) $plan['price']);
            if (is_finite($n)) {
                $online[] = $n;
            }
        }

        return [
            'actie' => $shared['vriendenactie']['headline'],
            'vriendkorting' => $shared['vriendenactie']['friendReward'],
            'jouwkorting' => $shared['vriendenactie']['referrerReward'],
            'ademprijs' => $prices['adem']['price'],
            'ademduur' => $prices['adem']['duration'],
            'online-vanaf' => $online ? self::formatPrice(min($online)) : '',
        ];
    }
}
