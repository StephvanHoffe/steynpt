<?php

namespace Tests\Feature;

use App\Jobs\GeneratePlan;
use App\Models\Intake;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\ClaudeClient;
use App\Services\Plans\Generator;
use App\Support\Plans\Mock;
use App\Support\Plans\PlanSchema;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** AI-concepten: de aanroep van de API (nagebootst met Http::fake), foutmeldingen, testmodus en daglimiet. */
class PlansGenerationTest extends TestCase
{
    use PlansTestHelpers;
    use RefreshDatabase;

    private const MODELS = ['data' => [
        ['type' => 'model', 'id' => 'model-anders', 'created_at' => '2026-09-01T00:00:00Z'],
        ['type' => 'model', 'id' => 'model-opus-1', 'created_at' => '2026-01-01T00:00:00Z'],
        ['type' => 'model', 'id' => 'model-opus-2', 'created_at' => '2026-08-01T00:00:00Z'],
    ], 'has_more' => false];

    protected function setUp(): void
    {
        parent::setUp();
        config(['steynpt.anthropic_api_key' => 'test-sleutel', 'steynpt.ai_mock' => false, 'steynpt.ai_model' => null]);
        Sleep::fake();
    }

    private function trainingJson(): string
    {
        return json_encode(Mock::mockTrainingPlan($this->intakeData()));
    }

    /** Laat Steyn een AI-concept maken en geeft het plan terug (de queue draait in de tests direct). */
    private function generate(string $type = 'training', array $form = [], ?array $intake = []): Plan
    {
        $client = $this->client([], $intake);
        $href = $type === 'training' ? '/admin/trainingsschemas' : '/admin/voedingsschemas';
        $response = $this->actingAs($this->steyn())->post("{$href}/nieuw", array_merge(['userId' => $client->id, 'type' => $type, 'method' => 'ai'], $form));
        $plan = Plan::query()->where('user_id', $client->id)->latest('id')->firstOrFail();
        $response->assertRedirect("{$href}/{$plan->id}");

        return $plan;
    }

    public function test_concept_via_de_api_met_structured_output_en_het_nieuwste_opus_model(): void
    {
        Http::fake([
            'api.anthropic.com/v1/models*' => Http::response(self::MODELS),
            'api.anthropic.com/v1/messages' => Http::response($this->messageStream($this->trainingJson()), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $plan = $this->generate('training', ['instruction' => '  Geen squats vanwege de knie ', 'startsOn' => $this->day(3)])->refresh();

        $this->assertSame('concept', $plan->status);
        $this->assertSame('model-opus-2', $plan->model);
        $this->assertSame('Geen squats vanwege de knie', $plan->instruction);
        $this->assertSame($this->day(3), $plan->starts_on);
        $this->assertNull($plan->error);
        $this->assertSame(PlanSchema::parse('training', json_decode($this->trainingJson(), true))[0], $plan->content);
        $this->assertSame($plan->content, $plan->ai_draft);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/v1/messages')) {
                return false;
            }
            $body = $request->data();

            return $request->hasHeader('x-api-key', 'test-sleutel')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request->hasHeader('anthropic-beta', 'server-side-fallback-2026-07-01')
                && $body['model'] === 'model-opus-2'
                && $body['max_tokens'] === 32000
                && $body['stream'] === true
                && $body['fallbacks'] === 'default'
                && $body['thinking'] === ['type' => 'adaptive']
                && $body['output_config']['effort'] === 'high'
                && $body['output_config']['format'] === ['type' => 'json_schema', 'schema' => PlanSchema::jsonSchema('training')]
                && str_contains($body['system'], 'trainingsschema')
                && $body['messages'][0]['role'] === 'user'
                && str_contains($body['messages'][0]['content'], 'Geen squats vanwege de knie');
        });
    }

    public function test_het_model_wordt_een_dag_onthouden_en_ai_model_gaat_voor(): void
    {
        Http::fake([
            'api.anthropic.com/v1/models*' => Http::response(self::MODELS),
            'api.anthropic.com/v1/messages' => Http::response($this->messageStream($this->trainingJson()), 200),
        ]);
        $this->generate();
        $this->generate();
        $this->assertCount(1, Http::recorded(fn (Request $r) => str_contains($r->url(), '/v1/models')));
        $this->assertSame('model-opus-2', Cache::get(ClaudeClient::MODEL_CACHE_KEY));

        config(['steynpt.ai_model' => 'model-vast']);
        $this->assertSame('model-vast', ClaudeClient::model());
    }

