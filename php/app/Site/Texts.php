<?php

namespace App\Site;

use App\Content\Defaults;
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
 */
final class Texts
{
    private ?array $stored = null;

    private array $raw = [];

    private array $filled = [];

    private ?array $vars = null;

    /** Eén exemplaar per verzoek (zie AppServiceProvider). */
    public static function store(): self
    {
        return app(self::class);
    }

    /** Teksten van een pagina zoals de bezoeker ze ziet: aangepast of standaard, met de automatische waarden ingevuld. */
    public static function get(string $slug): array
    {
        return self::store()->filled($slug);
    }

    /** Teksten zoals ze in het beheer staan (zonder {codes} in te vullen). */
    public static function editable(string $slug): array
    {
        return self::store()->raw($slug);
    }

    /** Automatische waarden zoals {ademprijs}. */
    public static function vars(): array
    {
        $store = self::store();

        return $store->vars ??= Vars::computeVars($store->raw('algemeen'), $store->raw('pakketten'));
    }

    /** Na opslaan in het beheer: opnieuw laden. */
    public static function flush(): void
    {
        $store = self::store();
        $store->stored = null;
        $store->raw = [];
        $store->filled = [];
        $store->vars = null;
    }

    /** De balk bovenaan de site, of null als Steyn hem heeft uitgezet. */
    public static function announcement(): ?array
    {
        $a = self::get('algemeen')['aankondiging'];

        return $a['show'] ? ['label' => $a['label'], 'text' => $a['text'], 'href' => $a['href']] : null;
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

    private function filled(string $slug): array
    {
        return $this->filled[$slug] ??= Markup::fillVars($this->raw($slug), self::vars());
    }

    private function raw(string $slug): array
    {
        if (isset($this->raw[$slug])) {
            return $this->raw[$slug];
        }
        $page = Registry::find($slug) ?? throw new RuntimeException("Onbekende pagina met teksten: {$slug}");

        return $this->raw[$slug] = Values::resolvePage($page, $this->stored());
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
