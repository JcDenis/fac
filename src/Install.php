<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       fac installation class.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
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
            self::growUp();

            // Set module settings
            foreach ($mod_conf as $v) {
                My::settings()->put(
                    $v[0],
                    $v[2],
                    $v[3],
                    $v[1],
                    false,
                    true
                );
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    private static function growUp(): void
    {
        // version < 1.0 : upgrade settings id and ns and array
        $current = App::version()->getVersion(My::id());
        if ($current && version_compare($current, '1.0', '<')) {
            $record = App::con()->select(
                'SELECT * FROM ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'fac' "
            );
            while ($record->fetch()) {
                if (preg_match('/^fac_(.*?)$/', $record->f('setting_id'), $match)) {
                    $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
                    if (in_array($record->f('setting_id'), ['fac_public_tpltypes', 'fac_formats'])) {
                        $cur->setField('setting_value', json_encode(@unserialize($record->f('setting_value'))));
                    }
                    $cur->setField('setting_id', $match[1]);
                    $cur->SetField('setting_ns', My::id());
                    $cur->update(
                        "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'fac' " .
                        'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . App::con()->escapeStr($record->f('blog_id')) . "' "))
                    );
                }
            }
        }
    }
}