    public function test_fallback_midden_in_het_antwoord(): void
    {
        $json = $this->trainingJson();
        $cut = intdiv(strlen($json), 3);
        Http::fake(['api.anthropic.com/v1/messages' => Http::response($this->sse([
            ['type' => 'message_start', 'message' => ['model' => 'model-opus-2', 'content' => [], 'stop_reason' => null]],
            ['type' => 'content_block_start', 'index' => 0, 'content_block' => ['type' => 'text', 'text' => '']],
            ['type' => 'content_block_delta', 'index' => 0, 'delta' => ['type' => 'text_delta', 'text' => substr($json, 0, $cut)]],
            ['type' => 'content_block_stop', 'index' => 0],
            ['type' => 'content_block_start', 'index' => 1, 'content_block' => ['type' => 'fallback', 'from' => ['model' => 'model-opus-2'], 'to' => ['model' => 'model-reserve']]],
            ['type' => 'content_block_stop', 'index' => 1],
            ['type' => 'content_block_start', 'index' => 2, 'content_block' => ['type' => 'text', 'text' => '']],
            ['type' => 'content_block_delta', 'index' => 2, 'delta' => ['type' => 'text_delta', 'text' => substr($json, $cut)]],
            ['type' => 'content_block_stop', 'index' => 2],
            ['type' => 'message_delta', 'delta' => ['stop_reason' => 'end_turn']],
            ['type' => 'message_stop'],
        ]))]);
        config(['steynpt.ai_model' => 'model-opus-2']);

        $plan = $this->generate()->refresh();
        $this->assertSame('concept', $plan->status);
        $this->assertSame('model-reserve', $plan->model);
        $this->assertSame(json_decode($json, true)['title'], $plan->content['title']);
    }

    public static function failures(): array
    {
        return [
            'weigering' => ['refusal', 'De AI heeft dit verzoek geweigerd. Maak het schema handmatig of pas de intake/instructie aan.'],
            'te lang' => ['max_tokens', 'Het antwoord van de AI was te lang en is afgebroken. Probeer het opnieuw.'],
            'onverwachte vorm' => ['vorm', 'Het antwoord van de AI had niet de verwachte vorm. Probeer het opnieuw.'],
            'ongeldige sleutel' => [401, 'De API-sleutel voor de AI is ongeldig. Controleer ANTHROPIC_API_KEY.'],
            'rate limit' => [429, 'De AI is tijdelijk overbelast. Probeer het over een paar minuten opnieuw.'],
            'overbelast' => [529, 'De AI is tijdelijk overbelast. Probeer het over een paar minuten opnieuw.'],
            'overbelast in de stream' => ['stream-overloaded', 'De AI is tijdelijk overbelast. Probeer het over een paar minuten opnieuw.'],
            'andere fout' => [400, 'De AI gaf een foutmelding (400). Probeer het opnieuw.'],
            'serverfout' => [500, 'De AI gaf een foutmelding (500). Probeer het opnieuw.'],
            'geen verbinding' => ['connection', 'Geen verbinding met de AI. Probeer het later opnieuw.'],
            'verbinding verbroken' => ['afgebroken', 'Geen verbinding met de AI. Probeer het later opnieuw.'],
        ];
    }

    #[DataProvider('failures')]
    public function test_foutmeldingen(string|int $case, string $message): void
    {
        config(['steynpt.ai_model' => 'model-opus-2']);
        $json = $this->trainingJson();
        $calls = 0;
        $respond = fn () => match ($case) {
            'refusal' => Http::response($this->messageStream('', stopReason: 'refusal')),
            'max_tokens' => Http::response($this->messageStream(substr($json, 0, 100), stopReason: 'max_tokens')),
            'vorm' => Http::response($this->messageStream('{"title": "Alleen een titel"}')),
            'stream-overloaded' => Http::response($this->sse([
                ['type' => 'message_start', 'message' => ['model' => 'model-opus-2', 'content' => []]],
                ['type' => 'error', 'error' => ['type' => 'overloaded_error', 'message' => 'Overloaded']],
            ])),
            'connection' => throw new ConnectionException('cURL error 7'),
            'afgebroken' => Http::response($this->messageStream($json, complete: false)),
            default => Http::response(['type' => 'error', 'error' => ['type' => 'fout', 'message' => 'Fout']], $case),
        };
        Http::fake(['api.anthropic.com/v1/messages' => function () use (&$calls, $respond) {
            $calls++;

            return $respond();
        }]);

        $plan = $this->generate()->refresh();
        $this->assertSame('fout', $plan->status);
        $this->assertSame($message, $plan->error);
        $this->assertNull($plan->content);

        // Net als de SDK: bij 429, 5xx en verbindingsfouten twee keer opnieuw, anders niet.
        $retried = in_array($case, [429, 529, 500, 'connection'], true);
        $this->assertSame($retried ? 3 : 1, $calls);

        // De detailpagina toont de melding en de knoppen om het opnieuw te proberen.
        $this->freshRequest();
        $this->get("/admin/trainingsschemas/{$plan->id}")->assertOk()
            ->assertSee('Het concept kon niet gemaakt worden')
            ->assertSee($message)
            ->assertSee('Trainingsschema laten maken');
    }

    public function test_zonder_intake_een_duidelijke_fout(): void
    {
        Http::fake();
        $plan = $this->generate(intake: null)->refresh();
        $this->assertSame('fout', $plan->status);
        $this->assertSame('De klant heeft nog geen (geldige) intake ingevuld.', $plan->error);
        Http::assertNothingSent();
    }

