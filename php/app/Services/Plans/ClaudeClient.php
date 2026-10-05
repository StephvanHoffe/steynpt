<?php

namespace App\Services\Plans;

use App\Support\Plans\PlanSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Aanroep van de Claude API (Messages API) via de HTTP-client van Laravel, zonder extra pakketten.
 * Zelfde verzoek als de Next.js-versie: structured output met het JSON-schema van het schema-type,
 * adaptive thinking, effort high, 32.000 max_tokens en de server-side fallback bij een weigering.
 * Het antwoord wordt gestreamd (server-sent events), zodat een lang antwoord niet op een time-out stukloopt.
 *
 * Het model staat niet in de code: AI_MODEL in de omgeving, of anders het nieuwste model met "opus" in de id
 * dat de API-sleutel kan gebruiken (opgevraagd bij de Models API en een dag onthouden).
 */
final class ClaudeClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1';

    private const VERSION = '2023-06-01';

    // Bij een (onterechte) weigering probeert de API het automatisch op een ander model (beta).
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    public const MAX_TOKENS = 32000;

    /** Maximale duur van één AI-verzoek in seconden; ruim binnen de time-out van de job (GeneratePlan::TIMEOUT). */
    public const REQUEST_SECONDS = 480;

    /** Zo lang mag de stream stil blijven voordat de verbinding als verbroken telt. */
    private const READ_TIMEOUT = 300;

    /** Net als de officiële SDK: twee keer opnieuw proberen bij een verbindingsfout, 408, 409, 429 of 5xx. */
    private const MAX_RETRIES = 2;

    public const MODEL_CACHE_KEY = 'steynpt.ai_model.nieuwste';

    /**
     * Laat de AI een schema maken en controleert het antwoord met PlanSchema.
     *
     * @param  'training'|'voeding'  $type
     * @param  array{system: string, user: string}  $prompt
     * @return array{content: array<string, mixed>, model: string}
     *
     * @throws PlanGenerationException|ClaudeApiException|ClaudeConnectionException|ConnectionException
     */
    public static function generatePlan(string $type, array $prompt): array
    {
        $model = self::model();
        $message = self::streamMessage([
            'model' => $model,
            'max_tokens' => self::MAX_TOKENS,
            'stream' => true,
            'fallbacks' => 'default',
            'thinking' => ['type' => 'adaptive'],
            'output_config' => [
                'effort' => 'high',
                'format' => ['type' => 'json_schema', 'schema' => PlanSchema::jsonSchema($type)],
            ],
            'system' => $prompt['system'],
            'messages' => [['role' => 'user', 'content' => $prompt['user']]],
        ]);

        if ($message['stop_reason'] === 'refusal') {
            throw new PlanGenerationException('De AI heeft dit verzoek geweigerd. Maak het schema handmatig of pas de intake/instructie aan.');
        }
        if ($message['stop_reason'] === 'max_tokens') {
            throw new PlanGenerationException('Het antwoord van de AI was te lang en is afgebroken. Probeer het opnieuw.');
        }
        $content = self::parseContent($type, $message['texts']);
        if ($content === null) {
            throw new PlanGenerationException('Het antwoord van de AI had niet de verwachte vorm. Probeer het opnieuw.');
        }

        return ['content' => $content, 'model' => $message['model'] !== '' ? $message['model'] : $model];
    }

    /** Het model voor de concepten: AI_MODEL, of het nieuwste model met "opus" in de id volgens de Models API (een dag onthouden). */
    public static function model(): string
    {
        $configured = trim((string) config('steynpt.ai_model'));
        if ($configured !== '') {
            return $configured;
        }

        return Cache::remember(self::MODEL_CACHE_KEY, now()->addDay(), fn () => self::latestModel());
    }

    private static function latestModel(): string
    {
        $response = self::send(fn () => self::client()->timeout(30)->get(self::BASE_URL.'/models', ['limit' => 1000]));
        $models = collect($response->json('data') ?? [])
            ->filter(fn ($m) => is_array($m) && is_string($m['id'] ?? null) && str_contains(strtolower($m['id']), 'opus'))
            ->sortByDesc(fn (array $m) => strtotime((string) ($m['created_at'] ?? '')) ?: 0);
        $id = $models->first()['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new PlanGenerationException('Er is geen geschikt AI-model gevonden. Stel AI_MODEL in of probeer het later opnieuw.');
        }

        return $id;
    }

    /**
     * Tekst van het antwoord als schema. Na een fallback midden in het antwoord gaat het andere model verder
     * waar het eerste ophield; lukt het samenvoegen niet, dan telt alleen de tekst na de laatste fallback.
     *
     * @param  list<array{text: string, afterFallback: bool}>  $texts
     */
    private static function parseContent(string $type, array $texts): ?array
    {
        $candidates = [implode('', array_column($texts, 'text'))];
        $lastFallback = array_filter($texts, fn (array $t) => $t['afterFallback']);
        if ($lastFallback !== [] && count($lastFallback) < count($texts)) {
            $candidates[] = implode('', array_column($lastFallback, 'text'));
        }
        foreach ($candidates as $text) {
            $json = json_decode($text, true);
            if (! is_array($json)) {
                continue;
            }
            [$content] = PlanSchema::parse($type, $json);
            if ($content !== null) {
                return $content;
            }
        }

        return null;
    }

    private static function client(): PendingRequest
    {
        return Http::withHeaders([
            'x-api-key' => (string) config('steynpt.anthropic_api_key'),
            'anthropic-version' => self::VERSION,
        ])->connectTimeout(30)->acceptJson();
    }

    /**
     * Verstuurt het verzoek met stream: true en leest de server-sent events.
     *
     * @return array{model: string, stop_reason: ?string, texts: list<array{text: string, afterFallback: bool}>}
     */
    private static function streamMessage(array $body): array
    {
        $response = self::send(fn () => self::client()
            ->accept('text/event-stream')
            ->withHeaders(['anthropic-beta' => self::FALLBACK_BETA])
            ->withOptions(['stream' => true, 'read_timeout' => self::READ_TIMEOUT])
            ->timeout(self::REQUEST_SECONDS)
            ->post(self::BASE_URL.'/messages', $body));

        return self::readEvents($response->toPsrResponse()->getBody());
    }

    /** Verzoek met opnieuw proberen zoals de SDK (Retry-After en x-should-retry tellen mee). */
    private static function send(callable $request): Response
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $request();
            } catch (ConnectionException $e) {
                if ($attempt >= self::MAX_RETRIES) {
                    throw $e;
                }
                self::pause($attempt, null);

                continue;
            }
            if ($response->successful()) {
                return $response;
            }
            if ($attempt < self::MAX_RETRIES && self::shouldRetry($response)) {
                self::pause($attempt, $response);

                continue;
            }

            throw ClaudeApiException::fromResponse($response);
        }
    }

    private static function shouldRetry(Response $response): bool
    {
        $header = strtolower($response->header('x-should-retry'));
        if ($header === 'true' || $header === 'false') {
            return $header === 'true';
        }
        $status = $response->status();

        return in_array($status, [408, 409, 429], true) || $status >= 500;
    }

    private static function pause(int $attempt, ?Response $response): void
    {
        $retryAfter = $response ? $response->header('retry-after') : '';
        if (is_numeric($retryAfter) && $retryAfter > 0 && $retryAfter <= 60) {
            Sleep::for((int) ceil($retryAfter * 1000))->milliseconds();

            return;
        }
        $seconds = min(0.5 * 2 ** $attempt, 8.0) * (1 - mt_rand(0, 250) / 1000);
        Sleep::for((int) round($seconds * 1000))->milliseconds();
    }

    /**
     * Server-sent events van de Messages API: tekstblokken verzamelen, thinking negeren, stop_reason en model onthouden.
     *
     * @return array{model: string, stop_reason: ?string, texts: list<array{text: string, afterFallback: bool}>}
     */
    private static function readEvents(StreamInterface $body): array
    {
        $deadline = microtime(true) + self::REQUEST_SECONDS;
        $state = ['model' => '', 'stop_reason' => null, 'blocks' => [], 'done' => false];
        $buffer = '';

        while (! $state['done']) {
            if (microtime(true) > $deadline) {
                throw new ClaudeConnectionException('Het antwoord van de AI duurde te lang.');
            }
            try {
                if ($body->eof()) {
                    break;
                }
                $chunk = $body->read(8192);
            } catch (RuntimeException $e) {
                throw new ClaudeConnectionException('De verbinding met de AI is verbroken: '.$e->getMessage(), 0, $e);
            }
            if ($chunk === '') {
                if ($body->getMetadata('timed_out')) {
                    throw new ClaudeConnectionException('De AI stuurde te lang geen gegevens.');
                }
                usleep(10_000);

                continue;
            }
            $buffer = str_replace("\r\n", "\n", $buffer.$chunk);
            while (! $state['done'] && ($end = strpos($buffer, "\n\n")) !== false) {
                self::handleEvent(substr($buffer, 0, $end), $state);
                $buffer = substr($buffer, $end + 2);
            }
        }
        if (! $state['done'] && trim($buffer) !== '') {
            self::handleEvent($buffer, $state);
        }
        if (! $state['done']) {
            throw new ClaudeConnectionException('De verbinding met de AI is verbroken voordat het antwoord compleet was.');
        }

        // Tekstblokken in volgorde; onthoud welke na de laatste fallback komen.
        ksort($state['blocks']);
        $lastFallback = -1;
        foreach ($state['blocks'] as $index => $block) {
            if (($block['type'] ?? null) === 'fallback') {
                $lastFallback = $index;
            }
        }
        $texts = [];
        foreach ($state['blocks'] as $index => $block) {
            if (($block['type'] ?? null) === 'text') {
                $texts[] = ['text' => (string) ($block['text'] ?? ''), 'afterFallback' => $index > $lastFallback && $lastFallback >= 0];
            }
        }

        return ['model' => $state['model'], 'stop_reason' => $state['stop_reason'], 'texts' => $texts];
    }

    private static function handleEvent(string $raw, array &$state): void
    {
        $event = null;
        $data = [];
        foreach (explode("\n", $raw) as $line) {
            if ($line === '' || str_starts_with($line, ':')) {
                continue;
            }
            [$field, $value] = array_pad(explode(':', $line, 2), 2, '');
            $value = str_starts_with($value, ' ') ? substr($value, 1) : $value;
            if ($field === 'event') {
                $event = $value;
            } elseif ($field === 'data') {
                $data[] = $value;
            }
        }
        if ($data === []) {
            return;
        }
        $payload = json_decode(implode("\n", $data), true);
        if (! is_array($payload)) {
            return;
        }

        switch ($payload['type'] ?? $event) {
            case 'message_start':
                $state['model'] = (string) ($payload['message']['model'] ?? '');
                $state['stop_reason'] = $payload['message']['stop_reason'] ?? null;
                break;
            case 'content_block_start':
                $block = is_array($payload['content_block'] ?? null) ? $payload['content_block'] : [];
                $state['blocks'][(int) ($payload['index'] ?? count($state['blocks']))] = $block;
                // Fallback: vanaf hier schrijft een ander model verder.
                if (($block['type'] ?? null) === 'fallback' && is_string($block['to']['model'] ?? null)) {
                    $state['model'] = $block['to']['model'];
                }
                break;
            case 'content_block_delta':
                $index = (int) ($payload['index'] ?? 0);
                $delta = $payload['delta'] ?? [];
                if (($delta['type'] ?? null) === 'text_delta' && isset($state['blocks'][$index])) {
                    $state['blocks'][$index]['text'] = ($state['blocks'][$index]['text'] ?? '').($delta['text'] ?? '');
                }
                break;
            case 'message_delta':
                if (array_key_exists('stop_reason', $payload['delta'] ?? [])) {
                    $state['stop_reason'] = $payload['delta']['stop_reason'];
                }
                break;
            case 'message_stop':
                $state['done'] = true;
                break;
            case 'error':
                $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];
                throw new ClaudeApiException(null, is_string($error['type'] ?? null) ? $error['type'] : null, (string) ($error['message'] ?? ''));
        }
    }
}
