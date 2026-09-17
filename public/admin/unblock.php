<?php
/**
 * Telegram'dagi havola orqali blokni ochish.
 *
 * GET — faqat tasdiqlash oynasi (Telegram havola oldindan ko'rish uchun
 * sahifani ochsa ham blok ochilib ketmasin). POST — haqiqiy ochish.
 * Token bir martalik, bazada faqat xeshi turadi.
 */
require __DIR__ . '/_lib/bootstrap.php';

hs_security_headers();

$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? hs_post('t') : hs_get('t');
$block = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = hs_db()->prepare('SELECT * FROM blocks WHERE token_hash = ? AND unblocked_at IS NULL');
    $st->execute(array(hash('sha256', $token)));
    $block = $st->fetch() ?: null;
}

$done = false;
if ($block && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $done = hs_unblock((int) $block['id'], 'egasi (Telegram havolasi)');
}

echo '<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<meta name="robots" content="noindex"><title>Blokni ochish</title><link rel="stylesheet" href="/admin/assets/admin.css?v=1"></head><body class="center"><div class="card narrow">';
echo '<h1>Blokni ochish</h1>';
if ($done) {
    echo '<p class="flash flash-ok">Blok ochildi. Endi shu qurilmadan qayta kirish mumkin.</p><p><a class="btn" href="/admin/login.php">Kirish sahifasi</a></p>';
} elseif (!$block) {
    echo "<p>Havola eskirgan yoki blok allaqachon ochilgan.</p>";
} else {
    echo '<dl class="kv"><dt>Vaqt</dt><dd>' . h($block['created_at']) . '</dd><dt>IP</dt><dd>' . h($block['ip']) . '</dd><dt>Qurilma</dt><dd>' . h(hs_describe_agent($block['user_agent'])) . '</dd><dt>Login</dt><dd>' . h($block['last_login']) . '</dd></dl>';
    echo "<p>Bu siz bo'lsangiz, blokni oching. Siz bo'lmasangiz — bu sahifani yoping.</p>";
    echo '<form method="post" action="/admin/unblock.php"><input type="hidden" name="t" value="' . h($token) . '"><div class="actions"><button class="btn" type="submit">Ha, blokni ochish</button></div></form>';
}
echo '</div></body></html>';
