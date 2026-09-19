<?php
/**
 * Vaqt bo'yicha ishlaydigan vazifalar:
 *   - javobsiz ariza eslatmasi (15 va 30 daqiqa, sozlanadi);
 *   - arizalarning kechki zaxira nusxasi (boshqaruvchiga Telegram'da fayl);
 *   - kunlik hisobot (avval cron/hisobot.php qilardi).
 *
 * Ikki yo'l bilan ishga tushadi:
 *   1. Hostingdagi Cron har 5 daqiqada: php .../admin/cron/vazifalar.php — aniq.
 *   2. Cron bo'lmasa: panel ochilganda yoki botga voqea kelganda (daqiqasiga
 *      ko'pi bilan bir marta). Tunda hech kim kirmasa — kechikadi, shuning
 *      uchun panel Cron sozlanmaganini ko'rsatib turadi.
 */
require_once __DIR__ . '/tgchats.php';
require_once __DIR__ . '/leads.php';

function hs_task_setting($key)
{
    $defaults = array(
        'remind_on' => '1',
        'remind_m1' => '15',   // shuncha daqiqada — ariza kelgan chatlarga (filial rahbari, guruh)
        'remind_m2' => '30',   // shuncha daqiqada — boshqaruvchiga
        'work_from' => '8',    // eslatmalar faqat ish vaqtida; tungi ariza ertalab hisoblanadi
        'work_to' => '21',
        'backup_on' => '1',
        'backup_hour' => '23',
    );
    return (string) hs_setting('task_' . $key, $defaults[$key]);
}

/**
 * Hammasini bir marta. $fromCron — Cron'dan chaqirilgan (panelda "Cron ishlayapti" belgisi uchun).
 * Bir vaqtda ikki nusxa ishlamasligi uchun fayl qulfi.
 */
function hs_tasks_run($fromCron = false)
{
    $lock = @fopen(hs_data_dir() . '/tasks.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return array('band');
    }
    $done = array();
    try {
        hs_set_setting('tasks_last_run', (string) time());
        if ($fromCron) {
            hs_set_setting('tasks_last_cron', (string) time());
        }
        $n = hs_tasks_reminders();
        if ($n) {
            $done[] = "{$n} ta eslatma";
        }
        if (hs_tasks_backup()) {
            $done[] = 'zaxira';
        }
        if (hs_tasks_daily_report()) {
            $done[] = 'hisobot';
        }
    } catch (Throwable $e) {
        error_log('HAMKOR SAVDO: vazifalar xatosi: ' . $e->getMessage());
        $done[] = 'xato: ' . $e->getMessage();
    }
    flock($lock, LOCK_UN);
    fclose($lock);
    return $done;
}

/** Cron bo'lmasa — sahifa yoki bot so'rovi tugagach, daqiqasiga ko'pi bilan bir marta. */
function hs_tasks_maybe_run()
{
    if (time() - (int) hs_setting('tasks_last_run', '0') < 60) {
        return;
    }
    register_shutdown_function(function () {
        // Javob foydalanuvchiga avval yetib borsin, vazifalar keyin.
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        hs_tasks_run(false);
    });
}

/**
 * Ariza qachondan beri kutyapti — faqat ish vaqti hisobga olinadi.
 * Kechasi 23:00 da kelgan ariza ertalab 8:00 dan "kutyapti" hisoblanadi,
 * aks holda ertalab hamma tungi arizalar bo'yicha birdan eslatma yog'iladi.
 */
function hs_lead_wait_minutes($createdAt, $now = null)
{
    $now = $now === null ? time() : $now;
    $from = (int) hs_task_setting('work_from');
    $to = (int) hs_task_setting('work_to');
    $t = strtotime($createdAt);
    $h = (int) date('G', $t);
    if ($h < $from) {
        $t = strtotime(date('Y-m-d', $t) . sprintf(' %02d:00:00', $from));
    } elseif ($h >= $to) {
        $t = strtotime(date('Y-m-d', $t + 86400) . sprintf(' %02d:00:00', $from));
    }
    return (int) floor(($now - $t) / 60);
}

