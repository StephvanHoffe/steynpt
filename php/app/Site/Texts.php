<?php

namespace App\Site;

use App\Content\Defaults;
use App\Content\English;
use App\Content\Markup;
use App\Content\Registry;
use App\Content\Values;
use App\Content\Vars;
use App\Models\SiteText;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * De teksten van de site zoals Steyn ze in het beheer heeft ingesteld (Website-teksten), met de standaardtekst
 * uit app/Content waar niets is aangepast. Eén keer per verzoek uit de database; lukt dat niet, dan toont de site
 * de standaardteksten in plaats van een foutmelding.
 *
 * In het Engels (App\Site\Locale) komen de teksten uit de Engelse versie (App\Content\English), met de prijzen,
 * links en het adres uit het Nederlands.
 */
final class Texts
{
    private ?array $stored = null;

    /** @var array<string, array<string, array>> [taal => [pagina => teksten]] */
    private array $raw = [];

    /** @var array<string, array<string, array>> */
    private array $filled = [];

    /** @var array<string, array<string, string>> */
    private array $vars = [];

    /** Eén exemplaar per verzoek (zie AppServiceProvider). */
    public static function store(): self
    {
        return app(self::class);
    }

    /** Teksten van een pagina zoals de bezoeker ze ziet (in de taal van de pagina), met de automatische waarden ingevuld. */
    public static function get(string $slug): array
    {
        return self::store()->filled($slug, Locale::current());
    }

    /** Teksten zoals ze in het beheer staan (zonder {codes} in te vullen), in het Nederlands. */
    public static function editable(string $slug): array
    {
        return self::store()->raw($slug, 'nl');
    }

    /** De Engelse paginadefinitie voor het beheer (zonder de velden die in beide talen gelijk zijn). */
    public static function englishPage(string $slug): array
    {
        $page = Registry::find($slug) ?? throw new RuntimeException("Onbekende pagina met teksten: {$slug}");

        return English::page($page, self::store()->raw($slug, 'nl'));
    }

    /** De Engelse teksten zoals ze in het beheer staan (alleen de vertaalde velden). */
    public static function editableEnglish(string $slug): array
    {
        return Values::resolvePage(self::englishPage($slug), self::store()->stored());
    }

    /** Teksten in een taal zonder {codes} in te vullen; in het Engels met de gedeelde velden uit het Nederlands. */
    public static function resolved(string $slug, string $locale): array
    {
        return self::store()->raw($slug, $locale);
    }

    /** Automatische waarden zoals {ademprijs}, in de gevraagde (of huidige) taal. */
    public static function vars(?string $locale = null): array
    {
        $locale ??= Locale::current();
        $store = self::store();
        if (! isset($store->vars[$locale])) {
            $vars = Vars::computeVars($store->raw('algemeen', $locale), $store->raw('pakketten', $locale));
            if ($locale === 'en') {
                // Bedragen in Engelse notatie: 59.50 in plaats van 59,50.
                $vars['ademprijs'] = Locale::price($vars['ademprijs'], 'en');
                $vars['online-vanaf'] = Locale::price($vars['online-vanaf'], 'en');
            }
            $store->vars[$locale] = $vars;
        }

        return $store->vars[$locale];
    }

    /** Na opslaan in het beheer: opnieuw laden. */
    public static function flush(): void
    {
        $store = self::store();
        $store->stored = null;
        $store->raw = [];
        $store->filled = [];
        $store->vars = [];
    }

    /** De balk bovenaan de site, of null als Steyn hem heeft uitgezet. */
    public static function announcement(): ?array
    {
        $a = self::get('algemeen')['aankondiging'];

        return $a['show'] ? ['label' => $a['label'], 'text' => $a['text'], 'href' => Locale::path($a['href'])] : null;
    }

    /** Online pakketten met vaste id's en de ingestelde naam, prijs en inhoud. */
    public static function onlinePlans(): array
    {
        $plans = self::get('pakketten')['online']['plans'];

        return array_map(fn (array $base, array $plan) => array_merge($base, $plan), Defaults::ONLINE_PLANS, $plans);
    }

    public static function onlinePlanName(?string $id): ?string
    {
        foreach (self::onlinePlans() as $plan) {
            if ($plan['id'] === $id) {
                return $plan['name'];
            }
        }

        return null;
    }

    /** Prijskaarten personal training, in de vorm die het onderdeel price-card verwacht. */
    public static function ptPrices(): array
    {
        return array_map(fn (array $card) => [
            'label' => $card['label'],
            'name' => $card['name'],
            'price' => $card['price'],
            'unit' => $card['unit'] !== '' ? $card['unit'] : null,
            'features' => $card['features'],
            'note' => $card['note'] !== '' ? $card['note'] : null,
            'featured' => $card['featured'],
        ], self::get('pakketten')['pt']['cards']);
    }

    /** Adres van een agendalocatie; voor Gymbase het adres uit Website-teksten (Op elke pagina › Locatie). */
    public static function agendaAddress(array $location): string
    {
        if ($location['id'] !== 'gymbase') {
            return $location['address'];
        }
        $locatie = self::get('algemeen')['locatie'];

        return "{$locatie['street']}, {$locatie['city']}";
    }

    public static function breathworkPrice(): array
    {
        $adem = self::get('pakketten')['adem'];

        return ['name' => $adem['name'], 'label' => $adem['label'], 'price' => $adem['price'], 'unit' => $adem['unit'], 'features' => $adem['features'], 'note' => null, 'featured' => false];
    }

    private function filled(string $slug, string $locale): array
    {
        return $this->filled[$locale][$slug] ??= Markup::fillVars($this->raw($slug, $locale), self::vars($locale));
    }

    private function raw(string $slug, string $locale): array
    {
        if (isset($this->raw[$locale][$slug])) {
            return $this->raw[$locale][$slug];
        }
        $page = Registry::find($slug) ?? throw new RuntimeException("Onbekende pagina met teksten: {$slug}");
        $nl = $this->raw['nl'][$slug] ??= Values::resolvePage($page, $this->stored());
        if ($locale !== 'en') {
            return $nl;
        }
        $en = Values::resolvePage(English::page($page, $nl), $this->stored());

        return $this->raw['en'][$slug] = English::merge($page, $nl, $en);
    }

    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }
        try {
            return $this->stored = SiteText::query()->pluck('value', 'key')->all();
        } catch (Throwable $e) {
            Log::error('Website-teksten konden niet worden geladen; de standaardteksten worden getoond.', ['error' => $e->getMessage()]);

            return $this->stored = [];
        }
    }
}
