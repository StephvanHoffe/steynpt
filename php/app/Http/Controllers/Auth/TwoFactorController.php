<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Accounts;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

/** Tweede stap van het inloggen: code invullen, of de tweestapsverificatie eerst instellen. */
class TwoFactorController extends Controller
{
    private const EXPIRED = 'Je inlogpoging is verlopen. Log opnieuw in.';

    private const FAILED = 'Er ging iets mis. Log opnieuw in.';

    public function show(): View|RedirectResponse
    {
        $challenge = Accounts::challenge();
        if (! $challenge) {
            return redirect('/inloggen?melding=verlopen');
        }
        $user = $challenge['user'];

        // Net ingesteld (zelfde geheim): de herstelcodes tonen tot het lid op "Verder" klikt.
        if ($this->justSetUp($challenge)) {
            return view('auth.two-factor-setup', ['codes' => $challenge['codes'] ?? []]);
        }
        if ($user->totp_enabled_at) {
            return view('auth.two-factor-code');
        }

        // Nieuw geheim voor deze tussenstap; pas na de eerste juiste code wordt het aan het account gekoppeld.
        $secret = $challenge['pending_secret'];
        if (! $secret) {
            $secret = Totp::generateTotpSecret();
            Accounts::updateChallenge(['pending_secret' => $secret]);
        }

        return view('auth.two-factor-setup', [
            'qr' => 'data:image/svg+xml;charset=utf-8,'.rawurlencode($this->qrSvg(Totp::otpauthUri($secret, $user->email))),
            'secret' => Totp::formatSecret($secret),
            'rawSecret' => $secret,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $challenge = Accounts::challenge();
        if (! $challenge) {
            return self::relogin(self::EXPIRED);
        }
        $user = $challenge['user'];
        if (! $user->totp_enabled_at) {
            return back()->with('error', __('Stel eerst de tweestapsverificatie in. Ververs de pagina.'));
        }
        if (Accounts::codeLocked($user)) {
            return back()->with('error', __(Accounts::LOCKED));
        }

        if (! Accounts::checkCode($user, (string) $request->input('code', ''))) {
            Accounts::registerCodeFailure($user);
            $attempts = $challenge['attempts'] + 1;
            if ($attempts >= Accounts::MAX_CODE_ATTEMPTS) {
                Accounts::clearChallenge();

                return redirect('/inloggen?melding=te-veel-codes');
            }
            Accounts::updateChallenge(['attempts' => $attempts]);

            return back()->with('recovery', $request->boolean('recovery'))
                ->withErrors(['code' => __('Deze code klopt niet. Kijk of de tijd op je telefoon goed staat en probeer de nieuwste code.')]);
        }

        Accounts::clearCodeFailures($user);
        Accounts::login($user);

        return redirect($challenge['next']);
    }

    /** Instellen: bevestigen met de eerste code uit de app, daarna de herstelcodes tonen. */
    public function confirm(Request $request): RedirectResponse
    {
        $challenge = Accounts::challenge();
        if (! $challenge) {
            return self::relogin(self::EXPIRED);
        }
        $user = $challenge['user'];
        if ($user->totp_enabled_at || ! $challenge['pending_secret']) {
            return self::relogin(self::FAILED);
        }
        if (Accounts::codeLocked($user)) {
            return back()->with('error', __(Accounts::LOCKED));
        }

        $step = Totp::verifyTotp($challenge['pending_secret'], (string) $request->input('code', ''));
        if ($step === null) {
            Accounts::registerCodeFailure($user);

            return back()->withErrors(['code' => __('Deze code klopt niet. Scan de QR-code opnieuw en vul de code in die de app nu toont.')]);
        }

        $enabled = User::query()->whereKey($user->id)->whereNull('totp_enabled_at')->update([
            'totp_secret' => Crypt::encryptString($challenge['pending_secret']),
            'totp_enabled_at' => now(),
            'totp_last_step' => $step,
        ]);
        if (! $enabled) {
            return self::relogin(self::FAILED);
        }
        $codes = Accounts::replaceRecoveryCodes($user);
        Accounts::clearCodeFailures($user);
        // Nog niet inloggen: eerst de herstelcodes tonen. De tussenstap blijft staan tot "Verder".
        Accounts::updateChallenge(['codes' => $codes]);

        return redirect('/inloggen/verificatie');
    }

    /** Na het bewaren van de herstelcodes: inloggen en door naar de pagina waar het lid heen wilde. */
    public function finish(): RedirectResponse
    {
        $challenge = Accounts::challenge();
        if (! $challenge) {
            return redirect('/inloggen?melding=verlopen');
        }
        if (! $this->justSetUp($challenge)) {
            return redirect('/inloggen/verificatie');
        }
        Accounts::login($challenge['user']);

        return redirect($challenge['next']);
    }

    /** Melding waarna alleen opnieuw inloggen helpt: de pagina toont dan de knop "Opnieuw inloggen" (ook in het Engels). */
    private static function relogin(string $message): RedirectResponse
    {
        return back()->with('error', __($message))->with('relogin', true);
    }

    private function justSetUp(array $challenge): bool
    {
        $user = $challenge['user'];

        return (bool) ($user->totp_enabled_at && $challenge['pending_secret'] && $challenge['pending_secret'] === $user->totp_secret);
    }

    private function qrSvg(string $content): string
    {
        $style = new RendererStyle(176, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(17, 19, 21)));
        $writer = new Writer(new ImageRenderer($style, new SvgImageBackEnd));

        return $writer->writeString($content, 'UTF-8', ErrorCorrectionLevel::M());
    }
}
