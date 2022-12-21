<?php
/**
 * @brief fac, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

// Module specs
$mod_conf = [
    [
        'active',
        'Enabled fac plugin',
        false,
        'boolean',
    ],
    [
        'public_tpltypes',
        'List of templates types which used fac',
        json_encode(['post', 'tag', 'archive']),
        'string',
    ],
    [
        'formats',
        'Formats of feeds contents',
        json_encode([
            uniqid() => [
                'name'                   => 'default',
                'dateformat'             => '',
                'lineslimit'             => '5',
                'linestitletext'         => '%T',
                'linestitleover'         => '%D',
                'linestitlelength'       => '150',
                'showlinesdescription'   => '0',
                'linesdescriptionlength' => '350',
                'linesdescriptionnohtml' => '1',
                'showlinescontent'       => '0',
                'linescontentlength'     => '350',
                'linescontentnohtml'     => '1',
            ],
            uniqid() => [
                'name'                   => 'full',
                'dateformat'             => '',
                'lineslimit'             => '20',
                'linestitletext'         => '%T',
                'linestitleover'         => '%D - %E',
                'linestitlelength'       => '',
                'showlinesdescription'   => '1',
                'linesdescriptionlength' => '',
                'linesdescriptionnohtml' => '1',
                'showlinescontent'       => '1',
                'linescontentlength'     => '',
                'linescontentnohtml'     => '1',
            ],
        ]),
        'string',
        false,
        true,
    ],
    [
        'defaultfeedtitle',
        'Default title of feed',
        '%T',
        'string',
    ],
    [
        'showfeeddesc',
        'Show description of feed',
        1,
        'boolean',
    ],
];

// Nothing to change below
try {
    // Check module version
    if (!dcCore::app()->newVersion(
        basename(__DIR__),
        dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
    )) {
        return null;
    }

    // version < 1.0 : upgrade settings id and ns and array
    $current = dcCore::app()->getVersion(basename(__DIR__));
    if ($current && version_compare($current, '1.0', '<')) {
        $record = dcCore::app()->con->select(
            'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_ns = 'fac' "
        );
        while ($record->fetch()) {
            if (preg_match('/^fac_(.*?)$/', $record->setting_id, $match)) {
                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                if (in_array($record->setting_id, ['fac_public_tpltypes', 'fac_formats'])) {
                    $cur->setting_value = json_encode(@unserialize($record->setting_value));
                }
                $cur->setting_id = $match[1];
                $cur->setting_ns = basename(__DIR__);
                $cur->update(
                    "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'fac' " .
                    'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                );
            }
        }
    }

    // Set module settings
    dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
    foreach ($mod_conf as $v) {
        dcCore::app()->blog->settings->get(basename(__DIR__))->put(
            $v[0],
            $v[2],
            $v[3],
            $v[1],
            false,
            true
        );
    }

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());

    return false;
}
