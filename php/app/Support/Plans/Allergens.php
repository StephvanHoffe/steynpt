<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Js;

/**
 * Hulpcontrole voor Steyn: zoekt in een voedingsschema naar ingrediënten die
 * botsen met de allergieën of eetstijl van de klant. Dit is een heuristiek op
 * trefwoorden en vervangt nooit de handmatige controle.
 *
 * Een regel (Rule) is een array met: label, en optioneel prefixes, words, freeFrom en except.
 */
final class Allergens
{
    /** Woorden die met een trefwoord beginnen maar er niets mee te maken hebben. */
    private const NOT_A_MATCH = ['speculaas', 'speculoos', 'nootmuskaat', 'pitaya', 'kippenvel', 'lampion'];

    /** "boterham" begint met "boter" maar is brood: geen zuivel (wel gluten). */
    private const NOT_DAIRY = ['boterham'];

    private const DAIRY = ['melk', 'yoghurt', 'kwark', 'kaas', 'room', 'boter', 'skyr', 'whey', 'zuivel', 'karnemelk', 'hüttenkäse', 'huttenkase', 'cottage cheese', 'mozzarella', 'feta', 'parmezaan', 'ricotta', 'mascarpone', 'crème fraîche', 'creme fraiche'];

    private const EGG_WORDS = ['ei', 'eieren', 'omelet', 'omelette', 'roerei', 'spiegelei', 'eiersalade', 'eidooier', 'mayonaise', 'frittata', 'shakshuka'];

    private const FISH = ['vis', 'zalm', 'tonijn', 'kabeljauw', 'makreel', 'haring', 'sardine', 'sardines', 'pangasius', 'koolvis', 'forel', 'heilbot', 'ansjovis', 'tilapia', 'schelvis', 'kibbeling', 'lekkerbekje'];

    /** FISH zonder "vis" (dat woord telt alleen als los woord). */
    private const FISH_PREFIXES = ['zalm', 'tonijn', 'kabeljauw', 'makreel', 'haring', 'sardine', 'sardines', 'pangasius', 'koolvis', 'forel', 'heilbot', 'ansjovis', 'tilapia', 'schelvis', 'kibbeling', 'lekkerbekje'];

    private const SHELLFISH = ['garnaal', 'garnalen', 'krab', 'kreeft', 'langoustine', 'scampi', 'gamba'];

    private const MOLLUSCS = ['mossel', 'mosselen', 'oester', 'oesters', 'inktvis', 'calamari', 'sint-jakobsschelp', 'kokkel'];

    private const MEAT = ['kip', 'kipfilet', 'kippendij', 'rund', 'rundvlees', 'gehakt', 'varken', 'ham', 'spek', 'kalkoen', 'worst', 'biefstuk', 'vlees', 'salami', 'chorizo', 'bacon', 'lam', 'lamsvlees', 'kalfsvlees', 'rookvlees', 'filet americain', 'shoarma', 'hamburger', 'frikandel', 'kipshoarma', 'carpaccio'];

    private const PORK = ['varken', 'varkensvlees', 'ham', 'spek', 'bacon', 'salami', 'chorizo', 'speklap', 'procureur', 'pancetta', 'prosciutto', 'rookworst', 'gelatine'];

