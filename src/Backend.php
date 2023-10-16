<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       fac backend class.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesFormV2(...),
            'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
            'adminPostHeaders'              => BackendBehaviors::adminPostHeaders(...),
            'adminPostFormItems'            => BackendBehaviors::adminPostFormItems(...),
            'adminAfterPostCreate'          => BackendBehaviors::adminAfterPostSave(...),
            'adminAfterPostUpdate'          => BackendBehaviors::adminAfterPostSave(...),
            'adminBeforePostDelete'         => BackendBehaviors::adminBeforePostDelete(...),
            'adminPostsActions'             => BackendBehaviors::adminPostsActions(...),
        ]);

        return true;
    }
}
