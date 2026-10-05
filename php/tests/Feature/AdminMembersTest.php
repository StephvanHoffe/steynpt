<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Measurement;
use App\Models\RecoveryCode;
use App\Models\User;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Beheer: coaching van een lid, metingen, tweestapsverificatie resetten, vriendenkorting en contactaanvragen. */
class AdminMembersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['first_name' => 'Steyn']);
    }

    public function test_coachingstatus_en_bericht_opslaan(): void
    {
        $member = User::factory()->create(['coaching_status' => 'aangevraagd']);
        $this->actingAs($this->admin)->from("/admin/leden/{$member->id}")
            ->post("/admin/leden/{$member->id}/coaching", ['userId' => $member->id, 'coachingStatus' => 'actief', 'coachNote' => '  Goed bezig met de eerste week!  '])
            ->assertRedirect("/admin/leden/{$member->id}")
            ->assertSessionHas('coaching_success', 'Opgeslagen. De klant ziet het bericht direct in Mijn omgeving.');
        $member->refresh();
        $this->assertSame('actief', $member->coaching_status);
        $this->assertSame('Goed bezig met de eerste week!', $member->coach_note);

        // Leeg bericht wordt null; een onbekende status wordt geweigerd.
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/coaching", ['coachingStatus' => 'gestopt', 'coachNote' => ''])->assertSessionHas('coaching_success');
        $this->assertNull($member->refresh()->coach_note);
        $this->actingAs($this->admin)->get("/admin/leden/{$member->id}")->assertOk()->assertSee('<option value="gestopt" selected', false);
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/coaching", ['coachingStatus' => 'vip'])->assertSessionHas('coaching_error', 'Controleer de invoer.');
        $this->assertSame('gestopt', $member->refresh()->coaching_status);
    }

    public function test_meting_toevoegen_controleert_en_slaat_op(): void
    {
        $member = User::factory()->create();
        $today = Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];

        // Zonder meetwaarde: foutmelding bij gewicht, ingevulde waarden blijven staan.
        $this->actingAs($this->admin)->from("/admin/leden/{$member->id}")
            ->post("/admin/leden/{$member->id}/metingen", ['userId' => $member->id, 'measuredAt' => $today, 'weight' => '', 'note' => 'nuchter'])
            ->assertRedirect("/admin/leden/{$member->id}#metingen")
            ->assertSessionHasErrorsIn('measurement', ['weight' => 'Vul minimaal één meetwaarde in']);
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/metingen", ['measuredAt' => $today, 'weight' => '500'])
            ->assertSessionHasErrorsIn('measurement', ['weight' => 'Gewicht lijkt niet te kloppen']);
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/metingen", ['measuredAt' => 'gisteren', 'weight' => '80'])
            ->assertSessionHasErrorsIn('measurement', ['measuredAt' => 'Kies een datum']);
        $this->assertSame(0, Measurement::query()->count());

        // Komma's mogen; de datum wordt 12:00 Nederlandse tijd (UTC+1) en lege velden worden null.
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/metingen", ['userId' => $member->id, 'measuredAt' => '2026-09-01', 'weight' => '80,4', 'bodyFat' => '23,1', 'waist' => '', 'note' => ''])
            ->assertRedirect("/admin/leden/{$member->id}#metingen")
            ->assertSessionHas('measurement_success', 'Meting opgeslagen. De klant ziet hem direct in Mijn omgeving.');
        $m = Measurement::query()->sole();
        $this->assertSame($member->id, $m->user_id);
        $this->assertSame('2026-09-01 11:00:00', $m->measured_at->format('Y-m-d H:i:s'));
        $this->assertSame(80.4, $m->weight);
        $this->assertSame(23.1, $m->body_fat);
        $this->assertNull($m->waist);
        $this->assertNull($m->note);

        $this->actingAs($this->admin)->get("/admin/leden/{$member->id}")->assertOk()
            ->assertSee('Meting van dinsdag 1 september verwijderen')->assertSee('80,4');
    }

    public function test_meting_verwijderen_alleen_van_dit_lid(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $mine = Measurement::query()->create(['user_id' => $member->id, 'measured_at' => now(), 'weight' => 80]);
        $theirs = Measurement::query()->create(['user_id' => $other->id, 'measured_at' => now(), 'weight' => 70]);

        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/metingen/verwijderen", ['id' => $theirs->id, 'userId' => $member->id]);
        $this->assertNotNull($theirs->fresh());
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/metingen/verwijderen", ['id' => $mine->id, 'userId' => $member->id])
            ->assertRedirect("/admin/leden/{$member->id}#metingen");
        $this->assertNull($mine->fresh());
    }

    public function test_tweestapsverificatie_van_een_lid_resetten(): void
    {
        $member = User::factory()->create(['first_name' => 'Anna']);
        RecoveryCode::query()->create(['user_id' => $member->id, 'code_hash' => str_repeat('a', 64)]);
        DB::table('sessions')->insert(['id' => 'sessie-anna', 'user_id' => $member->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->admin)->get("/admin/leden/{$member->id}")->assertSee('1 herstelcode over');
        $this->actingAs($this->admin)->post("/admin/leden/{$member->id}/tweestaps-resetten", ['userId' => $member->id])
            ->assertRedirect("/admin/leden/{$member->id}#beveiliging");
        $member->refresh();
        $this->assertNull($member->totp_enabled_at);
        $this->assertNull($member->totp_secret);
        $this->assertSame(0, RecoveryCode::query()->where('user_id', $member->id)->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $member->id)->count());
        $this->actingAs($this->admin)->get("/admin/leden/{$member->id}")->assertSee('nog niet ingesteld')->assertDontSee('Ja, resetten');

        // Een andere beheerder wordt niet gereset.
        $other = User::factory()->admin()->create();
        $this->actingAs($this->admin)->post("/admin/leden/{$other->id}/tweestaps-resetten");
        $this->assertNotNull($other->refresh()->totp_enabled_at);
    }

    public function test_vriendenkorting_verrekenen(): void
    {
        $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Jansen']);
        $bram = User::factory()->create(['first_name' => 'Bram', 'last_name' => 'Test', 'coaching_status' => 'actief', 'referred_by_id' => $anna->id]);

        $this->actingAs($this->admin)->get('/admin')->assertSee('bracht Bram Test aan', false);
        $this->actingAs($this->admin)->from('/admin')->post('/admin/vriendenkorting', ['friendId' => $bram->id])->assertRedirect('/admin');
        $this->assertNotNull($bram->refresh()->referral_reward_at);
        $this->actingAs($this->admin)->get('/admin')->assertDontSee('bracht Bram Test aan', false)->assertSee('Alles is bijgewerkt.');
    }

    public function test_contactaanvraag_afhandelen_en_terugzetten(): void
    {
        $request = ContactRequest::query()->create(['name' => 'Bedrijf BV', 'email' => 'hr@bedrijf.nl', 'interest' => 'ademcoaching']);
        $badge = '<span class="sr-only"> open</span>';
        $this->actingAs($this->admin)->get('/admin/aanvragen')->assertSee($badge, false);

        $this->actingAs($this->admin)->from('/admin/aanvragen')->post('/admin/aanvragen/afhandelen', ['id' => $request->id])->assertRedirect('/admin/aanvragen');
        $this->assertTrue($request->refresh()->handled);
        $this->actingAs($this->admin)->get('/admin/aanvragen')->assertSee('Geen open aanvragen. Mooi zo!')->assertDontSee($badge, false);
        $this->actingAs($this->admin)->get('/admin/aanvragen?toon=afgehandeld')->assertSee('Terugzetten naar open');

        $this->actingAs($this->admin)->post('/admin/aanvragen/afhandelen', ['id' => $request->id]);
        $this->assertFalse($request->refresh()->handled);
    }
}
