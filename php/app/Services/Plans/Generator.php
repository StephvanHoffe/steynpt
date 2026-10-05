<?php

namespace App\Services\Plans;

use App\Jobs\GeneratePlan;
use App\Models\Intake;
use App\Models\Plan;
use App\Models\User;
use App\Support\Intake as IntakeRules;
use App\Support\Plans\Mock;
use App\Support\Plans\Prompt;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI-concepten voor trainings- en voedingsschema's (generate.ts in de Next.js-versie).
 *
 * Een concept wordt klaargezet met status "genereren" en daarna gemaakt door de job GeneratePlan op de
 * database-queue. Op de hosting maakt de cronjob (php artisan schedule:run, elke minuut) die queue leeg;
 * zie routes/console.php. In de testmodus (AI_MOCK=1) draait de job direct na het antwoord, zonder cronjob.
 */
final class Generator
{
    /** Maximaal zoveel automatische AI-concepten per klant per 24 uur. */
    public const MAX_AUTO_PER_DAY = 6;

    /** Modelnaam die bij een voorbeeldconcept in de testmodus wordt opgeslagen. */
    public const MOCK_MODEL = 'testmodus';

    /** Testmodus: voorbeeldconcepten zonder API-sleutel (alleen voor lokaal testen). */
    public static function mockMode(): bool
    {
        return (bool) config('steynpt.ai_mock');
    }

    /** Kunnen er AI-concepten gemaakt worden? (testmodus of een API-sleutel in de omgeving) */
    public static function aiConfigured(): bool
    {
        return self::mockMode() || trim((string) config('steynpt.anthropic_api_key')) !== '';
    }

    /**
     * Zet een nieuw concept klaar (status "genereren") en vervangt openstaande concepten van hetzelfde type.
     * Het gepubliceerde schema blijft zichtbaar tot er een nieuw is gepubliceerd. Zet daarna de job op de queue.
     * Zonder taal schrijft de AI in de taal waarin de klant Mijn omgeving gebruikt.
     *
     * @param  'training'|'voeding'  $type
     * @param  'nl'|'en'|null  $language
     * @return int id van het nieuwe plan
     */
    public static function createPlanJob(string $userId, string $type, ?string $instruction = null, ?string $startsOn = null, ?string $language = null): int
    {
        $instruction = trim((string) $instruction);
        $language = in_array($language, ['nl', 'en'], true) ? $language : self::clientLanguage($userId);
        $id = DB::transaction(function () use ($userId, $type, $instruction, $startsOn, $language) {
            Plan::query()->where('user_id', $userId)->where('type', $type)->whereIn('status', ['genereren', 'concept', 'fout'])
                ->update(['status' => 'vervangen', 'updated_at' => CarbonImmutable::now('UTC')]);

            return Plan::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'status' => 'genereren',
                'source' => 'ai',
                'instruction' => $instruction !== '' ? $instruction : null,
                'language' => $language,
                'starts_on' => $startsOn,
            ])->id;
        });
        PipelineServer::flush();
        self::dispatch($id);

        return $id;
    }

    /** Taal waarin de klant Mijn omgeving gebruikt: 'en' of 'nl'. */
    public static function clientLanguage(string $userId): string
    {
        return User::query()->whereKey($userId)->value('locale') === 'en' ? 'en' : 'nl';
    }

    /** Job voor één concept: via de queue, of in de testmodus direct na het antwoord aan de browser. */
    public static function dispatch(int $planId): void
    {
        if (self::mockMode()) {
            GeneratePlan::dispatchAfterResponse($planId);

            return;
        }
        GeneratePlan::dispatch($planId);
    }

    /** Automatisch genereren na de intake: alleen voor (aspirant-)coachingklanten en met een daglimiet. */
    public static function mayAutoGenerate(User $user): bool
    {
        if (! self::aiConfigured()) {
            return false;
        }
        if ($user->coaching_status !== 'aangevraagd' && $user->coaching_status !== 'actief') {
            return false;
        }
        $n = Plan::query()->where('user_id', $user->id)->where('source', 'ai')
            ->where('created_at', '>', CarbonImmutable::now('UTC')->subDay())->count();

        return $n < self::MAX_AUTO_PER_DAY;
    }

    /** Maakt het AI-concept voor een plan met status "genereren" (aangeroepen door de job GeneratePlan). */
    public static function generate(int $planId): void
    {
        $plan = Plan::query()->find($planId);
        if (! $plan || $plan->status !== 'genereren') {
            return;
        }
        // "Vastgelopen" (na 10 minuten) telt vanaf het moment dat de job begint, niet vanaf het klaarzetten.
        Plan::query()->whereKey($planId)->where('status', 'genereren')->update(['updated_at' => CarbonImmutable::now('UTC')]);

        try {
            $intakeRow = Intake::query()->find($plan->user_id);
            [$intake] = IntakeRules::validate(is_array($intakeRow?->data) ? $intakeRow->data : []);
            if ($intake === null) {
                throw new PlanGenerationException('De klant heeft nog geen (geldige) intake ingevuld.');
            }

            if (self::mockMode()) {
                if (! app()->runningUnitTests()) {
                    usleep(1_200_000);
                }
                $content = $plan->type === 'training' ? Mock::mockTrainingPlan($intake) : Mock::mockNutritionPlan($intake);
                $model = self::MOCK_MODEL;
            } else {
                // De taal die Steyn bij het aanmaken koos; oudere concepten volgen de taal van de klant.
                $language = in_array($plan->language, ['nl', 'en'], true) ? $plan->language : self::clientLanguage($plan->user_id);
                ['content' => $content, 'model' => $model] = ClaudeClient::generatePlan($plan->type, Prompt::buildPlanPrompt($plan->type, $intake, $plan->instruction, null, $language));
            }

            // Alleen opslaan als het plan intussen niet vervangen is.
            $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            Plan::query()->whereKey($planId)->where('status', 'genereren')->update([
                'status' => 'concept',
                'content' => $json,
                'ai_draft' => $json,
                'error' => null,
                'model' => $model,
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
        } catch (Throwable $e) {
            Log::error("Genereren van plan {$planId} mislukt", ['exception' => $e]);
            self::markFailed($planId, $e);
        }
        PipelineServer::flush();
    }

    /** Concept op "fout" zetten met een begrijpelijke melding (ook als de job zelf is afgebroken). */
    public static function markFailed(int $planId, ?Throwable $error): void
    {
        Plan::query()->whereKey($planId)->where('status', 'genereren')->update([
            'status' => 'fout',
            'error' => self::describeError($error),
            'updated_at' => CarbonImmutable::now('UTC'),
        ]);
        PipelineServer::flush();
    }

    /** Nederlandse foutmelding voor Steyn. */
    public static function describeError(?Throwable $error): string
    {
        return match (true) {
            $error instanceof PlanGenerationException => $error->getMessage(),
            $error instanceof ClaudeApiException && $error->status === 401 => 'De API-sleutel voor de AI is ongeldig. Controleer ANTHROPIC_API_KEY.',
            $error instanceof ClaudeApiException && $error->overloaded() => 'De AI is tijdelijk overbelast. Probeer het over een paar minuten opnieuw.',
            $error instanceof ConnectionException, $error instanceof ClaudeConnectionException => 'Geen verbinding met de AI. Probeer het later opnieuw.',
            $error instanceof ClaudeApiException => 'De AI gaf een foutmelding ('.($error->status ?? 'onbekend').'). Probeer het opnieuw.',
            default => 'Er ging iets mis bij het maken van het concept. Probeer het opnieuw.',
        };
    }
}