function hs_is_work_time($now = null)
{
    $h = (int) date('G', $now === null ? time() : $now);
    return $h >= (int) hs_task_setting('work_from') && $h < (int) hs_task_setting('work_to');
}

/** Javobsiz arizalar bo'yicha eslatma. Qaytaradi: nechta xabar ketdi. */
function hs_tasks_reminders()
{
    if (hs_task_setting('remind_on') !== '1' || !hs_is_work_time()) {
        return 0;
    }
    $m1 = max(1, (int) hs_task_setting('remind_m1'));
    $m2 = max($m1 + 1, (int) hs_task_setting('remind_m2'));
    $db = hs_db();
    // Oxirgi 3 kun — undan eskisi uchun eslatishning ma'nosi yo'q.
    $st = $db->prepare("SELECT * FROM leads WHERE status = 'yangi' AND remind_level < 2 AND created_at >= ? ORDER BY id");
    $st->execute(array(date('Y-m-d H:i:s', time() - 3 * 86400)));
    $admins = hs_tg_admin_ids();
    $sent = 0;
    foreach ($st->fetchAll() as $lead) {
        $wait = hs_lead_wait_minutes($lead['created_at']);
        $id = (int) $lead['id'];
        $msgs = $db->prepare('SELECT chat_id, message_id FROM tg_lead_msgs WHERE lead_id = ?');
        $msgs->execute(array($id));
        $copies = array();
        foreach ($msgs->fetchAll() as $m) {
            $copies[(string) $m['chat_id']] = (int) $m['message_id'];
        }
        $who = $lead['name'] . ', ' . $lead['phone'];

        // 1-bosqich: hech kim olmagan bo'lsa — ariza kelgan chatlarga (boshqaruvchidan tashqari).
        if ((int) $lead['remind_level'] < 1 && $wait >= $m1) {
            if ($lead['claimed_by'] === '') {
                foreach ($copies as $chatId => $mid) {
                    // Raqamli kalitlarni PHP int qilib yuboradi — solishtirishdan oldin matnga.
                    $chatId = (string) $chatId;
                    if (in_array($chatId, $admins, true)) {
                        continue;
                    }
                    hs_tg_api('sendMessage', array(
                        'chat_id' => $chatId,
                        'reply_to_message_id' => $mid,
                        'allow_sending_without_reply' => 'true',
                        'text' => "⏰ {$wait} daqiqa o'tdi — bu arizaga hali hech kim javob bermadi.\n{$who}\n\nQo'ng'iroq qiling va \"🙋 Men oldim\" ni bosing.",
                    ));
                    $sent++;
                }
            }
            $db->prepare('UPDATE leads SET remind_level = 1 WHERE id = ?')->execute(array($id));
        }

        // 2-bosqich: hali ham "Yangi" — boshqaruvchiga.
        if ($wait >= $m2) {
            $claim = $lead['claimed_by'] !== '' ? "\n🙋 Olgan: " . $lead['claimed_by'] . " — lekin holat hali o'zgarmagan." : "\nHech kim olmagan.";
            foreach ($admins as $aid) {
                $params = array(
                    'chat_id' => $aid,
                    'text' => "⚠️ Ariza #{$id} ga {$wait} daqiqadan beri javob yo'q.\n{$who}\n📍 " . hs_tg_branch_short((string) $lead['branch']) . $claim
                        . (isset($copies[$aid]) ? '' : "\n\n" . hs_site_url() . '/admin/ariza.php?id=' . $id),
                );
                if (isset($copies[$aid])) {
                    $params['reply_to_message_id'] = $copies[$aid];
                    $params['allow_sending_without_reply'] = 'true';
                }
                hs_tg_api('sendMessage', $params);
                $sent++;
            }
            $db->prepare('UPDATE leads SET remind_level = 2 WHERE id = ?')->execute(array($id));
        }
    }
    return $sent;
}

