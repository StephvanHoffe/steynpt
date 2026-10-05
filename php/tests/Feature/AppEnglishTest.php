<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Measurement;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\Generator;
use App\Support\Agenda;
use App\Support\Plans\Mock;
use App\View\Fmt;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Mijn omgeving in het Engels (users.locale = 'en'): intake, schema, voortgang, afspraken, datums en de AI-prompt.
 * De Nederlandse versie blijft ongewijzigd (zie de andere tests).
 */
class AppEnglishTest extends TestCase
{
    use PlansTestHelpers;
    use RefreshDatabase;

    /** Nederlandse woorden die in een Engelse pagina niet (meer) mogen voorkomen. */
    private const DUTCH_WORDS = [
        'je', 'jij', 'jouw', 'het', 'een', 'van', 'de', 'en', 'met', 'voor', 'naar', 'op', 'niet', 'wat', 'hoe', 'wil', 'kies',
        'schema', 'trainingsschema', 'voedingsschema', 'metingen', 'meting', 'gewicht', 'stap', 'optioneel', 'bijv', 'opslaan',
        'afzeggen', 'weken', 'dag', 'eiwit', 'oefening', 'rust', 'herhalingen', 'sinds', 'gemeten', 'toelichting', 'vermijden',
        'optie', 'terug', 'omgeving', 'gecontroleerd', 'voortgang', 'notitie', 'datum', 'afspraken', 'agenda', 'uur', 'tevoren',
    ];

    /** Klant die Mijn omgeving in het Engels gebruikt, met een intake in Engelse vrije tekst. */
    private function englishClient(array $attributes = [], ?array $intake = []): User
    {
        $intake = $intake === null ? null : ['medical' => 'Mild asthma', 'injuries' => 'Sometimes my left knee hurts', ...$intake];

        return $this->client(['locale' => 'en', ...$attributes], $intake);
    }

    /**
     * Zichtbare tekst van het eigen deel van de pagina (vanaf de container van de pagina tot de footer), zonder tags.
     * Kop, menu en footer zijn van de layout en worden elders getest.
     */
    private function pageText(TestResponse $response, string $from): string
    {
        $html = $response->getContent();
        $start = strpos($html, $from);
        $this->assertNotFalse($start, "begin van de pagina ({$from}) niet gevonden");
        $end = strpos($html, '<footer', $start);
        $html = substr($html, $start, $end === false ? null : $end - $start);
        $html = preg_replace('#<(script|style|template)\b.*?</\1>#s', ' ', $html);

        return html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function assertNoDutch(string $text): void
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $found = array_values(array_unique(array_intersect($words, self::DUTCH_WORDS)));
        $this->assertSame([], $found, 'Nederlandse woorden in de Engelse pagina: '.implode(', ', $found)."\n\n".preg_replace('/\s+/', ' ', $text));
    }

    /** Trainingsschema met Engelse inhoud (zoals de AI het voor een Engelstalige klant schrijft). */
    private function englishTraining(): array
    {
        return [
            'title' => 'Your strength plan',
            'summary' => 'Three full-body sessions a week to build strength.',
            'durationWeeks' => 6,
            'daysPerWeek' => 3,
            'days' => array_map(fn (int $i) => [
                'name' => "Day {$i} – Full body",
                'focus' => 'Strength and technique',
                'warmup' => '5 minutes on the bike.',
                'exercises' => [['name' => 'Goblet squat', 'sets' => '3', 'reps' => '10-12', 'rest' => '90 sec', 'notes' => 'Chest up.']],
                'cooldown' => '5 minutes of stretching.',
            ], [1, 2, 3]),
            'progression' => 'Add a little weight each week.',
            'tips' => ['Log your weights after every session.'],
        ];
    }

    private function englishNutrition(): array
    {
        return [
            'title' => 'Your nutrition plan',
            'summary' => 'Balanced meals with plenty of protein.',
            'targets' => ['calories' => 1610, 'protein' => 130, 'carbs' => 160, 'fat' => 50, 'water' => '2-2.5 litres a day'],
            'meals' => [[
                'name' => 'Breakfast',
                'time' => '07:30',
                'options' => [
                    ['title' => 'Yoghurt bowl', 'ingredients' => '200 g Greek yoghurt, oats, blueberries', 'kcal' => 400, 'protein' => 25],
                    ['title' => 'Wholemeal toast', 'ingredients' => '2 slices with cottage cheese', 'kcal' => 380, 'protein' => 22],
                ],
            ]],
            'avoid' => ['Nuts', 'Lactose'],
            'tips' => ['Eat a source of protein at every meal.'],
        ];
    }

