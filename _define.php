<?php
/**
 * @file
 * @brief       The plugin fac definition
 * @ingroup     fac
 *
 * @defgroup    fac Plugin fac.
 *
 * Add RSS/Atom feeds after entries content.
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Feed after content',
    'Add RSS/Atom feeds after entries content',
    'Jean-Christian Denis and Contributors',
    '1.5.1',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'settings'    => ['blog' => '#params.' . basename(__DIR__) . '_params'],
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
