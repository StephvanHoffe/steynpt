<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\CheckIn;
use App\Models\Measurement;
use App\Models\User;
use App\Support\Agenda;
use App\Support\Totp;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Inloggen, registreren en Mijn omgeving in het Engels: de taal van het lid (users.locale) of, zonder account, de
 * taalcookie. Controleert per pagina de belangrijkste Engelse teksten en dat er geen Nederlands is blijven staan.
 *
 * Let op: een assertSession…-controle op een verzoek ruimt de geflashte meldingen op, dus een melding die ook op de
 * pagina erna moet staan, wordt met een apart verzoek (zonder die controle) getoond.
 */
class AccountEnglishTest extends TestCase
{
    use RefreshDatabase;

    /** Woorden die in Engelse tekst niet voorkomen, maar in bijna elke Nederlandse zin wel. */
    private const DUTCH_WORDS = [
        'je', 'jouw', 'de', 'het', 'een', 'en', 'van', 'voor', 'niet', 'mijn', 'naar', 'deze', 'wordt', 'kunt', 'wachtwoord', 'afspraak',
        'afspraken', 'opslaan', 'versturen', 'inloggen', 'uitloggen', 'voortgang', 'herstelcode', 'herstelcodes', 'schema', 'meting', 'gewicht',
        'kies', 'vul', 'bijv', 'optioneel', 'maand', 'gratis', 'uur', 'weken', 'dagen', 'maandag', 'oktober', 'locatie', 'taal', 'doel',
    ];

    private function english(array $attributes = []): User
    {
        return User::factory()->create(['locale' => 'en', 'first_name' => 'Lisa', 'last_name' => 'Smith', ...$attributes]);
    }

    private function guest(): static
    {
        return $this->withUnencryptedCookie('taal', 'en');
    }

