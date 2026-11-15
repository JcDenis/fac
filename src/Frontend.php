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
                    self::filters(true, $format->linestitlelength),
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
                    self::filters(true, 350),
                );

                // Entries description
                $description = '';
                if ($format->showlinesdescription
                 && '' != $item->description) {
                    $description = '<dd>' . self::text(
                        is_string($item->description) ? $item->description : '',
                        $format->linesdescriptionnohtml,
                        $format->linesdescriptionlength
                    ) . '</dd>';
                }

                // Entries content
                $content = '';
                if ($format->showlinescontent
                 && '' != $item->content) {
                    $content = '<dd>' . self::text(
                        is_string($item->content) ? $item->content : '',
                        $format->linescontentnohtml,
                        $format->linescontentlength
                    ) . '</dd>';
                }

                echo
                '<dt><a href="' . Html::escapeURL(is_string($item->link) ? $item->link : '') . '" ' .
                'title="' . Html::escapeHTML($overtitle) . '">' . Html::escapeHTML($title) . '</a></dt>' .
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

    /**
     * Get filters, without cut if length is 0 (no limit).
     *
     * @return  array<string, mixed>
     */
    private static function filters(bool $remove_html, int $length): array
    {
        $filters = ['remove_html' => (int) $remove_html];
        if ($length > 0) {
            $filters['cut_string'] = $length;
        }

        return $filters;
    }

    /**
     * Get remote text, HTML is cleaned when kept.
     */
    private static function text(string $str, bool $remove_html, int $length): string
    {
        $str = App::frontend()->context()->global_filters($str, self::filters($remove_html, $length));

        // Remote HTML is never trusted
        return $remove_html ? $str : App::filter()->HTMLfilter($str, true);
    }
}
