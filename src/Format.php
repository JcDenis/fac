<?php

declare(strict_types=1);

namespace Dotclear\Plugin\fac;

use Dotclear\App;

/**
 * @brief       fac feed format helper.
 * @ingroup     fac
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Format
{
    public readonly string $name;
    public readonly string $dateformat;
    public readonly int $lineslimit;
    public readonly string $linestitletext;
    public readonly string $linestitleover;
    public readonly int $linestitlelength;
    public readonly bool $showlinesdescription;
    public readonly int $linesdescriptionlength;
    public readonly bool $linesdescriptionnohtml;
    public readonly bool $showlinescontent;
    public readonly int $linescontentlength;
    public readonly bool $linescontentnohtml;

    /**
     * Construct a new format.
     *
     * @param   array<mixed>    $f  The format
     */
    public function __construct(array $f = [])
    {
        $this->name = isset($f['name']) && is_string($f['name']) ? $f['name'] : 'default';
        $this->dateformat = isset($f['dateformat']) && is_string($f['dateformat']) ? $f['dateformat'] : App::blog()->settings()->get('system')->getStr('date_format', false) . ',' . App::blog()->settings()->get('system')->getStr('time_format', false);
        $this->lineslimit = abs(isset($f['lineslimit']) && is_numeric($f['lineslimit']) ? (int) $f['lineslimit'] : 5);
        $this->linestitletext = isset($f['linestitletext']) && is_string($f['linestitletext']) ? $f['linestitletext'] : '%T';
        $this->linestitleover = isset($f['linestitleover']) && is_string($f['linestitleover']) ? $f['linestitleover'] : '%D';
        $this->linestitlelength = abs(isset($f['linestitlelength']) && is_numeric($f['linestitlelength']) ? (int) $f['linestitlelength'] : 150);
        $this->showlinesdescription = isset($f['showlinesdescription']) && !empty($f['showlinesdescription']) ? true : false;
        $this->linesdescriptionlength = abs(isset($f['linesdescriptionlength']) && is_numeric($f['linesdescriptionlength']) ? (int) $f['linesdescriptionlength'] : 350);
        $this->linesdescriptionnohtml = !isset($f['linesdescriptionnohtml']) || empty($f['linesdescriptionnohtml']) ? false : true;
        $this->showlinescontent = isset($f['showlinescontent']) && !empty($f['showlinescontent']) ? true : false;
        $this->linescontentlength = abs(isset($f['linescontentlength']) && is_numeric($f['linescontentlength']) ? (int) $f['linescontentlength'] : 350);
        $this->linescontentnohtml = !isset($f['linescontentnohtml']) || empty($f['linescontentnohtml']) ? false : true;
    }


}
