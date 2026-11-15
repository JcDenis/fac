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
        $this->lineslimit = self::limit($f, 'lineslimit', 5);
        $this->linestitletext = isset($f['linestitletext']) && is_string($f['linestitletext']) ? $f['linestitletext'] : '%T';
        $this->linestitleover = isset($f['linestitleover']) && is_string($f['linestitleover']) ? $f['linestitleover'] : '%D';
        $this->linestitlelength = self::limit($f, 'linestitlelength', 150);
        $this->showlinesdescription = isset($f['showlinesdescription']) && !empty($f['showlinesdescription']) ? true : false;
        $this->linesdescriptionlength = self::limit($f, 'linesdescriptionlength', 350);
        $this->linesdescriptionnohtml = !isset($f['linesdescriptionnohtml']) || empty($f['linesdescriptionnohtml']) ? false : true;
        $this->showlinescontent = isset($f['showlinescontent']) && !empty($f['showlinescontent']) ? true : false;
        $this->linescontentlength = self::limit($f, 'linescontentlength', 350);
        $this->linescontentnohtml = !isset($f['linescontentnohtml']) || empty($f['linescontentnohtml']) ? false : true;
    }

    /**
     * Get a limit: missing value uses default, empty value means no limit (0).
     *
     * @param   array<mixed>    $f  The format
     */
    private static function limit(array $f, string $key, int $default): int
    {
        if (!array_key_exists($key, $f)) {
            return $default;
        }

        return is_numeric($f[$key]) ? abs((int) $f[$key]) : 0;
    }
}
