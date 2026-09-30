<?php
/**
 * Telegram postining rasmini panelda ko'rsatish.
 *
 * Katalogni kanaldan to'ldirishda egasi mahsulot rasmini ko'rib turishi kerak —
 * nom va narx aynan rasmdan o'qilgan, tekshirish shundan boshlanadi. Lekin
 * panelning xavfsizlik qoidasi (`img-src 'self'`) tashqi manzildan rasm
 * yuklashga yo'l bermaydi va bu qoida yumshatilmaydi: panelda arizalar va
 * mijozlar raqami bor.
 *
 * Shuning uchun rasm shu yerdan o'tadi. Ochiq proksi bo'lib qolmasligi uchun
 * faqat BAZADA turgan manzillar beriladi: `mb_posts.rasm` yoki
 * `kat_import.rasm` da aynan shu manzil bo'lsagina yuklab olinadi. Ya'ni
 * boshqa manzilni so'rab, serverni begona joyga yubora olmaydi.
 */
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/kuzatuv.php';

$user = hs_require_login(true);

$src = hs_get('src');
$st = hs_db()->prepare('SELECT 1 FROM mb_posts WHERE rasm = ? UNION ALL SELECT 1 FROM kat_import WHERE rasm = ? LIMIT 1');
$st->execute(array($src, $src));
if ($src === '' || strncmp($src, 'https://', 8) !== 0 || !$st->fetchColumn()) {
    http_response_code(404);
    exit;
}

// Bir xil rasm ro'yxatda bir necha marta ochiladi — har safar Telegram'ga
// bormaymiz. Kesh bazada, 6 soat.
$kalit = 'rasmkoz:' . substr(hash('sha256', $src), 0, 32);
$kesh = hs_cache_get($kalit);
if (is_array($kesh) && isset($kesh['mime'], $kesh['b64'])) {
    $mime = $kesh['mime'];
    $bayt = base64_decode($kesh['b64']);
} else {
    list($code, $bayt) = hs_http('GET', $src, array('User-Agent: Mozilla/5.0 (HamkorSavdo panel)'), null, 20);
    $mime = $code === 200 ? hs_rq_mime((string) $bayt) : '';
    if ($mime === '' || strlen((string) $bayt) > 3000000) {
        http_response_code(502);
        exit;
    }
    hs_cache_set($kalit, array('mime' => $mime, 'b64' => base64_encode($bayt)), 6 * 3600);
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($bayt));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
echo $bayt;
