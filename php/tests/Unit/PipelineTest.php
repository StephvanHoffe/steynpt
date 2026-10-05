<?php

namespace Tests\Unit;

use App\Support\Plans\Pipeline;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    private const TODAY = '2026-10-03';

    private const BASE = ['type' => 'training', 'coachingStatus' => 'actief', 'wants' => ['training', 'voeding'], 'current' => null, 'open' => null];

    private static function published(string $publishedDay, ?string $renewOn = null, ?int $durationWeeks = null): array
    {
        return ['publishedDay' => $publishedDay, 'renewOn' => $renewOn, 'durationWeeks' => $durationWeeks];
    }

    private static function stage(array $input): array
    {
        return Pipeline::planStage([...self::BASE, ...$input], self::TODAY);
    }

    public function test_schema_planning_looptijd(): void
    {
        $this->assertSame('2026-11-26', Pipeline::defaultRenewOn('training', '2026-10-01', 8), 'trainingsschema volgt de duur uit het schema');
        $this->assertSame('2026-11-12', Pipeline::defaultRenewOn('training', '2026-10-01', null), 'zonder duur 6 weken');
        $this->assertSame('2026-11-12', Pipeline::defaultRenewOn('training', '2026-10-01', 99), 'onzinnige duur wordt genegeerd');
        $this->assertSame('2026-10-29', Pipeline::defaultRenewOn('voeding', '2026-10-01', 8), 'voeding altijd 4 weken');
        $this->assertSame(3, Pipeline::defaultRenewWeeks('training', 2.5), 'afronden zoals Math.round');
        $this->assertSame(6, Pipeline::defaultRenewWeeks('training', 0));
    }

    public function test_schema_planning_wie_staat_in_het_overzicht(): void
    {
        $this->assertTrue(Pipeline::inPipeline(self::BASE));
        $this->assertFalse(Pipeline::inPipeline([...self::BASE, 'coachingStatus' => 'geen']), 'geen coaching en geen schema');
        $this->assertTrue(Pipeline::inPipeline([...self::BASE, 'coachingStatus' => 'geen', 'current' => self::published('2026-09-01')]), 'wel als er al een schema is');
        $this->assertFalse(Pipeline::inPipeline([...self::BASE, 'wants' => ['voeding']]), 'klant wil geen trainingsschema');
        $this->assertTrue(Pipeline::inPipeline([...self::BASE, 'wants' => null]), 'zonder intake weten we het nog niet');
        $this->assertFalse(Pipeline::inPipeline([...self::BASE, 'coachingStatus' => 'gestopt']));
        $this->assertTrue(Pipeline::inPipeline([...self::BASE, 'coachingStatus' => 'geen', 'scheduled' => ['startsOn' => '2026-10-12']]));
    }

    public function test_schema_planning_fases(): void
    {
        $this->assertSame(['stage' => 'intake', 'dueOn' => null], self::stage(['wants' => null]));
        $this->assertSame(['stage' => 'eerste', 'dueOn' => null], self::stage([]));
        $this->assertSame('controleren', self::stage(['open' => ['status' => 'concept', 'stuck' => false]])['stage']);
        $this->assertSame('bezig', self::stage(['open' => ['status' => 'genereren', 'stuck' => false]])['stage']);
        $this->assertSame('mislukt', self::stage(['open' => ['status' => 'genereren', 'stuck' => true]])['stage']);
        $this->assertSame('mislukt', self::stage(['open' => ['status' => 'fout', 'stuck' => false], 'coachingStatus' => 'gepauzeerd'])['stage'], 'een open concept gaat voor');

        $this->assertSame(['stage' => 'verlopen', 'dueOn' => '2026-10-03'], self::stage(['current' => self::published('2026-08-01', '2026-10-03')]), 'vandaag is de dag');
        $this->assertSame('binnenkort', self::stage(['current' => self::published('2026-08-01', '2026-10-10')])['stage']);
        $this->assertSame('actief', self::stage(['current' => self::published('2026-08-01', '2026-10-11')])['stage']);
        $this->assertSame(['stage' => 'actief', 'dueOn' => '2026-10-13'], self::stage(['current' => self::published('2026-09-01', null, 6)]), 'zonder datum: publicatie + looptijd');
        $this->assertSame('pauze', self::stage(['current' => self::published('2026-08-01', '2026-09-01'), 'coachingStatus' => 'gepauzeerd'])['stage']);
        $this->assertSame(
            ['stage' => 'gepland', 'dueOn' => '2026-10-05'],
            self::stage(['current' => self::published('2026-08-01', '2026-10-05'), 'scheduled' => ['startsOn' => '2026-10-05']]),
            'volgend schema al ingepland: geen actie nodig',
        );
        $this->assertSame('gepland', self::stage(['scheduled' => ['startsOn' => '2026-10-12']])['stage'], 'ook een eerste schema kan later ingaan');
        $this->assertSame('controleren', self::stage(['scheduled' => ['startsOn' => '2026-10-12'], 'open' => ['status' => 'concept', 'stuck' => false]])['stage']);
        $this->assertSame(
            ['stage' => 'controleren', 'dueOn' => '2026-09-30'],
            self::stage(['current' => self::published('2026-08-01', '2026-09-30'), 'open' => ['status' => 'concept', 'stuck' => false]]),
            'nieuw concept voor een verlopen schema',
        );
    }

    public function test_schema_planning_volgorde_tellers_en_relatieve_dagen(): void
    {
        $rows = [
            ['name' => 'Actief', 'stage' => 'actief', 'dueOn' => '2026-11-01'],
            ['name' => 'Bijna', 'stage' => 'binnenkort', 'dueOn' => '2026-10-08'],
            ['name' => 'Laat', 'stage' => 'verlopen', 'dueOn' => '2026-09-20'],
            ['name' => 'Nieuw', 'stage' => 'eerste', 'dueOn' => null],
            ['name' => 'Concept', 'stage' => 'controleren', 'dueOn' => null],
        ];
        $sorted = $rows;
        usort($sorted, Pipeline::compareByUrgency(...));
        $this->assertSame(['Laat', 'Nieuw', 'Concept', 'Bijna', 'Actief'], array_column($sorted, 'name'));
        $this->assertSame(['wacht' => 2, 'controleren' => 1, 'binnenkort' => 1, 'ingepland' => 0, 'actief' => 1, 'intake' => 0, 'pauze' => 0], Pipeline::countGroups($rows));

        $this->assertSame('vandaag', Pipeline::relativeDay(self::TODAY, self::TODAY));
        $this->assertSame('morgen', Pipeline::relativeDay(self::TODAY, '2026-10-04'));
        $this->assertSame('gisteren', Pipeline::relativeDay(self::TODAY, '2026-10-02'));
        $this->assertSame('over 5 dagen', Pipeline::relativeDay(self::TODAY, '2026-10-08'));
        $this->assertSame('3 dagen geleden', Pipeline::relativeDay(self::TODAY, '2026-09-30'));
        $this->assertSame('over 4 weken', Pipeline::relativeDay(self::TODAY, '2026-11-01'));
        $this->assertSame('over 2 dagen', Pipeline::relativeDay('2026-10-24', '2026-10-26'), 'over de wintertijd heen');
    }

    public function test_namen_sorteren_zoals_locale_compare_nl(): void
    {
        $rows = array_map(fn ($n) => ['name' => $n, 'stage' => 'actief', 'dueOn' => null], ['eva', 'Bob', 'Émile', 'Anna', 'anna', 'Zoë']);
        usort($rows, Pipeline::compareByUrgency(...));
        $this->assertSame(['anna', 'Anna', 'Bob', 'Émile', 'eva', 'Zoë'], array_column($rows, 'name'));
    }

    public function test_fases_en_groepen(): void
    {
        $this->assertSame('wacht', Pipeline::STAGES['verlopen']['group']);
        $this->assertSame('Nieuw schema binnen 7 dagen', Pipeline::STAGE_GROUPS[2]['hint']);
        $this->assertSame(7, Pipeline::SOON_DAYS);
    }
}
