<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zeigt Datum und Bearbeiter der letzten Änderung des übergeordneten
 * Datensatzes (Artikel, Nachricht, Kalendereintrag) samt seiner Inhaltselemente.
 * Als Bearbeiter wird der Benutzername (tl_user.username) ausgegeben,
 * nie der Klarname. Fallback: in tl_version gespeicherter Benutzername.
 */
#[AsContentElement('last_edited', category: 'texts')]
class LastEditedController extends AbstractContentElementController
{
    private const PARENT_TABLES = ['tl_article', 'tl_news', 'tl_calendar_events'];

    public function __construct(private readonly Connection $connection)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $table = (string) $model->ptable;
        $id = (int) $model->pid;

        if (!\in_array($table, self::PARENT_TABLES, true) || $id <= 0) {
            return new Response();
        }

        $row = $this->connection->fetchAssociative(
            "SELECT x.tstamp, COALESCE(NULLIF(u.username, ''), NULLIF(x.username, '')) AS editor
             FROM (
                 SELECT v.tstamp, v.userid, v.username
                 FROM tl_version v
                 WHERE v.fromTable = :table AND v.pid = :id
                 UNION ALL
                 SELECT v.tstamp, v.userid, v.username
                 FROM tl_version v
                 INNER JOIN tl_content c ON c.id = v.pid
                 WHERE v.fromTable = 'tl_content' AND c.ptable = :table AND c.pid = :id
             ) x
             LEFT JOIN tl_user u ON u.id = x.userid
             ORDER BY x.tstamp DESC
             LIMIT 1",
            ['table' => $table, 'id' => $id]
        );

        $timestamp = $row ? (int) $row['tstamp'] : 0;
        $editor = $row ? (string) ($row['editor'] ?? '') : '';

        // Fallback, falls alte Versionen bereits bereinigt wurden
        if ($timestamp <= 0) {
            $timestamp = (int) $this->connection->fetchOne(
                sprintf('SELECT tstamp FROM %s WHERE id = ?', $table),
                [$id]
            );
        }

        if ($timestamp <= 0) {
            return new Response();
        }

        $template->set('edited_date', date('d.m.Y', $timestamp));
        $template->set('edited_time', date('H:i', $timestamp));
        $template->set('editor', $editor);

        $this->tagResponse(sprintf('contao.db.%s.%d', $table, $id));

        return $template->getResponse();
    }
}
