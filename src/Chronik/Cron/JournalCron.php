<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Cron;

use Darthmax\RetroBundle\Chronik\Journal\JournalDienst;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Psr\Log\LoggerInterface;

/**
 * Täglich: Tagesprotokoll für alle noch nicht erfassten Tage bis gestern (Chronik-Paket).
 *
 * Läuft über Contaos Cron. Dafür muss auf dem Server einmal der System-Cron eingerichtet sein:
 *   * * * * * php /pfad/zu/contao/vendor/bin/contao-console contao:cron
 * Verpasste Tage (Server aus, Cron gestoppt) werden beim nächsten Lauf nachgeholt.
 */
#[AsCronJob('daily')]
final class JournalCron
{
    public function __construct(
        private readonly JournalDienst $journal,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(): void
    {
        foreach ($this->journal->nachholen() as $e) {
            if ($e['neu']) {
                $this->logger?->info(sprintf('Chronik-Journal %s: %s', $e['tag'], $e['zusammenfassung']));
            }
        }
    }
}
