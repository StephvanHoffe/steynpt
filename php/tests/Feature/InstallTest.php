<?php

namespace Tests\Feature;

use App\Console\Commands\Install;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Installatie op de hosting met php artisan steynpt:installeren (vragen in plaats van .env bewerken). */
class InstallTest extends TestCase
{
    use RefreshDatabase;

    private const Q_URL = 'Adres van de website';

    private const Q_DB = 'Naam van de database (zie DirectAdmin › MySQL-beheer)';

    private const Q_USER = 'Gebruikersnaam van de database';

    private const Q_PASSWORD = 'Wachtwoord van de database';

    private const Q_ADMIN = 'E-mailadres van Steyn (dit account wordt beheerder)';

    private const Q_KEY = "API-sleutel voor de AI-schema's (leeg laten mag, dan kan dat later)";

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        // Een eigen map voor .env, zodat de test het echte bestand niet aanraakt.
        $this->dir = sys_get_temp_dir().'/steynpt-installeren-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->dir);
        $this->app->useEnvironmentPath($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    /** Doet alsof de database achtereenvolgens deze antwoorden geeft (null = verbinding gelukt). */
    private function fakeDatabase(array $results): void
    {
        $this->app->bind(Install::class, fn () => new class($results) extends Install
        {
            public function __construct(private array $results)
            {
                parent::__construct();
            }

            protected function databaseError(string $connection, array $db): ?string
            {
                return array_shift($this->results);
            }
        });
    }

    private function env(): array
    {
        return Dotenv::parse((string) file_get_contents($this->dir.'/.env'));
    }

    public function test_eerste_installatie(): void
    {
        $this->fakeDatabase([null]);
        $password = 'Ge#heim "$HOME" \\ ${APP_NAME} 1';

        $this->artisan('steynpt:installeren')
            ->expectsQuestion(self::Q_URL, '')
            ->expectsQuestion(self::Q_DB, 'gebruiker_steynpt')
            ->expectsQuestion(self::Q_USER, 'gebruiker_steynpt')
            ->expectsQuestion(self::Q_PASSWORD, $password)
            ->expectsOutputToContain('De database werkt')
            ->expectsQuestion(self::Q_ADMIN, '')
            ->expectsQuestion(self::Q_KEY, 'sk-ant-test-123')
            ->expectsOutputToContain('Instellingen opgeslagen')
            ->expectsOutputToContain('De tabellen staan klaar')
            ->expectsOutputToContain('cd '.base_path().' && '.PHP_BINARY.' artisan schedule:run >/dev/null 2>&1')
            ->assertSuccessful();

        $env = $this->env();
        $this->assertSame('https://www.steynpt.nl', $env['APP_URL']);
        $this->assertSame('gebruiker_steynpt', $env['DB_DATABASE']);
        $this->assertSame('gebruiker_steynpt', $env['DB_USERNAME']);
        $this->assertSame($password, $env['DB_PASSWORD'], 'bijzondere tekens in het wachtwoord blijven precies zo');
        $this->assertSame('steyn@steynpt.nl', $env['ADMIN_EMAILS']);
        $this->assertSame('sk-ant-test-123', $env['ANTHROPIC_API_KEY']);
        $this->assertSame('production', $env['APP_ENV'], 'de rest komt uit .env.example');
        $this->assertStringStartsWith('base64:', $env['APP_KEY']);
        $this->assertSame(32, strlen(base64_decode(substr($env['APP_KEY'], 7))));
    }

    public function test_verkeerde_gegevens_opnieuw_invullen(): void
    {
        $this->fakeDatabase([Install::describeDatabaseError("SQLSTATE[HY000] [1045] Access denied for user 'x'@'localhost' (using password: YES)"), null]);

        $this->artisan('steynpt:installeren')
            ->expectsQuestion(self::Q_URL, 'https://steynpt.nl/')
            ->expectsQuestion(self::Q_DB, 'gebruiker_steynpt')
            ->expectsQuestion(self::Q_USER, 'gebruiker_fout')
            ->expectsQuestion(self::Q_PASSWORD, 'fout')
            ->expectsOutputToContain('de gebruikersnaam of het wachtwoord klopt niet')
            ->expectsConfirmation('Opnieuw invullen?', 'yes')
            // De vorige antwoorden staan klaar; Enter houdt ze.
            ->expectsQuestion(self::Q_DB, '')
            ->expectsQuestion(self::Q_USER, 'gebruiker_steynpt')
            ->expectsQuestion(self::Q_PASSWORD, 'goed')
            ->expectsQuestion(self::Q_ADMIN, 'Steyn@SteynPT.nl')
            ->expectsQuestion(self::Q_KEY, 'geen-anthropic-sleutel')
            ->expectsOutputToContain('begint meestal met sk-ant-')
            ->assertSuccessful();

        $env = $this->env();
        $this->assertSame('https://steynpt.nl', $env['APP_URL']);
        $this->assertSame('gebruiker_steynpt', $env['DB_DATABASE']);
        $this->assertSame('gebruiker_steynpt', $env['DB_USERNAME']);
        $this->assertSame('goed', $env['DB_PASSWORD']);
        $this->assertSame('steyn@steynpt.nl', $env['ADMIN_EMAILS']);
    }

    public function test_stoppen_als_de_database_niet_werkt(): void
    {
        $this->fakeDatabase(['er bestaat geen database met deze naam.']);

        $this->artisan('steynpt:installeren')
            ->expectsQuestion(self::Q_URL, '')
            ->expectsQuestion(self::Q_DB, 'bestaat_niet')
            ->expectsQuestion(self::Q_USER, 'gebruiker')
            ->expectsQuestion(self::Q_PASSWORD, 'wachtwoord')
            ->expectsOutputToContain('er bestaat geen database met deze naam')
            ->expectsConfirmation('Opnieuw invullen?', 'no')
            ->expectsOutputToContain('Er is niets opgeslagen')
            ->assertFailed();

        $env = $this->env();
        $this->assertSame('', $env['DB_DATABASE']);
        $this->assertSame('', $env['APP_KEY']);
    }

    public function test_opnieuw_draaien_houdt_wat_er_staat(): void
    {
        // Bijvoorbeeld om later alleen de API-sleutel toe te voegen.
        $key = 'base64:'.base64_encode(random_bytes(32));
        File::put($this->dir.'/.env', "APP_KEY={$key}\nAPP_URL=https://www.steynpt.nl\nDB_HOST=localhost\nDB_DATABASE=gebruiker_steynpt\nDB_USERNAME=gebruiker_steynpt\nDB_PASSWORD=\"oud#wachtwoord\"\nADMIN_EMAILS=steyn@steynpt.nl\nANTHROPIC_API_KEY=\n");
        $this->fakeDatabase([null]);

        $this->artisan('steynpt:installeren')
            ->expectsQuestion(self::Q_URL, '')
            ->expectsQuestion(self::Q_DB, '')
            ->expectsQuestion(self::Q_USER, '')
            ->expectsQuestion(self::Q_PASSWORD.' (Enter = huidige houden)', '')
            ->expectsQuestion(self::Q_ADMIN, '')
            ->expectsQuestion(self::Q_KEY, 'sk-ant-nieuw')
            ->assertSuccessful();

        $env = $this->env();
        $this->assertSame($key, $env['APP_KEY'], 'de geheime sleutel blijft dezelfde');
        $this->assertSame('oud#wachtwoord', $env['DB_PASSWORD']);
        $this->assertSame('gebruiker_steynpt', $env['DB_DATABASE']);
        $this->assertSame('sk-ant-nieuw', $env['ANTHROPIC_API_KEY']);
    }

    public function test_waarden_in_env_zetten(): void
    {
        $values = ['A' => 'gewoon-1.2_3', 'B' => 'met spatie', 'C' => 'p@ss#"\'$x\\y${A}', 'D' => '', 'NIEUW' => 'erbij'];
        $contents = Install::setValues("A=oud\nB=\nC=1\nC_EXTRA=blijft\nD=weg\n", $values);

        $this->assertStringContainsString("A=gewoon-1.2_3\n", $contents);
        $this->assertStringContainsString("C_EXTRA=blijft\n", $contents);
        $this->assertEquals([...$values, 'C_EXTRA' => 'blijft'], Dotenv::parse($contents));
    }

    public function test_meldingen_van_de_database_in_gewone_taal(): void
    {
        $this->assertSame('de gebruikersnaam of het wachtwoord klopt niet.', Install::describeDatabaseError("SQLSTATE[HY000] [1045] Access denied for user 'x'@'localhost' (using password: YES) (Connection: mysql, SQL: select 1)"));
        $this->assertSame('deze gebruiker mag niet bij deze database (of de naam van de database klopt niet).', Install::describeDatabaseError("SQLSTATE[HY000] [1044] Access denied for user 'x'@'localhost' to database 'y'"));
        $this->assertSame('er bestaat geen database met deze naam.', Install::describeDatabaseError("SQLSTATE[HY000] [1049] Unknown database 'y'"));
        $this->assertSame('de databaseserver is niet bereikbaar.', Install::describeDatabaseError('SQLSTATE[HY000] [2002] Connection refused'));
    }
}
