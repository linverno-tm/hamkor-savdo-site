<?php
/** Bino surati — data papkasidan, faqat panelga kirganlarga. */
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

hs_ij_login();
hs_session_release();
$o = hs_ij_object((int) hs_get('id'));
$path = $o && $o['photo'] !== '' ? hs_ij_files_dir() . '/' . basename($o['photo']) : '';
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit;
}
$types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
// Manzilda v= bor — surat almashsa manzil ham o'zgaradi, shuning uchun uzoq kesh xavfsiz.
header('Cache-Control: private, max-age=2592000');
readfile($path);