    /** Zichtbare tekst van de pagina-inhoud (zonder de kop en footer van de site), met placeholders, alt- en title-teksten. */
    private function mainText(TestResponse $response): string
    {
        $html = (string) $response->getContent();
        if (preg_match('#<main[^>]*>(.*)</main>#s', $html, $m)) {
            $html = $m[1];
        }
        $html = preg_replace('#<(script|svg|template)\b[^>]*>.*?</\1>#s', ' ', $html);
        preg_match_all('/\s(?:placeholder|alt|title|aria-label)="([^"]*)"/', $html, $attributes);
        $text = strip_tags(str_replace('<', ' <', $html)).' '.implode(' ', $attributes[1]);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5)));
    }

    private function assertNoDutch(TestResponse $response): TestResponse
    {
        $text = $this->mainText($response);
        preg_match_all('/\b('.implode('|', self::DUTCH_WORDS).')\b/iu', $text, $m);
        $this->assertSame([], array_values(array_unique(array_map('mb_strtolower', $m[1]))), "Nederlands op een Engelse pagina:\n{$text}");

        return $response;
    }

    public function test_inloggen_in_het_engels(): void
    {
        $page = $this->guest()->get('/inloggen')->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Log in · SteynPT', false)
            ->assertSee('Welcome back')
            ->assertSee('Email address')
            ->assertSee('Create one for free')
            ->assertSee('href="/en/contact"', false)
            ->assertDontSee('Welkom terug');
        $this->assertNoDutch($page);

        $this->assertNoDutch($this->guest()->get('/inloggen?melding=te-veel-codes')->assertSee('Too many incorrect codes. Please log in again with your password.'));

        User::factory()->create(['email' => 'tom@example.com']);
        $this->guest()->from('/inloggen')->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'fout'])
            ->assertSessionHas('error', 'Email address or password is incorrect.');
        $this->guest()->from('/inloggen')->post('/inloggen', ['email' => 'tom@example.com'])
            ->assertSessionHasErrors(['password' => 'Enter your password']);
    }

    public function test_registreren_in_het_engels_bewaart_de_taal(): void
    {
        $lisa = $this->english(['referral_code' => 'LISA-AB12']);
        $page = $this->guest()->get('/registreren?ref=LISA-AB12')->assertOk()
            ->assertSee('Create your account')
            ->assertSee('per month')
            ->assertSee('Because Lisa invited you, you get')
            ->assertSee('Lose weight')
            ->assertSee('href="/en/privacy"', false)
            ->assertDontSee('per maand');
        $this->assertNoDutch($page);

        $invalid = ['email' => 'geen-adres', 'password' => 'kort'];
        $this->guest()->from('/registreren')->post('/registreren', $invalid)
            ->assertSessionHasErrors([
                'firstName' => 'Enter your first name',
                'password' => 'Choose a password of at least 8 characters',
                'terms' => 'Give your consent to create your account',
            ]);
        $this->guest()->from('/registreren')->post('/registreren', $invalid);
        $this->assertNoDutch($this->guest()->get('/registreren')->assertSee('Enter your first name')->assertSee('Give your consent to create your account'));

        $this->guest()->post('/registreren', [
            'plan' => 'online-pro', 'firstName' => 'Anna', 'lastName' => 'Brown', 'email' => 'anna@example.com',
            'goal' => 'fitter', 'password' => 'geheim123!', 'terms' => 'on', 'referralCode' => $lisa->referral_code,
        ])->assertRedirect('/inloggen/verificatie');
        $this->assertSame('en', User::query()->where('email', 'anna@example.com')->sole()->locale);
    }

    public function test_registreren_op_de_nederlandse_site_blijft_nederlands(): void
    {
        $this->post('/registreren', [
            'firstName' => 'Anna', 'lastName' => 'Veilig', 'email' => 'anna@example.com', 'goal' => 'fitter', 'password' => 'geheim123!', 'terms' => 'on',
        ])->assertRedirect('/inloggen/verificatie');
        $this->assertSame('nl', User::query()->sole()->locale);
    }

    public function test_tweestapsverificatie_instellen_in_het_engels(): void
    {
        $this->guest()->post('/registreren', [
            'firstName' => 'Anna', 'lastName' => 'Brown', 'email' => 'anna@example.com', 'goal' => 'fitter', 'password' => 'geheim123!', 'terms' => 'on',
        ]);
        $page = $this->guest()->get('/inloggen/verificatie')->assertOk()->assertSee('Secure your account')->assertSee('Install an authenticator app');
        $this->assertNoDutch($page);
        preg_match('/data-totp-secret="([A-Z2-7]{32})"/', $page->getContent(), $m);

        $this->guest()->from('/inloggen/verificatie')->post('/inloggen/verificatie/instellen', ['code' => '000000'])
            ->assertSessionHasErrors(['code' => "That code isn't right. Scan the QR code again and enter the code the app is showing now."]);
        $this->guest()->post('/inloggen/verificatie/instellen', ['code' => Totp::totp($m[1])])->assertRedirect('/inloggen/verificatie');
        $codes = $this->guest()->get('/inloggen/verificatie')->assertOk()
            ->assertSee('Two-step verification is on.')
            ->assertSee('Your recovery codes')
            ->assertSee('steynpt-recovery-codes.txt')
            ->assertSee("I've saved my recovery codes");
        $this->assertNoDutch($codes);

        $this->guest()->post('/inloggen/verificatie/klaar')->assertRedirect('/account?welkom=1');
        $this->assertNoDutch($this->get('/account?welkom=1')->assertOk()->assertSee('Welcome to SteynPT, Anna!'));
    }

    public function test_inlogcode_in_het_engels_ook_bij_een_mislukte_tussenstap(): void
    {
        User::factory()->create(['email' => 'tom@example.com']);
        $this->guest()->post('/inloggen', ['email' => 'tom@example.com', 'password' => 'geheim123!']);
        $this->assertNoDutch($this->guest()->get('/inloggen/verificatie')->assertOk()->assertSee('Enter your login code')->assertSee('Phone not to hand? Use a recovery code'));

        $this->guest()->from('/inloggen/verificatie')->post('/inloggen/verificatie', ['code' => '000000'])
            ->assertSessionHasErrors(['code' => "That code isn't right. Check that the time on your phone is correct and try the latest code."]);

        // Instellen terwijl de app al gekoppeld is: alleen opnieuw inloggen helpt, ook in het Engels.
        $this->guest()->from('/inloggen/verificatie')->post('/inloggen/verificatie/instellen', ['code' => '000000'])
            ->assertSessionHas('error', 'Something went wrong. Please log in again.');
        $page = $this->guest()->get('/inloggen/verificatie')->assertOk()->assertSee('Log in again')->assertDontSee('name="code"', false);
        $this->assertNoDutch($page);
    }

    public function test_wachtwoord_vernieuwen_in_het_engels(): void
    {
        $user = $this->english(['password_changed_at' => now()->subDays(60)]);
        $this->assertNoDutch($this->actingAs($user)->get('/wachtwoord-vernieuwen')->assertOk()
            ->assertSee('Time for a new password')
            ->assertSee('SteynPT asks for a new password every 8 weeks'));

        $this->actingAs($user)->from('/wachtwoord-vernieuwen')->post('/wachtwoord-vernieuwen', ['current' => 'fout', 'password' => 'nieuwgeheim1', 'confirm' => 'anders123'])
            ->assertSessionHasErrors(['confirm' => "The passwords don't match"]);
        $wrong = ['current' => 'fout', 'password' => 'nieuwgeheim1', 'confirm' => 'nieuwgeheim1'];
        $this->actingAs($user)->from('/wachtwoord-vernieuwen')->post('/wachtwoord-vernieuwen', $wrong)
            ->assertSessionHasErrors(['current' => 'Your current password is incorrect']);
        $this->actingAs($user)->from('/wachtwoord-vernieuwen')->post('/wachtwoord-vernieuwen', $wrong);
        $this->assertNoDutch($this->actingAs($user)->get('/wachtwoord-vernieuwen')->assertSee('Your current password is incorrect'));
    }

    public function test_dashboard_in_het_engels(): void
    {
        $user = $this->english(['coaching_status' => 'actief', 'plan' => 'online-pro', 'goal' => 'afvallen', 'coach_note' => 'Great work!', 'password_changed_at' => now()->subDays(52)]);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => now()->subWeeks(4), 'weight' => 80.2]);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => now()->subWeek(), 'weight' => 78.6]);
        CheckIn::query()->create(['user_id' => $user->id, 'week' => '2026-W01', 'energy' => 4, 'sleep' => 3, 'nutrition' => 5, 'workouts' => 3, 'weight' => 78.5, 'created_at' => now()]);
        Appointment::query()->create(['user_id' => $user->id, 'type' => 'kennismaking', 'location' => 'online', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addMinutes(30)]);
        User::factory()->create(['first_name' => 'Tom', 'last_name' => 'Brown', 'referred_by_id' => $user->id, 'coaching_status' => 'actief']);

        $page = $this->actingAs($user)->get('/account')->assertOk()
            ->assertSee('Hi Lisa')
            ->assertSee('Goal: Lose weight')
            ->assertSee('Your password expires in 4 days.')
            ->assertSee('Next appointment')
            ->assertSee('Free intro session · Online (video call)')
            ->assertSee('My plans')
            ->assertSee('Fill in your intake for your personal plan')
            ->assertSee("You're on", false)
            ->assertSee('Message from Steyn')
            ->assertSee('Weekly check-in')
            ->assertSee('78.5 kg')
            ->assertSee('Started · discount to follow')
            ->assertSee('href="/en/refer-a-friend#voorwaarden"', false)
            ->assertSee('Log out')
            ->assertDontSee('Volgende afspraak');
        $this->assertNoDutch($page);
    }

    public function test_dashboard_aanvraag_en_pauze_in_het_engels(): void
    {
        $new = $this->english(['coaching_status' => 'aangevraagd', 'plan' => 'online-start']);
        $this->assertNoDutch($this->actingAs($new)->get('/account?welkom=1')->assertOk()
            ->assertSee('Steyn will be in touch within 24 hours about your intake.')
            ->assertSee('Request received')
            ->assertSee('Intake call with Steyn'));

        $paused = $this->english(['coaching_status' => 'gepauzeerd', 'plan' => 'online-pro']);
        $this->assertNoDutch($this->actingAs($paused)->get('/account')->assertOk()
            ->assertSee('Your coaching is paused for now.')
            ->assertSee('href="/en/contact?onderwerp=online-coaching"', false));

        $none = $this->english();
        $this->assertNoDutch($this->actingAs($none)->get('/account')->assertOk()
            ->assertSee('Ready for the next step?')
            ->assertSee('href="/en/online-coaching#pakketten"', false));
        $this->actingAs($none)->from('/account')->post('/account/coaching', ['plan' => 'bestaat-niet'])
            ->assertSessionHas('coaching_error', 'Choose a package to get started.');
        $this->actingAs($none)->from('/account')->post('/account/coaching', ['plan' => 'online-start'])
            ->assertSessionHas('coaching_success', fn ($message) => str_starts_with($message, "We've received your request for online coaching"));
    }

    public function test_check_in_meldingen_in_het_engels(): void
    {
        $user = $this->english();
        $invalid = ['workouts' => '30', 'weight' => '10'];
        $this->actingAs($user)->from('/account')->post('/account/check-in', $invalid)
            ->assertSessionHasErrorsIn('checkin', [
                'energy' => 'Give a score for energy',
                'workouts' => 'Enter a number of workouts between 0 and 21',
                'weight' => 'Enter a valid weight (kg)',
            ]);
        $this->actingAs($user)->from('/account')->post('/account/check-in', $invalid);
        $this->assertNoDutch($this->actingAs($user)->get('/account')->assertSee('Give a score for sleep')->assertSee('Enter a valid weight (kg)'));

        $ok = ['energy' => '4', 'sleep' => '3', 'nutrition' => '5', 'workouts' => '2', 'weight' => '74.5'];
        $this->actingAs($user)->from('/account')->post('/account/check-in', $ok)->assertRedirect('/account');
        $this->assertNoDutch($this->actingAs($user)->get('/account')->assertSee('Check-in saved. Steyn will take a look.'));
        $this->actingAs($user)->from('/account')->post('/account/check-in', $ok)->assertSessionHas('checkin_error', "You've already checked in this week. See you next week!");
    }

    public function test_agenda_in_het_engels(): void
    {
        // Maandag 12 oktober 2026, 08:00-12:00 in Amsterdam.
        $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00:00', 'UTC'));
        Availability::query()->create(['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00', 'location' => 'gymbase']);
        $user = $this->english();
        $tom = $this->english(['first_name' => 'Tom']);

        $this->assertNoDutch($this->actingAs($user)->get('/account/agenda')->assertOk()
            ->assertSee('Appointments · SteynPT', false)
            ->assertSee('Upcoming appointments')
            ->assertSee('What would you like to book?')
            ->assertSee('Only for clients with active online coaching.'));

        $url = '/account/agenda?type=personal-training&locatie=gymbase&datum=2026-10-12';
        $this->assertNoDutch($this->actingAs($user)->get($url)->assertOk()
            ->assertSee('Which day?')
            ->assertSee('Mon 12 Oct')
            ->assertSee('Time on Monday 12 October')
            ->assertSee('Confirm appointment')
            ->assertSee('You can cancel up to 24 hours in advance.'));

        $start = Agenda::zonedTimeToUtc('2026-10-12', '09:00')->format('Y-m-d\TH:i:s.v\Z');
        $this->actingAs($user)->post('/account/agenda/boeken', ['type' => 'personal-training', 'location' => 'gymbase', 'start' => $start]);
        $appointment = Appointment::query()->sole();
        $this->assertNoDutch($this->actingAs($user)->get("/account/agenda?geboekt={$appointment->id}")->assertOk()
            ->assertSee('Your appointment is confirmed: Personal training on Monday 12 October at 09:00.')
            ->assertSee('Add it to your calendar'));
        $this->actingAs($user)->get("/account/agenda/{$appointment->id}/ics")->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=\"steynpt-appointment-{$appointment->id}.ics\"")
            ->assertSee('SUMMARY:Personal training with Steyn (SteynPT)')
            ->assertDontSee('met Steyn');

        $this->actingAs($tom)->from($url)->post('/account/agenda/boeken', ['type' => 'personal-training', 'location' => 'gymbase', 'start' => $start])
            ->assertSessionHas('booking_error', 'That time has just been taken. Please choose another time.');
        $this->assertNoDutch($this->actingAs($tom)->get($url)->assertSee('That time has just been taken.'));
        $this->actingAs($tom)->from($url)->post('/account/agenda/boeken', ['type' => 'online-call', 'location' => 'online', 'start' => $start])
            ->assertSessionHas('booking_error', 'This appointment is only for clients with active online coaching.');
    }

    public function test_profiel_in_het_engels(): void
    {
        $user = $this->english(['password_changed_at' => now()->subDays(52)]);
        $page = $this->actingAs($user)->get('/account/profiel')->assertOk()
            ->assertSee('My profile · SteynPT', false)
            ->assertSee('Your details')
            ->assertSee('Language')
            ->assertSee('<option value="en" selected>English</option>', false)
            ->assertSee('(in 4 days)')
            ->assertSee('Two-step verification')
            ->assertSee('Delete account');
        $this->assertNoDutch($page);

        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel', ['firstName' => '', 'lastName' => 'Smith', 'goal' => 'fitter', 'locale' => 'de'])
            ->assertSessionHasErrorsIn('profile', ['firstName' => 'Enter your first name', 'locale' => 'Choose a language']);
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/wachtwoord', ['current' => '', 'password' => 'kort'])
            ->assertSessionHasErrorsIn('password', ['current' => 'Enter your current password', 'password' => 'Choose a password of at least 8 characters']);
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/herstelcodes', ['code' => '1'])
            ->assertSessionHasErrorsIn('regen', ['code' => 'Enter the code your app is showing now.']);
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/tweestaps-resetten', ['code' => '1'])
            ->assertSessionHasErrorsIn('reset', ['code' => "That code isn't right."]);
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/verwijderen', ['password' => 'fout'])
            ->assertSessionHasErrorsIn('delete', ['confirm' => 'Confirm that you want to delete your account']);
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/wachtwoord', ['current' => '', 'password' => 'kort']);
        $this->assertNoDutch($this->actingAs($user)->get('/account/profiel')->assertSee('Enter your current password'));

        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel/herstelcodes', ['code' => Totp::totp($user->totp_secret)]);
        $this->assertNoDutch($this->actingAs($user)->get('/account/profiel')
            ->assertSee('New recovery codes created. The old ones no longer work.')
            ->assertSee('Your recovery codes'));
    }

    public function test_taal_kiezen_in_het_profiel(): void
    {
        $user = User::factory()->create(['first_name' => 'Lisa', 'last_name' => 'Jansen'])->fresh();
        $this->assertSame('nl', $user->locale);
        $this->actingAs($user)->get('/account/profiel')->assertOk()->assertSee('Mijn profiel')->assertSee('<option value="nl" selected>Nederlands</option>', false);

        // Naar het Engels: de melding en de pagina daarna zijn meteen Engels, en de keuze wordt ook als cookie onthouden.
        $this->actingAs($user)->from('/account/profiel')->post('/account/profiel', ['firstName' => 'Lisa', 'lastName' => 'Jansen', 'goal' => 'fitter', 'locale' => 'en'])
            ->assertRedirect('/account/profiel')
            ->assertCookie('taal', 'en', false);
        $this->assertSame('en', $user->fresh()->locale);
        $this->assertNoDutch($this->actingAs($user->fresh())->get('/account/profiel')->assertOk()->assertSee('My profile')->assertSee('Your profile has been updated.'));

        // Zonder taalveld blijft de taal gelijk.
        $this->actingAs($user->fresh())->from('/account/profiel')->post('/account/profiel', ['firstName' => 'Lisa', 'lastName' => 'Jansen', 'goal' => 'fitter']);
        $this->assertSame('en', $user->fresh()->locale);

        // En terug naar het Nederlands.
        $this->actingAs($user->fresh())->from('/account/profiel')->post('/account/profiel', ['firstName' => 'Lisa', 'lastName' => 'Jansen', 'goal' => 'fitter', 'locale' => 'nl'])
            ->assertSessionHas('profile_success', 'Je profiel is bijgewerkt.');
        $this->assertSame('nl', $user->fresh()->locale);
        $this->actingAs($user->fresh())->get('/account/profiel')->assertOk()->assertSee('Mijn profiel')->assertDontSee('My profile');
    }

    public function test_uitloggen_en_verwijderen_naar_de_engelse_homepage(): void
    {
        $user = $this->english();
        $this->actingAs($user)->post('/uitloggen')->assertRedirect('/en');

        $other = $this->english();
        $this->actingAs($other)->post('/account/profiel/verwijderen', ['password' => 'geheim123!', 'confirm' => 'on'])->assertRedirect('/en?account=verwijderd');
        $this->guest()->get('/account/agenda/1/ics')->assertStatus(401)->assertSee('Please log in first');
    }

    public function test_beheer_toont_de_taal_van_het_lid(): void
    {
        $steyn = User::factory()->admin()->create();
        $english = $this->english();
        $dutch = User::factory()->create();

        // Het beheer blijft Nederlands, ook voor een Engelstalig lid.
        $this->actingAs($steyn)->get("/admin/leden/{$english->id}")->assertOk()->assertSee('Taal: Engels')->assertSee('Lid sinds');
        $this->actingAs($steyn)->get("/admin/leden/{$dutch->id}")->assertOk()->assertDontSee('Taal: Engels');
    }
}
