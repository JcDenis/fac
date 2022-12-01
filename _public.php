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
if (!defined('DC_RC_PATH')) {
    return null;
}

dcCore::app()->addBehavior('publicEntryAfterContent', function (dcCore $core, context $_ctx) {
    dcCore::app()->blog->settings->addNamespace('fac');

    # Not active or not a post
    if (!dcCore::app()->blog->settings->fac->fac_active
     || !dcCore::app()->ctx->exists('posts')) {
        return null;
    }

    # Not in page to show
    $types = @unserialize((string) dcCore::app()->blog->settings->fac->fac_public_tpltypes);
    if (!is_array($types)
     || !in_array(dcCore::app()->url->type, $types)) {
        return null;
    }

    # Get related feed
    $fac_url = dcCore::app()->meta->getMetadata([
        'meta_type' => 'fac',
        'post_id'   => dcCore::app()->ctx->posts->post_id,
        'limit'     => 1,
    ]);
    if ($fac_url->isEmpty()) {
        return null;
    }

    # Get related format
    $fac_format = dcCore::app()->meta->getMetadata([
        'meta_type' => 'facformat',
        'post_id'   => dcCore::app()->ctx->posts->post_id,
        'limit'     => 1,
    ]);
    if ($fac_format->isEmpty()) {
        return null;
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

    $formats = @unserialize((string) dcCore::app()->blog->settings->fac->fac_formats);
    if (empty($formats)
     || !is_array($formats)
     || !isset($formats[$fac_format->meta_id])) {
        $format = $default_format;
    } else {
        $format = array_merge(
            $default_format,
            $formats[$fac_format->meta_id]
        );
    }

    # Read feed url
    $cache = is_dir(DC_TPL_CACHE . '/fac') ? DC_TPL_CACHE . '/fac' : null;

    try {
        $feed = feedReader::quickParse($fac_url->meta_id, $cache);
    } catch (Exception $e) {
        $feed = null;
    }

    # No entries
    if (!$feed) {
        return null;
    }

    # Feed title
    $feedtitle = '';
    if ('' != dcCore::app()->blog->settings->fac->fac_defaultfeedtitle) {
        $feedtitle = '<h3>' . html::escapeHTML(
            empty($feed->title) ?
            str_replace(
                '%T',
                __('a related feed'),
                dcCore::app()->blog->settings->fac->fac_defaultfeedtitle
            ) :
            str_replace(
                '%T',
                $feed->title,
                dcCore::app()->blog->settings->fac->fac_defaultfeedtitle
            )
        ) . '</h3>';
    }

    # Feed desc
    $feeddesc = '';
    if (dcCore::app()->blog->settings->fac->fac_showfeeddesc
     && '' != $feed->description) {
        $feeddesc = '<p>' . context::global_filters(
            $feed->description,
            ['encode_xml', 'remove_html']
        ) . '</p>';
    }

    # Date format
    $dateformat = '' != $format['dateformat'] ?
        $format['dateformat'] :
        dcCore::app()->blog->settings->system->date_format . ',' . dcCore::app()->blog->settings->system->time_format;

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
        $date = dt::dt2str($dateformat, $item->pubdate);

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
