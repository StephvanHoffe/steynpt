<?php

namespace Tests\Feature;

use App\Models\Intake;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\PipelineServer;
use App\Support\Agenda;
use App\Support\Plans\Mock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Once;

/** Gedeelde hulpjes voor de tests van de schema-onderdelen. */
trait PlansTestHelpers
{
    protected function intakeData(array $overrides = []): array
    {
        return array_merge([
            'wants' => ['training', 'voeding'], 'goal' => 'afvallen', 'sex' => 'vrouw', 'birthYear' => 1992, 'heightCm' => 168, 'weightKg' => 70.5,
            'activityLevel' => 'zittend', 'medical' => 'Lichte astma', 'experience' => 'beginner', 'trainingDays' => 3, 'sessionMinutes' => 60,
            'location' => 'sportschool', 'injuries' => 'Soms last van mijn linkerknie', 'diet' => 'vegetarisch', 'allergies' => ['noten', 'lactose'], 'mealsPerDay' => 4,
        ], $overrides);
    }

    protected function steyn(): User
    {
        return User::factory()->admin()->create(['first_name' => 'Steyn', 'last_name' => 'Admin']);
    }

    /** Klant met coaching en (standaard) een ingevulde intake. */
    protected function client(array $attributes = [], ?array $intake = []): User
    {
        $user = User::factory()->create(array_merge(['first_name' => 'Eva', 'last_name' => 'Test', 'coaching_status' => 'actief', 'plan' => 'online-pro'], $attributes));
        if ($intake !== null) {
            Intake::query()->create(['user_id' => $user->id, 'data' => $this->intakeData($intake)]);
        }

        return $user;
    }

    protected function plan(User $user, string $type, string $status, array $attributes = []): Plan
    {
        $intake = $this->intakeData();
        $content = $type === 'training' ? Mock::mockTrainingPlan($intake) : Mock::mockNutritionPlan($intake);

        return Plan::query()->create(array_merge([
            'user_id' => $user->id,
            'type' => $type,
            'status' => $status,
            'source' => 'ai',
            'content' => $content,
            'ai_draft' => $content,
            'model' => 'testmodus',
            'published_at' => $status === 'gepubliceerd' ? now()->subWeek() : null,
        ], $attributes));
    }

    protected function today(): string
    {
        return Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];
    }

    protected function day(int $offset): string
    {
        return Agenda::addDays($this->today(), $offset);
    }

    /** Tussen twee verzoeken in één test: berekeningen die per verzoek onthouden worden opnieuw laten doen. */
    protected function freshRequest(): void
    {
        PipelineServer::flush();
        Once::flush();
    }

    /** Server-sent events zoals de Messages API ze stuurt. */
    protected function sse(array $events): string
    {
        return implode('', array_map(fn (array $e) => 'event: '.$e['type']."\ndata: ".json_encode($e)."\n\n", $events));
    }

    /** Volledig gestreamd antwoord met één tekstblok (de JSON van het schema). */
    protected function messageStream(string $text, string $model = 'model-opus-2', string $stopReason = 'end_turn', bool $complete = true): string
    {
        $half = intdiv(strlen($text), 2);
        $events = [
            ['type' => 'message_start', 'message' => ['id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => $model, 'content' => [], 'stop_reason' => null]],
            ['type' => 'content_block_start', 'index' => 0, 'content_block' => ['type' => 'thinking', 'thinking' => '', 'signature' => '']],
            ['type' => 'ping'],
            ['type' => 'content_block_stop', 'index' => 0],
            ['type' => 'content_block_start', 'index' => 1, 'content_block' => ['type' => 'text', 'text' => '']],
            ['type' => 'content_block_delta', 'index' => 1, 'delta' => ['type' => 'text_delta', 'text' => substr($text, 0, $half)]],
            ['type' => 'content_block_delta', 'index' => 1, 'delta' => ['type' => 'text_delta', 'text' => substr($text, $half)]],
            ['type' => 'content_block_stop', 'index' => 1],
            ['type' => 'message_delta', 'delta' => ['stop_reason' => $stopReason], 'usage' => ['output_tokens' => 1234]],
        ];
        if ($complete) {
            $events[] = ['type' => 'message_stop'];
        }

        return $this->sse($events);
    }
}
