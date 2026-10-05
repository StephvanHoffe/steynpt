<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Back-up van de database naar storage/backups (gecomprimeerd), de oudste worden opgeruimd.
 * Draait elke nacht via de cronjob (routes/console.php); handmatig: php artisan steynpt:backup
 */
class Backup extends Command
{
    protected $signature = 'steynpt:backup {--days=30 : Zoveel dagen aan back-ups bewaren}';

    protected $description = 'Back-up van de database maken (storage/backups)';

    public function handle(): int
    {
        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");
        $base = $dir.'/steynpt-'.now('Europe/Amsterdam')->format('Y-m-d-His');

        if (($db['driver'] ?? null) === 'sqlite') {
            $target = "{$base}.sqlite";
            File::copy($db['database'], $target);
        } else {
            // mysqldump met het wachtwoord via de omgeving (komt zo niet in de proceslijst of de cronjob).
            $result = Process::env(['MYSQL_PWD' => (string) $db['password']])->timeout(600)->run([
                'mysqldump', '--single-transaction', '--no-tablespaces', '--default-character-set=utf8mb4',
                '-h', (string) $db['host'], '-P', (string) $db['port'], '-u', (string) $db['username'], (string) $db['database'],
            ]);
            if (! $result->successful() || $result->output() === '') {
                $this->error('Back-up mislukt: '.trim($result->errorOutput() ?: 'mysqldump gaf geen uitvoer'));

                return self::FAILURE;
            }
            $target = "{$base}.sql.gz";
            File::put($target, gzencode($result->output(), 9));
        }

        // Oude back-ups opruimen.
        $cutoff = now()->subDays((int) $this->option('days'))->getTimestamp();
        foreach (File::files($dir) as $file) {
            if (str_starts_with($file->getFilename(), 'steynpt-') && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
        $this->info('Back-up gemaakt: '.basename($target).' ('.round(filesize($target) / 1024).' kB)');

        return self::SUCCESS;
    }
}
