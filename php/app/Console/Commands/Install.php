<?php

namespace App\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Installatie op de hosting zonder bestanden te bewerken: stelt een paar vragen, controleert de database,
 * schrijft de antwoorden in .env, maakt een geheime sleutel en de tabellen, en toont de regel voor de cronjob.
 * Opnieuw draaien kan altijd (bijvoorbeeld om later de API-sleutel toe te voegen): Enter houdt wat er al staat.
 *
 *   php artisan steynpt:installeren && php artisan optimize
 */
class Install extends Command
{
    protected $signature = 'steynpt:installeren';

    protected $description = 'De site instellen: database, beheerder en AI-sleutel (.env), daarna sleutel en tabellen';

    public function handle(): int
    {
        $path = $this->laravel->environmentFilePath();
        if (! is_file($path) && ! copy(base_path('.env.example'), $path)) {
            $this->error("Kan {$path} niet aanmaken. Controleer of je in de map van de site staat.");

            return self::FAILURE;
        }
        $current = Dotenv::parse((string) file_get_contents($path));
        $connection = self::connectionName($current);

        $this->newLine();
        $this->line('<options=bold>De SteynPT-website instellen</>');
        $this->line('Beantwoord de vragen hieronder. Staat er iets tussen [haken], dan houd je dat met Enter.');
        $this->newLine();

        $url = $this->askUrl($current['APP_URL'] ?? '');

        // Database: net zo lang vragen tot de verbinding lukt (of tot je stopt).
        while (true) {
            $db = [
                'host' => ($current['DB_HOST'] ?? '') ?: 'localhost',
                'port' => ($current['DB_PORT'] ?? '') ?: '3306',
                'database' => $this->askRequired('Naam van de database (zie DirectAdmin › MySQL-beheer)', $current['DB_DATABASE'] ?? ''),
                'username' => $this->askRequired('Gebruikersnaam van de database', $current['DB_USERNAME'] ?? ''),
                'password' => $this->askSecret('Wachtwoord van de database', $current['DB_PASSWORD'] ?? '', true),
            ];
            $this->line('Verbinding met de database controleren…');
            $error = $this->databaseError($connection, $db);
            if ($error === null) {
                $this->info('✓ De database werkt.');
                break;
            }
            $this->error('De database geeft geen toegang: '.$error);
            $this->line('Controleer in DirectAdmin › MySQL-beheer de naam van de database, de gebruiker en het wachtwoord.');
            if (! $this->confirm('Opnieuw invullen?', true)) {
                $this->line('Er is niets opgeslagen.');

                return self::FAILURE;
            }
            $current = [...$current, 'DB_DATABASE' => $db['database'], 'DB_USERNAME' => $db['username']];
        }

        $admin = $this->askEmail($current['ADMIN_EMAILS'] ?? '');
        $apiKey = $this->askSecret('API-sleutel voor de AI-schema\'s (leeg laten mag, dan kan dat later)', $current['ANTHROPIC_API_KEY'] ?? '', false);
        if ($apiKey !== '' && ! str_starts_with($apiKey, 'sk-ant-')) {
            $this->warn('Let op: een API-sleutel van Anthropic begint meestal met sk-ant-. Controleer of je de hele sleutel hebt geplakt.');
        }

        $values = [
            'APP_URL' => $url,
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'ADMIN_EMAILS' => $admin,
            'ANTHROPIC_API_KEY' => $apiKey,
        ];
        // De geheime sleutel alleen de eerste keer maken: een nieuwe sleutel maakt opgeslagen gegevens onleesbaar.
        if (($current['APP_KEY'] ?? '') === '') {
            $values['APP_KEY'] = 'base64:'.base64_encode(Encrypter::generateKey((string) config('app.cipher', 'AES-256-CBC')));
        }
        if (file_put_contents($path, self::setValues((string) file_get_contents($path), $values)) === false) {
            $this->error("Kan {$path} niet opslaan.");

            return self::FAILURE;
        }
        $this->info('✓ Instellingen opgeslagen.');
        // Een oude kopie van de instellingen weghalen, zodat de nieuwe meteen gelden.
        $this->callSilent('config:clear');

        // Tabellen aanmaken met de zojuist gecontroleerde database. Bij de eerste keer bestond .env nog niet
        // toen dit commando startte; daarom de verbinding hier zelf instellen.
        config(['database.default' => $connection]);
        if (config("database.connections.{$connection}.driver") !== 'sqlite') {
            config(["database.connections.{$connection}" => [...config("database.connections.{$connection}"), ...$db]]);
            DB::purge($connection);
        }
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Het aanmaken van de tabellen is niet gelukt (zie de melding hierboven).');

            return self::FAILURE;
        }
        $this->info('✓ De tabellen staan klaar.');

