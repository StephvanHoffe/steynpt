<?php

namespace Tests\Feature;

use App\Content\English;
use App\Content\Fields;
use App\Content\Registry;
use App\Models\ContactRequest;
use App\Models\SiteText;
use App\Models\User;
use App\Site\Locale;
use App\Site\Texts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** De Engelse website: adressen onder /en, taalknop, hreflang, sitemap, contactformulier en Engels in het beheer. */
class EnglishSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://www.steynpt.nl']);
    }

    public function test_elke_openbare_pagina_heeft_een_engelse_versie_met_hreflang(): void
    {
        foreach (Locale::PATHS as $dutch => $english) {
            $html = $this->get($english)->assertOk()->getContent();
            $this->assertStringContainsString('<html lang="en"', $html, $english);
            $this->assertStringContainsString('<link rel="canonical" href="https://www.steynpt.nl'.($english).'">', $html);
            $this->assertStringContainsString('hreflang="nl" href="https://www.steynpt.nl'.$dutch.'"', $html);
            $this->assertStringContainsString('hreflang="en" href="https://www.steynpt.nl'.$english.'"', $html);
            $this->assertStringContainsString('hreflang="x-default" href="https://www.steynpt.nl'.$dutch.'"', $html);
            // De taalknop wijst naar dezelfde pagina in het Nederlands.
            $this->assertStringContainsString('href="'.$dutch.'" hreflang="nl"', $html);

            $nl = $this->get($dutch)->assertOk()->getContent();
            $this->assertStringContainsString('<html lang="nl"', $nl);
            $this->assertStringContainsString('href="'.$english.'" hreflang="en"', $nl);
        }
    }

    public function test_engelse_teksten_links_en_prijzen(): void
    {
        $this->get('/en/personal-training')->assertOk()
            ->assertSee('English-speaking personal trainer in Amsterdam Oud-West')
            ->assertSee('Do you train in English?')
            ->assertSee('Introduction package')
            ->assertSee('1,050')
            ->assertSee('href="/en/contact?onderwerp=personal-training"', false)
            ->assertDontSee('Introductiepakket')
            ->assertDontSee('Vraag dit pakket aan');

        $this->get('/en')->assertOk()
            ->assertSee('Online coaching from €79 per month')
            ->assertSee('href="/en/online-coaching"', false)
            ->assertSee('href="/en/breathwork"', false)
            ->assertSee('Book an appointment');

        // Het adres en de prijzen komen uit het Nederlands: één keer aanpassen is genoeg.
        SiteText::query()->create(['key' => 'pakketten.adem.price', 'value' => json_encode('225'), 'updated_at' => now()]);
        SiteText::query()->create(['key' => 'algemeen.locatie.street', 'value' => json_encode('Kinkerstraat 1'), 'updated_at' => now()]);
        Texts::flush();
        $this->get('/en/breathwork')->assertOk()->assertSee('for €225')->assertSee('Kinkerstraat 1');
    }

    public function test_engelse_gestructureerde_gegevens(): void
    {
        $html = $this->get('/en/pricing')->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = collect(json_decode($m[1], true)['@graph']);
        $this->assertSame('en', $graph->last()['inLanguage']);
        $offers = array_column(array_column($graph->firstWhere('@type', 'LocalBusiness')['hasOfferCatalog']['itemListElement'], 'itemOffered'), 'name');
        $this->assertContains('Personal training – Single session', $offers);
        $this->assertContains('https://www.steynpt.nl/en/breathwork', array_column(array_column($graph->firstWhere('@type', 'LocalBusiness')['hasOfferCatalog']['itemListElement'], 'itemOffered'), 'url'));
    }

    public function test_sitemap_met_beide_talen(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertSame(20, substr_count($xml, '<url>'));
        $this->assertStringContainsString('<loc>https://www.steynpt.nl/en/nutrition-coaching</loc>', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="en" href="https://www.steynpt.nl/en/pricing"/>', $xml);
    }

    public function test_contactformulier_in_het_engels(): void
    {
        $this->from('/en/contact')->post('/en/contact', ['name' => '', 'email' => 'x'])
            ->assertRedirect('/en/contact')
            ->assertSessionHasErrors(['name' => 'Please enter your name', 'email' => 'Please enter a valid email address']);

        $this->post('/en/contact', ['name' => 'Emma Smith', 'email' => 'emma@example.com', 'interest' => 'personal-training'])
            ->assertRedirect('/en/contact');
        $this->assertSame(1, ContactRequest::query()->count());
        $this->get('/en/contact')->assertSee('Thanks for your request!');
    }

    public function test_engelse_404_en_taalcookie(): void
    {
        $this->get('/en/bestaat-niet')->assertNotFound()->assertSee('Page not found')->assertSee('href="/en"', false);
        $this->get('/en/pricing')->assertCookie(Locale::COOKIE, 'en', false);
        // Inloggen en registreren volgen de gekozen taal.
        $this->withUnencryptedCookie(Locale::COOKIE, 'en')->get('/inloggen')->assertSee('<html lang="en"', false);
    }

    public function test_taalknop_op_pagina_zonder_engels_adres(): void
    {
        $user = User::factory()->create(['locale' => 'nl']);
        $this->actingAs($user)->get('/taal/en?terug=/account/profiel')
            ->assertRedirect('/account/profiel')
            ->assertCookie(Locale::COOKIE, 'en', false);
        $this->assertSame('en', $user->fresh()->locale);

        // Een openbare pagina: naar de Engelse versie. Een ander domein: naar de homepage.
        $this->get('/taal/en?terug=/tarieven%23personal-training')->assertRedirect('/en/pricing#personal-training');
        $this->get('/taal/nl?terug=/en/pricing')->assertRedirect('/tarieven');
        $this->get('/taal/en?terug=//evil.example')->assertRedirect('/en');
        $this->get('/taal/de')->assertNotFound();
    }

    public function test_beheer_is_altijd_nederlands(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'en']);
        $this->actingAs($admin)->withUnencryptedCookie(Locale::COOKIE, 'en')->get('/admin/teksten')
            ->assertOk()->assertSee('<html lang="nl"', false)->assertSee('Engelse versie');
    }

    public function test_engelse_teksten_aanpassen_in_het_beheer(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Texts::englishPage('home');
        $this->assertSame('en-home', $page['slug']);
        $this->actingAs($admin)->get('/admin/teksten/en-home')->assertOk()
            ->assertSee('Homepage (Engels)')
            ->assertSee('Stronger body.');

        $values = Fields::pageDefaults($page);
        $values['hero']['eyebrow'] = 'Personal trainer in Amsterdam';
        $this->actingAs($admin)->post('/admin/teksten/en-home', ['page' => 'en-home', 'values' => json_encode($values, JSON_UNESCAPED_UNICODE)])
            ->assertRedirect('/admin/teksten/en-home')
            ->assertSessionHas('texts_state', ['success' => 'Opgeslagen. 1 tekst is direct bijgewerkt op de website.']);
        $this->assertSame(['en-home.hero.eyebrow'], SiteText::query()->pluck('key')->all());

        $this->get('/en')->assertSee('Personal trainer in Amsterdam');
        $this->get('/')->assertSee('Personal training · Amsterdam Oud-West &amp; online', false);

        // Pakketten: in het Engels geen prijzen (die komen uit het Nederlands), wel een vast aantal.
        $packages = Texts::englishPage('pakketten');
        $this->assertArrayNotHasKey('price', $packages['sections']['pt']['fields']['cards']['fields']);
        $this->assertTrue($packages['sections']['pt']['fields']['cards']['fixed']);
        $this->assertArrayNotHasKey('street', $packages['sections']['pt']['fields']);
        $this->assertArrayNotHasKey('street', Texts::englishPage('algemeen')['sections']['locatie']['fields']);
    }

    public function test_elke_vertaalbare_tekst_heeft_een_engelse_standaardtekst(): void
    {
        foreach (Registry::all() as $page) {
            $english = English::page($page, Fields::pageDefaults($page));
            foreach ($english['sections'] as $s => $section) {
                foreach ($section['fields'] as $f => $field) {
                    $this->assertArrayHasKey($f, English::PAGES[$page['slug']][$s] ?? [], "Engelse tekst ontbreekt: {$page['slug']}.{$s}.{$f}");
                    $this->assertSame(gettype($field['default']), gettype(English::PAGES[$page['slug']][$s][$f]), "{$page['slug']}.{$s}.{$f}");
                }
            }
        }
    }
}