/** Barcha arizalar CSV'da (Excel ochadi). Qaytaradi: vaqtinchalik fayl yo'li. */
function hs_leads_backup_file(&$count = 0)
{
    $cols = array('id', 'created_at', 'name', 'phone', 'branch', 'note', 'special', 'page', 'source', 'source_detail', 'ym_client',
        'telegram_sent', 'status', 'operator_note', 'updated_at', 'updated_by', 'claimed_by', 'claimed_at');
    $path = hs_data_dir() . '/arizalar-zaxira-' . date('Y-m-d') . '.csv';
    $f = fopen($path, 'w');
    fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, $cols, ';');
    $count = 0;
    foreach (hs_db()->query('SELECT ' . implode(', ', $cols) . ' FROM leads ORDER BY id') as $r) {
        $row = array();
        foreach ($cols as $c) {
            $v = (string) $r[$c];
            // Excel formulasi sifatida bajarilmasin (=, +, -, @ bilan boshlangan matn). Telefon raqamiga tegilmaydi.
            if ($v !== '' && strpos('=+-@', $v[0]) !== false && !preg_match('/^\+?[\d\s()-]+$/', $v)) {
                $v = "'" . $v;
            }
            $row[] = $v;
        }
        fputcsv($f, $row, ';');
        $count++;
    }
    fclose($f);
    return $path;
}

/** Faylni Telegram'ga hujjat qilib yuboradi (multipart). */
function hs_tg_send_document($chatId, $path, $caption)
{
    $token = (string) hs_config('token', '');
    if (getenv('HS_DRY_RUN') === '1') {
        @file_put_contents(hs_data_dir() . '/telegram.log', '[' . hs_now() . "] sendDocument {$chatId} " . basename($path) . ' (' . filesize($path) . " bayt)\n{$caption}\n", FILE_APPEND);
        return true;
    }
    if ($token === '' || !function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendDocument');
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => array(
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new CURLFile($path, 'text/csv', basename($path)),
        ),
    ));
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200 && strpos((string) $body, '"ok":true') !== false;
}

/**
 * Zaxira: faqat arizalar (parollar, kalitlar, sozlamalar YO'Q — fayl Telegram'da
 * turadi va u yerdan boshqa odamga yuborilishi mumkin). Kuniga bir marta, belgilangan soatda.
 */
function hs_tasks_backup($force = false)
{
    if (!$force) {
        if (hs_task_setting('backup_on') !== '1' || (int) date('G') < (int) hs_task_setting('backup_hour')) {
            return false;
        }
        if (hs_setting('backup_last', '') === date('Y-m-d')) {
            return false;
        }
    }
    $count = 0;
    $path = hs_leads_backup_file($count);
    $today = (int) hs_db()->query("SELECT COUNT(*) FROM leads WHERE substr(created_at, 1, 10) = '" . date('Y-m-d') . "'")->fetchColumn();
    $caption = "💾 Arizalar zaxirasi — " . date('d.m.Y H:i') . "\nJami: {$count} ta ariza, bugun: {$today} ta.\n\nExcel'da ochiladi. Faylni saqlab qo'ying — server bilan biror narsa bo'lsa, arizalar shu yerda qoladi.";
    $ok = false;
    foreach (hs_tg_admin_ids() as $aid) {
        $ok = hs_tg_send_document($aid, $path, $caption) || $ok;
    }
    @unlink($path);
    if ($ok) {
        hs_set_setting('backup_last', date('Y-m-d'));
        hs_set_setting('backup_last_at', hs_now());
    }
    return $ok;
}

/** Kunlik hisobot — Sozlamalar'dagi soatda, kuniga bir marta. */
function hs_tasks_daily_report()
{
    // ">=": Cron bo'lmasa vazifa aynan o'sha soatda ishga tushmasligi mumkin — keyinroq bo'lsa ham yuboriladi.
    if (hs_setting('report_enabled', '1') !== '1' || (int) date('G') < (int) hs_setting('report_hour', '21')) {
        return false;
    }
    if (hs_setting('report_last', '') === date('Y-m-d')) {
        return false;
    }
    require_once __DIR__ . '/metrika.php';
    require_once __DIR__ . '/report.php';
    if (hs_telegram_send(hs_build_daily_report(date('Y-m-d')))) {
        hs_set_setting('report_last', date('Y-m-d'));
        return true;
    }
    return false;
}
