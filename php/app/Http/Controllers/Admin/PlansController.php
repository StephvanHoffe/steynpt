<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Plan;
use App\Models\User;
use App\Services\AdminLabels;
use App\Services\Plans\Generator;
use App\Services\Plans\PipelineServer;
use App\Services\Plans\PlanView;
use App\Services\Plans\Schedule;
use App\Site\Site;
use App\Site\Texts;
use App\Support\Agenda;
use App\Support\Intake as IntakeRules;
use App\Support\Js;
use App\Support\Plans\Pipeline;
use App\Support\Plans\PlanSchema;
use App\View\PlanLabels;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use JsonException;

/**
 * Trainings- en voedingsschema's in het beheer: overzicht per fase, nieuw schema, controleren, bewerken,
 * publiceren of inplannen (PlanOverview, NewPlan, PlanDetail en actions/plans.ts in de Next.js-versie).
 */
class PlansController extends Controller
{
    /** Maximale omvang van de inhoud van een schema (tekens JSON). */
    private const MAX_PLAN_BYTES = 200_000;

    /** Schematype van het onderdeel (route-standaardwaarde 'type' in routes/schemas.php). */
    private static function type(Request $request): string
    {
        return $request->route('type');
    }

    // ---------------------------------------------------------------------------
    // Overzicht

    public function index(Request $request): View
    {
        $type = self::type($request);
        $section = AdminLabels::PLAN_SECTION[$type];
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $groupIds = array_column(Pipeline::STAGE_GROUPS, 'id');
        $fase = in_array($request->query('fase'), $groupIds, true) ? $request->query('fase') : null;
        $pipeline = PipelineServer::load();
        $today = $pipeline['today'];

        $needle = mb_strtolower($q);
        $searched = array_values(array_filter($pipeline['rows'][$type], fn (array $r) => $needle === ''
            || str_contains(mb_strtolower("{$r['member']['firstName']} {$r['member']['lastName']} {$r['member']['email']}"), $needle)));
        $groupOf = fn (array $r) => Pipeline::STAGES[$r['stage']]['group'];
        $rows = $fase ? array_values(array_filter($searched, fn (array $r) => $groupOf($r) === $fase)) : $searched;
        $counts = array_fill_keys($groupIds, 0);
        foreach ($searched as $r) {
            $counts[$groupOf($r)]++;
        }
        $href = function (?string $group) use ($section, $q) {
            $query = http_build_query(array_filter(['fase' => $group, 'q' => $q !== '' ? $q : null]));

            return $section['href'].($query !== '' ? "?{$query}" : '');
        };
        $origin = self::origin($request);
        $steps = [];
        foreach ($rows as $r) {
            $steps[$r['member']['id']] = self::nextStep($r, $type, $origin);
        }

        return view('admin.schemas.index', [
            'type' => $type,
            'section' => $section,
            'q' => $q,
            'fase' => $fase,
            'today' => $today,
            'rows' => $rows,
            'total' => count($searched),
            'counts' => $counts,
            'href' => $href,
            'steps' => $steps,
        ]);
    }

    /** Wat is de logische volgende stap voor deze klant? */
    private static function nextStep(array $row, string $type, string $origin): ?array
    {
        $section = AdminLabels::PLAN_SECTION[$type];
        $member = $row['member'];

        return match ($row['stage']) {
            'eerste' => ['label' => 'Schema maken', 'href' => AdminLabels::newPlanHref($type, $member['id']), 'primary' => true],
            'verlopen' => ['label' => 'Nieuw schema', 'href' => AdminLabels::newPlanHref($type, $member['id']), 'primary' => true],
            'binnenkort' => ['label' => 'Nu voorbereiden', 'href' => AdminLabels::newPlanHref($type, $member['id']), 'primary' => false],
            'controleren' => ['label' => 'Controleren', 'href' => AdminLabels::planHref($type, $row['open']['id']), 'primary' => true],
            'mislukt' => ['label' => 'Opnieuw proberen', 'href' => AdminLabels::planHref($type, $row['open']['id']), 'primary' => true],
            'bezig' => ['label' => 'Bekijken', 'href' => AdminLabels::planHref($type, $row['open']['id']), 'primary' => false],
            'gepland' => ['label' => 'Ingepland bekijken', 'href' => AdminLabels::planHref($type, $row['scheduled']['id']), 'primary' => false],
            'intake' => [
                'label' => 'Herinnering mailen',
                'href' => 'mailto:'.$member['email'].'?subject='.Js::encodeURIComponent('Je intake voor je schema')
                    .'&body='.Js::encodeURIComponent("Hoi {$member['firstName']},\n\nWil je je intake invullen? Dan maak ik je {$section['one']} op maat.\n\n{$origin}/account/intake\n\nGroet,\nSteyn"),
                'primary' => false,
            ],
            default => $row['current'] ? ['label' => 'Bekijken', 'href' => AdminLabels::planHref($type, $row['current']['id']), 'primary' => false] : null,
        };
    }

