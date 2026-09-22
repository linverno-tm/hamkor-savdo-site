<?php
/** Dollar kursi kartochkasi (HTML bo'lagi) — ochiq sahifadagi kursni admin.js har 5 daqiqada shu yerdan yangilaydi. */
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

hs_ij_login();
hs_session_release();
$rate = hs_ij_rate();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('html' => hs_ij_rate_card($rate), 'source' => $rate ? $rate['source'] : ''), JSON_UNESCAPED_UNICODE);
