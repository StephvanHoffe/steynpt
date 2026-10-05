<?php

namespace App\Console\Commands;

use App\Auth\Accounts;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Noodgeval: tweestapsverificatie van een account uitzetten (bijvoorbeeld als Steyn zijn telefoon én herstelcodes kwijt is).
 * Het account wordt overal uitgelogd en koppelt bij de volgende keer inloggen een nieuwe telefoon.
 *
 *   php artisan steynpt:reset-2fa steyn@steynpt.nl
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'steynpt:reset-2fa {email : E-mailadres van het account}';

    protected $description = 'Tweestapsverificatie van een account uitzetten (noodgeval)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Geen account gevonden met {$email}.");

            return self::FAILURE;
        }
        Accounts::clearTwoFactor($user);
        $this->info("Tweestapsverificatie voor {$email} is uitgezet. Bij de volgende keer inloggen wordt een nieuwe telefoon gekoppeld.");

        return self::SUCCESS;
    }
}
