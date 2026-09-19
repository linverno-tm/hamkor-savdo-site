<?php
/**
 * Vaqt bo'yicha vazifalar: javobsiz ariza eslatmasi, kechki zaxira, kunlik hisobot.
 * Hostingdagi Cron har 5 daqiqada ishga tushiradi (aniq buyruq panelning
 * Telegram bo'limida ko'rsatiladi):
 *
 *   php /home/FOYDALANUVCHI/public_html/admin/cron/vazifalar.php
 *
 * Veb orqali ochib bo'lmaydi (faqat CLI + .htaccess).
 *   --zaxira  — zaxirani hozir yuborish (sinash uchun)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../_lib/bootstrap.php';
require_once __DIR__ . '/../_lib/tasks.php';

if (in_array('--zaxira', $argv, true)) {
    echo hs_tasks_backup(true) ? "zaxira yuborildi\n" : "zaxira yuborilmadi\n";
    exit(0);
}

$done = hs_tasks_run(true);
echo date('H:i') . ' ' . ($done ? implode(', ', $done) : 'ish yo\'q') . "\n";
