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

dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

# Admin behaviors
dcCore::app()->addBehavior('adminBlogPreferencesFormV2', ['facAdmin', 'adminBlogPreferencesFormV2']);
dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', ['facAdmin', 'adminBeforeBlogSettingsUpdate']);
dcCore::app()->addBehavior('adminPostHeaders', ['facAdmin', 'adminPostHeaders']);
dcCore::app()->addBehavior('adminPostFormItems', ['facAdmin', 'adminPostFormItems']);
dcCore::app()->addBehavior('adminAfterPostCreate', ['facAdmin', 'adminAfterPostSave']);
dcCore::app()->addBehavior('adminAfterPostUpdate', ['facAdmin', 'adminAfterPostSave']);
dcCore::app()->addBehavior('adminBeforePostDelete', ['facAdmin', 'adminBeforePostDelete']);
dcCore::app()->addBehavior('adminPostsActions', ['facAdmin', 'adminPostsActions']);

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
     * @return array         List of post type and name
     */
    public static function getPostsTypes()
    {
        $types = [
            __('home page')      => 'default',
            __('post pages')     => 'post',
            __('tags pages')     => 'tag',
            __('archives pages') => 'archive',
            __('category pages') => 'category',
            __('entries feed')   => 'feed',
        ];
        if (dcCore::app()->plugins->moduleExists('muppet')) {
            foreach (muppet::getPostTypes() as $k => $v) {
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
     * @param  dcSettings   $blog_settings  dcSettings instance
     */
    public static function adminBlogPreferencesFormV2(dcSettings $blog_settings)
    {
        echo
        '<div class="fieldset"><h4 id="fac_params">Feed after content</h4>' .
        '<p class="form-note">' .
        __('To add feed to an entry edit this entry and put in sidebar the url of the feed and select a format.') .
        '</p>';
        if (dcCore::app()->auth->isSuperAdmin()) {
            echo '<p><a href="' . dcCore::app()->adminurl->get('admin.plugins', [
                'module' => basename(__DIR__),
                'conf'   => 1,
                'redir'  => dcCore::app()->adminurl->get('admin.blog.pref') . '#fac_params',
            ]) . '">' . __('Configure formats') . '</a></p>';
        }
        echo
        '<div class="two-cols">' .
        '<div class="col">' .
        '<h5>' . __('Activation') . '</h5>' .
        '<p><label class="classic">' .
        form::checkbox('fac_active', '1', (bool) $blog_settings->get(basename(__DIR__))->get('active')) .
        __('Enable "fac" extension') . '</label></p>' .
        '<p class="form-note">' .
        __('You can manage related feed to display for each post with a predefined format.') .
        '</p>' .
        '<h5>' . __('Feed') . '</h5>' .
        '<p><label for="fac_defaultfeedtitle">' . __('Default title') . '</label>' .
        form::field('fac_defaultfeedtitle', 65, 255, (string) $blog_settings->get(basename(__DIR__))->get('defaultfeedtitle')) . '</p>' .
        '<p class="form-note">' . __('Use %T to insert title of feed.') . '</p>' .
        '<p><label class="classic" for="fac_showfeeddesc">' .
        form::checkbox('fac_showfeeddesc', 1, (bool) $blog_settings->get(basename(__DIR__))->get('showfeeddesc')) .
        __('Show description of feed') . '</label></p>' .
        '</div>' .
        '<div class="col">' .
        '<h5>' . __('Show feed after content on:') . '</h5>';

        $fac_public_tpltypes = json_decode($blog_settings->get(basename(__DIR__))->get('public_tpltypes'), true);
        if (!is_array($fac_public_tpltypes)) {
            $fac_public_tpltypes = [];
        }
        foreach (self::getPostsTypes() as $k => $v) {
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
        $blog_settings->get(basename(__DIR__))->put('active', !empty($_POST['fac_active']));
        $blog_settings->get(basename(__DIR__))->put('public_tpltypes', json_encode($_POST['fac_public_tpltypes']));
        $blog_settings->get(basename(__DIR__))->put('defaultfeedtitle', (string) $_POST['fac_defaultfeedtitle']);
        $blog_settings->get(basename(__DIR__))->put('showfeeddesc', !empty($_POST['fac_showfeeddesc']));
    }

    /**
     * Add javascript (toggle)
     *
     * @return string HTML head
     */
    public static function adminPostHeaders()
    {
        return dcPage::jsModuleLoad(basename(__DIR__) . '/js/admin.js');
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
        if (!dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
            return null;
        }

        # Get existing linked feed
        $fac_url = $fac_format = '';
        if ($post) {
            $rs = dcCore::app()->meta->getMetadata([
                'meta_type' => 'fac',
                'post_id'   => $post->post_id,
                'limit'     => 1,
            ]);
            $fac_url = $rs->isEmpty() ? '' : $rs->meta_id;

            $rs = dcCore::app()->meta->getMetadata([
                'meta_type' => 'facformat',
                'post_id'   => $post->post_id,
                'limit'     => 1,
            ]);
            $fac_format = $rs->isEmpty() ? '' : $rs->meta_id;
        }

        # Set linked feed form items
        $sidebar_items['options-box']['items']['fac'] = self::formFeed($fac_url, $fac_format);
    }

    /**
     * Save linked feed
     *
     * @param  cursor $cur      Current post cursor
     * @param  integer $post_id Post id
     */
    public static function adminAfterPostSave(cursor $cur, $post_id)
    {
        if (!isset($_POST['fac_url'])
         || !isset($_POST['fac_format'])) {
            return null;
        }

        # Delete old linked feed
        self::delFeed($post_id);

        # Add new linked feed
        self::addFeed($post_id, $_POST);
    }

    /**
     * Delete linked feed on post edition
     *
     * @param  integer $post_id Post id
     */
    public static function adminBeforePostDelete($post_id)
    {
        self::delFeed($post_id);
    }

    /**
     * Add actions to posts page combo
     *
     * @param  dcPostsActions $pa   dcPostsActionsPage instance
     */
    public static function adminPostsActions(dcPostsActions $pa)
    {
        if (!dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
            return null;
        }

        $pa->addAction(
            [__('Linked feed') => [__('Add feed') => 'fac_add']],
            ['facAdmin', 'callbackAdd']
        );

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
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
     * @param  dcPostsActions $pa   dcPostsActions instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackRemove(dcPostsActions $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('No enough right'));
        }

        # Delete unused feed
        foreach ($posts_ids as $post_id) {
            self::delFeed($post_id);
        }

        dcPage::addSuccessNotice(__('Linked feed deleted.'));
        $pa->redirect(true);
    }

    /**
     * Posts actions callback to add linked feed
     *
     * @param  dcPostsActions $pa   dcPostsActions instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackAdd(dcPostsActions $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # Save action
        if (!empty($post['fac_url'])
         && !empty($post['fac_format'])) {
            foreach ($posts_ids as $post_id) {
                self::delFeed($post_id);
                self::addFeed($post_id, $post);
            }

            dcPage::addSuccessNotice(__('Linked feed added.'));
            $pa->redirect(true);

        # Display form
        } else {
            $pa->beginPage(
                dcPage::breadcrumb([
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    $pa->getCallerTitle()                       => $pa->getRedirection(true),
                    __('Linked feed to this selection')         => '',
                ])
            );

            echo
            '<form action="' . $pa->getURI() . '" method="post">' .
            $pa->getCheckboxes() .

            self::formFeed() .

            '<p>' .
            dcCore::app()->formNonce() .
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
     * @param  string $url    Feed URL
     * @param  string $format Feed format
     * @return null|string         Feed form content
     */
    protected static function formFeed($url = '', $format = '')
    {
        if (!dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
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
            self::comboFac(),
            $format,
            'maximal'
        ) . '</p>' .
        ($url ? '<p><a href="' . $url . '" title="' . $url . '">' . __('view feed') . '</a></p>' : '') .
        '</div>';
    }

    /**
     * List of fac formats
     *
     * @return array        List of fac formats
     */
    protected static function comboFac()
    {
        $formats = json_decode(dcCore::app()->blog->settings->get(basename(__DIR__))->get('formats'), true);
        if (!is_array($formats) || empty($formats)) {
            return [];
        }

        $res = [];
        foreach ($formats as $uid => $f) {
            $res[$f['name']] = $uid;
        }

        return $res;
    }

    /**
     * Delete linked feed
     *
     * @param  integer $post_id Post id
     */
    protected static function delFeed($post_id)
    {
        $post_id = (int) $post_id;
        dcCore::app()->meta->delPostMeta($post_id, 'fac');
        dcCore::app()->meta->delPostMeta($post_id, 'facformat');
    }

    /**
     * Add linked feed
     *
     * @param  integer $post_id Post id
     * @param  array|ArrayObject   $options Feed options
     */
    protected static function addFeed($post_id, $options)
    {
        if (empty($options['fac_url'])
         || empty($options['fac_format'])) {
            return null;
        }

        $post_id = (int) $post_id;

        dcCore::app()->meta->setPostMeta(
            $post_id,
            'fac',
            $options['fac_url']
        );
        dcCore::app()->meta->setPostMeta(
            $post_id,
            'facformat',
            $options['fac_format']
        );
    }
}
