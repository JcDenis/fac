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

$core->blog->settings->addNamespace('fac');

# Admin behaviors
$core->addBehavior('adminBlogPreferencesForm', ['facAdmin', 'adminBlogPreferencesForm']);
$core->addBehavior('adminBeforeBlogSettingsUpdate', ['facAdmin', 'adminBeforeBlogSettingsUpdate']);
$core->addBehavior('adminPostHeaders', ['facAdmin', 'adminPostHeaders']);
$core->addBehavior('adminPostFormItems', ['facAdmin', 'adminPostFormItems']);
$core->addBehavior('adminAfterPostCreate', ['facAdmin', 'adminAfterPostSave']);
$core->addBehavior('adminAfterPostUpdate', ['facAdmin', 'adminAfterPostSave']);
$core->addBehavior('adminBeforePostDelete', ['facAdmin', 'adminBeforePostDelete']);
$core->addBehavior('adminPostsActionsPage', ['facAdmin', 'adminPostsActionsPage']);

/**
 * @ingroup DC_PLUGIN_FAC
 * @brief Linked feed to entries - admin methods.
 * @since 2.6
 */
class facAdmin
{
    /**
     * Get combos of types of supported public pages
     * 
     * @param  dcCore $core   dcCore instance
     * @return array         List of post type and name
     */
    public static function getPostsTypes(dcCore $core)
    {
        $types = [
            __('home page')         => 'default',
            __('post pages')        => 'post',
            __('tags pages')        => 'tag',
            __('archives pages')    => 'archive',
            __('category pages')    => 'category',
            __('entries feed')      => 'feed'
        ];
        if ($core->plugins->moduleExists('muppet')) {
            foreach(muppet::getPostTypes() as $k => $v) {
                $types[sprintf(
                    __('"%s" pages from extension muppet'),
                    $v['name']
                )] = $k;
            }
        }
        return $types;
    }

    /**
     * Add settings to blog preference
     * 
     * @param  dcCore       $core           dcCore instance
     * @param  dcSettings   $blog_settings  dcSettings instance
     */
    public static function adminBlogPreferencesForm(dcCore $core, dcSettings $blog_settings)
    {
        echo
        '<div class="fieldset"><h4 id="fac_params">Feed after content</h4>' .
        '<p class="form-note">' . 
        __('To add feed to an entry edit this entry and put in sidebar the url of the feed and select a format.') .
        '</p>';
        if ($core->auth->isSuperAdmin()) {
            echo '<p><a href="' . $core->adminurl->get('admin.plugins', [
                'module' => 'fac', 
                'conf' => 1, 
                'redir' => $core->adminurl->get('admin.blog.pref') . '#fac_params'
            ]) . '">' . __('Configure formats') . '</a></p>';
        }
        echo 
        '<div class="two-cols">' .
        '<div class="col">' .
        '<h5>' . __('Activation') . '</h5>' .
        '<p><label class="classic">' .
        form::checkbox('fac_active', '1', (boolean) $blog_settings->fac->fac_active) . 
        __('Enable "fac" extension') . '</label></p>' .
        '<p class="form-note">' .
        __("You can manage related feed to display for each post with a predefined format.") .
        '</p>' .
        '<h5>' . __('Feed') . '</h5>' .
        '<p><label for="fac_defaultfeedtitle">' . __('Default title') . '</label>' .
        form::field('fac_defaultfeedtitle', 65, 255, (string) $blog_settings->fac->fac_defaultfeedtitle) . '</p>' .
        '<p class="form-note">' . __('Use %T to insert title of feed.') . '</p>' .
        '<p><label class="classic" for="fac_showfeeddesc">' .
        form::checkbox('fac_showfeeddesc', 1, (boolean) $blog_settings->fac->fac_showfeeddesc) .
        __('Show description of feed') . '</label></p>' .
        '</div>' .
        '<div class="col">' .
        '<h5>' . __('Show feed after content on:') . '</h5>';

        $fac_public_tpltypes = @unserialize($blog_settings->fac->fac_public_tpltypes);
        if (!is_array($fac_public_tpltypes)) {
            $fac_public_tpltypes = [];
        }
        foreach(self::getPostsTypes($core) as $k => $v) {
            echo '
            <p><label class="classic" for="fac_public_tpltypes' . $k . '">' .
            form::checkbox(
                ['fac_public_tpltypes[]', 'fac_public_tpltypes' . $k],
                $v,
                in_array($v, $fac_public_tpltypes)
            ) . __($k) . '</label></p>';
        }

        echo 
        '</div>' .
        '</div>' .
        '<br class="clear" />' . 
        '</div>';
    }

