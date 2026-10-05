<?php

namespace App\Http\Controllers\Admin;

use App\Content\Fields;
use App\Content\Registry;
use App\Content\Values;
use App\Content\Vars;
use App\Http\Controllers\Controller;
use App\Models\SiteText;
use App\Services\AdminFormat;
use App\Site\Texts;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use JsonException;

/** Website-teksten: overzicht van de pagina's en het bewerkscherm per pagina. */
class SiteTextController extends Controller
{
    /** JSON zoals JSON.stringify in de Next.js-versie (geen \u-codes of \/), zodat opgeslagen teksten gelijk blijven. */
    private const JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS;

    public function index(): View
    {
        // Per pagina: hoeveel teksten zijn aangepast, en wanneer en door wie het laatst.
        $stats = [];
        foreach (self::rows() as $row) {
            $slug = explode('.', $row->key)[0];
            $stat = $stats[$slug] ?? null;
            if (! $stat) {
                $stats[$slug] = ['count' => 1, 'last' => $row->updated_at, 'by' => $row->by];
            } else {
                $stats[$slug]['count']++;
                if ($row->updated_at && (! $stat['last'] || $row->updated_at->gt($stat['last']))) {
                    $stats[$slug]['last'] = $row->updated_at;
                    $stats[$slug]['by'] = $row->by;
                }
            }
        }

        return view('admin.texts.index', [
            'groups' => [
                ['title' => "Pagina's", 'intro' => null, 'pages' => Registry::sitePages()],
                ['title' => "Op meerdere pagina's", 'intro' => 'Pas je hier iets aan, dan verandert het overal waar het op de site staat.', 'pages' => Registry::sharedPages()],
            ],
            'stats' => $stats,
        ]);
    }

    public function edit(string $pagina): View
    {
        $page = Registry::find($pagina) ?? abort(404);

        // Wanneer is elk aangepast veld voor het laatst opgeslagen (voor de tooltip in het bewerkscherm)?
        $prefix = $page['slug'].'.';
        $changedAt = [];
        $last = null;
        foreach (self::rows() as $row) {
            if (! str_starts_with($row->key, $prefix)) {
                continue;
            }
            $changedAt[substr($row->key, strlen($prefix))] = self::stamp($row);
            if (! $last || ($row->updated_at && $row->updated_at->gt($last->updated_at))) {
                $last = $row;
            }
        }

        return view('admin.texts.edit', [
            'page' => $page,
            'values' => Texts::editable($page['slug']),
            'shared' => ['algemeen' => Texts::editable('algemeen'), 'pakketten' => Texts::editable('pakketten')],
            'changedAt' => $changedAt,
            'last' => $last ? self::stamp($last) : null,
            'state' => session('texts_state', []),
        ]);
    }

    /**
     * Slaat de teksten van één pagina op. Een tekst die gelijk is aan de standaardtekst wordt niet bewaard (een eerder
     * aangepaste versie wordt dan verwijderd), zodat de site daar de standaardtekst uit de code blijft volgen.
     */
    public function update(Request $request, string $pagina): RedirectResponse
    {
        $page = Registry::find($pagina);
        if (! $page) {
            return back()->with('texts_state', ['error' => 'Deze pagina bestaat niet (meer).']);
        }
        $to = '/admin/teksten/'.$page['slug'];

        $raw = (string) $request->input('values', '');
        if (strlen($raw) > 500_000) {
            return redirect($to)->with('texts_state', ['error' => 'Dit is te veel tekst om in één keer op te slaan.']);
        }
        try {
            // Objecten als stdClass, zodat {} en [] net als in JavaScript verschillend blijven.
            $input = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return redirect($to)->with('texts_state', ['error' => 'De teksten konden niet worden gelezen. Laad de pagina opnieuw en probeer het nog eens.']);
        }

        ['values' => $values, 'errors' => $errors] = Values::normalizePage($page, $input, Vars::VAR_KEYS);
        $errorCount = count($errors);
        if ($errorCount) {
            return redirect($to)->with('texts_state', [
                'error' => $errorCount === 1
                    ? 'Er is 1 veld dat nog niet klopt. Het staat hieronder in rood.'
                    : "Er zijn {$errorCount} velden die nog niet kloppen. Ze staan hieronder in rood.",
                'fieldErrors' => $errors,
                // Wat er is ingestuurd, zodat het bewerkscherm het weer toont (met de fouten erbij).
                'submitted' => json_decode($raw, true),
            ]);
        }

        $stored = SiteText::query()->where('key', 'like', $page['slug'].'.%')->pluck('value', 'key')->all();
        $changes = [];
        foreach ($values as $s => $fields) {
            foreach ($fields as $f => $value) {
                $key = Fields::fieldKey($page['slug'], $s, $f);
                if (Values::sameValue($value, $page['sections'][$s]['fields'][$f]['default'])) {
                    if (array_key_exists($key, $stored)) {
                        $changes[] = ['key' => $key, 'value' => null];
                    }
                } else {
                    $json = json_encode($value, self::JSON);
                    if (($stored[$key] ?? null) !== $json) {
                        $changes[] = ['key' => $key, 'value' => $json];
                    }
                }
            }
        }

        if ($changes) {
            $adminId = $request->user()->id;
            $now = CarbonImmutable::now('UTC');
            DB::transaction(function () use ($changes, $adminId, $now) {
                foreach ($changes as ['key' => $key, 'value' => $value]) {
                    if ($value === null) {
                        SiteText::query()->whereKey($key)->delete();
                    } else {
                        SiteText::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by_id' => $adminId, 'updated_at' => $now]);
                    }
                }
            });
            // Teksten staan op elke pagina (kop, footer) en ook in Mijn omgeving en het beheer.
            Texts::flush();
        }

        $n = count($changes);

        return redirect($to)->with('texts_state', [
            'success' => $n ? 'Opgeslagen. '.($n === 1 ? '1 tekst is' : "{$n} teksten zijn").' direct bijgewerkt op de website.' : 'Opgeslagen. Er was niets veranderd.',
        ]);
    }

    /** Opgeslagen teksten met de voornaam van wie ze het laatst aanpaste. */
    private static function rows()
    {
        return SiteText::query()
            ->leftJoin('users', 'users.id', '=', 'site_texts.updated_by_id')
            ->get(['site_texts.key', 'site_texts.updated_at', 'users.first_name as by']);
    }

    /** "5 oktober om 16:30 door Steyn" */
    private static function stamp(SiteText $row): string
    {
        return ($row->updated_at ? AdminFormat::dayMonthTime($row->updated_at) : '').($row->by ? " door {$row->by}" : '');
    }
}
