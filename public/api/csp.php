<?php
/**
 * CSP buzilish hisobotlari (tools/csp.mjs dagi `report-uri /api/csp.php`).
 *
 * Report-Only davrida brauzer bloklanishi mumkin bo'lgan har bir skript yoki
 * so'rov haqida shu yerga JSON yuboradi. Biz faqat qaysi qoida va qaysi manba
 * ekanini yozamiz — IP, cookie, to'liq URL parametrlari yozilmaydi.
 *
 * Jurnal: <data_dir>/csp.log (admin/_data, vebdan yopiq). 512 KB dan oshsa
 * yangi yozuv qo'shilmaydi — bitta buzilish har sahifada takrorlanib, diskni
 * to'ldirmasin. Ko'rish: hosting fayl menejeri yoki FTP.
 */
require __DIR__ . '/../admin/_lib/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$raw = (string) file_get_contents('php://input', false, null, 0, 16384);
$data = json_decode($raw, true);
$r = is_array($data) && isset($data['csp-report']) && is_array($data['csp-report']) ? $data['csp-report'] : null;
if ($r === null) {
    http_response_code(400);
    exit;
}

/** Manzildan faqat sxema+host+yo'l (so'rov parametrlari va fragment tashlanadi). */
function hs_csp_url($v)
{
    $v = (string) $v;
    if ($v === '' || strpos($v, '://') === false) {
        return substr($v, 0, 40); // 'inline', 'eval', 'data' kabi kalit so'zlar
    }
    $p = parse_url($v);
    if (!is_array($p) || empty($p['host'])) {
        return '';
    }
    return substr((isset($p['scheme']) ? $p['scheme'] : 'https') . '://' . $p['host'] . (isset($p['path']) ? $p['path'] : ''), 0, 200);
}

$row = array(
    'vaqt' => date('Y-m-d H:i:s'),
    'qoida' => substr(isset($r['effective-directive']) ? (string) $r['effective-directive'] : (isset($r['violated-directive']) ? (string) $r['violated-directive'] : ''), 0, 40),
    'manba' => hs_csp_url(isset($r['blocked-uri']) ? $r['blocked-uri'] : ''),
    'sahifa' => hs_csp_url(isset($r['document-uri']) ? $r['document-uri'] : ''),
    'fayl' => hs_csp_url(isset($r['source-file']) ? $r['source-file'] : ''),
    'qator' => isset($r['line-number']) ? (int) $r['line-number'] : 0,
    'brauzer' => substr(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 0, 120),
);

$log = hs_data_dir() . '/csp.log';
if (!is_file($log) || filesize($log) < 512 * 1024) {
    @file_put_contents($log, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}
http_response_code(204);