    // ---------------------------------------------------------------------------
    // Intake

    public function test_intake_in_het_engels(): void
    {
        $user = $this->englishClient([], null);

        $response = $this->actingAs($user)->get('/account/intake')->assertOk()
            ->assertSee('<title>My intake · SteynPT</title>', false)
            ->assertSee('Your intake')
            ->assertSee('Step 1')
            ->assertSee('What do you want to achieve?')
            ->assertSee('Training plan')
            ->assertSee('Nutrition plan')
            ->assertSee('Your main goal')
            ->assertSee('Lose weight')
            ->assertSee('Sports performance / elite sport')
            ->assertSee('Mostly sitting')
            ->assertSee('Office work, not much walking')
            ->assertSee('Year of birth')
            ->assertSee('placeholder="E.g. 74.5"', false)
            ->assertSee('Pescatarian (fish, no meat)')
            ->assertSee('Molluscs')
            ->assertSee('(optional)')
            ->assertSee('href="/en/privacy"', false)
            ->assertSee('privacy policy')
            ->assertSee('Save intake')
            ->assertSee('Saving…')
            ->assertDontSee('Jouw intake')
            ->assertDontSee('Stap 1')
            ->assertDontSee('Afvallen');
        $this->assertNoDutch($this->pageText($response, '<div class="container-site max-w-3xl'));
    }

    public function test_bestaande_intake_bijwerken_in_het_engels(): void
    {
        $user = $this->englishClient();
        $response = $this->actingAs($user)->get('/account/intake')->assertOk()->assertSee('Update your intake')->assertSee('Mild asthma');
        $this->assertNoDutch($this->pageText($response, '<div class="container-site max-w-3xl'));
    }

    public function test_meldingen_van_de_intake_in_het_engels(): void
    {
        $user = $this->englishClient([], null);
        $this->actingAs($user)->from('/account/intake')
            ->post('/account/intake', ['wants' => ['training'], 'goalDetails' => str_repeat('x', 601), 'birthYear' => '1800', 'weightKg' => '20'])
            ->assertRedirect('/account/intake')
            ->assertSessionHasErrorsIn('intake', [
                'consent' => 'Please give permission to use your details for your plan',
                'goal' => 'Choose your main goal',
                'goalDetails' => 'Maximum 600 characters',
                'sex' => 'Make a choice',
                'birthYear' => 'Enter a valid year of birth',
                'heightCm' => 'Enter your height',
                'weightKg' => 'Enter a valid weight',
                'experience' => 'Choose your experience level',
                'trainingDays' => 'Choose how many times a week you want to train',
                'sessionMinutes' => 'Choose how long a session can last',
                'location' => 'Choose where you train',
                'activityLevel' => 'Choose how active you are day to day',
                'diet' => 'Choose your eating style',
                'mealsPerDay' => 'Choose the number of meals',
            ])
            ->assertSessionHas('intake_error', 'Please check the highlighted fields.');

        // Op de pagina zelf ook in het Engels, en wat de klant invulde blijft staan. (Opnieuw versturen: na een
        // assertSession… komt de foutenzak in de test niet meer bij het volgende verzoek aan.)
        $this->actingAs($user)->from('/account/intake')
            ->post('/account/intake', ['wants' => ['training'], 'goalDetails' => str_repeat('x', 601), 'birthYear' => '1800', 'weightKg' => '20']);
        $response = $this->actingAs($user)->get('/account/intake')->assertOk()
            ->assertSee('Please check the highlighted fields.')
            ->assertSee('Choose your main goal')
            ->assertSee('Maximum 600 characters')
            ->assertSee('Please give permission to use your details for your plan')
            ->assertDontSee('Kies je belangrijkste doel');
        $this->assertNoDutch(str_replace(str_repeat('x', 601), '', $this->pageText($response, '<div class="container-site max-w-3xl')));

        // Een geldige intake wordt gewoon opgeslagen.
        $form = ['wants' => ['training'], 'goal' => 'fitter', 'sex' => 'vrouw', 'birthYear' => '1990', 'heightCm' => '170', 'weightKg' => '65.5',
            'activityLevel' => 'licht', 'experience' => 'beginner', 'trainingDays' => '3', 'sessionMinutes' => '45', 'location' => 'thuis-geen',
            'diet' => 'alles', 'mealsPerDay' => '3', 'consent' => 'on'];
        $this->actingAs($user)->post('/account/intake', $form)->assertRedirect('/account?intake=opgeslagen');
    }

