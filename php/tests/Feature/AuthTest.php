<?php

namespace Tests\Feature;

use App\Auth\Accounts;
use App\Models\RecoveryCode;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Registreren en inloggen met tweestapsverificatie, herstelcodes en de vriendenlink. */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/registreren', [
            'plan' => 'online-pro', 'firstName' => 'Anna', 'lastName' => 'Veilig', 'email' => 'Anna@Example.com',
            'goal' => 'fitter', 'password' => 'geheim123!', 'terms' => 'on', ...$extra,
        ]);
    }

    public function test_registreren_daarna_eerst_de_app_koppelen(): void
    {
        $this->register()->assertRedirect('/inloggen/verificatie');
        $user = User::query()->sole();
        $this->assertSame('anna@example.com', $user->email);
        $this->assertSame('aangevraagd', $user->coaching_status);
        $this->assertNull($user->totp_enabled_at);
        $this->assertGuest();

        $page = $this->get('/inloggen/verificatie')->assertOk()->assertSee('Beveilig je account')->assertSee('data:image/svg+xml', false);
        preg_match('/data-totp-secret="([A-Z2-7]{32})"/', $page->getContent(), $m);
        $secret = $m[1];

        $this->post('/inloggen/verificatie/instellen', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/inloggen/verificatie/instellen', ['code' => Totp::totp($secret)])->assertRedirect('/inloggen/verificatie');
        $this->assertGuest();
        $this->get('/inloggen/verificatie')->assertOk()->assertSee('Herstelcodes');
        $this->assertSame(8, RecoveryCode::query()->count());

        $this->post('/inloggen/verificatie/klaar')->assertRedirect('/account?welkom=1');
        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertOk();
    }

    public function test_registreren_controleert_velden_en_dubbel_e_mailadres(): void
    {
        $this->from('/registreren')->post('/registreren', ['email' => 'geen-adres'])->assertRedirect('/registreren')
            ->assertSessionHasErrors(['firstName', 'email', 'password', 'terms']);
        User::factory()->create(['email' => 'anna@example.com']);
        $this->from('/registreren')->register()->assertRedirect('/registreren');
        $this->assertSame(1, User::query()->count());
    }

    public function test_vriendenlink_koppelt_de_uitnodiger(): void
    {
        $lisa = User::factory()->create(['referral_code' => 'LISA-AB12']);
        $this->get('/r/lisa-ab12')->assertRedirect('/online-coaching?uitnodiging=LISA-AB12')->assertCookie('steynpt_ref');
        $this->withCookie('steynpt_ref', 'LISA-AB12')->get('/registreren')->assertSee('value="LISA-AB12"', false);
        $this->register(['referralCode' => 'lisa-ab12'])->assertRedirect('/inloggen/verificatie');
        $this->assertSame($lisa->id, User::query()->where('email', 'anna@example.com')->sole()->referred_by_id);
    }

    public function test_inloggen_met_code_en_geen_hergebruik(): void
    {
        $user = User::factory()->create(['email' => 'tom@example.com']);
        $this->from('/inloggen')->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'fout'])
            ->assertRedirect('/inloggen')->assertSessionHas('error', 'E-mailadres of wachtwoord klopt niet.');
        $this->post('/inloggen', ['email' => 'TOM@example.com', 'password' => 'geheim123!', 'next' => '/account/agenda'])->assertRedirect('/inloggen/verificatie');
        $this->assertGuest();

        $code = Totp::totp($user->totp_secret);
        $this->post('/inloggen/verificatie', ['code' => $code])->assertRedirect('/account/agenda');
        $this->assertAuthenticatedAs($user);

        // Dezelfde code werkt geen tweede keer.
        Accounts::logout();
        $this->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'geheim123!']);
        $this->from('/inloggen/verificatie')->post('/inloggen/verificatie', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_herstelcode_werkt_een_keer(): void
    {
        $user = User::factory()->create(['email' => 'tom@example.com']);
        $codes = Accounts::replaceRecoveryCodes($user);
        $this->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'geheim123!']);
        $this->post('/inloggen/verificatie', ['code' => strtoupper($codes[0])])->assertRedirect('/account');
        $this->assertSame(7, Accounts::remainingRecoveryCodes($user));

        Accounts::logout();
        $this->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'geheim123!']);
        $this->from('/inloggen/verificatie')->post('/inloggen/verificatie', ['code' => $codes[0]])->assertSessionHasErrors('code');
    }

    public function test_te_veel_foute_codes_beeindigt_de_tussenstap(): void
    {
        User::factory()->create(['email' => 'tom@example.com']);
        $this->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'geheim123!']);
        for ($i = 0; $i < 4; $i++) {
            $this->from('/inloggen/verificatie')->post('/inloggen/verificatie', ['code' => '000000'])->assertRedirect('/inloggen/verificatie');
        }
        $this->post('/inloggen/verificatie', ['code' => '000000'])->assertRedirect('/inloggen?melding=te-veel-codes');
        $this->get('/inloggen/verificatie')->assertRedirect();
    }

    public function test_beheerder_via_admin_emails(): void
    {
        config(['steynpt.admin_emails' => ['steyn@steynpt.nl']]);
        $steyn = User::factory()->create(['email' => 'steyn@steynpt.nl']);
        $this->post('/inloggen', ['email' => 'steyn@steynpt.nl', 'password' => 'geheim123!']);
        $this->post('/inloggen/verificatie', ['code' => Totp::totp($steyn->totp_secret)])->assertRedirect('/admin');
        $this->assertSame('admin', $steyn->fresh()->role);
    }

    public function test_wachtwoord_vernieuwen_logt_andere_apparaten_uit(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subDays(60)]);
        $this->actingAs($user)->from('/wachtwoord-vernieuwen')->post('/wachtwoord-vernieuwen', ['current' => 'geheim123!', 'password' => 'geheim123!', 'confirm' => 'geheim123!'])
            ->assertSessionHasErrors(['password' => 'Kies een ander wachtwoord dan je huidige']);
        $this->actingAs($user)->post('/wachtwoord-vernieuwen', ['current' => 'geheim123!', 'password' => 'nieuwgeheim1', 'confirm' => 'nieuwgeheim1', 'next' => '/account/agenda'])
            ->assertRedirect('/account/agenda');
        $this->assertTrue($user->fresh()->password_changed_at->gt(now()->subMinute()));
        $this->actingAs($user->fresh())->get('/account')->assertOk();
    }

    public function test_account_verwijderen(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/verwijderen', ['password' => 'geheim123!'])
            ->assertSessionHasErrorsIn('delete', ['confirm']);
        $this->actingAs($user)->post('/account/profiel/verwijderen', ['password' => 'geheim123!', 'confirm' => 'on'])->assertRedirect('/?account=verwijderd');
        $this->assertSame(0, User::query()->count());
        $this->assertGuest();
    }
}