    /** Adres van de site voor links in e-mails (APP_URL, of anders het adres van dit verzoek). */
    private static function origin(Request $request): string
    {
        $url = (string) config('app.url');

        return $url !== '' && ! str_contains($url, 'localhost') ? Site::url() : $request->getSchemeAndHttpHost();
    }

    // ---------------------------------------------------------------------------
    // Nieuw schema

    public function create(Request $request): View
    {
        $type = self::type($request);
        $section = AdminLabels::PLAN_SECTION[$type];
        $lid = is_string($request->query('lid')) ? $request->query('lid') : null;
        $members = User::query()->where('role', 'member')->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'plan', 'coaching_status', 'locale']);
        $pipeline = PipelineServer::load();
        $member = $lid !== null ? $members->firstWhere('id', $lid) : null;

        // Wie op een schema wacht staat bovenaan de keuzelijst.
        $stageBy = [];
        foreach ($pipeline['rows'][$type] as $r) {
            $stageBy[$r['member']['id']] = $r['stage'];
        }
        $groupOf = fn (User $m) => isset($stageBy[$m->id]) ? Pipeline::STAGES[$stageBy[$m->id]]['group'] : null;
        $inGroup = fn (string $group) => $members->filter(fn (User $m) => $groupOf($m) === $group)
            ->map(fn (User $m) => ['id' => $m->id, 'name' => $m->fullName(), 'note' => mb_strtolower(Pipeline::STAGES[$stageBy[$m->id]]['label'])])->values()->all();
        $groups = [
            ['label' => 'Wacht op nieuw schema', 'members' => $inGroup('wacht')],
            ['label' => 'Komende week', 'members' => $inGroup('binnenkort')],
            ['label' => 'Overige klanten', 'members' => $members->filter(fn (User $m) => ! in_array($groupOf($m), ['wacht', 'binnenkort'], true))
                ->map(fn (User $m) => ['id' => $m->id, 'name' => $m->fullName()])->values()->all()],
        ];

        $details = null;
        if ($member) {
            $one = PipelineServer::load($member->id);
            $today = $one['today'];
            $row = $one['rows'][$type][0] ?? null;
            $intakeRow = Intake::query()->find($member->id);
            [$intake] = IntakeRules::validate(is_array($intakeRow?->data) ? $intakeRow->data : []);
            $ai = Generator::aiConfigured() && $intake !== null
                ? ['available' => true]
                : ['available' => false, 'reason' => $intake === null
                    ? 'Kan nog niet: de klant heeft de intake nog niet ingevuld.'
                    : 'AI staat uit: stel ANTHROPIC_API_KEY in om concepten te laten maken.'];
            // Standaard start het nieuwe schema waar het huidige ophoudt (of op de al ingeplande dag).
            $currentDue = $row['currentDueOn'] ?? null;
            $defaultStart = $row['scheduled']['startsOn'] ?? ($currentDue && $currentDue > $today ? $currentDue : $today);

            $details = [
                'row' => $row,
                'today' => $today,
                'intake' => $intake,
                'intakeUpdatedAt' => $intakeRow?->updated_at,
                'planName' => Texts::onlinePlanName($member->plan),
                'ai' => $ai,
                'defaultStart' => $defaultStart,
                'hasCurrent' => (bool) (($row['current'] ?? null) || ($row['scheduled'] ?? null)),
            ];
        }

        return view('admin.schemas.create', [
            'type' => $type,
            'section' => $section,
            'lid' => $lid,
            'member' => $member,
            'groups' => $groups,
            'details' => $details,
        ]);
    }

    /** Nieuw schema voor een klant (AI-concept, kopie van het huidige of leeg), eventueel met een startdatum in de toekomst. */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->input('userId');
        $planType = $request->input('type');
        $method = $request->input('method') ?? 'leeg';
        $instruction = $request->input('instruction');
        $language = $request->input('language');
        $valid = is_string($userId) && $userId !== '' && in_array($planType, Plan::TYPES, true)
            && in_array($method, ['ai', 'huidig', 'leeg'], true)
            && ($instruction === null || (is_string($instruction) && mb_strlen($instruction) <= 1500))
            && ($language === null || in_array($language, ['nl', 'en'], true))
            && User::query()->whereKey($userId)->exists();
        if (! $valid) {
            return back();
        }
        $today = Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];
        $startsOn = self::futureDay($request->input('startsOn'), $today);

        if ($method === 'ai') {
            $id = Generator::createPlanJob($userId, $planType, $instruction, $startsOn, $language);

            return redirect(AdminLabels::planHref($planType, $id));
        }

        $intakeRow = Intake::query()->find($userId);
        [$intake] = IntakeRules::validate(is_array($intakeRow?->data) ? $intakeRow->data : []);
        $latest = $method === 'huidig'
            ? Plan::query()->where('user_id', $userId)->where('type', $planType)->whereIn('status', ['gepland', 'gepubliceerd'])
                ->orderByDesc('created_at')->orderByDesc('id')->first(['content'])
            : null;
        [$copy] = $latest ? PlanSchema::parse($planType, $latest->content) : [null];
        $content = $copy ?? ($planType === 'training'
            ? PlanSchema::emptyTrainingPlan($intake ? (int) $intake['trainingDays'] : 3)
            : PlanSchema::emptyNutritionPlan($intake ? array_intersect_key(IntakeRules::estimateTargets($intake), array_flip(['calories', 'protein', 'carbs', 'fat'])) : null));

        $id = DB::transaction(function () use ($userId, $planType, $content, $startsOn) {
            Plan::query()->where('user_id', $userId)->where('type', $planType)->whereIn('status', ['genereren', 'concept', 'fout'])
                ->update(['status' => 'vervangen', 'updated_at' => CarbonImmutable::now('UTC')]);

            return Plan::query()->create([
                'user_id' => $userId,
                'type' => $planType,
                'status' => 'concept',
                'source' => 'handmatig',
                'content' => $content,
                'starts_on' => $startsOn,
            ])->id;
        });
        PipelineServer::flush();

        return redirect(AdminLabels::planHref($planType, $id));
    }

    /** Startdatum uit een formulier: een dag na vandaag, anders null (= gaat in zodra het gepubliceerd is). */
    private static function futureDay(mixed $value, string $today): ?string
    {
        return Agenda::isValidDay($value) && $value > $today ? $value : null;
    }

    // ---------------------------------------------------------------------------
    // Controleren en bewerken

    /** Oude adressen (/admin/schemas/{id}) verwijzen door naar het onderdeel van het juiste type. */
    public function legacy(string $id): RedirectResponse
    {
        $plan = Plan::query()->find($id, ['id', 'type']);
        abort_unless($plan, 404);

        return redirect(AdminLabels::planHref($plan->type, $plan->id));
    }

    public function show(Request $request, string $id): View|RedirectResponse
    {
        $type = self::type($request);
        Schedule::activateDuePlans();
        $plan = Plan::query()->find($id);
        abort_unless($plan, 404);
        if ($plan->type !== $type) {
            return redirect(AdminLabels::planHref($plan->type, $plan->id));
        }
        $member = User::query()->find($plan->user_id);
        abort_unless($member, 404);

        $intakeRow = Intake::query()->find($plan->user_id);
        $versions = Plan::query()->where('user_id', $plan->user_id)->where('type', $type)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get(['id', 'status', 'source', 'created_at', 'published_at', 'updated_at', 'starts_on']);
        $pipeline = PipelineServer::load($plan->user_id);
        $today = $pipeline['today'];
        $row = $pipeline['rows'][$type][0] ?? null;
        [$intake] = IntakeRules::validate(is_array($intakeRow?->data) ? $intakeRow->data : []);
        [$content] = $plan->content !== null ? PlanSchema::parse($plan->type, $plan->content) : [null];
        $stuck = PlanLabels::isStuck($plan->status, $plan->updated_at);
        $live = $plan->status === 'gepubliceerd';
        $durationWeeks = $content['durationWeeks'] ?? null;
        $startsOn = $live
            ? ($plan->starts_on ?? ($plan->published_at ? Agenda::zonedParts($plan->published_at)['day'] : $today))
            : ($plan->starts_on && $plan->starts_on > $today ? $plan->starts_on : $today);

        return view('admin.schemas.show', [
            'type' => $type,
            'section' => AdminLabels::PLAN_SECTION[$type],
            'plan' => $plan,
            'member' => $member,
            'row' => $row,
            'today' => $today,
            'versions' => $versions,
            'intake' => $intake,
            'intakeUpdatedAt' => $intakeRow?->updated_at,
            'content' => $content,
            'stuck' => $stuck,
            'status' => $stuck ? ['label' => 'Vastgelopen', 'tone' => 'bg-danger/10 text-danger'] : PlanLabels::STATUS[$plan->status],
            'intakeChanged' => $intakeRow && $plan->status !== 'vervangen' && $intakeRow->updated_at && $plan->created_at && $intakeRow->updated_at->gt($plan->created_at),
            'edited' => $plan->source === 'ai' && $plan->ai_draft !== null && ! PlanView::sameContent($plan->ai_draft, $plan->content),
            'editable' => in_array($plan->status, ['concept', 'gepland', 'gepubliceerd'], true) && $content !== null,
            'live' => $live,
            'startsOn' => $startsOn,
            'renewOn' => $plan->renew_on ?? ($live && ($row['currentDueOn'] ?? null) ? $row['currentDueOn'] : Pipeline::defaultRenewOn($type, $startsOn, is_numeric($durationWeeks) ? $durationWeeks + 0 : null)),
            'futureStart' => $plan->starts_on && $plan->starts_on > $today ? $plan->starts_on : null,
            'aiEnabled' => Generator::aiConfigured(),
        ]);
    }

    /** Opslaan van Steyns bewerkingen, en optioneel publiceren (nu) of inplannen (op de startdatum). */
    public function update(Request $request, string $id): RedirectResponse
    {
        $intent = $request->input('intent') === 'publiceren' ? 'publiceren' : 'opslaan';
        $raw = (string) $request->input('content', '');
        $fail = fn (string $message) => back()->withInput($request->only(['content', 'startsOn', 'renewOn']))->with('plan_error', $message);
        if (mb_strlen($raw) > self::MAX_PLAN_BYTES) {
            return $fail('Ongeldig schema.');
        }

        $plan = Plan::query()->find($id);
        if (! $plan || ! in_array($plan->status, ['concept', 'gepland', 'gepubliceerd'], true)) {
            return $fail('Dit schema kan niet (meer) bewerkt worden. Ververs de pagina.');
        }
        try {
            $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $fail('Het schema kon niet gelezen worden.');
        }
        [$content] = PlanSchema::parse($plan->type, $json);
        if ($content === null) {
            return $fail('Het schema is niet compleet. Controleer of alle getallen zijn ingevuld.');
        }
        if ($plan->type === 'training') {
            $content['daysPerWeek'] = count($content['days']);
        }

        $now = CarbonImmutable::now('UTC');
        $today = Agenda::zonedParts($now)['day'];
        $live = $plan->status === 'gepubliceerd';
        // De startdatum ligt vast zodra de klant het schema ziet; daarvoor kan hij nog schuiven.
        $startsOn = $live ? $plan->starts_on : self::futureDay($request->input('startsOn'), $today);
        $startDay = $live ? ($plan->starts_on ?? ($plan->published_at ? Agenda::zonedParts($plan->published_at)['day'] : $today)) : ($startsOn ?? $today);
        $schedule = ! $live && $startsOn !== null && ($intent === 'publiceren' || $plan->status === 'gepland');
        $publishNow = ! $live && $startsOn === null && ($intent === 'publiceren' || $plan->status === 'gepland');

        // Wanneer de klant toe is aan een nieuw schema: na de start (en na vandaag) als je hem kiest of publiceert.
        $requested = $request->input('renewOn');
        $renewOn = Agenda::isValidDay($requested) ? $requested : $plan->renew_on;
        $minRenew = $startDay > $today ? $startDay : $today;
        if ($renewOn && $renewOn <= $minRenew && ($intent === 'publiceren' || $renewOn !== $plan->renew_on)) {
            return $fail($startDay > $today ? 'Kies bij “Nieuw schema op” een datum na de startdatum.' : 'Kies bij “Nieuw schema op” een datum na vandaag.');
        }
        if ($renewOn && $renewOn > Agenda::addDays($startDay, 366)) {
            return $fail('Kies voor het volgende schema een datum binnen een jaar na de start.');
        }
        if (! $renewOn && ($schedule || $publishNow)) {
            $renewOn = Pipeline::defaultRenewOn($plan->type, $startDay, $plan->type === 'training' ? $content['durationWeeks'] : null);
        }

        $others = fn () => Plan::query()->where('user_id', $plan->user_id)->where('type', $plan->type)->whereKeyNot($plan->id);
        if ($schedule) {
            // Er is steeds één ingepland schema; het huidige blijft zichtbaar tot de startdatum.
            DB::transaction(function () use ($others, $plan, $content, $renewOn, $startsOn, $now) {
                $others()->where('status', 'gepland')->update(['status' => 'vervangen', 'updated_at' => $now]);
                $plan->forceFill(['content' => $content, 'renew_on' => $renewOn, 'starts_on' => $startsOn, 'status' => 'gepland', 'published_at' => null, 'updated_at' => $now])->save();
            });
        } elseif ($publishNow || ($live && $intent === 'publiceren')) {
            // Een ingepland volgend schema blijft staan en neemt het op zijn startdatum over.
            DB::transaction(function () use ($others, $plan, $content, $renewOn, $live, $today, $now) {
                $others()->where('status', 'gepubliceerd')->update(['status' => 'vervangen', 'updated_at' => $now]);
                $plan->forceFill([
                    'content' => $content,
                    'renew_on' => $renewOn,
                    'starts_on' => $live ? $plan->starts_on : $today,
                    'status' => 'gepubliceerd',
                    'published_at' => $now,
                    'updated_at' => $now,
                ])->save();
            });
        } else {
            $plan->forceFill(['content' => $content, 'renew_on' => $renewOn, 'starts_on' => $startsOn, 'updated_at' => $now])->save();
        }
        PipelineServer::flush();

        if ($schedule) {
            $success = ($intent === 'publiceren' ? 'Ingepland' : 'Opgeslagen').'. De klant ziet dit schema vanaf '.PlanLabels::formatPlanDayLong($startsOn).' in Mijn omgeving.';
        } elseif ($publishNow || ($live && $intent === 'publiceren')) {
            $success = 'Gepubliceerd. De klant ziet dit schema nu in Mijn omgeving.';
        } elseif ($live) {
            $success = 'Opgeslagen. De klant ziet de wijzigingen direct.';
        } else {
            $success = 'Concept opgeslagen.';
        }

        return redirect(AdminLabels::planHref($plan->type, $plan->id))->with('plan_success', $success);
    }

    /** Ingepland schema terugzetten naar concept: de klant krijgt het dan niet op de startdatum. */
    public function unschedule(Request $request, string $id): RedirectResponse
    {
        $type = self::type($request);
        Plan::query()->whereKey($id)->where('status', 'gepland')->update(['status' => 'concept', 'updated_at' => CarbonImmutable::now('UTC')]);
        PipelineServer::flush();
        $plan = Plan::query()->find($id, ['id', 'type']);

        return redirect($plan ? AdminLabels::planHref($plan->type, $plan->id) : AdminLabels::PLAN_SECTION[$type]['href']);
    }

    /** "Voorbeeld voor de klant" in de editor: de (nog niet opgeslagen) inhoud zoals de klant hem ziet. */
    public function preview(Request $request, string $id): Response
    {
        $plan = Plan::query()->find($id, ['id', 'type']);
        abort_unless($plan, 404);
        // De ruwe JSON gebruiken: de standaard-middleware zou lege teksten in null veranderen.
        $body = json_decode($request->getContent(), true);
        [$content] = is_array($body) && is_array($body['content'] ?? null) ? PlanSchema::parse($plan->type, $body['content']) : [null];
        if ($content === null) {
            return response('Het schema is niet compleet. Controleer of alle getallen zijn ingevuld.', 422);
        }
        $tag = $plan->type === 'training' ? '<x-plans.training-view :plan="$plan" />' : '<x-plans.nutrition-view :plan="$plan" />';

        return response(Blade::render($tag, ['plan' => $content]));
    }
}
