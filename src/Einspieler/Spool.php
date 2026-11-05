<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Einspieler;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Übergabe an den Dienst rg-einspieler.
 *
 * Die Website legt nur Dateien in eingang/ und auftrag/ ab und liest status/. Geprüft
 * und ausgeführt wird allein im Dienst; was hier ankommt, gilt dort als nicht vertrauenswürdig.
 */
final class Spool
{
    public const MAX_GROESSE = 20 * 1024 * 1024;

    private const AKTIONEN = ['pruefen', 'probelauf', 'einspielen', 'zurueck'];

    /** Welche Knöpfe in welchem Zustand angeboten werden. Der Dienst prüft das selbst noch einmal. */
    private const KNOEPFE = [
        'geprueft' => ['probelauf'],
        'probelauf-fehler' => ['probelauf'],
        'probelauf-ok' => ['probelauf', 'einspielen'],
        'eingespielt' => ['zurueck'],
        'fehler' => ['zurueck'],
        'zurueckgenommen' => ['probelauf'],
    ];

    private const LAUFEND = ['probelauf-laeuft', 'laeuft', 'zurueck-laeuft'];

    private readonly string $basis;

    public function __construct(string $projektOrdner)
    {
        $this->basis = $projektOrdner.'/downloads/_einspieler';
    }

    public function istEingerichtet(): bool
    {
        return is_dir($this->basis.'/status')
            && is_readable($this->basis.'/status/dienst.json')
            && is_writable($this->basis.'/eingang')
            && is_writable($this->basis.'/auftrag');
    }

    /**
     * Legt Paket und Signatur ab und beauftragt die Prüfung.
     *
     * @return string Paketname, z. B. rg-16
     */
    public function annehmen(UploadedFile $paket, UploadedFile $signatur): string
    {
        foreach ([$paket, $signatur] as $datei) {
            if (!$datei->isValid()) {
                throw new \RuntimeException('Hochladen fehlgeschlagen: '.$datei->getErrorMessage());
            }

            if ($datei->getSize() > self::MAX_GROESSE) {
                throw new \RuntimeException('Datei ist größer als 20 MB.');
            }
        }

        $name = $paket->getClientOriginalName();

        if (1 !== preg_match('/^(rg-\d{2,4})\.tar\.gz$/D', $name, $treffer)) {
            throw new \RuntimeException('Das Paket muss rg-NN.tar.gz heißen.');
        }

        if ($signatur->getClientOriginalName() !== $name.'.minisig') {
            throw new \RuntimeException('Die Signatur muss '.$name.'.minisig heißen.');
        }

        $this->ablegen($paket, $this->basis.'/eingang/'.$name);
        $this->ablegen($signatur, $this->basis.'/eingang/'.$name.'.minisig');
        $this->beauftragen('pruefen', $treffer[1]);

        return $treffer[1];
    }

    public function beauftragen(string $aktion, string $paket): void
    {
        if (!\in_array($aktion, self::AKTIONEN, true) || 1 !== preg_match('/^rg-\d{2,4}$/D', $paket)) {
            throw new \RuntimeException('Ungültiger Auftrag.');
        }

        $inhalt = json_encode(['aktion' => $aktion, 'paket' => $paket], JSON_THROW_ON_ERROR);
        $ziel = $this->basis.'/auftrag/'.date('YmdHis').'-'.bin2hex(random_bytes(4)).'.json';
        $tmp = $this->basis.'/eingang/.auftrag-'.bin2hex(random_bytes(4));

        // Erst fertig schreiben, dann umbenennen: Der Dienst sieht nie eine halbe Datei.
        if (false === file_put_contents($tmp, $inhalt) || !chmod($tmp, 0640) || !rename($tmp, $ziel)) {
            @unlink($tmp);

            throw new \RuntimeException('Auftrag konnte nicht abgelegt werden.');
        }
    }

    public function auftraegeOffen(): bool
    {
        return [] !== (glob($this->basis.'/auftrag/*.json') ?: []);
    }

    /**
     * @return list<array<string, mixed>> neuestes Paket zuerst
     */
    public function pakete(): array
    {
        $pakete = [];

        foreach (glob($this->basis.'/status/rg-*.json') ?: [] as $datei) {
            $daten = json_decode((string) file_get_contents($datei), true);

            if (!\is_array($daten) || !isset($daten['paket'], $daten['zustand'])) {
                continue;
            }

            $daten['knoepfe'] = self::KNOEPFE[$daten['zustand']] ?? [];
            $daten['laeuft'] = \in_array($daten['zustand'], self::LAUFEND, true);
            $daten['protokoll'] = $this->protokoll((string) $daten['paket']);
            $pakete[] = $daten;
        }

        usort($pakete, static fn (array $a, array $b): int => strnatcmp((string) $b['paket'], (string) $a['paket']));

        return $pakete;
    }

    /** Das Ende des Protokolls; die Datei wächst mit jedem Lauf. */
    private function protokoll(string $paket): string
    {
        $datei = $this->basis.'/status/'.$paket.'.log';

        if (!is_readable($datei)) {
            return '';
        }

        $text = (string) file_get_contents($datei, false, null, max(0, filesize($datei) - 200000));

        return mb_scrub($text, 'UTF-8');
    }

    private function ablegen(UploadedFile $datei, string $ziel): void
    {
        $tmp = $ziel.'.'.bin2hex(random_bytes(4)).'.tmp';

        // copy() statt move(): Nur eine neu angelegte Datei übernimmt die Gruppe des
        // Ordners, und über die liest der Dienst sie.
        if (!copy($datei->getPathname(), $tmp) || !chmod($tmp, 0640) || !rename($tmp, $ziel)) {
            @unlink($tmp);

            throw new \RuntimeException('Datei konnte nicht im Eingang abgelegt werden.');
        }
    }
}
