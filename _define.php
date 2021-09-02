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

$this->registerModule(
    'Feed after content',
    'Add RSS/Atom feeds after entries content',
    'Jean-Christian Denis and Contributors',
    '0.9.3',
    [
        'requires' => [['core', '2.19']], 
        'permissions' => 'usage,contentadmin',
        'type' => 'plugin',
        'support' => 'https://github.com/JcDenis/fac',
        'details' => 'https://plugins.dotaddict.org/dc2/details/fac',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/fac/master/repository.xml',
        'settings' => [
            'blog' => '#params.fac_params'
        ]
    ]
);