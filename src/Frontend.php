<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Feed\Reader;
use Exception;

/**
 * @brief       fac frontend class.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !App::blog()->isDefined() || !My::settings()->getBool('active', false)) {
            return false;
        }

        App::behavior()->addBehavior('publicEntryAfterContent', function ($___, $_____): void {
            if (!App::blog()->isDefined()) {
                return;
            }

            // Not a post
            if (!App::frontend()->context()->exists('posts')) {
                return;
            }

            // Not in page to show
            $types = json_decode(My::settings()->getStr('public_tpltypes', false), true);
            if (!is_array($types)
             || !App::url()->isType($types)
             || !(App::frontend()->context()->__get('posts') instanceof MetaRecord)) {
                return;
            }

            // Get related feed
            $fac_url = App::meta()->getMetadata([
                'meta_type' => 'fac',
                'post_id'   => App::frontend()->context()->__get('posts')->intField('post_id'),
                'limit'     => 1
            ]);
            if ($fac_url->isEmpty()) {
                return;
            }

            // Get related format
            $fac_format = App::meta()->getMetadata([
                'meta_type' => 'facformat',
                'post_id'   => App::frontend()->context()->__get('posts')->intField('post_id'),
                'limit'     => 1,
            ]);
            if ($fac_format->isEmpty()) {
                return;
            }

            $formats = json_decode(My::settings()->getStr('formats', false), true);
            if (empty($formats)
             || !is_array($formats)
             || !isset($formats[$fac_format->strField('meta_id')])
             || !is_array($formats[$fac_format->strField('meta_id')])
            ) {
                $format = new Format();
            } else {
                $format = new Format($formats[$fac_format->strField('meta_id')]);
            }

            // Read feed url
            $cache = is_dir(App::config()->cacheRoot() . '/fac') ? App::config()->cacheRoot() . '/fac' : null;

            try {
                $feed = Reader::quickParse($fac_url->strField('meta_id'), $cache);
            } catch (Exception $e) {
                $feed = null;
            }

            // No entries
            if (!$feed) {
                return;
            }

            // Feed title
            $feedtitle = '';
            if ('' != My::settings()->getStr('defaultfeedtitle', false)) {
                $feedtitle = '<h3>' . Html::escapeHTML(
                    empty($feed->title) ?
                    str_replace(
                        '%T',
                        __('a related feed'),
                        My::settings()->getStr('defaultfeedtitle', false)
                    ) :
                    str_replace(
                        '%T',
                        $feed->title,
                        My::settings()->getStr('defaultfeedtitle', false)
                    )
                ) . '</h3>';
            }

            // Feed desc
            $feeddesc = '';
            if (My::settings()->getBool('showfeeddesc', false)
             && '' != $feed->description) {
                $feeddesc = '<p>' . App::frontend()->context()->global_filters(
                    $feed->description,
                    ['encode_xml', 'remove_html']
                ) . '</p>';
            }

            // Enrties limit
            $entrieslimit = $format->lineslimit;
            $uselimit     = $entrieslimit > 0 ? true : false;

            echo
            '<div class="post-fac">' .
            $feedtitle . $feeddesc .
            '<dl>';

            $i = 0;
            foreach ($feed->items as $item) {
                # Format date
                $date = Date::dt2str($format->dateformat, is_string($item->pubdate) ? $item->pubdate : (string) time());

                // Entries title
                $title = App::frontend()->context()->global_filters(
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
                            is_string($item->title) ? $item->title : '',
                            is_string($item->creator) ? $item->creator : '',
                            is_string($item->description) ? $item->description : '',
                            is_string($item->content) ? $item->content : '',
                        ],
                        $format->linestitletext
                    ),
                    ['remove_html', 'cut_string' => $format->linestitlelength],
                );

                // Entries over title
                $overtitle = App::frontend()->context()->global_filters(
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
                            is_string($item->title) ? $item->title : '',
                            is_string($item->creator) ? $item->creator : '',
                            is_string($item->description) ? $item->description : '',
                            is_string($item->content) ? $item->content : '',
                        ],
                        $format->linestitleover
                    ),
                    ['remove_html', 'cut_string' => 350],
                );

                // Entries description
                $description = '';
                if ($format->showlinesdescription
                 && '' != $item->description) {
                    $description = '<dd>' .
                    App::frontend()->context()->global_filters(
                        is_string($item->description) ? $item->description : '',
                        ['remove_html' => (int) $format->linesdescriptionnohtml, 'cut_string' => $format->linesdescriptionlength]
                    ) . '</dd>';
                }

                // Entries content
                $content = '';
                if ($format->showlinescontent
                 && '' != $item->content) {
                    $content = '<dd>' .
                    App::frontend()->context()->global_filters(
                        is_string($item->content) ? $item->content : '',
                        ['remove_html' => (int) $format->linescontentnohtml, 'cut_string' => $format->linescontentlength]
                    ) . '</dd>';
                }

                echo
                '<dt><a href="' . (is_string($item->link) ? $item->link : '') . '" ' .
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