    public const ALLERGY_RULES = [
        'gluten' => ['label' => 'gluten', 'prefixes' => ['tarwe', 'gluten', 'rogge', 'gerst', 'spelt', 'couscous', 'bulgur', 'pasta', 'spaghetti', 'brood', 'boterham', 'crackers', 'beschuit', 'wrap', 'tortilla', 'seitan', 'paneermeel', 'muesli', 'granola', 'pannenkoek', 'bagel', 'croissant', 'pita'], 'words' => ['havermout'], 'freeFrom' => ['glutenvrij']],
        'melk' => ['label' => 'melk', 'prefixes' => self::DAIRY, 'except' => self::NOT_DAIRY],
        'lactose' => ['label' => 'lactose', 'prefixes' => self::DAIRY, 'freeFrom' => ['lactosevrij'], 'except' => self::NOT_DAIRY],
        'ei' => ['label' => 'ei', 'words' => self::EGG_WORDS],
        'pinda' => ['label' => 'pinda', 'prefixes' => ['pinda', 'satésaus', 'satesaus', 'saté', 'sate', 'apenootjes']],
        'noten' => ['label' => 'noten', 'prefixes' => ['noten', 'noot', 'amandel', 'walnoot', 'walnoten', 'cashew', 'hazelnoot', 'hazelnoten', 'pecan', 'pistache', 'macadamia', 'paranoot', 'marsepein', 'notenpasta']],
        'soja' => ['label' => 'soja', 'prefixes' => ['soja', 'tofu', 'tempeh', 'edamame', 'miso', 'ketjap', 'tamari']],
        'vis' => ['label' => 'vis', 'prefixes' => self::FISH_PREFIXES, 'words' => ['vis']],
        'schaaldieren' => ['label' => 'schaaldieren', 'prefixes' => self::SHELLFISH],
        'weekdieren' => ['label' => 'weekdieren', 'prefixes' => self::MOLLUSCS],
        'selderij' => ['label' => 'selderij', 'prefixes' => ['selderij', 'bleekselderij', 'knolselderij', 'selder']],
        'mosterd' => ['label' => 'mosterd', 'prefixes' => ['mosterd']],
        'sesam' => ['label' => 'sesam', 'prefixes' => ['sesam', 'tahin', 'tahini', 'hummus', 'humus']],
        'lupine' => ['label' => 'lupine', 'prefixes' => ['lupine']],
        'sulfiet' => ['label' => 'sulfiet', 'prefixes' => ['sulfiet', 'wijn', 'gedroogde abrikoos', 'gedroogde abrikozen']],
    ];

    public const DIET_RULES = [
        'vegetarisch' => ['label' => 'vegetarisch', 'prefixes' => [...self::MEAT, ...self::FISH_PREFIXES, ...self::SHELLFISH, ...self::MOLLUSCS], 'words' => ['vis']],
        'veganistisch' => [
            'label' => 'veganistisch',
            'prefixes' => [...self::MEAT, ...self::FISH_PREFIXES, ...self::SHELLFISH, ...self::MOLLUSCS, ...self::DAIRY, 'honing'],
            'words' => ['vis', ...self::EGG_WORDS],
            'except' => self::NOT_DAIRY,
        ],
        'pescotarisch' => ['label' => 'pescotarisch', 'prefixes' => self::MEAT],
        'halal' => ['label' => 'halal', 'prefixes' => self::PORK, 'except' => ['hamburger']],
    ];

    /** Woorden direct vóór een treffer die aangeven dat het ingrediënt er juist níet in zit. */
    private const ABSENT = ['zonder', 'geen'];

    /** Bij eetstijlen: woorden die aangeven dat het om een plantaardige vervanger gaat. */
    private const SUBSTITUTE = ['vegetarisch', 'vegan', 'veganistisch', 'plantaardig', 'vega'];

    private const LETTER = '/\p{L}$/u';

    /**
     * Alle treffers van een trefwoord: positie (bytes) en het hele woord vanaf die positie.
     *
     * @return list<array{index: int, word: string}>
     */
    private static function findTerm(string $haystack, string $term, bool $wholeWord): array
    {
        $hits = [];
        $from = 0;
        while (($index = strpos($haystack, $term, $from)) !== false) {
            $from = $index + strlen($term);
            if ($index > 0 && preg_match(self::LETTER, self::lastChar(substr($haystack, 0, $index)))) {
                continue;
            }
            preg_match('/^\p{L}*/u', substr($haystack, $index + strlen($term)), $rest);
            $tail = $rest[0] ?? '';
            if ($wholeWord && $tail !== '') {
                continue;
            }
            $hits[] = ['index' => $index, 'word' => $term.$tail];
        }

        return $hits;
    }

    /** Laatste teken (code point) van een tekst. */
    private static function lastChar(string $text): string
    {
        return preg_match('/.$/su', $text, $m) ? $m[0] : substr($text, -1);
    }

