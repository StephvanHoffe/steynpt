<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\BlockedPeriod;
use App\Models\Setting;
use App\Models\User;
use App\Services\AgendaServer;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Beheer: afspraken inplannen, verplaatsen en annuleren, beschikbaarheid, vrije dagen en de iCal-link. */
class AdminAgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mark;

    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['first_name' => 'Steyn']);
        $this->mark = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Bakker']);
        $this->day = Agenda::addDays(Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'], 8);
    }

    private function book(array $data, string $from = '/admin/agenda/nieuw')
    {
        return $this->actingAs($this->admin)->from($from)->post('/admin/agenda/nieuw', $data + [
            'userId' => $this->mark->id, 'type' => 'personal-training', 'location' => 'gymbase', 'day' => $this->day, 'time' => '10:00', 'note' => '',
        ]);
    }

    public function test_afspraak_inplannen(): void
    {
        $response = $this->book(['note' => 'Intervaltraining voor de marathon']);
        $a = Appointment::query()->sole();
        $response->assertRedirect("/admin/agenda?weergave=dag&datum={$this->day}&afspraak={$a->id}&melding=gepland");
        $this->assertSame('gepland', $a->fresh()->status);
        $this->assertTrue($a->starts_at->eq(Agenda::zonedTimeToUtc($this->day, '10:00')));
        $this->assertTrue($a->ends_at->eq(Agenda::zonedTimeToUtc($this->day, '11:00')));
        $this->assertSame('Intervaltraining voor de marathon', $a->note);

        // Buiten de beschikbaarheid mag, maar het paneel waarschuwt.
        $this->actingAs($this->admin)->get("/admin/agenda?weergave=dag&datum={$this->day}&afspraak={$a->id}&melding=gepland")
            ->assertSee('Afspraak ingepland.')
            ->assertSee('Valt buiten je beschikbaarheid voor Gymbase.');
    }

    public function test_controle_bij_inplannen(): void
    {
        $this->book(['userId' => '', 'type' => '', 'day' => 'morgen', 'time' => '25:00'])
            ->assertRedirect('/admin/agenda/nieuw')
            ->assertSessionHasErrorsIn('appointment', ['userId' => 'Kies een klant', 'type' => 'Kies een soort afspraak', 'day' => 'Kies een datum', 'time' => 'Vul een tijd in']);
        $this->book(['type' => 'meting', 'location' => 'online'])
            ->assertSessionHasErrorsIn('appointment', ['location' => 'Meting kan niet op deze locatie']);
        $this->book(['day' => Agenda::addDays($this->day, -20)])
            ->assertSessionHasErrorsIn('appointment', ['time' => 'Kies een tijdstip in de toekomst']);
        $this->book(['userId' => 'bestaat-niet'])->assertSessionHas('appointment_error', 'Deze klant bestaat niet meer.');
        $this->assertSame(0, Appointment::query()->count());

        // Na een fout staan de ingevulde waarden er weer.
        $this->book(['type' => 'meting', 'location' => 'online', 'time' => '10:30']);
        $this->actingAs($this->admin)->get('/admin/agenda/nieuw')
            ->assertSee('Meting kan niet op deze locatie')
            ->assertSee('value="10:30"', false)
            ->assertSee('value="meting" checked', false);
    }

    public function test_dubbel_boeken_kan_niet(): void
    {
        $this->book([]);
        $tom = User::factory()->create(['first_name' => 'Tom', 'last_name' => 'de Groot']);
        $this->book(['userId' => $tom->id, 'type' => 'meting', 'time' => '10:30'])
            ->assertSessionHas('appointment_error', 'Op dit tijdstip staat al een afspraak met Mark Bakker (10:00–11:00).');
        $this->assertSame(1, Appointment::query()->count());

        // Aansluitend mag wel, en een vrije periode houdt Steyn niet tegen.
        BlockedPeriod::query()->create(['starts_at' => Agenda::zonedTimeToUtc($this->day, '00:00'), 'ends_at' => Agenda::zonedTimeToUtc(Agenda::addDays($this->day, 1), '00:00')]);
        $this->book(['userId' => $tom->id, 'type' => 'meting', 'time' => '11:00'])->assertSessionHasNoErrors();
        $this->assertSame(2, Appointment::query()->count());
    }

    public function test_afspraak_verplaatsen(): void
    {
        $this->book(['note' => 'Intervaltraining']);
        $old = Appointment::query()->sole();
        $this->actingAs($this->admin)->get("/admin/agenda/nieuw?verplaats={$old->id}")->assertOk()
            ->assertSee('Afspraak verplaatsen')->assertSee('value="10:00"', false)->assertSee('Mark Bakker</span>', false);

        // Over de eigen oude afspraak heen verplaatsen mag; de opmerking gaat mee.
        $response = $this->book(['time' => '10:30', 'replaces' => (string) $old->id]);
        $new = Appointment::query()->whereKeyNot($old->id)->sole();
        $response->assertRedirect("/admin/agenda?weergave=dag&datum={$this->day}&afspraak={$new->id}&melding=verplaatst");
        $this->assertSame('Intervaltraining', $new->note);
        $old->refresh();
        $this->assertSame('geannuleerd', $old->status);
        $this->assertSame('steyn', $old->cancelled_by);
        $this->assertNotNull($old->cancelled_at);

        $this->book(['time' => '15:00', 'replaces' => (string) $old->id])
            ->assertSessionHas('appointment_error', 'De afspraak die je wilt verplaatsen is al geannuleerd.');
        $this->actingAs($this->admin)->get("/admin/agenda?weergave=dag&datum={$this->day}&afspraak={$old->id}&geannuleerd=1")
            ->assertSee('Geannuleerd door jou')->assertDontSee('Ja, annuleer deze afspraak');
    }

    public function test_afspraak_annuleren(): void
    {
        $this->book([]);
        $a = Appointment::query()->sole();
        $url = "/admin/agenda?weergave=dag&datum={$this->day}&afspraak={$a->id}";
        $this->actingAs($this->admin)->from($url)->post('/admin/agenda/annuleren', ['id' => $a->id])->assertRedirect($url);
        $a->refresh();
        $this->assertSame('geannuleerd', $a->status);
        $this->assertSame('steyn', $a->cancelled_by);
        $this->actingAs($this->admin)->get($url)->assertSee('Geannuleerd door jou')->assertSee('0 afspraken in deze dag');
    }

    public function test_beschikbaarheid_toevoegen_en_verwijderen(): void
    {
        $this->actingAs($this->admin)->from('/admin/agenda/instellingen')
            ->post('/admin/agenda/instellingen/beschikbaarheid', ['weekday' => '3', 'startTime' => '12:00', 'endTime' => '11:00', 'location' => 'gymbase'])
            ->assertRedirect('/admin/agenda/instellingen')
            ->assertSessionHas('availability_error', 'De eindtijd moet na de begintijd liggen');
        $this->actingAs($this->admin)->post('/admin/agenda/instellingen/beschikbaarheid', ['weekday' => '3', 'startTime' => '07:00', 'endTime' => '12:00', 'location' => 'gymbase'])
            ->assertSessionHas('availability_success', 'Beschikbaarheid toegevoegd.');
        $w = Availability::query()->sole();
        $this->assertSame(3, $w->weekday);

        $this->actingAs($this->admin)->get('/admin/agenda/instellingen')
            ->assertSee('Verwijder woensdag 07:00–12:00')
            ->assertDontSee('Er zijn nog geen tijden ingesteld');
        $this->actingAs($this->admin)->post('/admin/agenda/instellingen/beschikbaarheid/verwijderen', ['id' => $w->id]);
        $this->assertSame(0, Availability::query()->count());
    }

    public function test_vrije_dagen_blokkeren(): void
    {
        $this->book([]);
        $this->actingAs($this->admin)->from('/admin/agenda/instellingen')
            ->post('/admin/agenda/instellingen/blokkades', ['fromDay' => $this->day, 'toDay' => Agenda::addDays($this->day, -1)])
            ->assertSessionHas('block_error', 'De einddatum moet na de begindatum liggen');
        $this->actingAs($this->admin)->post('/admin/agenda/instellingen/blokkades', ['fromDay' => $this->day, 'toDay' => Agenda::addDays($this->day, 1), 'reason' => 'Vakantie'])
            ->assertSessionHas('block_success', 'Periode geblokkeerd. Let op: er staan nog 1 afspraken in deze periode.');
        $b = BlockedPeriod::query()->sole();
        $this->assertTrue($b->starts_at->eq(Agenda::zonedTimeToUtc($this->day, '00:00')));
        $this->assertTrue($b->ends_at->eq(Agenda::zonedTimeToUtc(Agenda::addDays($this->day, 2), '00:00')));
        $this->assertSame('Vakantie', $b->reason);

        $this->actingAs($this->admin)->get('/admin/agenda/instellingen')->assertSee(' t/m ', false)->assertSee('· Vakantie', false);
        $this->actingAs($this->admin)->post('/admin/agenda/instellingen/blokkades/verwijderen', ['id' => $b->id]);
        $this->assertSame(0, BlockedPeriod::query()->count());
    }

    public function test_nieuwe_ical_link(): void
    {
        $old = AgendaServer::icalToken();
        $this->actingAs($this->admin)->post('/admin/agenda/instellingen/ical-link')->assertRedirect('/admin/agenda/instellingen#ical');
        $new = Setting::get('ical_token');
        $this->assertNotSame($old, $new);
        $this->get("/ical/{$old}.ics")->assertNotFound();
        $this->get("/ical/{$new}.ics")->assertOk();
    }

    public function test_ical_feed_met_geannuleerde_afspraak(): void
    {
        $this->book(['note' => 'Techniek']);
        $a = Appointment::query()->sole();
        $a->update(['status' => 'geannuleerd', 'cancelled_by' => 'klant', 'cancelled_at' => now()]);
        $ics = $this->get('/ical/'.AgendaServer::icalToken().'.ics')->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="steynpt.ics"')
            ->getContent();
        $this->assertStringContainsString("UID:appointment-{$a->id}@steynpt.nl", $ics);
        $this->assertStringContainsString('STATUS:CANCELLED', $ics);
        $this->assertStringContainsString('Notitie: Techniek', str_replace("\r\n ", '', $ics));
    }

    public function test_klik_op_leeg_moment_en_weergaven(): void
    {
        $this->actingAs($this->admin)->get("/admin/agenda?datum={$this->day}")->assertOk()
            ->assertSee('href="/admin/agenda/nieuw?datum='.$this->day.'&amp;tijd=09:00"', false)
            ->assertSee('aria-label="Volgende periode"', false);
        // Ongeldige datum of weergave: vandaag in de weekweergave.
        $this->actingAs($this->admin)->get('/admin/agenda?datum=31-12-2026&weergave=jaar')->assertOk()->assertSee('Week ');
    }
}
