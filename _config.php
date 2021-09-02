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

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$redir = empty($_REQUEST['redir']) ? 
    $list->getURL() . '#plugins' : $_REQUEST['redir'];

# -- Get settings --
$core->blog->settings->addNamespace('fac');
$s = $core->blog->settings->fac;

$fac_formats = @unserialize($s->fac_formats);

if (!is_array($fac_formats)) {
    $fac_formats = [];
}

# -- Set settings --
if (!empty($_POST['save'])) {
    try {
        $fac_formats = [];

        foreach($_POST['fac_formats'] as $uid => $f) {
            if (!empty($f['name'])) {
                $fac_formats[$uid] = $f;
            }
        }

        // fix 2021.08.21 : formats are now global
        $s->drop('fac_formats');
        $s->put(
            'fac_formats', 
            serialize($fac_formats), 
            'string', 
            'Formats of feeds contents', 
            true, 
            true
        );

        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Configuration has been successfully updated.')
        );
        http::redirect(
            $list->getURL('module=fac&conf=1&redir=' . $list->getRedir())
        );
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# -- Display form --

$i = 1;
foreach($fac_formats as $uid => $f) {
    if (empty($f['name'])) {
        continue;
    }

    echo '
    <div class="fieldset">
    <h4>' . sprintf(__('Format %s'), $i) . '</h4>

    <div class="two-boxes"><h5>' . __('General') . '</h5>

    <p><label for="fac_formats_' . $uid . '_name">' .
    __('Name:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][name]',
            'fac_formats_' . $uid . '_name'
        ],
        20,
        255,
        empty($f['name']) ? '' : $f['name'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('In order to remove a format, leave its name empty.') .
    '</p>

    <p><label for="fac_formats_' . $uid . '_dateformat">' .
    __('Date format:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][dateformat]',
            'fac_formats_' . $uid . '_dateformat'
        ],
        20,
        255,
        empty($f['dateformat']) ? '' : $f['dateformat'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Use date format of Dotclear or leave empty to use default date format of blog.') .
    '</p>

    <p><label for="fac_formats_' . $uid . '_lineslimit">' .
    __('Entries limit:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][lineslimit]',
            'fac_formats_' . $uid . '_lineslimit'
        ],
        5,
        4,
        empty($f['lineslimit']) ? '' : $f['lineslimit'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Leave lengh empty for no limit.') .
    '</p>

    </div><div class="two-boxes"><h5>' . __('Title') . '</h5>

    <p><label for="fac_formats_' . $uid . '_linestitletext">' .
    __('Title format:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][linestitletext]',
            'fac_formats_' . $uid . '_linestitletext'
        ],
        20,
        255,
        empty($f['linestitletext']) ? '' : $f['linestitletext'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Format can be:') .
    '%D : ' . __('Date') .
    ', %T : ' . __('Title') .
    ', %A : ' . __('Author') .
    ', %E : ' . __('Description') .
    ', %C : ' . __('Content') .
    '</p>

    <p><label for="fac_formats_' . $uid . '_linestitleover">' .
    __('Over title format:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][linestitleover]',
            'fac_formats_' . $uid . '_linestitleover'
        ],
        20,
        255,
        empty($f['linestitleover']) ? '' : $f['linestitleover'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Format can be:') .
    '%D : ' . __('Date') .
    ', %T : ' . __('Title') .
    ', %A : ' . __('Author') .
    ', %E : ' . __('Description') .
    ', %C : ' . __('Content') .
    '</p>

    <p><label for="fac_formats_' . $uid . '_linestitlelength">' .
    __('Maximum length of title:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][linestitlelength]',
            'fac_formats_' . $uid . '_linestitlelength'
        ],
        5,
        4,
        empty($f['linestitlelength']) ? '' : $f['linestitlelength'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Leave lengh empty for no limit.') .
    '</p>

    </div><div class="two-boxes"><h5>' . __('Description') . '</h5>

    <p><label for="fac_formats_' . $uid . '_showlinesdescription">' .
    form::checkbox(
        [
            'fac_formats[' . $uid . '][showlinesdescription]',
            'fac_formats_' . $uid . '_showlinesdescription'
        ],
        1,
        !empty($f['showlinesdescription'])
    ) .
    __('Show description of entries') . '</label></p>

    <p><label for="fac_formats_' . $uid . '_linesdescriptionnohtml">' .
    form::checkbox(
        [
            'fac_formats[' . $uid . '][linesdescriptionnohtml]',
            'fac_formats_' . $uid . '_linesdescriptionnohtml'
        ],
        1,
        !empty($f['linesdescriptionnohtml'])
    ).
    __('Remove html of description') . '</label></p>

    <p><label for="fac_formats_' . $uid . '_linesdescriptionlength">' .
    __('Maximum length of description:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][linesdescriptionlength]',
            'fac_formats_' . $uid . '_linesdescriptionlength'
        ],
        5,
        4,
        empty($f['linesdescriptionlength']) ? '' : $f['linesdescriptionlength'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Leave lengh empty for no limit.') .
    '</p>

    </div><div class="two-boxes"><h5>' . __('Content') . '</h5>

    <p><label for="fac_formats_' . $uid . '_showlinescontent">' .
    form::checkbox(
        [
            'fac_formats[' . $uid . '][showlinescontent]',
            'fac_formats_' . $uid . '_showlinescontent'
        ],
        1,
        !empty($f['showlinescontent'])
    ) .
    __('Show content of entries') . '</label></p>

    <p><label for="fac_formats_' . $uid . '_linescontentnohtml">' .
    form::checkbox(
        [
            'fac_formats[' . $uid . '][linescontentnohtml]',
            'fac_formats_' . $uid . '_linescontentnohtml'
        ],
        1,
        !empty($f['linescontentnohtml'])
    ) .
    __('Remove html of content') . '</label></p>

    <p><label for="fac_formats_' . $uid . '_linescontentlength">' .
    __('Maximum length of content:') . '</label>' .
    form::field(
        [
            'fac_formats[' . $uid . '][linescontentlength]',
            'fac_formats_' . $uid . '_linescontentlength'
        ],
        5,
        4,
        empty($f['linescontentlength']) ? '' : $f['linescontentlength'],
        'maximal'
    ) . '</p>
    <p class="form-note">' .
    __('Leave lengh empty for no limit.') .
    '</p>

    </div>

    </div>';

    $i++;
}

$uid = uniqid();
echo '
<div class="fieldset">
<h4>' . __('New format') . '</h4>

<div class="two-boxes"><h5>' . __('General') . '</h5>

<p><label for="fac_formats_' . $uid . '_name">' .
__('Name:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][name]',
        'fac_formats_' . $uid . '_name'
    ],
    20,
    255,
    '',
    'maximal'
) . '</p>
<p class="form-note">'.
__('In order to remove a format, leave its name empty.') .
'</p>

<p><label for="fac_formats_' . $uid . '_dateformat">' .
__('Date format:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][dateformat]',
        'fac_formats_' . $uid . '_dateformat'
    ],
    20,
    255,
    '',
    'maximal'
) . '</p>
<p class="form-note">' .
__('Use date format of Dotclear or leave empty to use default date format of blog.') .
'</p>

<p><label for="fac_formats_' . $uid . '_lineslimit">' .
__('Entries limit:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][lineslimit]',
        'fac_formats_' . $uid . '_lineslimit'
    ],
    5,
    4,
    5,
    'maximal'
) . '</p>
<p class="form-note">' .
__('Leave lengh empty for no limit.') .
'</p>

</div><div class="two-boxes"><h5>' . __('Title') . '</h5>

<p><label for="fac_formats_' . $uid . '_linestitletext">' .
__('Title format:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][linestitletext]',
        'fac_formats_' . $uid . '_linestitletext'
    ],
    20,
    255,
    '%T',
    'maximal'
) . '</p>
<p class="form-note">' .
__('Format can be:') .
'%D : ' . __('Date') .
', %T : ' . __('Title') .
', %A : ' . __('Author') .
', %E : ' . __('Description') .
', %C : ' . __('Content') .
'</p>

<p><label for="fac_formats_' . $uid . '_linestitleover">' .
__('Over title format:')  .  '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][linestitleover]',
        'fac_formats_' . $uid . '_linestitleover'
    ],
    20,
    255,
    '%D',
    'maximal'
) . '</p>
<p class="form-note">' .
__('Format can be:') .
'%D : ' . __('Date') .
', %T : ' . __('Title') .
', %A : ' . __('Author') .
', %E : ' . __('Description') .
', %C : ' . __('Content') .
'</p>

<p><label for="fac_formats_' . $uid . '_linestitlelength">' .
__('Maximum length of title:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][linestitlelength]',
        'fac_formats_' . $uid . '_linestitlelength'
    ],
    5,
    4,
    150,
    'maximal'
) . '</p>
<p class="form-note">' . 
__('Leave lengh empty for no limit.') .
'</p>

</div><div class="two-boxes"><h5>' . __('Description') . '</h5>

<p><label for="fac_formats_' . $uid . '_showlinesdescription">' .
form::checkbox(
    [
        'fac_formats[' . $uid . '][showlinesdescription]',
        'fac_formats_' . $uid . '_showlinesdescription'
    ],
    1,
    0
) .
__('Show description of entries') . '</label></p>

<p><label for="fac_formats_' . $uid . '_linesdescriptionnohtml">' .
form::checkbox(
    [
        'fac_formats[' . $uid . '][linesdescriptionnohtml]',
        'fac_formats_' . $uid . '_linesdescriptionnohtml'
    ],
    1,
    1
) .
__('Remove html of description') . '</label></p>

<p><label for="fac_formats_' . $uid . '_linesdescriptionlength">' .
__('Maximum length of description:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][linesdescriptionlength]',
        'fac_formats_' . $uid . '_linesdescriptionlength'
    ],
    5,
    4,
    350,
    'maximal'
) . '</p>
<p class="form-note">' .
__('Leave lengh empty for no limit.') .
'</p>

