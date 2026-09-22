<?php
/**
 * Kichik .xlsx yozuvchi — tashqi kutubxonasiz (faqat ZipArchive).
 * Bezatilgan hisobotlar uchun: sarlavha, rangli bosh qator, chegaralar,
 * pul/sana formatlari, jami qatori, filtr, qotirilgan bosh qator, chop etish
 * sahifaga sig'adi.
 *
 *   $x = new HsXlsx();
 *   $s = $x->sheet('Kassa', array(12, 30, 16));
 *   $s->title('Kassa daftari', 'Davr: 01.09.2026 — 30.09.2026');
 *   $s->header(array('Sana', 'Ijarachi', 'Summa'));
 *   $s->row(array(array('2026-09-01', 'date'), 'Ali', array(5000000, 'money')));
 *   $s->total(array('Jami', '', array(5000000, 'money')));
 *   $x->send('kassa.xlsx');
 *
 * Katak: oddiy qiymat (matn yoki son) yoki array(qiymat, uslub).
 * Uslublar: text, int, money, usd, rate, area, date, in, out, bold,
 * floor (tik, qavat nomi), big, usd_in, usd_out, center, muted, bold_money.
 */

class HsXlsxSheet
{
    public $name;
    public $widths;
    public $rows = array();
    public $merges = array();
    public $headerRow = 0;
    public $filterFrom = 0;
    public $filterTo = 0;

    public function __construct($name, $widths)
    {
        $this->name = $name;
        $this->widths = $widths;
    }

    /** Sarlavha va izoh — butun kenglik bo'ylab birlashtirilgan. */
    public function title($title, $sub = '')
    {
        $n = count($this->widths);
        $r = count($this->rows) + 1;
        $this->rows[] = array('h' => 26, 'c' => array(array($title, 'title')));
        $this->merges[] = 'A' . $r . ':' . HsXlsx::col($n - 1) . $r;
        if ($sub !== '') {
            $this->rows[] = array('h' => 18, 'c' => array(array($sub, 'sub')));
            $this->merges[] = 'A' . ($r + 1) . ':' . HsXlsx::col($n - 1) . ($r + 1);
        }
        $this->rows[] = array('h' => 8, 'c' => array());
    }

    /** Kichik bo'lim sarlavhasi (bir varaqda bir nechta jadval bo'lsa). */
    public function section($text)
    {
        if ($this->rows && !empty($this->rows[count($this->rows) - 1]['c'])) {
            $this->rows[] = array('h' => 10, 'c' => array());
        }
        $this->rows[] = array('h' => 20, 'c' => array(array($text, 'section')));
    }

    /** Bosh qator. Birinchisi qotiriladi va filtr shundan boshlanadi. */
    public function header($cells, $h = 30)
    {
        $out = array();
        foreach ($cells as $c) {
            $out[] = array($c, 'head');
        }
        $this->rows[] = array('h' => $h, 'c' => $out);
        if ($this->headerRow === 0) {
            $this->headerRow = count($this->rows);
            $this->filterFrom = $this->headerRow;
            $this->filterTo = $this->headerRow;
        }
    }

    public function row($cells)
    {
        $out = array();
        foreach ($cells as $c) {
            $out[] = is_array($c) ? $c : array($c, is_int($c) || is_float($c) ? 'int' : 'text');
        }
        $this->rows[] = array('h' => 0, 'c' => $out);
        if ($this->headerRow > 0 && $this->filterTo === count($this->rows) - 1) {
            $this->filterTo = count($this->rows);
        }
    }

    /** Jami qatori — qalin, och binafsha fon. Uslub nomiga "t_" qo'shiladi. */
    public function total($cells)
    {
        $out = array();
        foreach ($cells as $c) {
            list($v, $s) = is_array($c) ? $c : array($c, 'text');
            $out[] = array($v, 't_' . $s);
        }
        $this->rows[] = array('h' => 20, 'c' => $out);
    }

