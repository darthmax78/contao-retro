<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Command;

use Darthmax\RetroBundle\Chronik\Journal\GithubCommits;
use Darthmax\RetroBundle\Chronik\Journal\JournalDienst;
use Darthmax\RetroBundle\Chronik\Journal\JournalUebersicht;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konsole für das Chronik-Journal.
 *
 *   chronik:journal erfassen                       verpasste Tage bis gestern (wie der Cronjob)
 *   chronik:journal erfassen --von=2023-08-13      rückwirkend alle Tage eines Zeitraums speichern
 *   chronik:journal erfassen 2026-11-08 --neu      einen Tag neu erfassen (überschreibt)
 *   chronik:journal uebersicht --von=2023-08-13    Tabelle aller Änderungstage (ohne Speichern)
 *   chronik:journal zeigen --von=2026-11-01        gespeicherte Protokolle als Markdown (Grundlage für News)
 */
#[AsCommand(name: 'chronik:journal', description: 'Tagesprotokoll der Seite erfassen, auswerten und ausgeben')]
final class JournalCommand extends Command
{
    public function __construct(
        private readonly JournalDienst $journal,
        private readonly JournalUebersicht $uebersicht,
        private readonly GithubCommits $github,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('aktion', InputArgument::REQUIRED, 'erfassen | uebersicht | zeigen')
            ->addArgument('tag', InputArgument::OPTIONAL, 'einzelner Tag (JJJJ-MM-TT)')
            ->addOption('von', null, InputOption::VALUE_REQUIRED, 'Beginn (JJJJ-MM-TT)')
            ->addOption('bis', null, InputOption::VALUE_REQUIRED, 'Ende (JJJJ-MM-TT), Vorgabe: gestern')
            ->addOption('neu', null, InputOption::VALUE_NONE, 'bereits erfasste Tage neu erfassen')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tag = $input->getArgument('tag');
        $von = $tag ?: $input->getOption('von');
        $bis = $tag ?: ($input->getOption('bis') ?: (new \DateTimeImmutable('yesterday'))->format('Y-m-d'));

        foreach (array_filter([$von, $bis]) as $d) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d)) {
                $output->writeln("<error>Datum bitte als JJJJ-MM-TT: $d</error>");

                return Command::INVALID;
            }
        }

        switch ($input->getArgument('aktion')) {
            case 'erfassen':
                $ergebnisse = [];

                if (!$von) {
                    $ergebnisse = $this->journal->nachholen();
                } else {
                    // Commits des ganzen Zeitraums auf einmal holen (GitHub-Limit ohne Token: 60/h)
                    $this->github->vorladen(
                        (new \DateTimeImmutable($von))->setTime(0, 0)->getTimestamp(),
                        (new \DateTimeImmutable($bis))->setTime(23, 59, 59)->getTimestamp(),
                    );

                    for ($d = new \DateTimeImmutable($von); $d <= new \DateTimeImmutable($bis); $d = $d->modify('+1 day')) {
                        $ergebnisse[] = $this->journal->erfassen($d, (bool) $input->getOption('neu'));
                    }
                }

                $aktiv = 0;

                foreach ($ergebnisse as $e) {
                    $aktiv += $e['leer'] ? 0 : 1;

                    if (!$e['leer'] || $output->isVerbose()) {
                        $output->writeln(sprintf('%s %s %s', $e['tag'], $e['neu'] ? 'erfasst ' : 'vorhanden', $e['zusammenfassung']));
                    }
                }

                $output->writeln(sprintf('%d Tage geprüft, %d mit Änderungen.', \count($ergebnisse), $aktiv));

                return Command::SUCCESS;

            case 'uebersicht':
                if (!$von) {
                    $output->writeln('<error>--von=JJJJ-MM-TT angeben, z. B. das Installationsdatum der Seite</error>');

                    return Command::INVALID;
                }

                $output->write($this->uebersicht->markdown(new \DateTimeImmutable($von), new \DateTimeImmutable($bis)));

                return Command::SUCCESS;

            case 'zeigen':
                $eintraege = $this->journal->lesen($von ?: $bis, $bis);

                if (!$eintraege) {
                    $output->writeln('Keine Protokolle mit Änderungen in diesem Zeitraum.');

                    return Command::SUCCESS;
                }

                foreach ($eintraege as $e) {
                    $output->writeln($e['inhalt']);
                }

                return Command::SUCCESS;
        }

        $output->writeln('<error>Aktion: erfassen, uebersicht oder zeigen</error>');

        return Command::INVALID;
    }
}