</div><div class="two-boxes"><h5>' . __('Content') . '</h5>

<p><label for="fac_formats_' . $uid . '_showlinescontent">' .
form::checkbox(
    [
        'fac_formats[' . $uid . '][showlinescontent]',
        'fac_formats_' . $uid . '_showlinescontent'
    ],
    1,
    0
) .
__('Show content of entries') . '</label></p>

<p><label for="fac_formats_' . $uid . '_linescontentnohtml">' .
form::checkbox(
    [
        'fac_formats[' . $uid . '][linescontentnohtml]',
        'fac_formats_' . $uid . '_linescontentnohtml'
    ],
    1,
    1
) .
__('Remove html of content') . '</label></p>

<p><label for="fac_formats_' . $uid . '_linescontentlength">' .
__('Maximum length of content:') . '</label>' .
form::field(
    [
        'fac_formats[' . $uid . '][linescontentlength]',
        'fac_formats_' . $uid . '_linescontentlength'
    ],
    5,
    4,
    350,
    'maximal'
) . '</p>
<p class="form-note">' .
__('Leave lengh empty for no limit.') .
'</p>

</div>

</div>

<div class="fieldset">
<h4>' . __('Informations') . '</h4>

<div class="two-boxes">

<h5>' . __('Theme') . '</h5>
<p>' .
__('Theme must have behavoir publicEntryAfterContent.') . ' ' .
__('To add feed to an entry edit this entry and put in sidebar the url of the feed and select a format.') .
'</p>

</div><div class="two-boxes">

<h5>' . __('Structure') . '</h5>
<pre>' . html::escapeHTML('
<div class="post-fac">
<h3>' . __('Title of feed') . '</h3>
<p>' . __('Description of feed') . '</p>
<dl>
<dt>' . __('Title of entry') . '</dt>
<dd>' . __('Description of entry') . '</dd>
</dl>
</div>
') . '</pre>

</div>

</div>';

dcPage::helpBlock('fac');