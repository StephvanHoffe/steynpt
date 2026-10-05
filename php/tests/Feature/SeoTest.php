<?php

namespace Tests\Feature;

use App\Models\SiteText;
use App\Site\Texts;
use App\Support\Agenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Zoekmachines: canonical, gestructureerde gegevens, sitemap, noindex en de oude adressen van de WordPress-site. */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://www.steynpt.nl']);
    }

    private function jsonLd(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m), 'geen JSON-LD gevonden');

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_canonical_zonder_parameters_en_lokaal_bedrijf(): void
    {
        $html = $this->get('/contact?onderwerp=ademcoaching')->assertOk()
            ->assertSee('<link rel="canonical" href="https://www.steynpt.nl/contact">', false)
            ->assertSee('Contact en gratis kennismaking bij Gymbase Amsterdam · SteynPT')
            ->assertSee('Op drie minuten lopen van het Vondelpark')
            ->getContent();
        $graph = collect($this->jsonLd($html)['@graph']);
        $business = $graph->firstWhere('@type', 'LocalBusiness');
        $this->assertSame('Overtoom 371-w', $business['address']['streetAddress']);
        $this->assertSame('1054 JN', $business['address']['postalCode']);
        $this->assertSame('Amsterdam', $business['address']['addressLocality']);
        $this->assertEqualsWithDelta(52.3595, $business['geo']['latitude'], 0.001);
        $this->assertContains('Oud-West, Amsterdam', array_column($business['areaServed'], 'name'));
        $this->assertSame('Steyn van Leeuwen', $graph->firstWhere('@type', 'Person')['name']);
        $prices = collect($business['hasOfferCatalog']['itemListElement'])->mapWithKeys(fn ($o) => [$o['itemOffered']['name'] => $o['priceSpecification']['price'] ?? null]);
        $this->assertSame(79, $prices['Online coaching Start']);
    }

    public function test_veelgestelde_vragen_als_faqpage(): void
    {
        $graph = collect($this->jsonLd($this->get('/ademcoaching')->assertOk()->getContent())['@graph']);
        $page = $graph->first(fn ($n) => is_array($n['@type']) && in_array('FAQPage', $n['@type'], true));
        $this->assertNotNull($page);
        $this->assertGreaterThan(2, count($page['mainEntity']));
        $this->assertSame('Question', $page['mainEntity'][0]['@type']);
    }

    public function test_aangepast_adres_zonder_vaste_coordinaten_en_veilig_ge_escaped(): void
    {
        SiteText::query()->create(['key' => 'algemeen.locatie.street', 'value' => json_encode('Kinkerstraat 1</script>'), 'updated_at' => now()]);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Kinkerstraat 1</script>', $html);
        $business = collect($this->jsonLd($html)['@graph'])->firstWhere('@type', 'LocalBusiness');
        $this->assertSame('Kinkerstraat 1</script>', $business['address']['streetAddress']);
        $this->assertArrayNotHasKey('geo', $business);
    }

    public function test_noindex_paginas_zonder_canonical(): void
    {
        foreach (['/inloggen', '/registreren'] as $path) {
            $this->get($path)->assertOk()->assertSee('<meta name="robots" content="noindex">', false)->assertDontSee('rel="canonical"', false)->assertDontSee('application/ld+json', false);
        }
    }

    public function test_sitemap_en_robots(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>https://www.steynpt.nl/</loc>', $xml);
        $this->assertStringContainsString('<loc>https://www.steynpt.nl/personal-training</loc>', $xml);
        $this->assertStringNotContainsString('/registreren', $xml);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://www.steynpt.nl/sitemap.xml');
    }

    public function test_oude_adressen_van_de_wordpress_site(): void
    {
        $this->get('/over-steynpt')->assertRedirect('/over-steyn')->assertStatus(301);
        $this->get('/vraag-een-gratis-kennismaking-aan')->assertRedirect('/contact');
        $this->get('/10-weken-programma')->assertRedirect('/tarieven');
        $this->get('/small-group-training')->assertRedirect('/personal-training');
        foreach (['/shop', '/product/protein', '/events/cardio-burn', '/portfolio/two-columns', '/blog', '/home-1', '/wp-sitemap.xml', '/wp-content/uploads/foto.jpg'] as $path) {
            $this->get($path)->assertStatus(410)->assertSee('Deze pagina bestaat niet meer');
        }
        $this->get('/shopping')->assertNotFound();
    }

    public function test_foutpaginas_in_het_nederlands(): void
    {
        $this->assertStringContainsString('Te veel pogingen', view('errors.429')->render());
        $this->assertStringContainsString('Er ging iets mis', view('errors.500')->render());
        $this->assertStringContainsString('Even onderhoud', view('errors.503')->render());

        // 500 en 503 tonen geen kop en footer: die lezen uit de database, en die kan juist de oorzaak zijn.
        $this->assertStringNotContainsString('<footer', view('errors.500')->render());
    }

    public function test_agenda_gebruikt_het_adres_uit_website_teksten(): void
    {
        SiteText::query()->create(['key' => 'algemeen.locatie.street', 'value' => json_encode('Kinkerstraat 1'), 'updated_at' => now()]);

        $this->assertSame('Kinkerstraat 1, 1054 JN Amsterdam', Texts::agendaAddress(Agenda::getAgendaLocation('gymbase')));
        $this->assertSame('Locatie in overleg', Texts::agendaAddress(Agenda::getAgendaLocation('op-locatie')));
    }

    public function test_zoekterm_in_de_h1_en_veelgestelde_vragen_bij_personal_training_en_voeding(): void
    {
        foreach (['/personal-training' => 'Personal training in Amsterdam Oud-West', '/voedingscoaching' => 'Voedingscoach in Amsterdam Oud-West'] as $path => $eyebrow) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $m), "precies één H1 op {$path}");
            $this->assertStringContainsString($eyebrow, strip_tags($m[1]));
            $page = collect($this->jsonLd($html)['@graph'])->last();
            $this->assertContains('FAQPage', (array) $page['@type']);
            $this->assertGreaterThanOrEqual(5, count($page['mainEntity']));
        }
    }

    public function test_telefoon_en_e_mail_alleen_als_ze_zijn_ingevuld(): void
    {
        $html = $this->get('/contact')->assertOk()->assertDontSee('tel:', false)->getContent();
        $this->assertArrayNotHasKey('telephone', collect($this->jsonLd($html)['@graph'])->firstWhere('@type', 'LocalBusiness'));

        SiteText::query()->create(['key' => 'algemeen.locatie.phone', 'value' => json_encode('06 12 34 56 78'), 'updated_at' => now()]);
        SiteText::query()->create(['key' => 'algemeen.locatie.email', 'value' => json_encode('info@steynpt.nl'), 'updated_at' => now()]);
        Texts::flush(); // in de test loopt alles in één proces; op de server is elk verzoek nieuw
        $html = $this->get('/contact')->assertOk()
            ->assertSee('href="tel:0612345678"', false)
            ->assertSee('href="mailto:info@steynpt.nl"', false)
            ->getContent();
        $business = collect($this->jsonLd($html)['@graph'])->firstWhere('@type', 'LocalBusiness');
        $this->assertSame('06 12 34 56 78', $business['telephone']);
        $this->assertSame('info@steynpt.nl', $business['email']);
    }

    public function test_sitemap_met_datum_van_de_laatste_wijziging(): void
    {
        SiteText::query()->create(['key' => 'privacy.intro.title', 'value' => json_encode('Privacy'), 'updated_at' => '2030-01-02 10:00:00']);
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertSame(10, substr_count($xml, '<lastmod>'));
        $this->assertStringContainsString("<loc>https://www.steynpt.nl/privacy</loc>\n<lastmod>2030-01-02</lastmod>", $xml);
        $this->assertStringNotContainsString("<loc>https://www.steynpt.nl/contact</loc>\n<lastmod>2030-01-02</lastmod>", $xml);
    }

    public function test_fotos_met_lichtere_webp_versies(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('type="image/webp"', false)
            ->assertSee('/images/steyn-glimlach.480w.webp 480w', false)
            ->assertSee('src="'.asset('images/steyn-glimlach.jpg').'"', false);
    }
}
