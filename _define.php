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
    '1.6',
    [
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-02T14:03:16+00:00',
    ]
);
