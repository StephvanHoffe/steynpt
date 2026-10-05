<?php

namespace Tests\Feature;

use App\Content\Registry;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\BlockedPeriod;
use App\Models\CheckIn;
use App\Models\ContactRequest;
use App\Models\Intake;
use App\Models\Measurement;
use App\Models\User;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Beheer: toegang en het tonen van alle pagina's (overzicht, leden, aanvragen, agenda, instellingen, teksten). */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['first_name' => 'Steyn', 'last_name' => 'van Leeuwen']);
    }

    private function today(): string
    {
        return Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];
    }

    public function test_alleen_voor_de_beheerder(): void
    {
        $this->get('/admin')->assertRedirect('/inloggen?next=%2Fadmin');
        $member = User::factory()->create();
        foreach (['/admin', '/admin/leden', '/admin/agenda', '/admin/teksten/home'] as $url) {
            $this->actingAs($member)->get($url)->assertRedirect('/account');
        }
        $this->actingAs($member)->post('/admin/aanvragen/afhandelen', ['id' => 1])->assertRedirect('/account');
    }

    public function test_overzicht_met_vandaag_te_doen_en_tellers(): void
    {
        $admin = $this->admin();
        $mark = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Bakker', 'phone' => '06 12345678']);
        $later = CarbonImmutable::now('UTC')->addDays(2)->setTime(9, 0);
        Appointment::query()->create(['user_id' => $mark->id, 'type' => 'meting', 'location' => 'gymbase', 'starts_at' => $later, 'ends_at' => $later->addMinutes(30)]);
        $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Jansen']);
        User::factory()->create(['first_name' => 'Bram', 'last_name' => 'Test', 'coaching_status' => 'actief', 'referred_by_id' => $anna->id]);
        ContactRequest::query()->create(['name' => 'Bedrijf BV', 'email' => 'hr@bedrijf.nl', 'interest' => 'ademcoaching']);
        User::factory()->create(['coaching_status' => 'aangevraagd']);

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Steyn', false)
            ->assertSee('Geen afspraken vandaag.')
            ->assertSee('Hierna')
            ->assertSee('Mark Bakker, meting')
            ->assertSee('1</strong> nieuwe contactaanvraag', false)
            ->assertSee('1</strong> lid heeft coaching aangevraagd', false)
            ->assertSee('bracht Bram Test aan', false)
            ->assertSee('Anna Jansen')
            ->assertSee('Nieuwe leden (30 dagen)')
            ->assertSee('nav aria-label="Beheer"', false);
    }

    public function test_ledenlijst_filtert_en_zoekt_zonder_beheerder(): void
    {
        $admin = $this->admin();
        User::factory()->create(['first_name' => 'Tom', 'last_name' => 'de Groot', 'coaching_status' => 'aangevraagd']);
        $sanne = User::factory()->create(['first_name' => 'Sanne', 'last_name' => 'de Vries', 'coaching_status' => 'actief', 'plan' => 'online-pro']);
        CheckIn::query()->create(['user_id' => $sanne->id, 'week' => '2026-W40', 'energy' => 4, 'sleep' => 4, 'nutrition' => 4, 'workouts' => 3]);

        $this->actingAs($admin)->get('/admin/leden')->assertOk()
            ->assertSee('2 leden')
            ->assertSee('Tom de Groot')->assertSee('Sanne de Vries')
            ->assertSee('<span class="text-muted">Pro</span>', false)
            ->assertSee('week 40')
            ->assertDontSee('van Leeuwen');
        $this->actingAs($admin)->get('/admin/leden?status=aangevraagd')->assertOk()
            ->assertSee('Tom de Groot')->assertDontSee('Sanne de Vries');
        $this->actingAs($admin)->get('/admin/leden?q=sanne')->assertOk()
            ->assertSee('Sanne de Vries')->assertDontSee('Tom de Groot');
        $this->actingAs($admin)->get('/admin/leden?q=niemand')->assertOk()
            ->assertSee('Geen leden gevonden voor “niemand”.', false);
    }

    public function test_ledenpagina_toont_alle_onderdelen(): void
    {
        $admin = $this->admin();
        $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Jansen']);
        $member = User::factory()->create(['first_name' => 'Sanne', 'last_name' => 'de Vries', 'coaching_status' => 'actief', 'referred_by_id' => $anna->id, 'coach_note' => 'Goed bezig']);
        Measurement::query()->create(['user_id' => $member->id, 'measured_at' => now()->subWeeks(2), 'weight' => 80.4, 'body_fat' => 23.1]);
        Measurement::query()->create(['user_id' => $member->id, 'measured_at' => now()->subWeek(), 'weight' => 79.5]);
        $later = CarbonImmutable::now('UTC')->addDays(3)->setTime(8, 0);
        Appointment::query()->create(['user_id' => $member->id, 'type' => 'personal-training', 'location' => 'gymbase', 'starts_at' => $later, 'ends_at' => $later->addHour()]);

        $this->actingAs($admin)->get("/admin/leden/{$member->id}")->assertOk()
            ->assertSee('Sanne de Vries')
            ->assertSee('Uitgenodigd door Anna Jansen', false)
            ->assertSee('Goed bezig')
            ->assertSee('Trainingsschema')->assertSee('Nieuw schema')
            ->assertSee('80,4')->assertSee('Meting van', false)
            ->assertSee('Personal training · Gymbase', false)
            ->assertSee('aan sinds', false)
            ->assertSee('Tweestapsverificatie resetten…', false)
            ->assertSee('Dit lid heeft nog geen intake ingevuld.');
        $this->actingAs($admin)->get('/admin/leden/bestaat-niet')->assertNotFound();
    }

    public function test_aanvragen_open_en_afgehandeld(): void
    {
        $admin = $this->admin();
        ContactRequest::query()->create(['name' => 'Bedrijf BV', 'email' => 'hr@bedrijf.nl', 'phone' => '020 123 4567', 'interest' => 'ademcoaching', 'message' => 'Ademsessie voor 12 collega\'s?']);

        $this->actingAs($admin)->get('/admin/aanvragen')->assertOk()
            ->assertSee('Bedrijf BV')->assertSee('Ademsessie 1-op-1')->assertSee('tel:0201234567', false)
            ->assertSee('Markeer als afgehandeld');
        $this->actingAs($admin)->get('/admin/aanvragen?toon=afgehandeld')->assertOk()->assertSee('Nog niets afgehandeld.');
    }

    public function test_agenda_in_alle_weergaven(): void
    {
        $admin = $this->admin();
        $mark = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Bakker']);
        $day = Agenda::addDays($this->today(), 8);
        $start = Agenda::zonedTimeToUtc($day, '10:00');
        $a = Appointment::query()->create(['user_id' => $mark->id, 'type' => 'personal-training', 'location' => 'gymbase', 'starts_at' => $start, 'ends_at' => $start->addHour(), 'note' => 'Intervaltraining']);
        Availability::query()->create(['weekday' => Agenda::weekdayOf($day), 'start_time' => '07:00', 'end_time' => '12:00', 'location' => 'gymbase']);
        BlockedPeriod::query()->create(['starts_at' => Agenda::zonedTimeToUtc(Agenda::addDays($day, 1), '00:00'), 'ends_at' => Agenda::zonedTimeToUtc(Agenda::addDays($day, 2), '00:00'), 'reason' => 'Vakantie']);

        $this->actingAs($admin)->get("/admin/agenda?datum={$day}")->assertOk()
            ->assertSee('Mark B.')->assertSee('Week ')
            ->assertSee('/admin/agenda/nieuw?datum='.$day.'&amp;tijd=09:00', false)
            ->assertSee('Vrij · Vakantie', false);
        $this->actingAs($admin)->get("/admin/agenda?weergave=dag&datum={$day}")->assertOk()->assertSee('Beschikbaar · Gymbase', false);
        $this->actingAs($admin)->get("/admin/agenda?weergave=maand&datum={$day}")->assertOk()->assertSee('Bekijk '.$day);
        $this->actingAs($admin)->get("/admin/agenda?weergave=lijst&datum={$day}")->assertOk()->assertSee('28 dagen')->assertSee('Vrij (Vakantie)');
        $this->actingAs($admin)->get("/admin/agenda?weergave=dag&datum={$day}&afspraak={$a->id}&melding=gepland")->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('Afspraak ingepland.')
            ->assertSee('Intervaltraining')
            ->assertSee('10:00–11:00 · 60 minuten', false)
            ->assertSee('Ja, annuleer deze afspraak')
            ->assertDontSee('Valt buiten je beschikbaarheid');
    }

    public function test_nieuwe_afspraak_en_verplaatsen_formulier(): void
    {
        $admin = $this->admin();
        $mark = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Bakker', 'coaching_status' => 'actief']);
        $day = Agenda::addDays($this->today(), 3);
        $this->actingAs($admin)->get("/admin/agenda/nieuw?datum={$day}&tijd=10:30&lid={$mark->id}")->assertOk()
            ->assertSee('Nieuwe afspraak')
            ->assertSee('Mark Bakker · coaching actief', false)
            ->assertSee('value="'.$mark->id.'" selected', false)
            ->assertSee('value="10:30"', false)
            ->assertSee('Afspraak inplannen');

        $start = Agenda::zonedTimeToUtc($day, '09:00');
        $a = Appointment::query()->create(['user_id' => $mark->id, 'type' => 'meting', 'location' => 'gymbase', 'starts_at' => $start, 'ends_at' => $start->addMinutes(30)]);
        $this->actingAs($admin)->get("/admin/agenda/nieuw?verplaats={$a->id}")->assertOk()
            ->assertSee('Afspraak verplaatsen')
            ->assertSee('name="replaces" value="'.$a->id.'"', false)
            ->assertSee('value="09:00"', false);
    }

    public function test_instellingen_en_ical_feed(): void
    {
        $admin = $this->admin();
        $mark = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Bakker', 'phone' => '0612345678']);
        $start = CarbonImmutable::now('UTC')->addDays(2)->setTime(8, 0);
        Appointment::query()->create(['user_id' => $mark->id, 'type' => 'personal-training', 'location' => 'gymbase', 'starts_at' => $start, 'ends_at' => $start->addHour()]);

        $html = $this->actingAs($admin)->get('/admin/agenda/instellingen')->assertOk()
            ->assertSee('Beschikbaarheid per week')
            ->assertSee('Er zijn nog geen tijden ingesteld')
            ->assertSee('aria-label="iCal-link"', false)
            ->getContent();
        preg_match('#/ical/([\w-]+)\.ics#', $html, $m);
        $this->assertNotEmpty($m);

        $feed = $this->get("/ical/{$m[1]}.ics")->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $feed->getContent());
        $this->assertStringContainsString('SUMMARY:Personal training – Mark Bakker', $feed->getContent());
        $this->get('/ical/verkeerd.ics')->assertNotFound();
    }

    public function test_teksten_overzicht_en_bewerkscherm(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/teksten')->assertOk()
            ->assertSee('Homepage')->assertSee('Standaardteksten')->assertSee('Prijzen en pakketten');
        foreach (Registry::all() as $page) {
            $this->actingAs($admin)->get('/admin/teksten/'.$page['slug'])->assertOk()
                ->assertSee('name="values"', false)
                ->assertSee('Nog niets aangepast: alles is de standaardtekst.');
        }
        $this->actingAs($admin)->get('/admin/teksten/onbekend')->assertNotFound();
    }

    public function test_intake_bij_lid(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        Intake::query()->create(['user_id' => $member->id, 'data' => ['onzin' => true]]);
        $this->actingAs($admin)->get("/admin/leden/{$member->id}")->assertOk()->assertSee('Dit lid heeft nog geen intake ingevuld.');
    }
}
