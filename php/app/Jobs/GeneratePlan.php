<?php

namespace App\Jobs;

use App\Services\Plans\Generator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Maakt het AI-concept van één schema (status "genereren" -> "concept" of "fout").
 * Draait op de database-queue; op de hosting start de cronjob elke minuut een worker (routes/console.php).
 */
class GeneratePlan implements ShouldQueue
{
    use Queueable;

    /**
     * Maximale looptijd in seconden. De database-queue moet een grotere retry_after hebben
     * (DB_QUEUE_RETRY_AFTER, standaard 600), anders start een tweede worker dezelfde job opnieuw.
     */
    public const TIMEOUT = 540;

    /** Niet automatisch opnieuw: Steyn kan zelf een nieuw concept laten maken. */
    public int $tries = 1;

    public int $timeout = self::TIMEOUT;

    public bool $failOnTimeout = true;

    public function __construct(public int $planId) {}

    public function handle(): void
    {
        Generator::generate($this->planId);
    }

    /** Afgebroken (bijv. time-out): het concept niet op "genereren" laten staan. */
    public function failed(?Throwable $exception): void
    {
        Generator::markFailed($this->planId, $exception);
    }
}
