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

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminBlogPreferencesFormV2'    => [BackendBehaviors::class, 'adminBlogPreferencesFormV2'],
            'adminBeforeBlogSettingsUpdate' => [BackendBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
            'adminPostHeaders'              => [BackendBehaviors::class, 'adminPostHeaders'],
            'adminPostFormItems'            => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminAfterPostCreate'          => [BackendBehaviors::class, 'adminAfterPostSave'],
            'adminAfterPostUpdate'          => [BackendBehaviors::class, 'adminAfterPostSave'],
            'adminBeforePostDelete'         => [BackendBehaviors::class, 'adminBeforePostDelete'],
            'adminPostsActions'             => [BackendBehaviors::class, 'adminPostsActions'],
        ]);

        return true;
    }
}
