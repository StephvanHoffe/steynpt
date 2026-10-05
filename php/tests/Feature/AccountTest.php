<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\CheckIn;
use App\Models\Measurement;
use App\Models\User;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mijn omgeving: toegang, dashboard, check-in, agenda en voortgang. */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_zonder_inloggen_naar_de_inlogpagina(): void
    {
        $this->get('/account/agenda')->assertRedirect('/inloggen?next=%2Faccount%2Fagenda');
        $this->get('/account/agenda/1/ics')->assertStatus(401);
    }

    public function test_sessie_zonder_tweestapsverificatie_telt_niet(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $this->actingAs($user)->get('/account')->assertRedirect('/inloggen?next=%2Faccount');
    }

    public function test_verlopen_wachtwoord_eerst_vernieuwen(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subDays(57)]);
        $this->actingAs($user)->get('/account/profiel')->assertRedirect('/wachtwoord-vernieuwen?next=%2Faccount%2Fprofiel');
        $this->actingAs($user)->get('/wachtwoord-vernieuwen')->assertOk()->assertSee('Tijd voor een nieuw wachtwoord');
    }

    public function test_dashboard_toont_schemas_voortgang_en_vriendenlink(): void
    {
        $user = User::factory()->create(['first_name' => 'Lisa', 'coaching_status' => 'actief', 'plan' => 'online-pro', 'coach_note' => 'Goed bezig!']);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => now()->subWeeks(4), 'weight' => 80.2]);
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => now()->subWeek(), 'weight' => 78.6]);

        $this->actingAs($user)->get('/account?welkom=1')->assertOk()
            ->assertSee('Welkom bij SteynPT, Lisa!')
            ->assertSee('online coaching Pro')
            ->assertSee('Vul je intake in voor je persoonlijke schema')
            ->assertSee('78,6')
            ->assertSee('-1,6 kg sinds', false)
            ->assertSee('Bericht van Steyn')
            ->assertSee('/r/'.$user->referral_code);
    }

    public function test_check_in_controleert_en_slaat_een_keer_per_week_op(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/account')->post('/account/check-in', ['workouts' => '3'])
            ->assertRedirect('/account')->assertSessionHasErrorsIn('checkin', ['energy', 'sleep', 'nutrition']);
        $this->actingAs($user)->from('/account')->post('/account/check-in', ['energy' => '4', 'sleep' => '3', 'nutrition' => '5', 'workouts' => '2', 'weight' => '20'])
            ->assertSessionHasErrorsIn('checkin', ['weight' => 'Vul een geldig gewicht in (kg)']);

        $ok = ['energy' => '4', 'sleep' => '3', 'nutrition' => '5', 'workouts' => '2', 'weight' => '74,5', 'note' => 'Prima week'];
        $this->actingAs($user)->from('/account')->post('/account/check-in', $ok)->assertSessionHas('checkin_success', 'Check-in opgeslagen. Steyn kijkt ernaar.');
        $this->assertSame(74.5, CheckIn::query()->sole()->weight);
        $this->actingAs($user)->from('/account')->post('/account/check-in', $ok)->assertSessionHas('checkin_error', 'Je hebt deze week al ingecheckt. Tot volgende week!');
        $this->assertSame(1, CheckIn::query()->count());
    }

    public function test_coaching_aanvragen(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/account')->post('/account/coaching', ['plan' => 'bestaat-niet'])->assertSessionHas('coaching_error', 'Kies een pakket om te starten.');
        $this->actingAs($user)->from('/account')->post('/account/coaching', ['plan' => 'online-start'])->assertSessionHas('coaching_success');
        $this->assertSame('aangevraagd', $user->fresh()->coaching_status);
        $this->assertSame('online-start', $user->fresh()->plan);
    }

    public function test_afspraak_boeken_niet_dubbel_en_alleen_eigen_ics(): void
    {
        // Maandag 12 oktober 2026, 08:00-12:00 in Amsterdam.
        CarbonImmutable::setTestNow('2026-10-05 08:00:00');
        Availability::query()->create(['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00', 'location' => 'gymbase']);
        $lisa = User::factory()->create();
        $tom = User::factory()->create();
        $start = Agenda::zonedTimeToUtc('2026-10-12', '09:00')->format('Y-m-d\TH:i:s.v\Z');

        $this->actingAs($lisa)->get('/account/agenda?type=personal-training')->assertOk()->assertSee('datum=2026-10-12', false);
        $response = $this->actingAs($lisa)->post('/account/agenda/boeken', ['type' => 'personal-training', 'location' => 'gymbase', 'start' => $start]);
        $appointment = Appointment::query()->sole();
        $response->assertRedirect("/account/agenda?geboekt={$appointment->id}");
        $this->assertSame('2026-10-12 07:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-12 08:00:00', $appointment->ends_at->format('Y-m-d H:i:s'));

        $this->actingAs($tom)->from('/account/agenda')->post('/account/agenda/boeken', ['type' => 'personal-training', 'location' => 'gymbase', 'start' => $start])
            ->assertSessionHas('booking_error', 'Dit tijdstip is net niet meer beschikbaar. Kies een ander tijdstip.');
        $this->actingAs($tom)->from('/account/agenda')->post('/account/agenda/boeken', ['type' => 'online-call', 'location' => 'online', 'start' => $start])
            ->assertSessionHas('booking_error', 'Deze afspraak is alleen voor klanten met actieve online coaching.');

        $this->actingAs($lisa)->get("/account/agenda/{$appointment->id}/ics")->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('Personal training met Steyn (SteynPT)');
        $this->actingAs($tom)->get("/account/agenda/{$appointment->id}/ics")->assertNotFound();

        // Afzeggen door een ander lid doet niets; door het lid zelf wel (meer dan 24 uur van tevoren).
        $this->actingAs($tom)->post('/account/agenda/afzeggen', ['id' => $appointment->id]);
        $this->assertSame('gepland', $appointment->fresh()->status);
        $this->actingAs($lisa)->post('/account/agenda/afzeggen', ['id' => $appointment->id]);
        $this->assertSame('geannuleerd', $appointment->fresh()->status);
        $this->assertSame('klant', $appointment->fresh()->cancelled_by);
    }

    public function test_voortgang_met_tabel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account/voortgang')->assertOk()->assertSee('Er zijn nog geen metingen.');
        Measurement::query()->create(['user_id' => $user->id, 'measured_at' => '2026-09-01 10:00:00', 'weight' => 80, 'waist' => 90.5, 'note' => 'Nulmeting']);
        $this->actingAs($user)->get('/account/voortgang')->assertOk()->assertSee('Alle metingen')->assertSee('Nulmeting')->assertSee('90,5');
    }

    public function test_intake_controleert_toestemming(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account/intake')->assertOk()->assertSee('Jouw intake');
        $this->actingAs($user)->from('/account/intake')->post('/account/intake', ['wants' => ['training']])
            ->assertRedirect('/account/intake')
            ->assertSessionHasErrorsIn('intake', ['consent' => 'Geef toestemming om je gegevens te gebruiken voor je schema'])
            ->assertSessionHas('intake_error', 'Controleer de gemarkeerde velden.');
    }
}
