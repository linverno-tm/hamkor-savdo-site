<?php
/**
 * Arizalarni Excel (.xlsx) ga. ZipArchive bo'lmasa CSV (Excel ham ochadi).
 */
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/leads.php';

$user = hs_require_login();
list($where, $args) = hs_lead_filters($user);
$st = hs_db()->prepare("SELECT * FROM leads {$where} ORDER BY id DESC LIMIT 20000");
$st->execute($args);

$branches = hs_branch_names();
$statuses = hs_lead_statuses();
$rows = array(array('#', 'Sana', 'Ism', 'Telefon', 'Filial', "So'rovi", "Yo'q mahsulot", 'Manba', 'Holat', 'Operator izohi', "O'zgartirgan"));
foreach ($st->fetchAll() as $r) {
    $rows[] = array(
        (int) $r['id'],
        date('d.m.Y H:i', strtotime($r['created_at'])),
        $r['name'],
        $r['phone'],
        $r['branch'] !== '' ? (isset($branches[$r['branch']]) ? $branches[$r['branch']] : $r['branch']) : '',
        $r['note'],
        (int) $r['special'] ? 'ha' : '',
        hs_source_label($r['source']),
        isset($statuses[$r['status']]) ? $statuses[$r['status']] : $r['status'],
        $r['operator_note'],
        (string) $r['updated_by'],
    );
}
hs_audit($user['login'], 'eksport', (count($rows) - 1) . ' ta ariza');

$name = 'arizalar-' . date('Y-m-d');

/**
 * CSV: Excel matnni formula sifatida bajarmasin (=, +, -, @ bilan boshlangan).
 * Telefon raqami (+998...) xavfsiz — unga tegilmaydi.
 */
function hs_cell_safe($v)
{
    if (is_string($v) && $v !== '' && strpos('=+-@', $v[0]) !== false && !preg_match('/^\+?[\d\s()-]+$/', $v)) {
        return "'" . $v;
    }
    return $v;
}

if (class_exists('ZipArchive')) {
    $x = function ($s) {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    foreach ($rows as $ri => $row) {
        $sheet .= '<row r="' . ($ri + 1) . '">';
        foreach ($row as $ci => $val) {
            $ref = chr(65 + $ci) . ($ri + 1);
            if (is_int($val)) {
                $sheet .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
            } else {
                $sheet .= '<c r="' . $ref . '" t="inlineStr"' . ($ri === 0 ? ' s="1"' : '') . '><is><t xml:space="preserve">' . $x($val) . '</t></is></c>';
            }
        }
        $sheet .= '</row>';
    }
    $sheet .= '</sheetData></worksheet>';

    $tmp = tempnam(sys_get_temp_dir(), 'hsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Arizalar" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $name . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    unlink($tmp);
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $name . '.csv"');
header('Cache-Control: no-store');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
foreach ($rows as $row) {
    fputcsv($out, array_map('hs_cell_safe', $row), ';');
}
fclose($out);
