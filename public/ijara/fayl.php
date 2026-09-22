<?php
/**
 * Ijarachi faylini (shartnoma skani) ko'rsatadi. Fayllar data papkasida —
 * veb orqali to'g'ridan-to'g'ri ochilmaydi, faqat shu sahifa orqali, egasiga.
 * ?yuklab=1 — kompyuterga saqlash; aks holda brauzerda ochiladi.
 */
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

$user = hs_ij_login();
hs_session_release();

$st = hs_db()->prepare('SELECT * FROM ij_files WHERE id = ?');
$st->execute(array((int) hs_get('id')));
$f = $st->fetch();
$path = $f ? hs_ij_files_dir() . '/' . basename($f['stored']) : '';
if (!$f || !is_file($path)) {
    http_response_code(404);
    hs_page_start('Fayl topilmadi', $user);
    echo '<div class="card"><p>Fayl topilmadi yoki o\'chirilgan.</p></div>';
    hs_page_end();
    exit;
}

$types = hs_ij_file_types();
$mime = isset($types[$f['mime']]) ? $f['mime'] : 'application/octet-stream';
// Fayl nomida faqat xavfsiz belgilar; asl nomi UTF-8 da filename* orqali.
$ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $f['orig_name']);
// Word, Excel, HEIC brauzerda ochilmaydi — ular doim yuklab olinadi.
$disp = hs_get('yuklab') === '1' || !hs_ij_file_inline($mime) ? 'attachment' : 'inline';

// Panelning umumiy CSP'sidagi object-src 'none' brauzerning PDF ko'ruvchisini to'sadi.
// Shu fayl uchun yumshoqrog'i — ijara/.htaccess dagi <Files> bilan bir xil.
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; object-src 'self'; frame-ancestors 'none'");
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disp . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($f['orig_name']));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