        $this->newLine();
        $this->line('<options=bold>Klaar!</> Gebruik voor de cronjob in DirectAdmin precies deze regel:');
        $this->newLine();
        $this->line('  '.$this->cronCommand());
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Welke databaseverbinding de site gebruikt: een echte omgevingsvariabele gaat voor (zoals bij de tests),
     * anders DB_CONNECTION uit .env.
     */
    private static function connectionName(array $env): string
    {
        $name = Env::get('DB_CONNECTION') ?: ($env['DB_CONNECTION'] ?? null) ?: 'mysql';

        return is_array(config("database.connections.{$name}")) ? $name : 'mysql';
    }

    /** Foutmelding als de database niet bereikbaar is met deze gegevens, anders null. */
    protected function databaseError(string $connection, array $db): ?string
    {
        $name = 'steynpt_installatie';
        $base = config("database.connections.{$connection}");
        config(["database.connections.{$name}" => [...($base['driver'] !== 'sqlite' ? $base : config('database.connections.mysql')), ...$db]]);
        try {
            DB::connection($name)->getPdo();

            return null;
        } catch (Throwable $e) {
            return self::describeDatabaseError($e->getMessage());
        } finally {
            DB::purge($name);
        }
    }

    /** De melding van MySQL in gewone taal. */
    public static function describeDatabaseError(string $message): string
    {
        return match (true) {
            str_contains($message, 'Access denied') && str_contains($message, 'to database') => 'deze gebruiker mag niet bij deze database (of de naam van de database klopt niet).',
            str_contains($message, 'Access denied') => 'de gebruikersnaam of het wachtwoord klopt niet.',
            str_contains($message, 'Unknown database') => 'er bestaat geen database met deze naam.',
            str_contains($message, 'Connection refused'), str_contains($message, 'No such file'), str_contains($message, 'getaddrinfo') => 'de databaseserver is niet bereikbaar.',
            default => trim(strtok($message, "\n") ?: $message),
        };
    }

    /** De regel voor de cronjob, met het pad van deze site en deze PHP-versie. Vimexx keurt regels af die met cd beginnen. */
    protected function cronCommand(): string
    {
        return PHP_BINARY.' '.base_path('artisan').' schedule:run >/dev/null 2>&1';
    }

    /**
     * Zet waarden in de tekst van een .env-bestand: bestaande regels worden vervangen, ontbrekende achteraan toegevoegd.
     *
     * @param  array<string, string>  $values
     */
    public static function setValues(string $contents, array $values): string
    {
        foreach ($values as $key => $value) {
            $line = $key.'='.self::quote($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace_callback($pattern, fn () => $line, $contents, 1);
            } else {
                $contents = rtrim($contents, "\n")."\n".$line."\n";
            }
        }

        return $contents;
    }

    /** Waarde zoals .env hem verwacht: gewone tekens los, al het andere tussen aanhalingstekens. */
    public static function quote(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_.,:\/@+=-]*$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    private function askUrl(string $current): string
    {
        $default = $current !== '' ? $current : 'https://www.steynpt.nl';
        while (true) {
            $url = rtrim(trim((string) $this->ask('Adres van de website', $default)) ?: $default, '/');
            if (preg_match('#^https?://[^\s/]+$#', $url)) {
                return $url;
            }
            $this->warn('Vul het adres in zoals https://www.steynpt.nl');
        }
    }

    private function askEmail(string $current): string
    {
        $default = $current !== '' ? $current : 'steyn@steynpt.nl';
        while (true) {
            $email = strtolower(trim((string) $this->ask('E-mailadres van Steyn (dit account wordt beheerder)', $default)) ?: $default);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
            $this->warn('Dat is geen geldig e-mailadres.');
        }
    }

    private function askRequired(string $question, string $current): string
    {
        while (true) {
            $value = trim((string) $this->ask($question, $current !== '' ? $current : null)) ?: $current;
            if ($value !== '') {
                return $value;
            }
            $this->warn('Dit is nodig om verder te gaan.');
        }
    }

    /** Verborgen vraag (wachtwoord of sleutel). Enter houdt de huidige waarde. */
    private function askSecret(string $question, string $current, bool $required): string
    {
        $hint = $current !== '' ? ' (Enter = huidige houden)' : '';
        $this->line('<fg=gray>Je ziet niets terwijl je typt of plakt. Dat is normaal; druk daarna op Enter.</>');
        while (true) {
            $value = trim((string) $this->secret($question.$hint)) ?: $current;
            if ($value !== '' || ! $required) {
                return $value;
            }
            $this->warn('Dit is nodig om verder te gaan.');
        }
    }
}
