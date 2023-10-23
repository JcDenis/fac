<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Note,
    Number,
    Para,
    Text
};
use Exception;

/**
 * @brief       fac configuration class.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!App::blog()->isDefined()) {
            return false;
        }

        $redir = empty($_REQUEST['redir']) ?
            App::backend()->__get('list')->getURL() . '#plugins' : $_REQUEST['redir'];

        # -- Get settings --
        $s = My::settings();

        $fac_formats = json_decode($s->get('formats'), true);

        if (!is_array($fac_formats)) {
            $fac_formats = [];
        }

        # -- Set settings --
        if (!empty($_POST['save'])) {
            try {
                $fac_formats = [];

                foreach ($_POST['fac_formats'] as $uid => $f) {
                    if (!empty($f['name'])) {
                        $fac_formats[$uid] = $f;
                    }
                }

                // fix 2021.08.21 : formats are now global
                $s->drop('formats');
                $s->put(
                    'formats',
                    json_encode($fac_formats),
                    'string',
                    'Formats of feeds contents',
                    true,
                    true
                );

                App::blog()->triggerBlog();

                Notices::addSuccessNotice(
                    __('Configuration successfully updated.')
                );
                App::backend()->url()->redirect(
                    'admin.plugins',
                    ['module' => My::id(), 'conf' => 1, 'redir' => App::backend()->__get('list')->getRedir()]
                );
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $s = My::settings();

        $fac_formats = json_decode($s->get('formats'), true);

        $i = 1;
        foreach ($fac_formats as $uid => $format) {
            if (empty($format['name'])) {
                continue;
            }

            self::displayFacFormat(sprintf(__('Format %s'), $i), $uid, $format);

            $i++;
        }

        $new_format = [
            'name'                   => '',
            'dateformat'             => '',
            'lineslimit'             => '5',
            'linestitletext'         => '%T',
            'linestitleover'         => '%D',
            'linestitlelength'       => '150',
            'showlinesdescription'   => '0',
            'linesdescriptionlength' => '350',
            'linesdescriptionnohtml' => '1',
            'showlinescontent'       => '0',
            'linescontentlength'     => '350',
            'linescontentnohtml'     => '1',
        ];

        self::displayFacFormat(__('New format'), uniqid(), $new_format);

        echo '
        <div class="fieldset">
        <h4 id="' . My::id() . 'Params">' . __('Informations') . '</h4>

        <div class="two-boxes">

        <h5>' . __('Theme') . '</h5>
        <p>' .
        __('Theme must have behavoir publicEntryAfterContent.') . ' ' .
        __('To add feed to an entry edit this entry and put in sidebar the url of the feed and select a format.') .
        '</p>

        </div><div class="two-boxes">

        <h5>' . __('Structure') . '</h5>
        <pre>' . Html::escapeHTML('
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

        Page::helpBlock('fac');
    }

    private static function displayFacFormat(string $title, string $uid, array $format): void
    {
        echo
        (new Div())->class('fieldset')->separator('')->items([
            (new Text('h4', $title)),
            (new Div())->class('two-boxes even')->items([
                (new Text('h5', __('General'))),
                // name
                (new Para())->items([
                    (new Label(__('Name:')))->for('fac_formats_' . $uid . '_name'),
                    (new Input([
                        'fac_formats[' . $uid . '][name]',
                        'fac_formats_' . $uid . '_name',
                    ]))->value(empty($format['name']) ? '' : $format['name'])->size(20)->maxlength(255)->class('maximal'),
                ]),
                (new Note())->text(__('In order to remove a format, leave its name empty.'))->class('form-note'),
                // dateformat
                (new Para())->items([
                    (new Label(__('Date format:')))->for('fac_formats_' . $uid . '_dateformat'),
                    (new Input([
                        'fac_formats[' . $uid . '][dateformat]',
                        'fac_formats_' . $uid . '_dateformat',
                    ]))->value(empty($format['dateformat']) ? '' : $format['dateformat'])->size(20)->maxlength(255)->class('maximal'),
                ]),
                (new Note())->text(__('Use date format of Dotclear or leave empty to use default date format of blog.'))->class('form-note'),
                // dateformat //todo: use Number
                (new Para())->items([
                    (new Label(__('Entries limit:')))->for('fac_formats_' . $uid . '_lineslimit'),
                    (new Input([
                        'fac_formats[' . $uid . '][lineslimit]',
                        'fac_formats_' . $uid . '_lineslimit',
                    ]))->value(empty($format['lineslimit']) ? '' : $format['lineslimit'])->size(4)->maxlength(5),
                ]),
                (new Note())->text(__('Leave lengh empty for no limit.'))->class('form-note'),
            ]),
            (new Div())->class('two-boxes odd')->items([
                (new Text('h5', __('Title'))),
                // linestitletext
                (new Para())->items([
                    (new Label(__('Title format:')))->for('fac_formats_' . $uid . '_linestitletext'),
                    (new Input([
                        'fac_formats[' . $uid . '][linestitletext]',
                        'fac_formats_' . $uid . '_linestitletext',
                    ]))->value(empty($format['linestitletext']) ? '' : $format['linestitletext'])->size(20)->maxlength(255)->class('maximal'),
                ]),
                (new Note())->text(
                    __('Format can be:') .
                    '%D : ' . __('Date') .
                    ', %T : ' . __('Title') .
                    ', %A : ' . __('Author') .
                    ', %E : ' . __('Description') .
                    ', %C : ' . __('Content')
                )->class('form-note'),
                // linestitleover
                (new Para())->items([
                    (new Label(__('Over title format:')))->for('fac_formats_' . $uid . '_linestitleover'),
                    (new Input([
                        'fac_formats[' . $uid . '][linestitleover]',
                        'fac_formats_' . $uid . '_linestitleover',
                    ]))->value(empty($format['linestitleover']) ? '' : $format['linestitleover'])->size(20)->maxlength(255)->class('maximal'),
                ]),
                (new Note())->text(
                    __('Format can be:') .
                    '%D : ' . __('Date') .
                    ', %T : ' . __('Title') .
                    ', %A : ' . __('Author') .
                    ', %E : ' . __('Description') .
                    ', %C : ' . __('Content')
                )->class('form-note'),
                // linestitlelength //todo: use Number
                (new Para())->items([
                    (new Label(__('Maximum length of title:')))->for('fac_formats_' . $uid . '_linestitlelength'),
                    (new Input([
                        'fac_formats[' . $uid . '][linestitlelength]',
                        'fac_formats_' . $uid . '_linestitlelength',
                    ]))->value(empty($format['linestitlelength']) ? '' : $format['linestitlelength'])->size(4)->maxlength(5),
                ]),
                (new Note())->text(__('Leave lengh empty for no limit.'))->class('form-note'),
            ]),
            (new Div())->class('two-boxes even')->items([
                (new Text('h5', __('Description'))),
                // showlinesdescription
                (new Para())->items([
                    (new Checkbox([
                        'fac_formats[' . $uid . '][showlinesdescription]',
                        'fac_formats_' . $uid . '_showlinesdescription',
                    ], !empty($format['showlinesdescription'])))->value(1),
                    (new Label(__('Show description of entries'), Label::OUTSIDE_LABEL_AFTER))->for('fac_formats_' . $uid . '_showlinesdescription')->class('classic'),
                ]),
                // linesdescriptionnohtml
                (new Para())->items([
                    (new Checkbox([
                        'fac_formats[' . $uid . '][linesdescriptionnohtml]',
                        'fac_formats_' . $uid . '_linesdescriptionnohtml',
                    ], !empty($format['linesdescriptionnohtml'])))->value(1),
                    (new Label(__('Remove html of description'), Label::OUTSIDE_LABEL_AFTER))->for('fac_formats_' . $uid . '_linesdescriptionnohtml')->class('classic'),
                ]),
                // linesdescriptionlength //todo: use Number
                (new Para())->items([
                    (new Label(__('Maximum length of description:')))->for('fac_formats_' . $uid . '_linesdescriptionlength'),
                    (new Input([
                        'fac_formats[' . $uid . '][linesdescriptionlength]',
                        'fac_formats_' . $uid . '_linesdescriptionlength',
                    ]))->value(empty($format['linesdescriptionlength']) ? '' : $format['linesdescriptionlength'])->size(4)->maxlength(5),
                ]),
                (new Note())->text(__('Leave lengh empty for no limit.'))->class('form-note'),
            ]),
            (new Div())->class('two-boxes odd')->items([
                (new Text('h5', __('Content'))),
                // showlinescontent
                (new Para())->items([
                    (new Checkbox([
                        'fac_formats[' . $uid . '][showlinescontent]',
                        'fac_formats_' . $uid . '_showlinescontent',
                    ], !empty($format['showlinescontent'])))->value(1),
                    (new Label(__('Show content of entries'), Label::OUTSIDE_LABEL_AFTER))->for('fac_formats_' . $uid . '_showlinescontent')->class('classic'),
                ]),
                // linescontentnohtml
                (new Para())->items([
                    (new Checkbox([
                        'fac_formats[' . $uid . '][linescontentnohtml]',
                        'fac_formats_' . $uid . '_linescontentnohtml',
                    ], !empty($format['linescontentnohtml'])))->value(1),
                    (new Label(__('Remove html of content'), Label::OUTSIDE_LABEL_AFTER))->for('fac_formats_' . $uid . '_linescontentnohtml')->class('classic'),
                ]),
                // linescontentlength //todo: use Number
                (new Para())->items([
                    (new Label(__('Maximum length of content:')))->for('fac_formats_' . $uid . '_linescontentlength'),
                    (new Input([
                        'fac_formats[' . $uid . '][linescontentlength]',
                        'fac_formats_' . $uid . '_linescontentlength',
                    ]))->value(empty($format['linescontentlength']) ? '' : $format['linescontentlength'])->size(4)->maxlength(5),
                ]),
                (new Note())->text(__('Leave lengh empty for no limit.'))->class('form-note'),
            ]),
        ])->render();
    }
}