    /** Keyingi qo'shiladigan qatorning raqami (1 dan). */
    public function nextRow()
    {
        return count($this->rows) + 1;
    }

    /** Kataklarni birlashtirish, masalan "A5:A9". */
    public function merge($ref)
    {
        $this->merges[] = $ref;
    }

    /** Katta sarlavha (bino nomi) — butun kenglikda. */
    public function bigTitle($title, $sub = '')
    {
        $n = count($this->widths);
        $r = count($this->rows) + 1;
        $this->rows[] = array('h' => 38, 'c' => array(array($title, 'big')));
        $this->merges[] = 'A' . $r . ':' . HsXlsx::col($n - 1) . $r;
        if ($sub !== '') {
            $this->rows[] = array('h' => 18, 'c' => array(array($sub, 'sub')));
            $this->merges[] = 'A' . ($r + 1) . ':' . HsXlsx::col($n - 1) . ($r + 1);
        }
        $this->rows[] = array('h' => 8, 'c' => array());
    }

    /** Qator balandligi bilan (tik yozuv sig'ishi uchun). */
    public function rowH($cells, $h)
    {
        $this->row($cells);
        $this->rows[count($this->rows) - 1]['h'] = $h;
    }

    public function blank()
    {
        $this->rows[] = array('h' => 0, 'c' => array());
    }
}

class HsXlsx
{
    /** @var HsXlsxSheet[] */
    private $sheets = array();

    /** Uslub nomi => cellXfs tartib raqami (styles() dagi tartib bilan bir xil). */
    private static $style = array(
        'plain' => 0, 'title' => 1, 'sub' => 2, 'head' => 3, 'text' => 4, 'int' => 5, 'money' => 6,
        'usd' => 7, 'rate' => 8, 'area' => 9, 'date' => 10, 'in' => 11, 'out' => 12, 'section' => 13, 'bold' => 14,
        't_text' => 15, 't_int' => 16, 't_money' => 17, 't_usd' => 18, 't_rate' => 19, 't_area' => 20,
        't_date' => 15, 't_in' => 21, 't_out' => 22, 't_bold' => 15,
        'floor' => 23, 'big' => 24, 'usd_in' => 25, 'usd_out' => 26, 'center' => 27, 'muted' => 28, 'bold_money' => 29, 'objbar' => 30, 't_center' => 31,
    );