    /**
     * Save blog settings
     * 
     * @param  dcSettings   $blog_settings  dcSettings instance
     */
    public static function adminBeforeBlogSettingsUpdate(dcSettings $blog_settings)
    {
        $blog_settings->fac->put('fac_active', !empty($_POST['fac_active']));
        $blog_settings->fac->put('fac_public_tpltypes', serialize($_POST['fac_public_tpltypes']));
        $blog_settings->fac->put('fac_defaultfeedtitle', (string) $_POST['fac_defaultfeedtitle']);
        $blog_settings->fac->put('fac_showfeeddesc', !empty($_POST['fac_showfeeddesc']));
    }

    /**
     * Add javascript (toggle)
     * 
     * @return string HTML head
     */
    public static function adminPostHeaders()
    {
        return dcPage::jsLoad('index.php?pf=fac/js/admin.js');
    }

    /**
     * Add form to post sidebar
     * 
     * @param  ArrayObject $main_items    Main items
     * @param  ArrayObject $sidebar_items Sidebar items
     * @param  record      $post          Post record or null
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, $post)
    {
        global $core;

        if (!$core->blog->settings->fac->fac_active) {
            return null;
        }

        # Get existing linked feed
        $fac_url = $fac_format = '';
        if ($post) {

            $rs = $core->meta->getMetadata([
                'meta_type' => 'fac',
                'post_id' => $post->post_id,
                'limit' => 1
            ]);
            $fac_url = $rs->isEmpty() ? '' : $rs->meta_id;

            $rs = $core->meta->getMetadata([
                'meta_type' => 'facformat',
                'post_id' => $post->post_id,
                'limit' => 1
            ]);
            $fac_format = $rs->isEmpty() ? '' : $rs->meta_id;
        }

        # Set linked feed form items
        $sidebar_items['options-box']['items']['fac'] =
            self::formFeed($core, $fac_url, $fac_format);
    }

    /**
     * Save linked feed
     * 
     * @param  cursor $cur      Current post cursor
     * @param  integer $post_id Post id
     */
    public static function adminAfterPostSave(cursor $cur, $post_id)
    {
        global $core;

        if (!isset($_POST['fac_url']) 
         || !isset($_POST['fac_format'])) {
            return null;
        }

        # Delete old linked feed
        self::delFeed($core, $post_id);

        # Add new linked feed
        self::addFeed($core, $post_id, $_POST);
    }

    /**
     * Delete linked feed on post edition
     * 
     * @param  integer $post_id Post id
     */
    public static function adminBeforePostDelete($post_id)
    {
        self::delFeed($GLOBALS['core'], $post_id);
    }

    /**
     * Add actions to posts page combo
     * 
     * @param  dcCore             $core dcCore instance
     * @param  dcPostsActionsPage $ap   dcPostsActionsPage instance
     */
    public static function adminPostsActionsPage(dcCore $core, dcPostsActionsPage $pa)
    {
        if (!$core->blog->settings->fac->fac_active) {
            return null;
        }

        $pa->addAction(
            [__('Linked feed') => [__('Add feed') => 'fac_add']],
            ['facAdmin', 'callbackAdd']
        );

        if (!$core->auth->check('delete,contentadmin', $core->blog->id)) {
            return null;
        }
        $pa->addAction(
            [__('Linked feed') => [__('Remove feed') => 'fac_remove']],
            ['facAdmin', 'callbackRemove']
        );
    }

