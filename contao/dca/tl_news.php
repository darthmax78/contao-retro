<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Timeline (rg-27): eigener Bereich „Timeline“ in jeder News.
 * Bewusst eigene Felder statt Contaos „Hervorheben“ (featured), das für die Startseite genutzt wird.
 * Ohne Kurztitel/Kurztext nimmt das Element Überschrift und Teaser der News.
 */
if (!isset($GLOBALS['TL_DCA']['tl_news'])) {
    return;
}

$GLOBALS['TL_DCA']['tl_news']['palettes']['__selector__'][] = 'timeline_zeigen';
$GLOBALS['TL_DCA']['tl_news']['subpalettes']['timeline_zeigen'] = 'timeline_typ,timeline_titel,timeline_text,timeline_tags';

$manipulator = PaletteManipulator::create()
    ->addLegend('timeline_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField('timeline_zeigen', 'timeline_legend', PaletteManipulator::POSITION_APPEND)
;

foreach (array_keys($GLOBALS['TL_DCA']['tl_news']['palettes']) as $palette) {
    if ('__selector__' !== $palette && \is_string($GLOBALS['TL_DCA']['tl_news']['palettes'][$palette])) {
        $manipulator->applyToPalette($palette, 'tl_news');
    }
}

unset($manipulator);

$GLOBALS['TL_DCA']['tl_news']['fields']['timeline_zeigen'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_news']['fields']['timeline_typ'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['eintrag', 'meilenstein'],
    'reference' => &$GLOBALS['TL_LANG']['tl_news']['timeline_typ_opt'],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'eintrag'],
];

$GLOBALS['TL_DCA']['tl_news']['fields']['timeline_titel'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_news']['fields']['timeline_text'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'text', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_news']['fields']['timeline_tags'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];
