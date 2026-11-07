<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * Auswahl „Abschnitt auf dieser Seite“ für die Kachel (rg-20): alle Abschnittstitel
 * (sa_abschnitt) im selben Artikel bzw. Beitrag. Steht die Kachel in einem Kacheln-Rahmen,
 * ist ihr Elternteil das Rahmen-Element; gesucht wird dann eine Ebene höher.
 */
class KachelListener
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @return array<int, string>
     */
    #[AsCallback('tl_content', 'fields.kachel_abschnitt.options')]
    public function abschnitte(DataContainer|null $dc = null): array
    {
        $datensatz = $dc?->getCurrentRecord();

        if (!$datensatz) {
            return [];
        }

        $ptable = (string) ($datensatz['ptable'] ?? '');
        $pid = (int) ($datensatz['pid'] ?? 0);

        // Kindelement eines Rahmens: Artikel/Beitrag des Rahmens verwenden
        for ($i = 0; 'tl_content' === $ptable && $pid > 0 && $i < 5; ++$i) {
            $eltern = $this->db->fetchAssociative('SELECT ptable, pid FROM tl_content WHERE id = ?', [$pid]);

            if (!$eltern) {
                return [];
            }

            $ptable = (string) $eltern['ptable'];
            $pid = (int) $eltern['pid'];
        }

        $zeilen = $this->db->fetchAllAssociative(
            "SELECT id, headline, sa_kicker FROM tl_content
             WHERE ptable = ? AND pid = ? AND type = 'sa_abschnitt'
             ORDER BY sorting",
            [$ptable, $pid],
        );

        $optionen = [];

        foreach ($zeilen as $zeile) {
            $headline = StringUtil::deserialize($zeile['headline'], true);
            $titel = trim(StringUtil::decodeEntities((string) ($headline['value'] ?? '')));
            $kicker = trim(StringUtil::decodeEntities((string) $zeile['sa_kicker']));
            $optionen[(int) $zeile['id']] = ($kicker && $titel ? $kicker.' – '.$titel : ($kicker ?: $titel ?: 'Abschnitt '.$zeile['id']));
        }

        return $optionen;
    }
}
