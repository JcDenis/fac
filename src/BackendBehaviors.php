<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Input,
    Label,
    Note,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Interface\Core\BlogSettingsInterface;
use Exception;

/**
 * @brief       fac backend behaviors class.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    /**
     * Get combos of types of supported public pages.
     *
     * @return  array<string, string>    List of post type and name
     */
    public static function getPostsTypes(): array
    {
        $types = [
            __('home page')      => 'default',
            __('post pages')     => 'post',
            __('tags pages')     => 'tag',
            __('archives pages') => 'archive',
            __('category pages') => 'category',
            __('entries feed')   => 'feed',
        ];
        if (App::plugins()->getDefine('muppet')->isDefined() && class_exists('\muppet')) {
            foreach (\muppet::getPostTypes() as $k => $v) {
                $types[sprintf(
                    __('"%s" pages from extension muppet'),
                    $v['name']
                )] = (string) $k;
            }
        }

        return $types;
    }

    /**
     * Add settings to blog preference.
     *
     * @param   BlogSettingsInterface   $blog_settings  Blog settings instance
     */
    public static function adminBlogPreferencesFormV2(BlogSettingsInterface $blog_settings): void
    {
        $lines               = '';
        $fac_public_tpltypes = json_decode($blog_settings->get(My::id())->get('public_tpltypes'), true);
        if (!is_array($fac_public_tpltypes)) {
            $fac_public_tpltypes = [];
        }
        foreach (self::getPostsTypes() as $k => $v) {
            $lines .= (new Para())->items([
                (new Checkbox(['fac_public_tpltypes[]', 'fac_public_tpltypes' . $k], in_array($v, $fac_public_tpltypes)))->value($v),
                (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))->for('fac_public_tpltypes' . $k)->class('classic'),
            ])->render();
        }

        echo
        '<div class="fieldset"><h4 id="' . My::id() . '_params">Feed after content</h4>' .
        '<p class="form-note">' .
        __('To add feed to an entry edit this entry and put in sidebar the url of the feed and select a format.') .
        '</p>';
        if (App::auth()->isSuperAdmin()) {
            echo '<p><a href="' . App::backend()->url()->get('admin.plugins', [
                'module' => My::id(),
                'conf'   => 1,
                'redir'  => App::backend()->url()->get('admin.blog.pref') . '#params.' . My::id() . '_params',
            ]) . '">' . __('Configure formats') . '</a></p>';
        }

        echo
        (new Div())->class('two-cols')->items([
            (new Div())->class('col')->items([
                (new Text('h5', Html::escapeHTML(__('Activation')))),
                // active
                (new Para())->items([
                    (new Checkbox('fac_active', (bool) $blog_settings->get(My::id())->get('active')))->value(1),
                    (new Label(__('Enable "fac" extension'), Label::OUTSIDE_LABEL_AFTER))->for('fac_active')->class('classic'),
                ]),
                (new Note())->text(__('You can manage related feed to display for each post with a predefined format.'))->class('form-note'),
                (new Text('h5', Html::escapeHTML(__('Feed')))),
                // defaultfeedtitle
                (new Para())->items([
                    (new Label(__('Default title')))->for('fac_defaultfeedtitle'),
                    (new Input('fac_defaultfeedtitle'))->size(70)->maxlength(255)->value((string) $blog_settings->get(My::id())->get('defaultfeedtitle')),
                ]),
                (new Note())->text(__('Use %T to insert title of feed.'))->class('form-note'),
                // showfeeddesc
                (new Para())->items([
                    (new Checkbox('fac_showfeeddesc', (bool) $blog_settings->get(My::id())->get('showfeeddesc')))->value(1),
                    (new Label(__('Show description of feed'), Label::OUTSIDE_LABEL_AFTER))->for('fac_showfeeddesc')->class('classic'),
                ]),
            ]),
            (new Div())->class('col')->items([
                (new Text('h5', Html::escapeHTML(__('Show feed after content on:')))),
                (new Text('', $lines)),
            ]),
        ])->render() .
        '<br class="clear" />' .
        '</div>';
    }

    /**
     * Save blog settings.
     *
     * @param   BlogSettingsInterface   $blog_settings  Blog settings instance
     */
    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $blog_settings): void
    {
        $blog_settings->get(My::id())->put('active', !empty($_POST['fac_active']));
        $blog_settings->get(My::id())->put('public_tpltypes', json_encode($_POST['fac_public_tpltypes']));
        $blog_settings->get(My::id())->put('defaultfeedtitle', (string) $_POST['fac_defaultfeedtitle']);
        $blog_settings->get(My::id())->put('showfeeddesc', !empty($_POST['fac_showfeeddesc']));
    }

    /**
     * Add javascript (toggle).
     *
     * @return string HTML head
     */
    public static function adminPostHeaders(): string
    {
        return My::jsLoad('backend');
    }

    /**
     * Add form to post sidebar.
     *
     * @param   ArrayObject<string, string>                 $main_items     Main items
     * @param   ArrayObject<string, array<string, mixed>>   $sidebar_items  Sidebar items
     * @param   null|MetaRecord                             $post           Post record or null
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        if (!App::blog()->isDefined() || !My::settings()->get('active')) {
            return;
        }

        # Get existing linked feed
        $fac_url = $fac_format = '';
        if ($post) {
            $rs = App::meta()->getMetadata([
                'meta_type' => 'fac',
                'post_id'   => $post->f('post_id'),
                'limit'     => 1,
            ]);
            $fac_url = $rs->isEmpty() ? '' : $rs->f('meta_id');

            $rs = App::meta()->getMetadata([
                'meta_type' => 'facformat',
                'post_id'   => $post->f('post_id'),
                'limit'     => 1,
            ]);
            $fac_format = $rs->isEmpty() ? '' : $rs->f('meta_id');
        }

        # Set linked feed form items
        $sidebar_items['options-box']['items']['fac'] = self::formFeed($fac_url, $fac_format);
    }

    /**
     * Save linked feed.
     *
     * @param   Cursor  $cur        Current post Cursor
     * @param   int     $post_id    Post id
     */
    public static function adminAfterPostSave(Cursor $cur, int $post_id): void
    {
        if (!isset($_POST['fac_url'])
         || !isset($_POST['fac_format'])) {
            return;
        }

        # Delete old linked feed
        self::delFeed($post_id);

        # Add new linked feed
        self::addFeed($post_id, $_POST);
    }

    /**
     * Delete linked feed on post edition.
     *
     * @param   int     $post_id    Post id
     */
    public static function adminBeforePostDelete(int $post_id): void
    {
        self::delFeed($post_id);
    }

    /**
     * Add actions to posts page combo.
     *
     * @param   ActionsPosts    $pa     ActionsPostsPage instance
     */
    public static function adminPostsActions(ActionsPosts $pa): void
    {
        if (!App::blog()->isDefined() || !My::settings()->get('active')) {
            return;
        }

        $pa->addAction(
            [__('Linked feed') => [__('Add feed') => 'fac_add']],
            self::callbackAdd(...)
        );

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            return;
        }
        $pa->addAction(
            [__('Linked feed') => [__('Remove feed') => 'fac_remove']],
            self::callbackRemove(...)
        );
    }

    /**
     * Posts actions callback to remove linked feed.
     *
     * @param   ActionsPosts                $pa     ActionsPosts instance
     * @param   ArrayObject<string, mixed>  $post   _POST actions
     */
    public static function callbackRemove(ActionsPosts $pa, ArrayObject $post): void
    {
        if (!App::blog()->isDefined()) {
            return;
        }
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            throw new Exception(__('No enough right'));
        }

        # Delete unused feed
        foreach ($posts_ids as $post_id) {
            self::delFeed((int) $post_id);
        }

        Notices::addSuccessNotice(__('Linked feed deleted.'));
        $pa->redirect(true);
    }

    /**
     * Posts actions callback to add linked feed.
     *
     * @param   ActionsPosts                $pa     ActionsPosts instance
     * @param   ArrayObject<string, mixed>  $post   _POST actions
     */
    public static function callbackAdd(ActionsPosts $pa, ArrayObject $post): void
    {
        if (!App::blog()->isDefined()) {
            return;
        }
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # Save action
        if (!empty($post['fac_url'])
         && !empty($post['fac_format'])) {
            foreach ($posts_ids as $post_id) {
                self::delFeed((int) $post_id);
                self::addFeed((int) $post_id, $post);
            }

            Notices::addSuccessNotice(__('Linked feed added.'));
            $pa->redirect(true);

            # Display form
        } else {
            $pa->beginPage(
                Page::breadcrumb([
                    Html::escapeHTML(App::blog()->name()) => '',
                    $pa->getCallerTitle()                 => $pa->getRedirection(true),
                    __('Linked feed to this selection')   => '',
                ])
            );

            echo
            (new Form('fac_form'))->action($pa->getURI())->method('post')->fields([
                (new Text('', $pa->getCheckboxes() . self::formFeed())),
                (new Para())->items([
                    App::nonce()->formNonce(),
                    ... $pa->hiddenFields(),
                    (new Hidden(['action'], 'fac_add')),
                    (new Submit(['save']))->value(__('Save')),
                ]),
            ])->render();

            $pa->endPage();
        }
    }

    /**
     * Linked feed form field.
     *
     * @param   string  $url        Feed URL
     * @param   string  $format     Feed format
     *
     * @return  string  Feed form content
     */
    protected static function formFeed(string $url = '', string $format = ''): string
    {
        if (!App::blog()->isDefined() || !My::settings()->get('active')) {
            return '';
        }

        return
        (new Div('fac'))->items([
            (new Text('h5', __('Linked feed'))),
            // fac_url
            (new Para())->items([
                (new Label(__('Feed URL:')))->for('fac_url')->class('required'),
                (new Input('fac_url'))->size(60)->maxlength(255)->value($url),
            ]),
            // fac_format
            (new Para())->items([
                (new Label(__('Format:')))->for('fac_format'),
                (new Select('fac_format'))->default($format)->items(self::comboFac()),
            ]),
            (new Text('', $url ? '<p><a href="' . $url . '" title="' . $url . '">' . __('view feed') . '</a></p>' : '')),
        ])->render();
    }

    /**
     * List of fac formats.
     *
     * @return  array<string, string>   List of fac formats
     */
    protected static function comboFac(): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }
        $formats = json_decode((string) My::settings()->get('formats'), true);
        if (!is_array($formats) || empty($formats)) {
            return [];
        }

        $res = [];
        foreach ($formats as $uid => $f) {
            $res[(string) $f['name']] = (string) $uid;
        }

        return $res;
    }

    /**
     * Delete linked feed.
     *
     * @param   int     $post_id    Post id
     */
    protected static function delFeed(int $post_id): void
    {
        $post_id = (int) $post_id;
        App::meta()->delPostMeta($post_id, 'fac');
        App::meta()->delPostMeta($post_id, 'facformat');
    }

    /**
     * Add linked feed.
     *
     * @param   int                 $post_id    Post id
     * @param   array<string, mixed>|ArrayObject<string, mixed>   $options    Feed options
     */
    protected static function addFeed(int $post_id, $options): void
    {
        if (empty($options['fac_url'])
         || empty($options['fac_format'])) {
            return;
        }

        $post_id = (int) $post_id;

        App::meta()->setPostMeta(
            $post_id,
            'fac',
            $options['fac_url']
        );
        App::meta()->setPostMeta(
            $post_id,
            'facformat',
            $options['fac_format']
        );
    }
}
