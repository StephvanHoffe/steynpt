<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

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
            $target = "{$base}.sql.gz";
            // Liefst mysqldump; kan de hosting geen programma's starten (proc_open uit), dan een export in PHP.
            $sql = self::canRunPrograms() ? $this->mysqldump($db) : null;
            File::put($target, gzencode($sql ?? $this->phpDump(), 9));
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

    private static function canRunPrograms(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && ! in_array('proc_open', $disabled, true);
    }

    /** mysqldump met het wachtwoord via de omgeving (komt zo niet in de proceslijst of de cronjob). */
    private function mysqldump(array $db): ?string
    {
        try {
            $result = Process::env(['MYSQL_PWD' => (string) $db['password']])->timeout(600)->run([
                'mysqldump', '--single-transaction', '--no-tablespaces', '--default-character-set=utf8mb4',
                '-h', (string) $db['host'], '-P', (string) $db['port'], '-u', (string) $db['username'], (string) $db['database'],
            ]);
        } catch (Throwable) {
            return null;
        }

        return $result->successful() && $result->output() !== '' ? $result->output() : null;
    }

    /** Export in PHP: per tabel de structuur en de rijen als INSERT-regels (zelfde opzet als mysqldump). */
    private function phpDump(): string
    {
        $pdo = DB::connection()->getPdo();
        $out = "-- Back-up SteynPT (PHP-export)\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n".$create['Create Table'].";\n\n";
            foreach (DB::table($table)->cursor() as $record) {
                $values = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values((array) $record));
                $out .= "INSERT INTO `{$table}` VALUES (".implode(',', $values).");\n";
            }
            $out .= "\n";
        }

        return $out."SET FOREIGN_KEY_CHECKS=1;\n";
    }
}
