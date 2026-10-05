<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use App\Site\Invitation;
use App\Site\Site;
use App\Site\Texts;
use App\Support\ReferralProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
            return redirect('/contact')->with('contact_success', 'Bedankt!');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'interest' => ['required', Rule::in(array_column(Site::INTERESTS, 'id'))],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Vul je naam in',
            'name.min' => 'Vul je naam in',
            'name.max' => 'Je naam mag maximaal 120 tekens hebben',
            'email.required' => 'Vul een geldig e-mailadres in',
            'email.email' => 'Vul een geldig e-mailadres in',
            'email.max' => 'Vul een geldig e-mailadres in',
            'phone.max' => 'Je telefoonnummer mag maximaal 30 tekens hebben',
            'interest.required' => 'Kies waar je interesse in hebt',
            'interest.in' => 'Kies waar je interesse in hebt',
            'message.max' => 'Je bericht mag maximaal 2000 tekens hebben',
        ]);

        ContactRequest::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => ($data['phone'] ?? null) ?: null,
            'interest' => $data['interest'],
            'message' => ($data['message'] ?? null) ?: null,
        ]);

        return redirect('/contact')->with('contact_success', 'Bedankt voor je aanvraag! Steyn streeft ernaar om binnen 24 uur contact met je op te nemen. Lukt dat niet telefonisch, check dan je mailbox.');
    }

    /** Persoonlijke uitnodigingslink: /r/LISA-7K2Q */
    public function referral(string $code): RedirectResponse
    {
        $code = ReferralProgram::normalizeReferralCode($code);
        if (! $code) {
            return redirect('/online-coaching');
        }

        return redirect('/online-coaching?uitnodiging='.rawurlencode($code))
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
        $pages = [
            '/' => '1', '/personal-training' => '0.9', '/online-coaching' => '0.9', '/contact' => '0.8', '/ademcoaching' => '0.8',
            '/voedingscoaching' => '0.8', '/tarieven' => '0.7', '/over-steyn' => '0.7', '/vriend-uitnodigen' => '0.5', '/privacy' => '0.3',
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($pages as $path => $priority) {
            $xml .= "<url>\n<loc>{$url}{$path}</loc>\n<changefreq>monthly</changefreq>\n<priority>{$priority}</priority>\n</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
