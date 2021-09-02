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

# -- Module specs --
$dc_min = '2.19';
$mod_id = 'fac';
$mod_conf = [
    [
        'fac_active',
        'Enabled fac plugin',
        false,
        'boolean'
    ],
    [
        'fac_public_tpltypes',
        'List of templates types which used fac',
        serialize(['post', 'tag', 'archive']),
        'string'
    ],
    [
        'fac_formats',
        'Formats of feeds contents',
        serialize([
            uniqid() => [
                'name'              => 'default',
                'dateformat'            => '',
                'lineslimit'            => '5',
                'linestitletext'        => '%T',
                'linestitleover'        => '%D',
                'linestitlelength'      => '150',
                'showlinesdescription'  => '0',
                'linesdescriptionlength'    => '350',
                'linesdescriptionnohtml'    => '1',
                'showlinescontent'      => '0',
                'linescontentlength'    => '350',
                'linescontentnohtml'    => '1'
            ],
            uniqid() => [
                'name'              => 'full',
                'dateformat'            => '',
                'lineslimit'            => '20',
                'linestitletext'        => '%T',
                'linestitleover'        => '%D - %E',
                'linestitlelength'      => '',
                'showlinesdescription'  => '1',
                'linesdescriptionlength'    => '',
                'linesdescriptionnohtml'    => '1',
                'showlinescontent'      => '1',
                'linescontentlength'    => '',
                'linescontentnohtml'    => '1'
            ]
        ]),
        'string',
        false,
        true
    ],
    [
        'fac_defaultfeedtitle',
        'Default title of feed',
        '%T',
        'string'
    ],
    [
        'fac_showfeeddesc',
        'Show description of feed',
        1,
        'boolean'
    ]
];

# -- Nothing to change below --
try {
    # Check module version
    if (version_compare(
        $core->getVersion($mod_id),
        $core->plugins->moduleInfo($mod_id, 'version'),
        '>=')) {
        return null;
    }
    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare') 
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s', $mod_id, $dc_min
        ));
    }
    # Set module settings
    $core->blog->settings->addNamespace($mod_id);
    foreach($mod_conf as $v) {
        $core->blog->settings->{$mod_id}->put(
            $v[0], $v[2], $v[3], $v[1], false, true
        );
    }
    # Set module version
    $core->setVersion(
        $mod_id,
        $core->plugins->moduleInfo($mod_id, 'version')
    );
    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
    return false;
}