    public function test_testmodus_zonder_api_sleutel(): void
    {
        config(['steynpt.anthropic_api_key' => null, 'steynpt.ai_mock' => true]);
        Http::fake();
        $this->assertTrue(Generator::aiConfigured());

        $plan = $this->generate('voeding')->refresh();
        $this->assertSame('concept', $plan->status);
        $this->assertSame('testmodus', $plan->model);
        $this->assertSame('voeding', $plan->type);
        Http::assertNothingSent();
    }

    public function test_ai_staat_uit_zonder_sleutel(): void
    {
        config(['steynpt.anthropic_api_key' => null]);
        $this->assertFalse(Generator::aiConfigured());
        $client = $this->client();
        $this->actingAs($this->steyn())->get("/admin/trainingsschemas/nieuw?lid={$client->id}")->assertOk()
            ->assertSee('AI staat uit: stel ANTHROPIC_API_KEY in om concepten te laten maken.');
    }

    public function test_tijdens_het_genereren_ververst_de_pagina_en_een_nieuw_concept_vervangt_het_oude(): void
    {
        $client = $this->client();
        $old = $this->plan($client, 'training', 'concept');
        $busy = $this->plan($client, 'training', 'genereren', ['content' => null, 'ai_draft' => null]);
        $steyn = $this->steyn();

        $this->actingAs($steyn)->get("/admin/trainingsschemas/{$busy->id}")->assertOk()
            ->assertSee('De AI maakt het concept…')
            ->assertSee('window.location.reload()', false);

        // Na 10 minuten telt hij als vastgelopen.
        Plan::query()->whereKey($busy->id)->update(['updated_at' => now()->subMinutes(11)]);
        $this->freshRequest();
        $this->get("/admin/trainingsschemas/{$busy->id}")->assertOk()
            ->assertSee('Vastgelopen')
            ->assertSee('De generatie is niet afgerond')
            ->assertDontSee('window.location.reload()', false);

        config(['steynpt.ai_mock' => true]);
        $id = Generator::createPlanJob($client->id, 'training', 'Meer core');
        $this->assertSame('vervangen', $old->refresh()->status);
        $this->assertSame('vervangen', $busy->refresh()->status);
        $this->assertSame('genereren', Plan::query()->find($id)->status);
    }

    public function test_job_die_afbreekt_zet_het_concept_op_fout(): void
    {
        $plan = $this->plan($this->client(), 'training', 'genereren', ['content' => null]);
        (new GeneratePlan($plan->id))->failed(new Exception('Time-out'));
        $plan->refresh();
        $this->assertSame('fout', $plan->status);
        $this->assertSame('Er ging iets mis bij het maken van het concept. Probeer het opnieuw.', $plan->error);
        $this->assertGreaterThan(GeneratePlan::TIMEOUT, config('queue.connections.database.retry_after'));
    }

    public function test_automatisch_genereren_na_de_intake_met_daglimiet(): void
    {
        config(['steynpt.ai_mock' => true]);
        $client = $this->client(['coaching_status' => 'aangevraagd']);
        $this->assertTrue(Generator::mayAutoGenerate($client));
        $this->assertFalse(Generator::mayAutoGenerate($this->client(['coaching_status' => 'geen'])));
        $this->assertFalse(Generator::mayAutoGenerate($this->client(['coaching_status' => 'gepauzeerd'])));

        for ($i = 0; $i < Generator::MAX_AUTO_PER_DAY; $i++) {
            $this->plan($client, 'training', 'vervangen');
        }
        $this->assertFalse(Generator::mayAutoGenerate($client));
        Plan::query()->where('user_id', $client->id)->update(['created_at' => now()->subDays(2)]);
        $this->assertTrue(Generator::mayAutoGenerate($client));

        config(['steynpt.ai_mock' => false, 'steynpt.anthropic_api_key' => null]);
        $this->assertFalse(Generator::mayAutoGenerate($client));
    }

    public function test_cronjob_maakt_de_queue_leeg_en_publiceert_ingeplande_schemas(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('schemas-publiceren')
            ->expectsOutputToContain('back-up')
            ->expectsOutputToContain('aanvragen-opschonen')
            ->expectsOutputToContain('wachtrij')
            ->assertSuccessful();
    }

    public function test_cronjob_verwerkt_de_queue_zonder_apart_proces(): void
    {
        // De wachtrij draait binnen schedule:run (geen proc_open nodig op gedeelde hosting).
        Http::fake([
            'api.anthropic.com/v1/models*' => Http::response(self::MODELS),
            'api.anthropic.com/v1/messages' => Http::response($this->messageStream($this->trainingJson()), 200, ['Content-Type' => 'text/event-stream']),
        ]);
        config(['queue.default' => 'database', 'steynpt.ai_mock' => false]);
        $user = User::factory()->create(['coaching_status' => 'actief']);
        Intake::query()->create(['user_id' => $user->id, 'data' => $this->intakeData()]);
        $id = Generator::createPlanJob($user->id, 'training');
        $this->assertSame(1, DB::table('jobs')->count());

        $this->artisan('schedule:run')->assertSuccessful();
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame('concept', Plan::query()->find($id)->status);
    }
}