    public static function col($i)
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = (int) (($i - $m) / 26);
        }
        return $s;
    }

    public function sheet($name, $widths)
    {
        // Excel varaq nomi: 31 belgigacha, []:*?/\ bo'lmaydi, takrorlanmaydi.
        $name = mb_substr(preg_replace('#[\[\]:*?/\\\\]#u', ' ', $name), 0, 31);
        $base = $name;
        $i = 2;
        foreach ($this->sheets as $s) {
            if (mb_strtolower($s->name) === mb_strtolower($name)) {
                $name = mb_substr($base, 0, 28) . ' ' . $i++;
            }
        }
        $s = new HsXlsxSheet($name, $widths);
        $this->sheets[] = $s;
        return $s;
    }

    private static function x($s)
    {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** 'Y-m-d' → Excel sana raqami. */
    private static function serial($ymd)
    {
        $t = strtotime(substr((string) $ymd, 0, 10) . ' 00:00:00 UTC');
        return $t === false ? null : (int) round($t / 86400) + 25569;
    }

    private function sheetXml(HsXlsxSheet $s)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView workbookViewId="0" showGridLines="0">';
        if ($s->headerRow > 0) {
            $x .= '<pane ySplit="' . $s->headerRow . '" topLeftCell="A' . ($s->headerRow + 1) . '" activePane="bottomLeft" state="frozen"/>';
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="16"/><cols>';
        foreach ($s->widths as $i => $w) {
            $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $w . '" customWidth="1"/>';
        }
        $x .= '</cols><sheetData>';
        foreach ($s->rows as $ri => $row) {
            $r = $ri + 1;
            $x .= '<row r="' . $r . '"' . ($row['h'] > 0 ? ' ht="' . $row['h'] . '" customHeight="1"' : '') . '>';
            foreach ($row['c'] as $ci => $cell) {
                list($v, $st) = $cell;
                $sid = isset(self::$style[$st]) ? self::$style[$st] : 4;
                $ref = self::col($ci) . $r;
                if ($v === null || $v === '') {
                    $x .= '<c r="' . $ref . '" s="' . $sid . '"/>';
                } elseif (($st === 'date' || $st === 't_date') && self::serial($v) !== null) {
                    $x .= '<c r="' . $ref . '" s="' . $sid . '"><v>' . self::serial($v) . '</v></c>';
                } elseif (is_int($v) || is_float($v)) {
                    $x .= '<c r="' . $ref . '" s="' . $sid . '"><v>' . (is_float($v) ? rtrim(rtrim(sprintf('%.6F', $v), '0'), '.') : $v) . '</v></c>';
                } else {
                    $x .= '<c r="' . $ref . '" s="' . $sid . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($s->headerRow > 0 && $s->filterTo > $s->filterFrom) {
            $x .= '<autoFilter ref="A' . $s->filterFrom . ':' . self::col(count($s->widths) - 1) . $s->filterTo . '"/>';
        }
        if ($s->merges) {
            $x .= '<mergeCells count="' . count($s->merges) . '">';
            foreach ($s->merges as $m) {
                $x .= '<mergeCell ref="' . $m . '"/>';
            }
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
        return $x;
    }

    private function styles()
    {
        $b = '<border><left style="thin"><color rgb="FFD9D2E6"/></left><right style="thin"><color rgb="FFD9D2E6"/></right><top style="thin"><color rgb="FFD9D2E6"/></top><bottom style="thin"><color rgb="FFD9D2E6"/></bottom><diagonal/></border>';
        // Shriftlar: 0 oddiy, 1 sarlavha, 2 izoh, 3 bosh qator, 4 qalin, 5 yashil, 6 qizil, 7 bo'lim, 8 qalin yashil, 9 qalin qizil.
        $fonts = '<fonts count="13">'
            . '<font><sz val="10"/><name val="Arial"/></font>'
            . '<font><b/><sz val="15"/><color rgb="FF3A1C5E"/><name val="Arial"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF6B6280"/><name val="Arial"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
            . '<font><b/><sz val="10"/><name val="Arial"/></font>'
            . '<font><sz val="10"/><color rgb="FF1B7A3E"/><name val="Arial"/></font>'
            . '<font><sz val="10"/><color rgb="FFB42318"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF5A3089"/><name val="Arial"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF1B7A3E"/><name val="Arial"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFB42318"/><name val="Arial"/></font>'
            . '<font><b/><sz val="22"/><color rgb="FF3A1C5E"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF3A1C5E"/><name val="Arial"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF6B6280"/><name val="Arial"/></font>'
            . '</fonts>';
        $fills = '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF5A3089"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEFE9F7"/><bgColor indexed="64"/></patternFill></fill></fills>';
        $numFmts = '<numFmts count="5"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="&quot;$&quot;#,##0.00"/>'
            . '<numFmt numFmtId="166" formatCode="#,##0.00"/><numFmt numFmtId="167" formatCode="#,##0.0"/><numFmt numFmtId="168" formatCode="dd.mm.yyyy"/></numFmts>';
        $mid = '<alignment vertical="center"/>';
        $xf = function ($font, $fill, $border, $fmt, $align) {
            return '<xf numFmtId="' . $fmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
                . ($fmt ? ' applyNumberFormat="1"' : '') . ($font ? ' applyFont="1"' : '') . ($fill ? ' applyFill="1"' : '') . ($border ? ' applyBorder="1"' : '')
                . ($align !== '' ? ' applyAlignment="1">' . $align . '</xf>' : '/>');
        };
        $cells = array(
            $xf(0, 0, 0, 0, ''),                                   // 0 plain
            $xf(1, 0, 0, 0, '<alignment vertical="center"/>'),     // 1 title
            $xf(2, 0, 0, 0, '<alignment vertical="center"/>'),     // 2 sub
            $xf(3, 2, 1, 0, '<alignment horizontal="center" vertical="center" wrapText="1"/>'), // 3 head
            $xf(0, 0, 1, 0, '<alignment vertical="center" wrapText="1"/>'), // 4 text
            $xf(0, 0, 1, 164, $mid),                               // 5 int
            $xf(0, 0, 1, 164, $mid),                               // 6 money
            $xf(0, 0, 1, 165, $mid),                               // 7 usd
            $xf(0, 0, 1, 166, $mid),                               // 8 rate
            $xf(0, 0, 1, 167, $mid),                               // 9 area
            $xf(0, 0, 1, 168, '<alignment horizontal="center" vertical="center"/>'), // 10 date
            $xf(5, 0, 1, 164, $mid),                               // 11 in (yashil)
            $xf(6, 0, 1, 164, $mid),                               // 12 out (qizil)
            $xf(7, 0, 0, 0, '<alignment vertical="center"/>'),     // 13 section
            $xf(4, 0, 1, 0, '<alignment vertical="center" wrapText="1"/>'), // 14 bold
            $xf(4, 3, 1, 0, '<alignment vertical="center"/>'),     // 15 t_text
            $xf(4, 3, 1, 164, $mid),                               // 16 t_int
            $xf(4, 3, 1, 164, $mid),                               // 17 t_money
            $xf(4, 3, 1, 165, $mid),                               // 18 t_usd
            $xf(4, 3, 1, 166, $mid),                               // 19 t_rate
            $xf(4, 3, 1, 167, $mid),                               // 20 t_area
            $xf(8, 3, 1, 164, $mid),                               // 21 t_in
            $xf(9, 3, 1, 164, $mid),                               // 22 t_out
            $xf(11, 3, 1, 0, '<alignment horizontal="center" vertical="center" textRotation="90" wrapText="1"/>'), // 23 floor (tik qavat nomi)
            $xf(10, 0, 0, 0, '<alignment vertical="center"/>'),     // 24 big (katta sarlavha)
            $xf(5, 0, 1, 165, $mid),                               // 25 usd_in
            $xf(6, 0, 1, 165, $mid),                               // 26 usd_out
            $xf(0, 0, 1, 0, '<alignment horizontal="center" vertical="center"/>'), // 27 center
            $xf(12, 0, 1, 0, '<alignment vertical="center" wrapText="1"/>'), // 28 muted
            $xf(4, 0, 1, 164, $mid),                               // 29 bold_money
            $xf(3, 2, 1, 0, '<alignment vertical="center"/>'),       // 30 objbar (bino nomi qatori)
            $xf(4, 3, 1, 0, '<alignment horizontal="center" vertical="center"/>'), // 31 t_center
        );
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmts . $fonts . $fills . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $b . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($cells) . '">' . implode('', $cells) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    /** Faylni vaqtinchalik papkaga yozadi va yo'lini qaytaradi (ZipArchive bo'lmasa null). */
    public function build()
    {
        if (!class_exists('ZipArchive') || !$this->sheets) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'hsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $types = '';
        $wbSheets = '';
        $rels = '';
        foreach ($this->sheets as $i => $s) {
            $n = $i + 1;
            $types .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wbSheets .= '<sheet name="' . self::x($s->name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', $this->sheetXml($s));
        }
        $rels .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $types . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wbSheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->close();
        return $tmp;
    }

    public function send($filename)
    {
        $tmp = $this->build();
        if ($tmp === null) {
            return false;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store');
        readfile($tmp);
        unlink($tmp);
        return true;
    }
}
