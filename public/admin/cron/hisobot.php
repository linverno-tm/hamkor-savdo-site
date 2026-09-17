<?php
/**
 * Kunlik Telegram hisoboti. Hostingdagi Cron har soatda ishga tushiradi:
 *
 *   php /home/FOYDALANUVCHI/public_html/admin/cron/hisobot.php
 *
 * Qaysi soatda va nimalar yuborilishi panelning Sozlamalar bo'limida.
 * Veb orqali ochib bo'lmaydi (faqat CLI + .htaccess).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../_lib/bootstrap.php';
require_once __DIR__ . '/../_lib/metrika.php';
require_once __DIR__ . '/../_lib/leads.php';
require_once __DIR__ . '/../_lib/report.php';

$force = in_array('--hozir', $argv, true);
if (!$force) {
    if (hs_setting('report_enabled', '1') !== '1') {
        exit(0);
    }
    $hour = (int) hs_setting('report_hour', '21');
    if ((int) date('G') !== $hour) {
        exit(0);
    }
    if (hs_setting('report_last', '') === date('Y-m-d')) {
        exit(0);
    }
}

$text = hs_build_daily_report(date('Y-m-d'));
if (hs_telegram_send($text)) {
    hs_set_setting('report_last', date('Y-m-d'));
    echo "yuborildi\n";
} else {
    fwrite(STDERR, "yuborilmadi\n");
    exit(1);
}
