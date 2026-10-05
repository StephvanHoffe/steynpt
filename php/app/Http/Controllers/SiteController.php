<?php

namespace App\Http\Controllers;

use App\Content\English;
use App\Content\Registry;
use App\Models\ContactRequest;
use App\Models\SiteText;
use App\Site\Invitation;
use App\Site\Locale;
use App\Site\Site;
use App\Site\Texts;
use App\Support\ReferralProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ReflectionClass;
use Throwable;

/** De openbare pagina's van de website. De teksten komen uit Website-teksten (App\Site\Texts). */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', ['t' => Texts::get('home'), 'shared' => Texts::get('algemeen')]);
    }

    public function onlineCoaching(Request $request): View
    {
        return view('site.online-coaching', [
            't' => Texts::get('online-coaching'),
            'shared' => Texts::get('algemeen'),
            'invitation' => Invitation::resolve($request->query('uitnodiging')),
        ]);
    }

    /** Pagina's zonder eigen logica: dezelfde naam voor de teksten en het sjabloon. */
    public function page(string $slug): View
    {
        return view("site.{$slug}", ['t' => Texts::get($slug), 'shared' => Texts::get('algemeen')]);
    }

    public function contact(Request $request): View
    {
        $onderwerp = $request->query('onderwerp');

        return view('site.contact', [
            't' => Texts::get('contact'),
            'shared' => Texts::get('algemeen'),
            'defaultInterest' => is_string($onderwerp) && Site::interestLabel($onderwerp) ? $onderwerp : '',
        ]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        // Honeypot-veld: echte bezoekers zien dit veld niet.
        if ((string) $request->input('website', '') !== '') {
            return redirect(Locale::path('/contact'))->with('contact_success', __('Bedankt!'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'interest' => ['required', Rule::in(array_column(Site::INTERESTS, 'id'))],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => __('Vul je naam in'),
            'name.min' => __('Vul je naam in'),
            'name.max' => __('Je naam mag maximaal 120 tekens hebben'),
            'email.required' => __('Vul een geldig e-mailadres in'),
            'email.email' => __('Vul een geldig e-mailadres in'),
            'email.max' => __('Vul een geldig e-mailadres in'),
            'phone.max' => __('Je telefoonnummer mag maximaal 30 tekens hebben'),
            'interest.required' => __('Kies waar je interesse in hebt'),
            'interest.in' => __('Kies waar je interesse in hebt'),
            'message.max' => __('Je bericht mag maximaal 2000 tekens hebben'),
        ]);

        ContactRequest::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => ($data['phone'] ?? null) ?: null,
            'interest' => $data['interest'],
            'message' => ($data['message'] ?? null) ?: null,
        ]);

        return redirect(Locale::path('/contact'))->with('contact_success', __('Bedankt voor je aanvraag! Steyn streeft ernaar om binnen 24 uur contact met je op te nemen. Lukt dat niet telefonisch, check dan je mailbox.'));
    }

    /** Persoonlijke uitnodigingslink: /r/LISA-7K2Q */
    public function referral(string $code): RedirectResponse
    {
        $code = ReferralProgram::normalizeReferralCode($code);
        if (! $code) {
            return redirect(Locale::path('/online-coaching'));
        }

        // In de laatst gekozen taal (de cookie bepaalt de taal van /r/…).
        return redirect(Locale::path('/online-coaching').'?uitnodiging='.rawurlencode($code))
            ->withCookie(cookie(Invitation::COOKIE, $code, Invitation::COOKIE_MINUTES));
    }

    public function robots(): Response
    {
        $url = Site::url();
        $body = "User-Agent: *\nAllow: /\nDisallow: /account\nDisallow: /admin\nDisallow: /r/\n\nSitemap: {$url}/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $url = Site::url();
        // Zelfde adressen als de canonical-links op de pagina's; inloggen en registreren staan op noindex.
        // Elke pagina staat er in het Nederlands en in het Engels in, met de andere taal als hreflang.
        $priorities = [
            '/' => '1', '/personal-training' => '0.9', '/online-coaching' => '0.9', '/contact' => '0.8', '/ademcoaching' => '0.8',
            '/voedingscoaching' => '0.8', '/tarieven' => '0.7', '/over-steyn' => '0.7', '/vriend-uitnodigen' => '0.5', '/privacy' => '0.3',
        ];
        $lastModified = self::lastModified();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($priorities as $path => $priority) {
            $alternates = ['nl' => $url.$path, 'en' => $url.Locale::path($path, 'en')];
            $links = '';
            foreach ([...$alternates, 'x-default' => $alternates['nl']] as $lang => $href) {
                $links .= "<xhtml:link rel=\"alternate\" hreflang=\"{$lang}\" href=\"{$href}\"/>\n";
            }
            foreach ($alternates as $lang => $loc) {
                $time = $lastModified[$lang][$path] ?? null;
                $lastmod = $time ? '<lastmod>'.date('Y-m-d', $time)."</lastmod>\n" : '';
                $xml .= "<url>\n<loc>{$loc}</loc>\n{$lastmod}<changefreq>monthly</changefreq>\n<priority>{$priority}</priority>\n{$links}</url>\n";
            }
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Per taal en pagina het moment van de laatste wijziging (voor <lastmod>): het sjabloon, de standaardteksten of een
     * tekst die Steyn in het beheer heeft aangepast. Teksten van 'Op elke pagina' en 'Prijzen en pakketten' tellen
     * overal mee. Engelse teksten staan onder "en-<pagina>" (App\Content\English).
     *
     * @return array<string, array<string, int>> taal => [Nederlands pad => Unix-tijd]
     */
    private static function lastModified(): array
    {
        $stored = [];
        try {
            foreach (SiteText::query()->get(['key', 'updated_at']) as $text) {
                $slug = strstr((string) $text->key, '.', true) ?: (string) $text->key;
                $time = $text->updated_at?->getTimestamp() ?? 0;
                $stored[$slug] = max($stored[$slug] ?? 0, $time);
            }
        } catch (Throwable) {
            // Zonder database: alleen de bestanden.
        }

        $times = [];
        foreach (Registry::SITE_PAGES as $class) {
            $page = $class::page();
            $view = resource_path("views/site/{$page['slug']}.blade.php");
            $files = max(
                is_file($view) ? (int) filemtime($view) : 0,
                (int) filemtime((string) (new ReflectionClass($class))->getFileName()),
            );
            foreach (['nl' => '', 'en' => English::PREFIX] as $lang => $prefix) {
                $texts = max($stored[$prefix.$page['slug']] ?? 0, $stored[$prefix.'algemeen'] ?? 0, $stored[$prefix.'pakketten'] ?? 0);
                // Engelse teksten en prijzen: ook wijzigingen in de Nederlandse gedeelde velden tellen mee.
                $times[$lang][$page['path']] = max($files, $texts, $lang === 'en' ? (int) filemtime((new ReflectionClass(English::class))->getFileName()) : 0, $stored['pakketten'] ?? 0);
            }
        }

        return $times;
    }
}