    /**
     * Posts actions callback to remove linked feed
     * 
     * @param  dcCore             $core dcCore instance
     * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackRemove(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!$core->auth->check('delete,contentadmin',$core->blog->id)) {
            throw new Exception(__('No enough right'));
        }

        # Delete unused feed
        foreach($posts_ids as $post_id) {
            self::delFeed($core, $post_id);
        }

        dcPage::addSuccessNotice(__('Linked feed deleted.'));
        $pa->redirect(true);
    }

    /**
     * Posts actions callback to add linked feed
     * 
     * @param  dcCore             $core dcCore instance
     * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackAdd(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
    {
         # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # Save action
        if (!empty($post['fac_url'])
         && !empty($post['fac_format'])) {
            foreach($posts_ids as $post_id) {
                self::delFeed($core, $post_id);
                self::addFeed($core, $post_id, $post);
            }

            dcPage::addSuccessNotice(__('Linked feed added.'));
            $pa->redirect(true);

        # Display form
        } else {
            $pa->beginPage(
                dcPage::breadcrumb([
                    html::escapeHTML($core->blog->name) => '',
                    $pa->getCallerTitle() => $pa->getRedirection(true),
                    __('Linked feed to this selection') => '' 
                ])
            );

            echo
            '<form action="' . $pa->getURI() . '" method="post">' .
            $pa->getCheckboxes() .

            self::formFeed($core) .

            '<p>' .
            $core->formNonce() .
            $pa->getHiddenFields() .
            form::hidden(['action'], 'fac_add') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
            '</form>';

            $pa->endPage();
        }
    }

    /**
     * Linked feed form field
     * 
     * @param  dcCore $core   dcCore instance
     * @param  string $url    Feed URL
     * @param  string $format Feed format
     * @return string         Feed form content
     */
    protected static function formFeed(dcCore $core, $url = '', $format = '')
    {
        if (!$core->blog->settings->fac->fac_active) {
            return null;
        }

        return 
        '<div id="fac">' . 
        '<h5>' . __('Linked feed') . '</h5>' .
        '<p><label for="fac_url">' .
        __('Feed URL:') . '</label>' .
        form::field(
            'fac_url',
            60,
            255,
            $url,
            'maximal'
        ) . '</p>' .
        '<p><label for="fac_format">' .
        __('Format:') . '</label>' .
        form::combo(
            'fac_format',
            self::comboFac($core),
            $format,
            'maximal'
        ) . '</p>' .
        ($url ? '<p><a href="' . $url . '" title="' . $url . '">' . __('view feed') . '</a></p>' : '') .
        '</div>';
    }

    /**
     * List of fac formats
     * 
     * @param  dcCore $core dcCore instance
     * @return array        List of fac formats
     */
    protected static function comboFac(dcCore $core)
    {
        $formats = @unserialize($core->blog->settings->fac->fac_formats);
        if (!is_array($formats) || empty($formats)) {
            return [];
        }

        $res = [];
        foreach($formats as $uid => $f) {
            $res[$f['name']] = $uid;
        }

        return $res;
    }

    /**
     * Delete linked feed
     * 
     * @param  dcCore  $core    dcCore instance
     * @param  integer $post_id Post id
     */
    protected static function delFeed(dcCore $core, $post_id)
    {
        $post_id = (integer) $post_id;
        $core->meta->delPostMeta($post_id, 'fac');
        $core->meta->delPostMeta($post_id, 'facformat');
    }

    /**
     * Add linked feed
     * 
     * @param  dcCore  $core    dcCore instance
     * @param  integer $post_id Post id
     * @param  array   $options Feed options
     */
    protected static function addFeed($core, $post_id, $options)
    {
        if (empty($options['fac_url']) 
         || empty($options['fac_format'])) {
            return null;
        }

        $post_id = (integer) $post_id;

        $core->meta->setPostMeta(
            $post_id,
            'fac',
            $options['fac_url']
        );
        $core->meta->setPostMeta(
            $post_id,
            'facformat',
            $options['fac_format']
        );
    }
}