<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** De klant ziet alleen zijn eigen, gepubliceerde schema's (een ingepland schema pas vanaf de startdag). */
class PlansAccountTest extends TestCase
{
    use PlansTestHelpers;
    use RefreshDatabase;

    public function test_eigen_gepubliceerd_schema_met_printknop(): void
    {
        $client = $this->client();
        $plan = $this->plan($client, 'voeding', 'gepubliceerd', ['published_at' => '2026-09-13 10:00:00']);

        $this->get("/account/schema/{$plan->id}")->assertRedirect("/inloggen?next=%2Faccount%2Fschema%2F{$plan->id}");
        $this->actingAs($client)->get("/account/schema/{$plan->id}")->assertOk()
            ->assertSee('<title>Mijn schema · SteynPT</title>', false)
            ->assertSee('Voedingsschema · gecontroleerd door Steyn · 13 september 2026')
            ->assertSee($plan->content['title'])
            ->assertSee('Energie per dag')
            ->assertSee('Printen of opslaan als pdf')
            ->assertSee('Terug naar mijn omgeving');

        $training = $this->plan($client, 'training', 'gepubliceerd');
        $this->freshRequest();
        $this->get("/account/schema/{$training->id}")->assertOk()->assertSee('× per week')->assertSee('Oefening');
    }

    public function test_concepten_en_schemas_van_anderen_zijn_niet_te_zien(): void
    {
        $client = $this->client();
        $other = $this->client(['first_name' => 'Bob']);
        $concept = $this->plan($client, 'training', 'concept');
        $replaced = $this->plan($client, 'training', 'vervangen');
        $notMine = $this->plan($other, 'training', 'gepubliceerd');

        $this->actingAs($client);
        foreach ([$concept, $replaced, $notMine] as $plan) {
            $this->freshRequest();
            $this->get("/account/schema/{$plan->id}")->assertNotFound();
        }
        $this->get('/account/schema/999')->assertNotFound();
        // Ook de beheerpagina's zijn niet voor klanten.
        $this->get("/admin/schemas/{$concept->id}")->assertRedirect('/account');
    }

    public function test_ingepland_schema_pas_vanaf_de_startdag(): void
    {
        $client = $this->client();
        $current = $this->plan($client, 'voeding', 'gepubliceerd');
        $next = $this->plan($client, 'voeding', 'gepland', ['starts_on' => $this->day(1)]);
        $this->actingAs($client);

        $this->get("/account/schema/{$next->id}")->assertNotFound();
        $this->freshRequest();
        $this->get("/account/schema/{$current->id}")->assertOk();

        // De startdag is aangebroken: het nieuwe schema neemt het over.
        Plan::query()->whereKey($next->id)->update(['starts_on' => $this->today()]);
        $this->freshRequest();
        $this->get("/account/schema/{$next->id}")->assertOk();
        $this->assertSame('gepubliceerd', $next->refresh()->status);
        $this->assertSame('vervangen', $current->refresh()->status);
        $this->freshRequest();
        $this->get("/account/schema/{$current->id}")->assertNotFound();
    }
}
