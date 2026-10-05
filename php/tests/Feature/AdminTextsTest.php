<?php

namespace Tests\Feature;

use App\Content\Fields;
use App\Content\Registry;
use App\Models\SiteText;
use App\Models\User;
use App\Site\Texts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Beheer: website-teksten opslaan, controleren en terugzetten naar de standaardtekst. */
class AdminTextsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['first_name' => 'Steyn']);
    }

    /** Zoals het bewerkscherm: alle teksten van de pagina als JSON, met de gegeven wijzigingen. */
    private function save(string $slug, array $changes, ?string $raw = null)
    {
        $values = Fields::pageDefaults(Registry::get($slug));
        foreach ($changes as $path => $value) {
            [$s, $f] = explode('.', $path);
            $values[$s][$f] = $value;
        }

        return $this->actingAs($this->admin)->post("/admin/teksten/{$slug}", [
            'page' => $slug,
            'values' => $raw ?? json_encode($values, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function test_alleen_gewijzigde_teksten_worden_opgeslagen(): void
    {
        $this->save('home', ['hero.eyebrow' => 'Coaching in Amsterdam', 'hero.title' => "Sterk *lijf*.\nGezond leven."])
            ->assertRedirect('/admin/teksten/home')
            ->assertSessionHas('texts_state', ['success' => 'Opgeslagen. 2 teksten zijn direct bijgewerkt op de website.']);
        $this->assertSame(['home.hero.eyebrow', 'home.hero.title'], SiteText::query()->orderBy('key')->pluck('key')->all());
        $row = SiteText::query()->find('home.hero.title');
        $this->assertSame('"Sterk *lijf*.\nGezond leven."', $row->value);
        $this->assertSame($this->admin->id, $row->updated_by_id);
        $this->assertNotNull($row->updated_at);

        // De site toont de nieuwe teksten direct.
        $this->get('/')->assertSee('Coaching in Amsterdam')->assertSee('Sterk <span class="text-accent">lijf</span>.<br>Gezond leven.', false);

        // Nog een keer hetzelfde: niets veranderd.
        $this->save('home', ['hero.eyebrow' => 'Coaching in Amsterdam', 'hero.title' => "Sterk *lijf*.\nGezond leven."])
            ->assertSessionHas('texts_state', ['success' => 'Opgeslagen. Er was niets veranderd.']);

        // Terug naar de standaardtekst: de aangepaste versie verdwijnt.
        $this->save('home', ['hero.eyebrow' => 'Coaching in Amsterdam'])
            ->assertSessionHas('texts_state', ['success' => 'Opgeslagen. 1 tekst is direct bijgewerkt op de website.']);
        $this->assertSame(['home.hero.eyebrow'], SiteText::query()->pluck('key')->all());

        $this->actingAs($this->admin)->get('/admin/teksten')->assertSee('1 tekst aangepast')->assertSee('door Steyn');
        $this->actingAs($this->admin)->get('/admin/teksten/home')->assertSee('Laatst opgeslagen op ')
            ->assertSee('title="Opgeslagen op ', false);
    }

    public function test_fouten_worden_niet_opgeslagen_en_komen_terug(): void
    {
        $this->save('home', ['hero.eyebrow' => '', 'hero.badgeText' => 'Vanaf € {prijs} per maand'])
            ->assertRedirect('/admin/teksten/home')
            ->assertSessionHas('texts_state', fn (array $state) => $state['error'] === 'Er zijn 2 velden die nog niet kloppen. Ze staan hieronder in rood.'
                && $state['fieldErrors']['hero.eyebrow'] === 'Dit veld mag niet leeg zijn.'
                && str_starts_with($state['fieldErrors']['hero.badgeText'], 'Onbekende automatische waarde {prijs}')
                && $state['submitted']['hero']['badgeText'] === 'Vanaf € {prijs} per maand');
        $this->assertSame(0, SiteText::query()->count());

        // Het bewerkscherm krijgt de ingestuurde teksten en de fouten mee.
        $this->actingAs($this->admin)->get('/admin/teksten/home')->assertOk()
            ->assertSee('Vanaf € {prijs} per maand', false)
            ->assertSee('Dit veld mag niet leeg zijn.');

        $this->save('home', [], '{kapot')->assertSessionHas('texts_state', ['error' => 'De teksten konden niet worden gelezen. Laad de pagina opnieuw en probeer het nog eens.']);
        $this->save('pakketten', ['adem.price' => '€ 225'])
            ->assertSessionHas('texts_state', fn (array $state) => $state['fieldErrors']['adem.price'] === 'Vul een bedrag in zoals 120 of 79,50, zonder €-teken.');
        $this->actingAs($this->admin)->post('/admin/teksten/onbekend', ['values' => '{}'])->assertSessionHas('texts_state', ['error' => 'Deze pagina bestaat niet (meer).']);
    }

    public function test_aan_uit_lijsten_en_items(): void
    {
        $plans = Fields::pageDefaults(Registry::get('pakketten'))['online']['plans'];
        $plans[1]['name'] = 'Plus';
        $plans[0]['price'] = '69,50';
        $this->save('algemeen', ['aankondiging.show' => false])->assertSessionHas('texts_state', ['success' => 'Opgeslagen. 1 tekst is direct bijgewerkt op de website.']);
        $this->assertSame('false', SiteText::query()->find('algemeen.aankondiging.show')->value);
        $this->assertNull(Texts::announcement());

        $this->save('pakketten', ['online.plans' => $plans, 'adem.features' => ['Eén', '', '  Twee  ']])
            ->assertSessionHas('texts_state', ['success' => 'Opgeslagen. 2 teksten zijn direct bijgewerkt op de website.']);
        $this->assertSame('["Eén","Twee"]', SiteText::query()->find('pakketten.adem.features')->value);
        $this->assertSame('Plus', Texts::onlinePlanName('online-pro'));
        $this->assertSame('69,50', Texts::vars()['online-vanaf']);
    }

    public function test_bewerkscherm_bevat_velden_met_vaste_ids(): void
    {
        $this->actingAs($this->admin)->get('/admin/teksten/home')->assertOk()
            ->assertSee('id="veld-hero-eyebrow"', false)
            ->assertSee('id="sectie-hero"', false)
            ->assertSee('aria-label="Titel: standaardtekst terugzetten"', false)
            ->assertSee('Bekijk op de site');
        $this->actingAs($this->admin)->get('/admin/teksten/online-coaching')->assertOk()
            ->assertSee('Vraag toevoegen')
            ->assertSee('&quot;veld-faq-questions-&quot; + i + &quot;-q&quot;', false);
        // Gedeelde teksten hebben geen eigen pagina op de site.
        $this->actingAs($this->admin)->get('/admin/teksten/algemeen')->assertOk()->assertDontSee('Bekijk op de site');
    }
}