    private static function isNeutralized(string $haystack, int $index, string $word, array $rule, bool $diet): bool
    {
        if (str_contains($word, 'vrij') || str_contains($word, 'vervanger')) {
            return true;
        }
        foreach ([...self::NOT_A_MATCH, ...($rule['except'] ?? [])] as $w) {
            if (str_starts_with($word, $w)) {
                return true;
            }
        }
        if (preg_match('/^['.Js::SPACE.']*-?vervanger/u', substr($haystack, $index + strlen($word)))) {
            return true;
        }
        // Alleen de twee woorden direct ervoor tellen mee ("zonder noten", "lactosevrije kwark").
        $before = self::lastUtf16Units(substr($haystack, 0, $index), 40);
        $words = preg_split('/[^\p{L}-]+/u', $before, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $previous = array_slice($words, -2);
        $markers = [...self::ABSENT, ...($rule['freeFrom'] ?? []), ...($diet ? self::SUBSTITUTE : [])];
        foreach ($previous as $w) {
            foreach ($markers as $m) {
                if (str_starts_with($w, $m)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** De laatste $units UTF-16-eenheden van een tekst (zoals haystack.slice(index - 40, index)). */
    private static function lastUtf16Units(string $text, int $units): string
    {
        $chars = mb_str_split($text, 1, 'UTF-8');
        $out = '';
        $count = 0;
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            $size = strlen($chars[$i]) === 4 ? 2 : 1;
            if ($count + $size > $units) {
                break;
            }
            $count += $size;
            $out = $chars[$i].$out;
        }

        return $out;
    }

    private static function matchRule(string $haystack, array $rule, bool $diet): ?string
    {
        foreach ([[$rule['words'] ?? [], true], [$rule['prefixes'] ?? [], false]] as [$terms, $whole]) {
            foreach ($terms as $term) {
                foreach (self::findTerm($haystack, $term, $whole) as $hit) {
                    if (! self::isNeutralized($haystack, $hit['index'], $hit['word'], $rule, $diet)) {
                        return $hit['word'];
                    }
                }
            }
        }

        return null;
    }

    /** @return list<array{where: string, text: string}> */
    private static function nutritionTexts(array $plan): array
    {
        $texts = [];
        foreach ($plan['meals'] as $i => $meal) {
            $mealName = Js::truthy($meal['name']) ? $meal['name'] : 'Maaltijd '.($i + 1);
            foreach ($meal['options'] as $j => $option) {
                $texts[] = [
                    'where' => $mealName.' › '.(Js::truthy($option['title']) ? $option['title'] : 'optie '.($j + 1)),
                    'text' => "{$option['title']} {$option['ingredients']}",
                ];
            }
        }
        foreach ($plan['tips'] as $i => $tip) {
            $texts[] = ['where' => 'Tip '.($i + 1), 'text' => $tip];
        }
        $texts[] = ['where' => 'Toelichting', 'text' => $plan['summary']];
        // "avoid" wordt bewust overgeslagen: daar horen allergenen juist genoemd te worden.

        return $texts;
    }

    /**
     * @param  array<string, mixed>  $plan  voedingsschema (NutritionPlan)
     * @param  array{allergies: list<string>, diet: string}  $intake
     * @return list<array{term: string, reason: string, where: string}>
     */
    public static function findAllergenWarnings(array $plan, array $intake): array
    {
        $rules = [];
        foreach ($intake['allergies'] as $a) {
            if (isset(self::ALLERGY_RULES[$a])) {
                $rules[] = ['rule' => self::ALLERGY_RULES[$a], 'reason' => 'allergie: '.self::ALLERGY_RULES[$a]['label'], 'diet' => false];
            }
        }
        if (isset(self::DIET_RULES[$intake['diet']])) {
            $rule = self::DIET_RULES[$intake['diet']];
            $rules[] = ['rule' => $rule, 'reason' => 'eetstijl: '.$rule['label'], 'diet' => true];
        }

        $warnings = [];
        foreach (self::nutritionTexts($plan) as ['where' => $where, 'text' => $text]) {
            $haystack = mb_strtolower($text, 'UTF-8');
            foreach ($rules as ['rule' => $rule, 'reason' => $reason, 'diet' => $diet]) {
                $term = self::matchRule($haystack, $rule, $diet);
                if ($term === null || $term === '') {
                    continue;
                }
                foreach ($warnings as $w) {
                    if ($w['where'] === $where && $w['reason'] === $reason) {
                        continue 2;
                    }
                }
                $warnings[] = ['term' => $term, 'reason' => $reason, 'where' => $where];
            }
        }

        return $warnings;
    }
}
