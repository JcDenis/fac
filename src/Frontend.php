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
declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use context;
use dcCore;
use dcNsProcess;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Feed\Reader;
use Exception;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_RC_PATH');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init || !dcCore::app()->blog->settings->get(My::id())->get('active')) {
            return false;
        }

        dcCore::app()->addBehavior('publicEntryAfterContent', function (dcCore $core, context $_ctx): void {
            # Not a post
            if (!dcCore::app()->ctx->exists('posts')) {
                return;
            }

            # Not in page to show
            $types = json_decode((string) dcCore::app()->blog->settings->get(My::id())->get('public_tpltypes'), true);
            if (!is_array($types)
             || !in_array(dcCore::app()->url->type, $types)) {
                return;
            }

            # Get related feed
            $fac_url = dcCore::app()->meta->getMetadata([
                'meta_type' => 'fac',
                'post_id'   => dcCore::app()->ctx->__get('posts')->f('post_id'),
                'limit'     => 1,
            ]);
            if ($fac_url->isEmpty()) {
                return;
            }

            # Get related format
            $fac_format = dcCore::app()->meta->getMetadata([
                'meta_type' => 'facformat',
                'post_id'   => dcCore::app()->ctx->__get('posts')->f('post_id'),
                'limit'     => 1,
            ]);
            if ($fac_format->isEmpty()) {
                return;
            }

            # Get format info
            $default_format = [
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
            ];

            $formats = json_decode((string) dcCore::app()->blog->settings->get(My::id())->get('formats'), true);
            if (empty($formats)
             || !is_array($formats)
             || !isset($formats[$fac_format->f('meta_id')])) {
                $format = $default_format;
            } else {
                $format = array_merge(
                    $default_format,
                    $formats[$fac_format->f('meta_id')]
                );
            }

            # Read feed url
            $cache = is_dir(DC_TPL_CACHE . '/fac') ? DC_TPL_CACHE . '/fac' : null;

            try {
                $feed = Reader::quickParse($fac_url->f('meta_id'), $cache);
            } catch (Exception $e) {
                $feed = null;
            }

            # No entries
            if (!$feed) {
                return;
            }

            # Feed title
            $feedtitle = '';
            if ('' != dcCore::app()->blog->settings->get(My::id())->get('defaultfeedtitle')) {
                $feedtitle = '<h3>' . Html::escapeHTML(
                    empty($feed->title) ?
                    str_replace(
                        '%T',
                        __('a related feed'),
                        dcCore::app()->blog->settings->get(My::id())->get('defaultfeedtitle')
                    ) :
                    str_replace(
                        '%T',
                        $feed->title,
                        dcCore::app()->blog->settings->get(My::id())->get('defaultfeedtitle')
                    )
                ) . '</h3>';
            }

            # Feed desc
            $feeddesc = '';
            if (dcCore::app()->blog->settings->get(My::id())->get('showfeeddesc')
             && '' != $feed->description) {
                $feeddesc = '<p>' . context::global_filters(
                    $feed->description,
                    ['encode_xml', 'remove_html']
                ) . '</p>';
            }

            # Date format
            $dateformat = '' != $format['dateformat'] ?
                $format['dateformat'] :
                dcCore::app()->blog->settings->get('system')->get('date_format') . ',' . dcCore::app()->blog->settings->get('system')->get('time_format');

            # Enrties limit
            $entrieslimit = abs((int) $format['lineslimit']);
            $uselimit     = $entrieslimit > 0 ? true : false;

            echo
            '<div class="post-fac">' .
            $feedtitle . $feeddesc .
            '<dl>';

            $i = 0;
            foreach ($feed->items as $item) {
                # Format date
                $date = Date::dt2str($dateformat, $item->pubdate);

                # Entries title
                $title = context::global_filters(
                    str_replace(
                        [
                            '%D',
                            '%T',
                            '%A',
                            '%E',
                            '%C',
                        ],
                        [
                            $date,
                            $item->title,
                            $item->creator,
                            $item->description,
                            $item->content,
                        ],
                        $format['linestitletext']
                    ),
                    ['remove_html', 'cut_string' => abs((int) $format['linestitlelength'])],
                );

                # Entries over title
                $overtitle = context::global_filters(
                    str_replace(
                        [
                            '%D',
                            '%T',
                            '%A',
                            '%E',
                            '%C',
                        ],
                        [
                            $date,
                            $item->title,
                            $item->creator,
                            $item->description,
                            $item->content,
                        ],
                        $format['linestitleover']
                    ),
                    ['remove_html', 'cut_string' => 350],
                );

                # Entries description
                $description = '';
                if ($format['showlinesdescription']
                 && '' != $item->description) {
                    $description = '<dd>' .
                    context::global_filters(
                        $item->description,
                        ['remove_html' => (int) $format['linesdescriptionnohtml'], 'cut_string' => abs((int) $format['linesdescriptionlength'])]
                    ) . '</dd>';
                }

                # Entries content
                $content = '';
                if ($format['showlinescontent']
                 && '' != $item->content) {
                    $content = '<dd>' .
                    context::global_filters(
                        $item->content,
                        ['remove_html' => (int) $format['linescontentnohtml'], 'cut_string' => abs((int) $format['linescontentlength'])]
                    ) . '</dd>';
                }

                echo
                '<dt><a href="' . $item->link . '" ' .
                'title="' . $overtitle . '">' . $title . '</a></dt>' .
                $description . $content;

                $i++;
                if ($uselimit && $i == $entrieslimit) {
                    break;
                }
            }
            echo '</dl></div>';
        });

        return true;
    }
}
