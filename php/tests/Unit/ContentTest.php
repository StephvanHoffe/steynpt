<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Content\Fields;
use App\Content\Js;
use App\Content\Markup;
use App\Content\Pages\Ademcoaching;
use App\Content\Pages\Algemeen;
use App\Content\Pages\Home;
use App\Content\Pages\OnlineCoaching;
use App\Content\Pages\Pakketten;
use App\Content\Pages\PersonalTraining;
use App\Content\Registry;
use App\Content\Values;
use App\Content\Vars;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tekstbeheer: de tests uit src/lib/content/content.test.ts, plus een vergelijking met de TypeScript-versie.
 * De vergelijking gebruikt tests/fixtures/content-*.json; opnieuw maken vanuit de hoofdmap van het project met
 *   npx tsx php/tests/fixtures/dump-content-defaults.mts
 */
final class ContentTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../fixtures';

    /** @var array<string, array> */
    private static array $fixtures = [];

    private static function fixture(string $name): array
    {
        return self::$fixtures[$name] ??= json_decode((string) file_get_contents(self::FIXTURES."/{$name}"), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function defaults(array $page): array
    {
        return Fields::pageDefaults($page);
    }

    public static function pages(): iterable
    {
        foreach (Registry::all() as $page) {
            yield $page['slug'] => [$page['slug']];
        }
    }

    // --- register ---

    #[TestDox('register: heeft unieke paginanamen')]
    public function test_heeft_unieke_paginanamen(): void
    {
        $slugs = array_column(Registry::all(), 'slug');
        $this->assertSame(count($slugs), count(array_unique($slugs)));
    }

    #[DataProvider('pages')]
    #[TestDox('register: standaardteksten van $slug zijn geldig en veranderen niet bij opslaan')]
    public function test_standaardteksten_zijn_geldig_en_veranderen_niet_bij_opslaan(string $slug): void
    {
        $page = Registry::get($slug);
        $defaults = self::defaults($page);
        ['values' => $values, 'errors' => $errors] = Values::normalizePage($page, $defaults, Vars::VAR_KEYS);
        $this->assertSame([], $errors);
        foreach ($defaults as $s => $fields) {
            foreach ($fields as $f => $value) {
                $this->assertTrue(Values::sameValue($values[$s][$f], $value), "{$slug}.{$s}.{$f} verandert bij opslaan");
            }
        }
    }

    #[TestDox('register: gebruikt alleen bestaande automatische waarden in de standaardteksten')]
    public function test_gebruikt_alleen_bestaande_automatische_waarden(): void
    {
        $all = json_encode(array_map(Fields::pageDefaults(...), Registry::all()), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $used = Markup::placeholdersIn($all);
        $this->assertNotEmpty($used);
        foreach ($used as $key) {
            $this->assertContains($key, Vars::VAR_KEYS, $key);
        }
    }

    // --- automatische waarden ---

    private static function defaultVars(): array
    {
        return Vars::computeVars(self::defaults(Algemeen::page()), self::defaults(Pakketten::page()));
    }

    #[TestDox('automatische waarden: komen uit de vriendenactie en de pakketten')]
    public function test_komen_uit_de_vriendenactie_en_de_pakketten(): void
    {
        $vars = self::defaultVars();
        $this->assertSame('210', $vars['ademprijs']);
        $this->assertSame('1,5 uur', $vars['ademduur']);
        $this->assertSame('79', $vars['online-vanaf']);
        $this->assertSame('Samen 50% korting', $vars['actie']);
    }

    #[TestDox('automatische waarden: laagste online prijs met komma')]
    public function test_laagste_online_prijs_met_komma(): void
    {
        $prices = self::defaults(Pakketten::page());
        $prices['online']['plans'][1]['price'] = '59,50';
        $this->assertSame('59,50', Vars::computeVars(self::defaults(Algemeen::page()), $prices)['online-vanaf']);
        $this->assertSame(1050.0, Values::priceNumber('1.050'));
    }

    #[TestDox('automatische waarden: worden ingevuld in teksten, opsommingen en lijsten')]
    public function test_worden_ingevuld_in_teksten_opsommingen_en_lijsten(): void
    {
        $vars = self::defaultVars();
        $filled = Markup::fillVars(self::defaults(Ademcoaching::page()), $vars);
        $this->assertSame('1-op-1: 1,5 uur voor € 210,-', $filled['faq']['points'][0]);
        $this->assertMatchesRegularExpression('/duurt 1,5 uur en kost € 210,-/u', $filled['faq']['questions'][0]['a']);
        $this->assertSame('{onbekend} blijft', Markup::fillText('{onbekend} blijft', $vars));
        $this->assertSame(['a', 'b'], Markup::placeholdersIn('{a} {b} {a} {Geen}'));
    }

    // --- opmaak ---

    #[TestDox('opmaak: accent en regeleinden in titels')]
    public function test_accent_en_regeleinden_in_titels(): void
    {
        $this->assertSame([
            ['text' => 'Sterker lichaam.', 'accent' => false],
            ['br' => true],
            ['text' => 'Gezonder', 'accent' => true],
            ['text' => ' leven.', 'accent' => false],
        ], Markup::parseRich("Sterker lichaam.\n*Gezonder* leven."));
        $this->assertSame([['text' => 'Een * los sterretje', 'accent' => false]], Markup::parseRich('Een * los sterretje'));
        $this->assertSame([
            ['text' => 'a', 'accent' => true],
            ['text' => ' en *b', 'accent' => false],
        ], Markup::parseRich('*a* en *b'));
        $this->assertSame('Breng een vriend mee. Samen 50% korting.', Markup::plainRich('Breng een vriend mee. *Samen 50% korting.*'));
    }

    #[TestDox("opmaak: alinea's gescheiden door een lege regel")]
    public function test_alineas_gescheiden_door_een_lege_regel(): void
    {
        $this->assertSame(['Een', "Twee\ndrie"], Markup::paragraphs("Een\n\n  \nTwee\ndrie\n\n"));
    }

    // --- controle bij opslaan ---

    private static function save(mixed $input): array
    {
        return Values::normalizePage(Home::page(), $input, Vars::VAR_KEYS);
    }

    #[TestDox('controle bij opslaan: schoont tekst op en laat niet-meegestuurde velden met rust')]
    public function test_schoont_tekst_op_en_laat_niet_meegestuurde_velden_met_rust(): void
    {
        ['values' => $values, 'errors' => $errors] = self::save([
            'hero' => ['eyebrow' => "  Nieuwe kop \r\n op twee regels  ", 'title' => "Regel 1\r\n\r\n\r\n*Regel* 2"],
            'onbekend' => ['x' => 1],
        ]);
        $this->assertSame([], $errors);
        $this->assertSame('Nieuwe kop op twee regels', $values['hero']['eyebrow']);
        $this->assertSame("Regel 1\n*Regel* 2", $values['hero']['title']);
        $this->assertCount(2, $values['hero']);
        $this->assertArrayNotHasKey('onbekend', $values);
    }

    #[TestDox('controle bij opslaan: verplichte velden, lengte en onbekende codes')]
    public function test_verplichte_velden_lengte_en_onbekende_codes(): void
    {
        $errors = self::save(['hero' => ['eyebrow' => '  ', 'intro' => str_repeat('x', 501), 'badgeText' => 'Vanaf {prijs}']])['errors'];
        $this->assertSame('Dit veld mag niet leeg zijn.', $errors['hero.eyebrow']);
        $this->assertMatchesRegularExpression('/Maximaal 500 tekens/', $errors['hero.intro']);
        $this->assertMatchesRegularExpression('/Onbekende automatische waarde \{prijs\}/', $errors['hero.badgeText']);
    }

    #[TestDox("controle bij opslaan: Enter wordt een spatie in tekst zonder alinea's, en blijft staan in tekst met alinea's")]
    public function test_enter_wordt_een_spatie_in_tekst_zonder_alineas(): void
    {
        $this->assertSame('Regel een regel twee', self::save(['hero' => ['intro' => "Regel een\nregel twee"]])['values']['hero']['intro']);
        $pt = Values::normalizePage(PersonalTraining::page(), ['leefstijl' => ['body' => "Alinea een\n\n\nAlinea twee"]], Vars::VAR_KEYS);
        $this->assertSame("Alinea een\n\nAlinea twee", $pt['values']['leefstijl']['body']);
    }

    #[TestDox('controle bij opslaan: titels hebben maximaal drie regels')]
    public function test_titels_hebben_maximaal_drie_regels(): void
    {
        $this->assertSame('Maximaal 3 regels.', self::save(['hero' => ['title' => "a\nb\nc\nd"]])['errors']['hero.title']);
    }

    #[TestDox('controle bij opslaan: opsommingen: één punt per regel, lege regels vallen weg')]
    public function test_opsommingen_een_punt_per_regel(): void
    {
        ['values' => $values, 'errors' => $errors] = self::save(['diensten' => ['list' => "Een\n\n Twee \n"]]);
        $this->assertSame([], $errors);
        $this->assertSame(['Een', 'Twee'], $values['diensten']['list']);
        $this->assertSame('Vul minstens één punt in.', self::save(['diensten' => ['list' => '']])['errors']['diensten.list']);
        $this->assertSame('Maximaal 8 punten.', self::save(['diensten' => ['list' => array_fill(0, 9, 'x')]])['errors']['diensten.list']);
    }

    #[TestDox('controle bij opslaan: vaste aantallen en lijsten met vragen')]
    public function test_vaste_aantallen_en_lijsten_met_vragen(): void
    {
        $this->assertMatchesRegularExpression('/altijd 4 items/', self::save(['hero' => ['stats' => [['value' => '1', 'label' => 'a']]]])['errors']['hero.stats']);
        $faq = Values::normalizePage(OnlineCoaching::page(), ['faq' => ['questions' => [['q' => 'Vraag?', 'a' => '']]]], Vars::VAR_KEYS);
        $this->assertSame('Dit veld mag niet leeg zijn.', $faq['errors']['faq.questions.0.a']);
        $none = Values::normalizePage(OnlineCoaching::page(), ['faq' => ['questions' => []]], Vars::VAR_KEYS);
        $this->assertSame('Voeg minstens één vraag toe.', $none['errors']['faq.questions']);
    }

    #[TestDox('controle bij opslaan: prijzen, links, webadressen en automatische waarden')]
    public function test_prijzen_links_webadressen_en_automatische_waarden(): void
    {
        $plans = self::defaults(Pakketten::page())['online']['plans'];
        foreach (['79,50', '1.050', '12,5'] as $i => $price) {
            $plans[$i]['price'] = $price;
        }
        $prices = Values::normalizePage(
            Pakketten::page(),
            ['adem' => ['price' => '€ 210', 'duration' => '{ademduur}'], 'online' => ['plans' => $plans]],
            Vars::VAR_KEYS,
        );
        $this->assertMatchesRegularExpression('/Vul een bedrag in/', $prices['errors']['adem.price']);
        $this->assertMatchesRegularExpression('/geen automatische waarden/', $prices['errors']['adem.duration']);
        $this->assertArrayNotHasKey('online.plans.0.price', $prices['errors']);
        $this->assertArrayNotHasKey('online.plans.1.price', $prices['errors']);
        $this->assertMatchesRegularExpression('/Vul een bedrag in/', $prices['errors']['online.plans.2.price']);
        $this->assertFalse($prices['values']['online']['plans'][0]['featured']);

        $shared = Values::normalizePage(
            Algemeen::page(),
            ['aankondiging' => ['href' => 'https://example.com', 'show' => 'on'], 'locatie' => ['instagramUrl' => 'javascript:alert(1)']],
            Vars::VAR_KEYS,
        );
        $this->assertSame('Kies een pagina uit de lijst.', $shared['errors']['aankondiging.href']);
        $this->assertTrue($shared['values']['aankondiging']['show']);
        $this->assertMatchesRegularExpression('/https:\/\//', $shared['errors']['locatie.instagramUrl']);
    }

    // --- opgeslagen teksten lezen ---

    #[TestDox('opgeslagen teksten lezen: valt terug op de standaard bij een verkeerde vorm')]
    public function test_valt_terug_op_de_standaard_bij_een_verkeerde_vorm(): void
    {
        $hero = Home::page()['sections']['hero']['fields'];
        $list = Home::page()['sections']['diensten']['fields']['list'];
        $this->assertSame($hero['title']['default'], Values::coerceStored($hero['title'], 12));
        $this->assertSame($list['default'], Values::coerceStored($list, ['a', 1]));
        $this->assertSame($hero['stats']['default'], Values::coerceStored($hero['stats'], [['value' => '1', 'label' => 'a']]));
        $this->assertTrue(Values::coerceStored(Algemeen::page()['sections']['aankondiging']['fields']['show'], 'ja'));
    }

    #[TestDox('opgeslagen teksten lezen: vult ontbrekende onderdelen van een item aan')]
    public function test_vult_ontbrekende_onderdelen_van_een_item_aan(): void
    {
        $faq = OnlineCoaching::page()['sections']['faq']['fields']['questions'];
        $stored = Values::coerceStored($faq, [['q' => 'Eigen vraag'], ['q' => 'Nog een', 'a' => 'Antwoord'], ['q' => 3, 'a' => 'x']]);
        $this->assertCount(3, $stored);
        $this->assertSame('Eigen vraag', $stored[0]['q']);
        $this->assertSame($faq['default'][0]['a'], $stored[0]['a']);
        $this->assertSame($faq['default'][2]['q'], $stored[2]['q']);
    }

    // --- Alleen in PHP ---

    #[TestDox('opgeslagen teksten lezen: resolvePage leest JSON en valt terug op de standaardtekst')]
    public function test_resolve_page(): void
    {
        $page = Home::page();
        $defaults = self::defaults($page);
        $out = Values::resolvePage($page, [
            'home.hero.title' => json_encode('Eigen *titel*'),
            'home.hero.intro' => 'geen json',
            'home.diensten.list' => '{}',
            'home.aanbod.onlinePoints' => '["a","b"]',
            'algemeen.aankondiging.text' => '"andere pagina"',
        ]);
        $this->assertSame(array_keys($defaults), array_keys($out));
        $this->assertSame('Eigen *titel*', $out['hero']['title']);
        $this->assertSame($defaults['hero']['intro'], $out['hero']['intro']);
        $this->assertSame($defaults['diensten']['list'], $out['diensten']['list']);
        $this->assertSame(['a', 'b'], $out['aanbod']['onlinePoints']);
        $this->assertSame($defaults, Values::resolvePage($page, []));
    }

    #[TestDox('sameValue: zelfde inhoud, ongeacht de volgorde van sleutels')]
    public function test_same_value(): void
    {
        $this->assertTrue(Values::sameValue(['a' => 1, 'b' => [1, ['x' => 1, 'y' => 2]]], ['b' => [1, ['y' => 2, 'x' => 1]], 'a' => 1]));
        $this->assertFalse(Values::sameValue([1, 2], [2, 1]));
        $this->assertFalse(Values::sameValue('1', 1));
        $this->assertTrue(Values::sameValue(1, 1.0));
        $this->assertFalse(Values::sameValue(['a' => ''], ['a' => null]));
    }

    #[TestDox('register: find, get en de volgorde van het menu')]
    public function test_register(): void
    {
        $this->assertSame('home', Registry::find('home')['slug']);
        $this->assertSame('Prijzen en pakketten', Registry::find('pakketten')['title']);
        $this->assertNull(Registry::find('bestaat-niet'));
        $this->assertSame(
            ['home', 'online-coaching', 'personal-training', 'ademcoaching', 'voedingscoaching', 'tarieven', 'over-steyn', 'vriend-uitnodigen', 'contact', 'privacy'],
            array_column(Registry::sitePages(), 'slug'),
        );
        $this->assertSame(['algemeen', 'pakketten'], array_column(Registry::sharedPages(), 'slug'));
        foreach ([...Registry::SITE_PAGES, ...Registry::SHARED_PAGES] as $class) {
            $this->assertSame($class::SLUG, $class::page()['slug']);
        }
        $this->expectException(InvalidArgumentException::class);
        Registry::get('bestaat-niet');
    }

    #[TestDox('velden: opties komen na kind, label en default; een titel is een regel met accent')]
    public function test_velden(): void
    {
        $this->assertSame(
            ['kind' => 'line', 'label' => 'Titel', 'default' => 'x', 'rich' => true, 'max' => 80],
            Fields::title('Titel', 'x', ['max' => 80]),
        );
        $this->assertSame(['title' => 'T', 'hint' => null, 'fields' => []], Fields::section('T', []));
        $this->assertSame('home.hero.title', Fields::fieldKey('home', 'hero', 'title'));
        $this->assertSame(Vars::VAR_KEYS, array_column(Vars::VARS, 'key'));
    }

    #[TestDox('JavaScript-gedrag: lengte, witruimte en getallen')]
    public function test_javascript_gedrag(): void
    {
        $this->assertSame(2, Js::length("\u{1F4AA}"));
        $this->assertSame(3, Js::length('één'));
        $this->assertSame("a\u{A0}b", Js::trim("\u{FEFF}\u{A0} a\u{A0}b\u{2028}\t"));
        $this->assertSame("a\0", Js::trim("a\0"));
        $this->assertSame('0.13', Js::toFixed(0.125, 2));
        $this->assertSame('100.00', Js::toFixed(99.999, 2));
        $this->assertSame('-0.00', Js::toFixed(-0.001, 2));
        $this->assertSame('1e+21', Js::numberToString(1e21));
        $this->assertSame('123456789012345680000', Js::numberToString(123456789012345680000.0));
        $this->assertSame('1e-7', Js::numberToString(1e-7));
        $this->assertSame('0.000001', Js::numberToString(1e-6));
        $this->assertSame('-12.5', Js::numberToString(-12.5));
        $this->assertSame(0.0, Js::toNumber(' '));
        $this->assertNan(Js::toNumber('12abc'));
        $this->assertSame("\u{FFFD}a", Js::utf8("\xC3a"));
    }

    // --- Vergelijking met de TypeScript-versie (tests/fixtures) ---

    #[TestDox("TypeScript: zelfde pagina's in dezelfde volgorde")]
    public function test_zelfde_paginas_als_typescript(): void
    {
        $this->assertSame(array_keys(self::fixture('content-defaults.json')), array_column(Registry::all(), 'slug'));
        $this->assertSame(array_column(self::fixture('content-reference.json')['pages'], 'slug'), array_column(Registry::all(), 'slug'));
    }

    #[DataProvider('pages')]
    #[TestDox('TypeScript: standaardteksten van $slug zijn precies gelijk')]
    public function test_standaardteksten_gelijk_aan_typescript(string $slug): void
    {
        $fixture = self::fixture('content-defaults.json');
        $this->assertArrayHasKey($slug, $fixture);
        // assertSame op arrays: zelfde sleutels, in dezelfde volgorde, met precies dezelfde waarden.
        $this->assertSame($fixture[$slug], self::defaults(Registry::get($slug)));
    }

    #[DataProvider('pages')]
    #[TestDox('TypeScript: paginadefinitie van $slug is precies gelijk (labels, uitleg, maxima, volgorde)')]
    public function test_paginadefinitie_gelijk_aan_typescript(string $slug): void
    {
        $expected = array_column(self::fixture('content-reference.json')['pages'], null, 'slug')[$slug];
        $page = Registry::get($slug);
        // Een onderdeel zonder uitleg heeft in TypeScript geen 'hint' (undefined); in PHP is die null.
        foreach ($page['sections'] as $s => $section) {
            if ($section['hint'] === null) {
                unset($page['sections'][$s]['hint']);
            }
        }
        $this->assertSame($expected, $page);
    }

    #[TestDox('TypeScript: automatische waarden en links zijn gelijk')]
    public function test_vars_en_links_gelijk_aan_typescript(): void
    {
        $reference = self::fixture('content-reference.json');
        $this->assertSame($reference['vars'], Vars::VARS);
        $this->assertSame($reference['varKeys'], Vars::VAR_KEYS);
        $this->assertSame($reference['siteLinks'], Values::SITE_LINKS);
    }

    public static function normalizeCases(): iterable
    {
        foreach (self::fixture('content-reference.json')['cases']['normalize'] as $i => $case) {
            yield "{$i}: {$case['slug']}" => [$case['slug'], $case['input'], $case['output']];
        }
    }

    #[DataProvider('normalizeCases')]
    #[TestDox('TypeScript: normalizePage geeft dezelfde waarden en fouten ($slug)')]
    public function test_normalize_gelijk_aan_typescript(string $slug, mixed $input, array $expected): void
    {
        $this->assertSame($expected, Values::normalizePage(Registry::get($slug), $input, Vars::VAR_KEYS));
    }

    public static function resolveCases(): iterable
    {
        foreach (self::fixture('content-reference.json')['cases']['resolve'] as $i => $case) {
            yield "{$i}: {$case['slug']}" => [$case['slug'], $case['stored'], $case['output']];
        }
    }

    #[DataProvider('resolveCases')]
    #[TestDox('TypeScript: resolvePage geeft dezelfde teksten ($slug)')]
    public function test_resolve_gelijk_aan_typescript(string $slug, array $stored, array $expected): void
    {
        $this->assertSame($expected, Values::resolvePage(Registry::get($slug), $stored));
    }

    public static function varsCases(): iterable
    {
        foreach (self::fixture('content-reference.json')['cases']['vars'] as $case) {
            yield implode(' | ', $case['prices']) => [$case['prices'], $case['output']];
        }
    }

    #[DataProvider('varsCases')]
    #[TestDox('TypeScript: computeVars geeft dezelfde waarden')]
    public function test_compute_vars_gelijk_aan_typescript(array $prices, array $expected): void
    {
        $values = self::defaults(Pakketten::page());
        foreach ($prices as $i => $price) {
            $values['online']['plans'][$i]['price'] = $price;
        }
        $this->assertSame($expected, Vars::computeVars(self::defaults(Algemeen::page()), $values));
    }

    #[TestDox('TypeScript: opmaakfuncties geven hetzelfde')]
    public function test_opmaak_gelijk_aan_typescript(): void
    {
        $markup = self::fixture('content-reference.json')['cases']['markup'];
        $vars = self::defaultVars();
        foreach ($markup as $function => $cases) {
            foreach ($cases as $case) {
                $actual = match ($function) {
                    'parseRich' => Markup::parseRich($case['input']),
                    'plainRich' => Markup::plainRich($case['input']),
                    'paragraphs' => Markup::paragraphs($case['input']),
                    'placeholdersIn' => Markup::placeholdersIn($case['input']),
                    'fillText' => Markup::fillText($case['input'], $vars),
                };
                $this->assertSame($case['output'], $actual, "{$function}(".json_encode($case['input'], JSON_UNESCAPED_UNICODE).')');
            }
        }
    }
}