    public function test_intake_blijft_nederlands_voor_een_nederlands_lid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account/intake')->assertOk()
            ->assertSee('Jouw intake')->assertSee('Stap 1')->assertSee('Afvallen')->assertSee('href="/privacy"', false)->assertDontSee('Your intake');
        $this->actingAs($user)->from('/account/intake')->post('/account/intake', ['wants' => ['training'], 'goalDetails' => str_repeat('x', 601)])
            ->assertSessionHasErrorsIn('intake', ['goal' => 'Kies je belangrijkste doel', 'goalDetails' => 'Maximaal 600 tekens']);
    }

    // ---------------------------------------------------------------------------
    // Schema

    public function test_trainingsschema_in_het_engels(): void
    {
        $client = $this->englishClient();
        $plan = $this->plan($client, 'training', 'gepubliceerd', ['content' => $this->englishTraining(), 'published_at' => '2026-09-13 10:00:00']);

        $response = $this->actingAs($client)->get("/account/schema/{$plan->id}")->assertOk()
            ->assertSee('<title>My plan · SteynPT</title>', false)
            ->assertSee('Training plan · checked by Steyn · 13 September 2026')
            ->assertSee('Back to my account')
            ->assertSee('Print or save as PDF')
            ->assertSee('3× a week')
            ->assertSee('6 weeks')
            ->assertSee('Warm-up:')
            ->assertSee('Exercise')
            ->assertSee('Reps')
            ->assertSee('Cool-down:')
            ->assertSee('Progression')
            ->assertSee('Tips')
            ->assertSee('weekly check-in');
        $this->assertNoDutch($this->pageText($response, '<div class="container-site max-w-4xl'));
    }

    public function test_voedingsschema_in_het_engels(): void
    {
        $client = $this->englishClient();
        $plan = $this->plan($client, 'voeding', 'gepubliceerd', ['content' => $this->englishNutrition(), 'published_at' => '2026-09-13 10:00:00']);

        $response = $this->actingAs($client)->get("/account/schema/{$plan->id}")->assertOk()
            ->assertSee('Nutrition plan · checked by Steyn · 13 September 2026')
            ->assertSee('Energy per day')
            ->assertSee('Protein per day')
            ->assertSee('Carbohydrates per day')
            ->assertSee('Water per day')
            ->assertSee('Avoid')
            ->assertSee('Option 2')
            ->assertSee('± 400 kcal · 25 g protein');
        $this->assertNoDutch($this->pageText($response, '<div class="container-site max-w-4xl'));
    }

    public function test_bestaand_nederlands_schema_blijft_leesbaar(): void
    {
        // De inhoud van een eerder (in het Nederlands) gemaakt schema blijft Nederlands; de vaste teksten zijn Engels.
        $client = $this->englishClient();
        $plan = $this->plan($client, 'training', 'gepubliceerd');
        $this->actingAs($client)->get("/account/schema/{$plan->id}")->assertOk()
            ->assertSee($plan->content['title'])->assertSee('Exercise')->assertDontSee('Oefening');
    }

    // ---------------------------------------------------------------------------
    // Voortgang

    public function test_voortgang_in_het_engels(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user)->get('/account/voortgang')->assertOk()
            ->assertSee('<title>My progress · SteynPT</title>', false)
            ->assertSee('There are no measurements yet.')
            ->assertSee('Book a measurement');

        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => '2026-09-01 10:00:00', 'weight' => 1080.2, 'waist' => 90.5, 'note' => 'Baseline']);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => '2026-09-29 10:00:00', 'weight' => 1078.6, 'waist' => 88, 'body_fat' => 21.4]);

        $response = $this->actingAs($user)->get('/account/voortgang')->assertOk()
            ->assertSee('My progress')
            ->assertSee('Weight')
            ->assertSee('Waist')
            ->assertSee('Body fat')
            ->assertSee('1,078.6')
            ->assertSee('-1.6 kg since', false)
            ->assertSee('Measured on')
            ->assertSee('All measurements')
            ->assertSee('Date')
            ->assertSee('Note')
            ->assertSee('29 Sep 2026')
            ->assertSee('Weight: 2 measurements, from 1,080.2 kg on 1 September 2026 to 1,078.6 kg on 29 September 2026. Use the arrow keys to view the measurements.')
            ->assertDontSee('1.078,6')
            ->assertDontSee('Gewicht');
        $this->assertNoDutch($this->pageText($response, '<div class="container-site max-w-5xl'));
    }

    public function test_voortgang_blijft_nederlands_voor_een_nederlands_lid(): void
    {
        $user = User::factory()->create();
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => '2026-09-01 10:00:00', 'weight' => 80.2]);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => '2026-09-29 10:00:00', 'weight' => 78.6]);
        $this->actingAs($user)->get('/account/voortgang')->assertOk()
            ->assertSee('Mijn voortgang')->assertSee('-1,6 kg sinds', false)->assertSee('29 sep 2026')
            ->assertSee('Gewicht: 2 metingen, van 80,2 kg op 1 september 2026 naar 78,6 kg op 29 september 2026. Gebruik de pijltjestoetsen om metingen te bekijken.');
    }

    // ---------------------------------------------------------------------------
    // Afspraken

    public function test_afsprakenlijst_in_het_engels(): void
    {
        CarbonImmutable::setTestNow('2026-10-05 08:00:00');
        $user = User::factory()->create(['locale' => 'en']);
        $start = Agenda::zonedTimeToUtc('2026-10-12', '09:00');
        Appointment::query()->create(['user_id' => $user->id, 'type' => 'kennismaking', 'location' => 'online', 'starts_at' => $start, 'ends_at' => $start->addMinutes(30), 'status' => 'gepland']);
        $soon = Agenda::zonedTimeToUtc('2026-10-06', '09:00');
        Appointment::query()->create(['user_id' => $user->id, 'type' => 'meting', 'location' => 'gymbase', 'starts_at' => $soon, 'ends_at' => $soon->addMinutes(30), 'status' => 'gepland']);

        $html = $this->actingAs($user)->get('/account/agenda')->assertOk()
            ->assertSee('Free intro session')
            ->assertSee('Online (video call)')
            ->assertSee('Monday 12 October, 09:00–09:30')
            ->assertSee('Measurement')
            ->assertSee('Tuesday 6 October, 09:00–09:30')
            ->assertSee('Add to my calendar')
            ->assertSee('Cancel')
            ->assertSee('You can cancel up to 24 hours in advance')
            ->assertDontSee('Gratis kennismaking')
            ->assertDontSee('maandag 12 oktober')
            ->getContent();
        $list = substr($html, strpos($html, '<ul class="divide-y divide-line rounded-lg border border-line">'));
        $list = substr($list, 0, strpos($list, '</ul>') + 5);
        $this->assertNoDutch(html_entity_decode(strip_tags(str_replace('<', ' <', $list))));
        CarbonImmutable::setTestNow();
    }

    // ---------------------------------------------------------------------------
    // Datums (App\View\Fmt volgt de taal van de app)

    public function test_datums_volgen_de_taal(): void
    {
        $date = '2026-10-05T10:00:00Z';
        App::setLocale('en');
        $this->assertSame('5 October 2026', Fmt::date($date));
        $this->assertSame('5 October', Fmt::dayMonth($date));
        $this->assertSame('5 Oct', Fmt::shortDate($date));
        $this->assertSame('5 Oct 2026', Fmt::shortDate($date, true));
        $this->assertSame('Mon 5 Oct', Fmt::shortDay($date));
        $this->assertSame('Monday 5 October', Fmt::longDay($date));
        $this->assertSame('12:00', Fmt::time($date));
        $this->assertSame('1,234.5', Fmt::number(1234.5));
        $this->assertSame('79', Fmt::number(79.0));

        App::setLocale('nl');
        $this->assertSame('5 oktober 2026', Fmt::date($date));
        $this->assertSame('5 oktober', Fmt::dayMonth($date));
        $this->assertSame('5 okt', Fmt::shortDate($date));
        $this->assertSame('5 okt 2026', Fmt::shortDate($date, true));
        $this->assertSame('ma 5 okt', Fmt::shortDay($date));
        $this->assertSame('maandag 5 oktober', Fmt::longDay($date));
        $this->assertSame('1.234,5', Fmt::number(1234.5));
        $this->assertSame('–', Fmt::number(null));
    }

    public function test_beheer_blijft_nederlands(): void
    {
        $client = $this->englishClient();
        $plan = $this->plan($client, 'training', 'gepubliceerd', ['content' => $this->englishTraining(), 'published_at' => '2026-09-13 10:00:00']);
        Measurement::query()->create(['user_id' => $client->id, 'measured_at' => '2026-09-01 10:00:00', 'weight' => 80.2]);
        Measurement::query()->create(['user_id' => $client->id, 'measured_at' => '2026-09-29 10:00:00', 'weight' => 78.6]);
        $steyn = $this->steyn();
        $steyn->forceFill(['locale' => 'en'])->save();

        $this->actingAs($steyn)->get("/admin/trainingsschemas/{$plan->id}")->assertOk()->assertSee('Oefening')->assertDontSee('Reps');
        $this->freshRequest();
        $this->actingAs($steyn)->get("/admin/leden/{$client->id}")->assertOk()->assertSee('-1,6 kg sinds', false)->assertDontSee('since');
    }

    // ---------------------------------------------------------------------------
    // AI-concept in de taal van de klant

    public function test_ai_schrijft_het_schema_in_het_engels_voor_een_engelstalige_klant(): void
    {
        config(['steynpt.anthropic_api_key' => 'test-sleutel', 'steynpt.ai_mock' => false, 'steynpt.ai_model' => 'model-opus-2']);
        Sleep::fake();
        Http::fake(['api.anthropic.com/v1/messages' => Http::response($this->messageStream(json_encode($this->englishTraining())), 200, ['Content-Type' => 'text/event-stream'])]);

        $english = $this->englishClient();
        $dutch = $this->client();
        $englishPlan = Plan::query()->findOrFail(Generator::createPlanJob($english->id, 'training', 'Geen squats'));
        $dutchPlan = Plan::query()->findOrFail(Generator::createPlanJob($dutch->id, 'voeding'));

        $this->assertSame('concept', $englishPlan->refresh()->status);
        $this->assertSame('Your strength plan', $englishPlan->content['title']);
        $this->assertSame('fout', $dutchPlan->refresh()->status, 'het nagebootste antwoord is een trainingsschema');

        $sent = Http::recorded()->map(fn ($pair) => $pair[0]->data())->values();
        $this->assertCount(2, $sent);
        $this->assertStringContainsString('Write the whole plan in English', $sent[0]['system']);
        $this->assertStringContainsString('Create a training plan:', $sent[0]['system']);
        $this->assertStringNotContainsString('Schrijf in het Nederlands', $sent[0]['system']);
        $this->assertStringStartsWith('Client intake (in Dutch):', $sent[0]['messages'][0]['content']);
        $this->assertStringContainsString("Additional instruction from Steyn (takes precedence; may be written in Dutch):\nGeen squats", $sent[0]['messages'][0]['content']);
        $this->assertStringEndsWith('Now create the training plan. Write all of it in English.', $sent[0]['messages'][0]['content']);

        $this->assertStringContainsString('Schrijf in het Nederlands', $sent[1]['system']);
        $this->assertStringEndsWith('Maak nu het voedingsschema.', $sent[1]['messages'][0]['content']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/messages'));
    }

    public function test_vinkje_in_het_engels_bij_het_aanmaken(): void
    {
        // Steyn kiest de taal per concept: Engels voor een Nederlandstalige klant, of juist Nederlands voor een Engelstalige.
        config(['steynpt.anthropic_api_key' => 'test-sleutel', 'steynpt.ai_mock' => false, 'steynpt.ai_model' => 'model-opus-2']);
        Sleep::fake();
        Http::fake(['api.anthropic.com/v1/messages' => Http::sequence()
            ->push($this->messageStream(json_encode($this->englishNutrition())), 200, ['Content-Type' => 'text/event-stream'])
            ->push($this->messageStream(json_encode(Mock::mockTrainingPlan($this->intakeData()))), 200, ['Content-Type' => 'text/event-stream'])]);

        $dutch = $this->client();
        $english = $this->englishClient();
        $steyn = $this->steyn();

        // Formulier stuurt 'nl' (verborgen veld) en bij een vinkje daarna 'en'.
        $this->actingAs($steyn)->post('/admin/voedingsschemas/nieuw', ['userId' => $dutch->id, 'type' => 'voeding', 'method' => 'ai', 'instruction' => 'Meer warme lunches', 'language' => 'en']);
        $englishPlan = Plan::query()->where('user_id', $dutch->id)->latest('id')->firstOrFail();
        $this->assertSame('en', $englishPlan->language);
        $this->assertSame('concept', $englishPlan->status);
        $this->assertSame('Your nutrition plan', $englishPlan->content['title']);

        $this->freshRequest();
        $this->actingAs($steyn)->post('/admin/trainingsschemas/nieuw', ['userId' => $english->id, 'type' => 'training', 'method' => 'ai', 'language' => 'nl']);
        $dutchPlan = Plan::query()->where('user_id', $english->id)->latest('id')->firstOrFail();
        $this->assertSame('nl', $dutchPlan->language);
        $this->assertSame('concept', $dutchPlan->status);

        $sent = Http::recorded()->map(fn ($pair) => $pair[0]->data())->values();
        $this->assertCount(2, $sent);
        $this->assertStringContainsString('Write the whole plan in English', $sent[0]['system']);
        $this->assertStringContainsString("Additional instruction from Steyn (takes precedence; may be written in Dutch):\nMeer warme lunches", $sent[0]['messages'][0]['content']);
        $this->assertStringEndsWith('Now create the nutrition plan. Write all of it in English.', $sent[0]['messages'][0]['content']);
        $this->assertStringContainsString('Schrijf in het Nederlands', $sent[1]['system']);
        $this->assertStringEndsWith('Maak nu het trainingsschema.', $sent[1]['messages'][0]['content']);

        // Zonder taal (automatisch na de intake) volgt het concept de taal van de klant.
        $this->assertSame('en', Plan::query()->findOrFail(Generator::createPlanJob($english->id, 'voeding'))->language);

        // Een onbekende taal wordt geweigerd.
        $this->freshRequest();
        $before = Plan::query()->count();
        $this->actingAs($steyn)->post('/admin/trainingsschemas/nieuw', ['userId' => $dutch->id, 'type' => 'training', 'method' => 'ai', 'language' => 'de']);
        $this->assertSame($before, Plan::query()->count());
    }

    public function test_vinkje_staat_vooraf_goed(): void
    {
        config(['steynpt.anthropic_api_key' => 'test-sleutel', 'steynpt.ai_mock' => false]);
        $dutch = $this->client();
        $english = $this->englishClient();
        $steyn = $this->steyn();
        $checked = 'name="language" value="en" checked';

        // Nieuw schema: aangevinkt als de klant de site in het Engels gebruikt.
        $this->actingAs($steyn)->get("/admin/trainingsschemas/nieuw?lid={$english->id}")->assertOk()
            ->assertSee('Schema in het Engels maken')->assertSee($checked, false)->assertSee('Deze klant gebruikt de site in het Engels.');
        $this->freshRequest();
        $this->actingAs($steyn)->get("/admin/voedingsschemas/nieuw?lid={$dutch->id}")->assertOk()
            ->assertSee('Schema in het Engels maken')->assertDontSee($checked, false)->assertDontSee('Deze klant gebruikt de site in het Engels.');

        // Een nieuw concept start in de taal van het huidige AI-concept, ook als die afwijkt van de klant.
        $plan = $this->plan($dutch, 'training', 'concept', ['source' => 'ai', 'language' => 'en']);
        $this->freshRequest();
        $this->actingAs($steyn)->get("/admin/trainingsschemas/{$plan->id}")->assertOk()
            ->assertSee('In het Engels')->assertSee($checked, false);

        $manual = $this->plan($english, 'voeding', 'concept', ['source' => 'handmatig']);
        $this->freshRequest();
        $this->actingAs($steyn)->get("/admin/voedingsschemas/{$manual->id}")->assertOk()
            ->assertDontSee('In het Engels')->assertSee($checked, false);
    }

    public function test_testmodus_blijft_een_nederlands_voorbeeld(): void
    {
        // De testmodus (AI_MOCK=1) is alleen voor lokaal testen en maakt altijd het Nederlandse voorbeeld.
        config(['steynpt.ai_mock' => true]);
        $client = $this->englishClient();
        $plan = Plan::query()->findOrFail(Generator::createPlanJob($client->id, 'training'));
        Generator::generate($plan->id); // in de testmodus draait de job pas na het antwoord aan de browser
        $this->assertSame(Mock::mockTrainingPlan($this->intakeData(['medical' => 'Mild asthma', 'injuries' => 'Sometimes my left knee hurts']))['title'], $plan->refresh()->content['title']);
    }
}
