<?php
/**
 * Telegram webhook: bot guruhga qo'shilganda, chiqarilganda yoki unga
 * /start yozilganda Telegram shu manzilga xabar beradi. Chat admin
 * paneldagi ro'yxatga tushadi (admin/telegram.php), arizalar esa faqat
 * u yerda yoqilgan chatlarga ketadi.
 *
 * Manzilni paneldagi "Botni ulash" tugmasi o'rnatadi. Telegram har
 * so'rovga maxfiy kalitni sarlavhada qo'shadi — kalitsiz kelgan so'rov
 * (ya'ni Telegram'dan emas) hech narsa qilmaydi.
 */
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/tgchats.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$secret = (string) hs_setting('tg_webhook_secret', '');
$given = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? (string) $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo '{"ok":false}';
    exit;
}

$update = json_decode((string) file_get_contents('php://input'), true);
if (is_array($update)) {
    try {
        hs_tg_handle_update($update);
    } catch (Throwable $e) {
        // Telegram'ga baribir 200 qaytaramiz — aks holda u bir xil voqeani qayta-qayta yuboradi.
        error_log('HAMKOR SAVDO: Telegram webhook xatosi: ' . $e->getMessage());
    }
}
echo '{"ok":true}';

// Cron bo'lmasa ham eslatma va zaxira vaqtida ketsin: botga har voqea kelganda tekshiriladi.
require_once __DIR__ . '/../admin/_lib/tasks.php';
hs_tasks_maybe_run();
