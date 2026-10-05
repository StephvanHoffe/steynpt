<?php

namespace Tests\Feature;

use App\Models\SiteText;
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
            ->assertSee('Contact en gratis kennismaking bij Gymbase in Amsterdam · SteynPT')
            ->assertSee('Op 3 minuten lopen van het Vondelpark')
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
}
