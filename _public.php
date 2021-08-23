<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of fac, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

$core->addBehavior(
    'publicEntryAfterContent',
    array('facPublic', 'publicEntryAfterContent')
);

/**
 * @ingroup DC_PLUGIN_FAC
 * @brief Linked feed to entries - public methods.
 * @since 2.6
 */
class facPublic
{
    /**
     * Add feed after entry
     * 
     * @param  dcCore $core dcCore instance
     * @param  context $_ctx context instance
     */
    public static function publicEntryAfterContent(dcCore $core, context $_ctx)
    {
        $core->blog->settings->addNamespace('fac');

        # Not active or not a post
        if (!$core->blog->settings->fac->fac_active
         || !$_ctx->exists('posts')) {
            return null;
        }

        # Not in page to show
        $types = @unserialize($core->blog->settings->fac->fac_public_tpltypes);
        if (!is_array($types)
         || !in_array($core->url->type,$types)) {
            return null;
        }

        # Get related feed
        $fac_url = $core->meta->getMetadata([
            'meta_type' => 'fac',
            'post_id' => $_ctx->posts->post_id,
            'limit' => 1
        ]);
        if ($fac_url->isEmpty()) {
            return null;
        }

        # Get related format
        $fac_format = $core->meta->getMetadata([
            'meta_type' => 'facformat',
            'post_id' => $_ctx->posts->post_id,
            'limit' => 1
        ]);
        if ($fac_format->isEmpty()) {
            return null;
        }

        # Get format info
        $default_format = array(
            'name' => 'default',
            'dateformat' => '',
            'lineslimit' => '5',
            'linestitletext' => '%T',
            'linestitleover' => '%D',
            'linestitlelength' => '150',
            'showlinesdescription' => '0',
            'linesdescriptionlength' => '350',
            'linesdescriptionnohtml' => '1',
            'showlinescontent' => '0',
            'linescontentlength' => '350',
            'linescontentnohtml' => '1'
        );

        $formats = @unserialize($core->blog->settings->fac->fac_formats);
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
        if ('' != $core->blog->settings->fac->fac_defaultfeedtitle) {
            $feedtitle = '<h3>' . html::escapeHTML(empty($feed->title) ? 
                str_replace(
                    '%T',
                    __('a related feed'),
                    $core->blog->settings->fac->fac_defaultfeedtitle
                ) : 
                str_replace(
                    '%T',
                    $feed->title,
                    $core->blog->settings->fac->fac_defaultfeedtitle
                )
            ) . '</h3>';
        }

        # Feed desc
        $feeddesc = '';
        if ($core->blog->settings->fac->fac_showfeeddesc 
         && '' != $feed->description) {
            $feeddesc = 
            '<p>' . context::global_filters(
                $feed->description, 
                ['encode_xml', 'remove_html']
            ) . '</p>';
        }

        # Date format
        $dateformat = '' != $format['dateformat'] ? 
            $format['dateformat'] :
            $core->blog->settings->system->date_format . ',' . $core->blog->settings->system->time_format;

        # Enrties limit
        $entrieslimit = abs((integer) $format['lineslimit']);
        $uselimit = $entrieslimit > 0 ? true : false;

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
                    array(
                        '%D',
                        '%T',
                        '%A',
                        '%E',
                        '%C'
                    ),
                    array(
                        $date,
                        $item->title,
                        $item->creator,
                        $item->description,
                        $item->content
                    ),
                    $format['linestitletext']
                ),
                ['remove_html', 'cut_string' => abs((integer) $format['linestitlelength'])],
            );

            # Entries over title
            $overtitle = context::global_filters(
                str_replace(
                    array(
                        '%D',
                        '%T',
                        '%A',
                        '%E',
                        '%C'
                    ),
                    array(
                        $date,
                        $item->title,
                        $item->creator,
                        $item->description,
                        $item->content
                    ),
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
                    ['remove_html' => (integer) $format['linesdescriptionnohtml'], 'cut_string' => abs((integer) $format['linesdescriptionlength'])]
                ) . '</dd>';
            }

            # Entries content
            $content = '';
            if ($format['showlinescontent'] 
             && '' != $item->content) {
                $content = '<dd>'.
                context::global_filters(
                    $item->content,
                    ['remove_html' => (integer) $format['linescontentnohtml'], 'cut_string' => abs((integer) $format['linescontentlength'])]
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
    }
}