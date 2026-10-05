<?php

namespace App\Services\Plans;

use App\Models\Intake;
use App\Models\Plan;
use App\Models\User;
use App\Support\Agenda;
use App\Support\Plans\Pipeline;
use App\View\PlanLabels;
use Carbon\CarbonImmutable;
use WeakMap;

/** Overzicht van de schema's per klant en type, voor het beheer (zijbalk, overzicht, schema-pagina's en ledendetail). */
final class PipelineServer
{
    /**
     * Alle klanten per schematype met hun fase, meest dringende eerst. Met $userId alleen dat lid (ook als het buiten het overzicht valt).
     * Eén keer per verzoek berekend: de zijbalk en de pagina delen hetzelfde resultaat.
     *
     * @return array{today: string, rows: array{training: list<array>, voeding: list<array>}, counts: array{training: array, voeding: array}}
     */
    public static function load(?string $userId = null): array
    {
        // Per applicatie-instantie onthouden: in de tests draait elke test in een nieuwe instantie binnen hetzelfde proces.
        self::$cache ??= new WeakMap;
        $app = app();
        $entries = self::$cache[$app] ?? [];
        $key = $userId ?? '*';
        if (! array_key_exists($key, $entries)) {
            $entries[$key] = self::compute($userId);
            self::$cache[$app] = $entries;
        }

        return $entries[$key];
    }

    /** Na een wijziging aan schema's binnen hetzelfde verzoek opnieuw laten berekenen. */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @var WeakMap<object, array<string, array>>|null */
    private static ?WeakMap $cache = null;

    private static function compute(?string $userId): array
    {
        Schedule::activateDuePlans();
        $today = Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];

        $members = User::query()->when($userId, fn ($q) => $q->whereKey($userId), fn ($q) => $q->where('role', 'member'))
            ->get(['id', 'first_name', 'last_name', 'email', 'plan', 'coaching_status']);
        $planRows = Plan::query()->when($userId, fn ($q) => $q->where('user_id', $userId))->where('status', '!=', 'vervangen')
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'user_id', 'type', 'status', 'source', 'created_at', 'updated_at', 'published_at', 'renew_on', 'starts_on', 'content']);
        $wantsBy = [];
        foreach (Intake::query()->when($userId, fn ($q) => $q->where('user_id', $userId))->get(['user_id', 'data']) as $intake) {
            $wants = $intake->data['wants'] ?? [];
            $wantsBy[$intake->user_id] = is_array($wants) ? array_values(array_filter($wants, fn ($w) => in_array($w, Plan::TYPES, true))) : [];
        }

        // Per lid en type: het gepubliceerde schema, het ingeplande en het nieuwste openstaande concept.
        $byKey = [];
        foreach ($planRows as $p) {
            $slot = match ($p->status) {
                'gepubliceerd' => 'current',
                'gepland' => 'scheduled',
                default => 'open',
            };
            $byKey["{$p->user_id}:{$p->type}"][$slot] = $p;
        }

        $result = ['training' => [], 'voeding' => []];
        foreach (Plan::TYPES as $type) {
            foreach ($members as $m) {
                $entry = $byKey["{$m->id}:{$type}"] ?? [];
                $current = $entry['current'] ?? null;
                $open = $entry['open'] ?? null;
                $scheduled = $entry['scheduled'] ?? null;
                $duration = $current?->content['durationWeeks'] ?? null;
                $input = [
                    'type' => $type,
                    'coachingStatus' => $m->coaching_status,
                    'wants' => $wantsBy[$m->id] ?? null,
                    'current' => $current?->published_at ? [
                        'publishedDay' => Agenda::zonedParts($current->published_at)['day'],
                        'renewOn' => $current->renew_on,
                        'durationWeeks' => is_numeric($duration) ? $duration + 0 : null,
                    ] : null,
                    'open' => $open && in_array($open->status, ['genereren', 'concept', 'fout'], true)
                        ? ['status' => $open->status, 'stuck' => PlanLabels::isStuck($open->status, $open->updated_at)]
                        : null,
                    'scheduled' => $scheduled?->starts_on ? ['startsOn' => $scheduled->starts_on] : null,
                ];
                if (! $userId && ! Pipeline::inPipeline($input)) {
                    continue;
                }
                $stage = Pipeline::planStage($input, $today);
                $title = $current?->content['title'] ?? null;
                $result[$type][] = [
                    'member' => ['id' => $m->id, 'firstName' => $m->first_name, 'lastName' => $m->last_name, 'email' => $m->email, 'plan' => $m->plan, 'coachingStatus' => $m->coaching_status],
                    'type' => $type,
                    'stage' => $stage['stage'],
                    'dueOn' => $stage['dueOn'],
                    'currentDueOn' => $input['current'] ? Pipeline::dueOn($type, $input['current']) : null,
                    'hasIntake' => array_key_exists($m->id, $wantsBy),
                    'current' => $current?->published_at ? ['id' => $current->id, 'title' => is_string($title) ? $title : null, 'publishedAt' => $current->published_at] : null,
                    'open' => $input['open'] && $open ? ['id' => $open->id, 'status' => $input['open']['status'], 'source' => $open->source, 'createdAt' => $open->created_at] : null,
                    'scheduled' => $input['scheduled'] && $scheduled ? ['id' => $scheduled->id, 'startsOn' => $input['scheduled']['startsOn']] : null,
                ];
            }
            usort($result[$type], fn (array $a, array $b) => Pipeline::compareByUrgency(
                $a + ['name' => "{$a['member']['firstName']} {$a['member']['lastName']}"],
                $b + ['name' => "{$b['member']['firstName']} {$b['member']['lastName']}"],
            ));
        }

        return [
            'today' => $today,
            'rows' => $result,
            'counts' => ['training' => Pipeline::countGroups($result['training']), 'voeding' => Pipeline::countGroups($result['voeding'])],
        ];
    }
}
