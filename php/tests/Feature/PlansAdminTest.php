<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Support\Intake as IntakeRules;
use App\Support\Plans\Pipeline;
use App\View\PlanLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Beheer van trainings- en voedingsschema's: overzicht, nieuw schema, opslaan, publiceren, inplannen en versies. */
class PlansAdminTest extends TestCase
{
    use PlansTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['steynpt.ai_mock' => true, 'steynpt.anthropic_api_key' => null]);
        Http::preventStrayRequests();
    }

    /** Opslaan zoals de editor het verstuurt. */
    private function save(Plan $plan, array $form = [], ?array $content = null): TestResponse
    {
        $href = ($plan->type === 'training' ? '/admin/trainingsschemas/' : '/admin/voedingsschemas/').$plan->id;

        return $this->from($href)->post($href, array_merge([
            'planId' => $plan->id,
            'content' => json_encode($content ?? $plan->content),
            'intent' => 'opslaan',
            'renewOn' => $this->day(42),
        ], $form));
    }

    public function test_alleen_voor_steyn(): void
    {
        $this->get('/admin/trainingsschemas')->assertRedirect('/inloggen?next=%2Fadmin%2Ftrainingsschemas');
        $client = $this->client();
        $this->actingAs($client)->get('/admin/voedingsschemas')->assertRedirect('/account');
        $this->actingAs($client)->post('/admin/trainingsschemas/nieuw', ['userId' => $client->id, 'type' => 'training', 'method' => 'leeg'])->assertRedirect('/account');
        $this->assertSame(0, Plan::query()->count());
    }

    public function test_overzicht_per_fase_met_zoeken(): void
    {
        $noor = $this->client(['first_name' => 'Noor', 'last_name' => 'Visser', 'email' => 'noor@example.com']);
        $this->plan($noor, 'training', 'gepubliceerd', ['renew_on' => $this->day(-3), 'content' => ['title' => 'Wedstrijdvoorbereiding: opbouw'] + $this->plan($noor, 'training', 'vervangen')->content]);
        $fleur = $this->client(['first_name' => 'Fleur', 'last_name' => 'Hendriks']);
        $tom = $this->client(['first_name' => 'Tom', 'last_name' => 'de Groot']);
        $this->plan($tom, 'training', 'concept');
        $this->client(['first_name' => 'Sanne', 'last_name' => 'de Vries', 'email' => 'sanne@example.com'], null);
        $this->client(['first_name' => 'Bob', 'coaching_status' => 'geen'], null);

        $steyn = $this->steyn();
        $response = $this->actingAs($steyn)->get('/admin/trainingsschemas')->assertOk()
            ->assertSee("Trainingsschema's")
            ->assertSee('4 klanten, meest dringende bovenaan')
            ->assertSeeInOrder(['Noor Visser', 'Fleur Hendriks', 'Tom de Groot', 'Sanne de Vries'])
            ->assertSee('Wedstrijdvoorbereiding: opbouw')
            ->assertSee('3 dagen geleden')
            ->assertSee('Toe aan nieuw schema')
            ->assertSee('Eerste schema nodig')
            ->assertSee('Herinnering mailen')
            ->assertSee('mailto:sanne@example.com?subject=Je%20intake%20voor%20je%20schema', false)
            ->assertSee("/admin/trainingsschemas/nieuw?lid={$fleur->id}", false)
            ->assertDontSee('Bob');
        $this->assertStringContainsString('Nieuw trainingsschema', $response->getContent());

        $this->get('/admin/trainingsschemas?fase=wacht')->assertOk()
            ->assertSee('Wacht op nieuw schema: 2 klanten')
            ->assertSee('alle 4 tonen')
            ->assertDontSee('Tom de Groot');
        $this->get('/admin/trainingsschemas?q=noor')->assertOk()->assertSee('1 klant, meest dringende bovenaan');
        $this->get('/admin/trainingsschemas?q=niemand')->assertOk()->assertSee('Geen klanten gevonden voor “niemand”.', false);
        $this->get('/admin/schemas')->assertRedirect('/admin/trainingsschemas');
    }

    public function test_nieuw_schema_pagina(): void
    {
        $noor = $this->client(['first_name' => 'Noor', 'last_name' => 'Visser']);
        $this->plan($noor, 'training', 'gepubliceerd', ['renew_on' => $this->day(-3)]);
        $this->client(['first_name' => 'Fleur', 'last_name' => 'Hendriks']);

        $this->actingAs($this->steyn())->get('/admin/trainingsschemas/nieuw')->assertOk()
            ->assertSee('Kies een klant…')
            ->assertSee('<optgroup label="Wacht op nieuw schema">', false)
            ->assertSee('Noor Visser · toe aan nieuw schema')
            ->assertSee('Fleur Hendriks · eerste schema nodig')
            ->assertDontSee('Huidige situatie');

        $this->get("/admin/trainingsschemas/nieuw?lid={$noor->id}")->assertOk()
            ->assertSee('aria-label="Huidige situatie"', false)
            ->assertSee('Toe aan nieuw schema')
            ->assertSee('Laat de AI een concept maken')
            ->assertSee('Verder met het huidige schema')
            ->assertSee('Zelf een leeg schema opstellen')
            ->assertSee('Concept laten maken')
            ->assertSee('value="'.$this->today().'"', false)
            ->assertSee('Berekende richtwaarden')
            ->assertSee('Lichte astma');

        $this->get('/admin/trainingsschemas/nieuw?lid=bestaat-niet')->assertOk()->assertSee('Deze klant bestaat niet (meer).');
    }

    public function test_leeg_schema_met_gegevens_uit_de_intake(): void
    {
        $client = $this->client([], ['trainingDays' => 4]);
        $old = $this->plan($client, 'training', 'fout');
        $steyn = $this->steyn();

        $this->actingAs($steyn)->post('/admin/trainingsschemas/nieuw', ['userId' => $client->id, 'type' => 'training', 'method' => 'leeg', 'startsOn' => $this->day(-1)]);
        $plan = Plan::query()->latest('id')->first();
        $this->assertSame(['concept', 'handmatig', null], [$plan->status, $plan->source, $plan->starts_on]);
        $this->assertCount(4, $plan->content['days']);
        $this->assertSame('vervangen', $old->refresh()->status);

        $this->post('/admin/voedingsschemas/nieuw', ['userId' => $client->id, 'type' => 'voeding', 'method' => 'leeg', 'startsOn' => $this->day(5)])
            ->assertRedirect('/admin/voedingsschemas/'.($plan->id + 1));
        $voeding = Plan::query()->latest('id')->first();
        $targets = IntakeRules::estimateTargets($this->intakeData(['trainingDays' => 4]));
        $this->assertSame($targets['calories'], $voeding->content['targets']['calories']);
        $this->assertSame(['calories', 'protein', 'carbs', 'fat', 'water'], array_keys($voeding->content['targets']));
        $this->assertSame($this->day(5), $voeding->starts_on);

        // Ongeldige invoer doet niets.
        $this->post('/admin/trainingsschemas/nieuw', ['userId' => 'bestaat-niet', 'type' => 'training', 'method' => 'leeg']);
        $this->post('/admin/trainingsschemas/nieuw', ['userId' => $client->id, 'type' => 'yoga', 'method' => 'leeg']);
        $this->post('/admin/trainingsschemas/nieuw', ['userId' => $client->id, 'type' => 'training', 'method' => 'ai', 'instruction' => str_repeat('x', 1501)]);
        $this->assertSame(3, Plan::query()->count());
    }

    public function test_kopie_van_het_huidige_schema(): void
    {
        $client = $this->client();
        $current = $this->plan($client, 'training', 'gepubliceerd');
        $this->actingAs($this->steyn())->post('/admin/trainingsschemas/nieuw', ['userId' => $client->id, 'type' => 'training', 'method' => 'huidig']);
        $copy = Plan::query()->latest('id')->first();
        $this->assertSame('concept', $copy->status);
        $this->assertSame($current->content['title'], $copy->content['title']);
        $this->assertSame('gepubliceerd', $current->refresh()->status);
    }

    public function test_concept_opslaan_en_publiceren(): void
    {
        $client = $this->client();
        $previous = $this->plan($client, 'voeding', 'gepubliceerd');
        $plan = $this->plan($client, 'voeding', 'concept');
        $steyn = $this->steyn();

        // Detailpagina met editor, allergenencontrole en intake.
        $this->actingAs($steyn)->get("/admin/voedingsschemas/{$plan->id}")->assertOk()
            ->assertSee('Voedingsschema · ')
            ->assertSee('Versie #'.$plan->id.' · AI-concept (testmodus)')
            ->assertSee('planEditor(', false)
            ->assertSee('allergie: noten')
            ->assertSee('Goedkeuren &amp; publiceren', false)
            ->assertSee('Nieuw concept laten maken')
            ->assertSee('Lichte astma');

        $content = $plan->content;
        $content['title'] = 'Aangepast voedingsplan';
        $this->freshRequest();
        $this->save($plan, [], $content)->assertRedirect("/admin/voedingsschemas/{$plan->id}")->assertSessionHas('plan_success', 'Concept opgeslagen.');
        $plan->refresh();
        $this->assertSame(['concept', 'Aangepast voedingsplan', $this->day(42)], [$plan->status, $plan->content['title'], $plan->renew_on]);
        $this->freshRequest();
        $this->get("/admin/voedingsschemas/{$plan->id}")->assertSee('Concept opgeslagen.')->assertSee('Aangepast door Steyn');

        $this->freshRequest();
        $this->save($plan, ['intent' => 'publiceren'])->assertSessionHas('plan_success', 'Gepubliceerd. De klant ziet dit schema nu in Mijn omgeving.');
        $plan->refresh();
        $this->assertSame('gepubliceerd', $plan->status);
        $this->assertSame($this->today(), $plan->starts_on);
        $this->assertNotNull($plan->published_at);
        $this->assertSame('vervangen', $previous->refresh()->status);

        // Een gepubliceerd schema bijwerken: de klant ziet het meteen; de oude versie is niet meer te bewerken.
        $this->freshRequest();
        $this->save($plan)->assertSessionHas('plan_success', 'Opgeslagen. De klant ziet de wijzigingen direct.');
        $this->freshRequest();
        $this->save($previous)->assertSessionHas('plan_error', 'Dit schema kan niet (meer) bewerkt worden. Ververs de pagina.');
        $this->freshRequest();
        $this->get("/admin/voedingsschemas/{$previous->id}")->assertOk()->assertSee('Dit is een oudere versie en is niet meer bewerkbaar.')->assertSee('Oude versie');
    }

    public function test_trainingsschema_met_standaardduur_bij_publiceren(): void
    {
        $plan = $this->plan($this->client(), 'training', 'concept');
        $content = $plan->content;
        $content['durationWeeks'] = 8;
        $content['days'][] = $content['days'][0];
        $this->actingAs($this->steyn());
        $this->save($plan, ['intent' => 'publiceren', 'renewOn' => null], $content)->assertSessionHas('plan_success');
        $plan->refresh();
        $this->assertSame(Pipeline::defaultRenewOn('training', $this->today(), 8), $plan->renew_on);
        $this->assertSame(count($content['days']), $plan->content['daysPerWeek']);
    }

    public function test_inplannen_op_de_startdatum_en_terugzetten(): void
    {
        $client = $this->client();
        $current = $this->plan($client, 'voeding', 'gepubliceerd');
        $otherScheduled = $this->plan($client, 'voeding', 'gepland', ['starts_on' => $this->day(10)]);
        $plan = $this->plan($client, 'voeding', 'concept');
        $this->actingAs($this->steyn());

        $this->save($plan, ['intent' => 'publiceren', 'startsOn' => $this->day(6), 'renewOn' => $this->day(34)])
            ->assertSessionHas('plan_success', 'Ingepland. De klant ziet dit schema vanaf '.PlanLabels::formatPlanDayLong($this->day(6)).' in Mijn omgeving.');
        $plan->refresh();
        $this->assertSame(['gepland', $this->day(6), $this->day(34), null], [$plan->status, $plan->starts_on, $plan->renew_on, $plan->published_at]);
        $this->assertSame('gepubliceerd', $current->refresh()->status);
        $this->assertSame('vervangen', $otherScheduled->refresh()->status);

        $this->freshRequest();
        $this->get("/admin/voedingsschemas/{$plan->id}")->assertOk()
            ->assertSee('De klant ziet dit schema vanaf '.PlanLabels::formatPlanDayLong($this->day(6)).' (over 6 dagen).')
            ->assertSee('Tot dan blijft het huidige schema zichtbaar.')
            ->assertSee('Terugzetten naar concept');

        $this->freshRequest();
        $this->post("/admin/voedingsschemas/{$plan->id}/terugzetten", ['planId' => $plan->id])->assertRedirect("/admin/voedingsschemas/{$plan->id}");
        $this->assertSame('concept', $plan->refresh()->status);
    }

    public function test_datumcontroles_bij_opslaan(): void
    {
        $plan = $this->plan($this->client(), 'training', 'concept');
        $this->actingAs($this->steyn());

        $this->save($plan, ['renewOn' => $this->day(-1)])->assertSessionHas('plan_error', 'Kies bij “Nieuw schema op” een datum na vandaag.')
            ->assertSessionHasInput('renewOn', $this->day(-1));
        $this->freshRequest();
        $this->save($plan, ['startsOn' => $this->day(7), 'renewOn' => $this->day(5)])->assertSessionHas('plan_error', 'Kies bij “Nieuw schema op” een datum na de startdatum.');
        $this->freshRequest();
        $this->save($plan, ['renewOn' => $this->day(400)])->assertSessionHas('plan_error', 'Kies voor het volgende schema een datum binnen een jaar na de start.');
        $this->freshRequest();
        $this->save($plan, ['content' => '{kapot'])->assertSessionHas('plan_error', 'Het schema kon niet gelezen worden.');
        $this->freshRequest();
        $this->save($plan, ['content' => json_encode(['title' => 'Alleen een titel'])])->assertSessionHas('plan_error', 'Het schema is niet compleet. Controleer of alle getallen zijn ingevuld.');
        $this->freshRequest();
        $this->save($plan, ['content' => str_repeat('x', 200_001)])->assertSessionHas('plan_error', 'Ongeldig schema.');
        $this->assertSame('concept', $plan->refresh()->status);
        $this->assertNull($plan->renew_on);

        // Na een fout staan de ingevulde gegevens weer in de editor.
        $this->freshRequest();
        $content = $plan->content;
        $content['title'] = 'Nog niet opgeslagen titel';
        $this->save($plan, ['renewOn' => $this->day(-1)], $content);
        $this->get("/admin/trainingsschemas/{$plan->id}")->assertOk()
            ->assertSee('Kies bij “Nieuw schema op” een datum na vandaag.', false)
            ->assertSee('Nog niet opgeslagen titel')
            ->assertSee('Niet-opgeslagen wijzigingen');
    }

    public function test_voorbeeld_voor_de_klant(): void
    {
        $plan = $this->plan($this->client(), 'voeding', 'concept');
        $content = $plan->content;
        $content['title'] = 'Voorbeeld zonder opslaan';
        $content['summary'] = '';
        $this->actingAs($this->steyn())->postJson("/admin/voedingsschemas/{$plan->id}/voorbeeld", ['content' => $content])->assertOk()
            ->assertSee('Voorbeeld zonder opslaan')
            ->assertSee('Energie per dag');
        $this->postJson("/admin/voedingsschemas/{$plan->id}/voorbeeld", ['content' => ['title' => 'x']])->assertStatus(422);
        $this->assertSame($plan->content['title'], $plan->refresh()->content['title']);
    }

    public function test_doorverwijzingen_en_versies(): void
    {
        $client = $this->client();
        $old = $this->plan($client, 'training', 'vervangen');
        $plan = $this->plan($client, 'training', 'gepubliceerd');
        $this->actingAs($this->steyn());

        $this->get("/admin/schemas/{$plan->id}")->assertRedirect("/admin/trainingsschemas/{$plan->id}");
        $this->get("/admin/voedingsschemas/{$plan->id}")->assertRedirect("/admin/trainingsschemas/{$plan->id}");
        $this->get('/admin/schemas/999')->assertNotFound();
        $this->get('/admin/trainingsschemas/999')->assertNotFound();

        $this->get("/admin/trainingsschemas/{$plan->id}")->assertOk()
            ->assertSee('aria-labelledby="versies"', false)
            ->assertSeeInOrder(["#{$plan->id}", 'Gepubliceerd', "#{$old->id}", 'Oude versie'])
            ->assertSee('Opnieuw publiceren')
            ->assertSee('De datum pas je aan onder het schema');
    }

    public function test_intake_gewijzigd_na_het_concept(): void
    {
        $client = $this->client();
        $plan = $this->plan($client, 'training', 'concept', ['created_at' => now()->subDay()]);
        $client->intake->forceFill(['updated_at' => now()])->save();
        $this->actingAs($this->steyn())->get("/admin/trainingsschemas/{$plan->id}")->assertOk()
            ->assertSee('De klant heeft de intake gewijzigd nadat dit concept is gemaakt.');
    }
}
