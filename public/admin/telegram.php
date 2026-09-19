<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/tgchats.php';
require_once __DIR__ . '/_lib/tasks.php';

$user = hs_require_login(true);
hs_tg_seed();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $id = hs_post('id');
    $db = hs_db();
    $row = null;
    if ($id !== '') {
        $st = $db->prepare('SELECT * FROM tg_chats WHERE chat_id = ?');
        $st->execute(array($id));
        $row = $st->fetch() ?: null;
    }

    if ($action === 'ulash') {
        list($ok, $res) = hs_tg_connect_webhook();
        hs_cache_set('tg:info', null, 1);
        hs_audit($user['login'], 'telegram webhook ulandi', $ok ? 'ok' : (string) $res);
        hs_flash($ok ? "Bot ulandi. Endi bot qo'shilgan guruhlar va unga /start yozgan odamlar shu ro'yxatda o'zi paydo bo'ladi." : 'Ulanmadi: ' . $res, $ok ? 'ok' : 'err');
    } elseif ($action === 'arizalar' && $row) {
        $on = hs_post('leads') === '1' ? 1 : 0;
        $db->prepare('UPDATE tg_chats SET leads = ?, updated_at = ? WHERE chat_id = ?')->execute(array($on, hs_now(), $id));
        hs_audit($user['login'], $on ? 'telegram: arizalar yoqildi' : "telegram: arizalar o'chirildi", $row['title'] . " ({$id})");
        hs_flash($on ? "Arizalar endi «{$row['title']}» ga ham boradi." : "«{$row['title']}» ga arizalar yuborilmaydi.");
    } elseif ($action === 'filial' && $row) {
        $b = preg_replace('/[^a-z0-9-]/', '', hs_post('filial'));
        $db->prepare('UPDATE tg_chats SET branch = ?, updated_at = ? WHERE chat_id = ?')->execute(array($b, hs_now(), $id));
        hs_audit($user['login'], 'telegram: filial filtri', $row['title'] . ': ' . ($b !== '' ? $b : 'hammasi'));
        hs_flash('Saqlandi.');
    } elseif ($action === 'admin' && $row && $row['type'] === 'private') {
        $on = hs_post('admin') === '1' ? 1 : 0;
        $db->prepare('UPDATE tg_chats SET admin = ?, updated_at = ? WHERE chat_id = ?')->execute(array($on, hs_now(), $id));
        hs_audit($user['login'], $on ? 'telegram: boshqaruvchi qilindi' : 'telegram: boshqaruvchilikdan olindi', $row['title'] . " ({$id})");
        if ($on) {
            hs_tg_api('sendMessage', array('chat_id' => $id, 'text' => "⭐ Siz HAMKOR SAVDO botining boshqaruvchisisiz.\n\nKimdir botga yozsa yoki botni guruhga qo'shsa, sizga ruxsat so'rovi keladi — tugmani bosib hal qilasiz.\n\n/royxat — hamma chatlar, bosib yoqish/o'chirish."));
        }
        hs_flash($on ? "«{$row['title']}» endi boshqaruvchi: ruxsat so'rovlari unga Telegram'da keladi." : "«{$row['title']}» boshqaruvchilikdan olindi.");
    } elseif ($action === 'vazifalar') {
        $int = function ($k, $min, $max) {
            return (string) max($min, min($max, (int) hs_post($k)));
        };
        hs_set_setting('task_remind_on', hs_post('remind_on') === '1' ? '1' : '0');
        hs_set_setting('task_remind_m1', $int('remind_m1', 1, 240));
        hs_set_setting('task_remind_m2', (string) max((int) hs_setting('task_remind_m1') + 1, (int) $int('remind_m2', 2, 480)));
        hs_set_setting('task_work_from', $int('work_from', 0, 23));
        hs_set_setting('task_work_to', (string) max((int) hs_setting('task_work_from') + 1, (int) $int('work_to', 1, 24)));
        hs_set_setting('task_backup_on', hs_post('backup_on') === '1' ? '1' : '0');
        hs_set_setting('task_backup_hour', $int('backup_hour', 0, 23));
        hs_audit($user['login'], 'eslatma va zaxira sozlamasi');
        hs_flash('Saqlandi.');
    } elseif ($action === 'zaxira') {
        $ok = hs_tasks_backup(true);
        hs_audit($user['login'], 'zaxira qo\'lda yuborildi', $ok ? 'ok' : 'xato');
        hs_flash($ok ? "Zaxira fayli boshqaruvchiga Telegram'da yuborildi." : "Yuborilmadi — boshqaruvchi tanlanganini va bot ishlayotganini tekshiring.", $ok ? 'ok' : 'err');
    } elseif ($action === 'sinov' && $row) {
        $ok = hs_tg_send_to($id, "🧪 Sinov xabari — HAMKOR SAVDO admin paneli\n\nBu chat ro'yxatda. " . ((int) $row['leads'] ? 'Saytdan kelgan arizalar shu yerga keladi.' : "Arizalar hozircha o'chirilgan — panelda yoqing."));
        hs_flash($ok ? "Sinov xabari «{$row['title']}» ga yuborildi." : "Yuborilmadi. Bot bu chatdan chiqarilgan yoki odam botni to'xtatgan bo'lishi mumkin.", $ok ? 'ok' : 'err');
    } elseif ($action === 'ochirish' && $row) {
        $db->prepare('DELETE FROM tg_chats WHERE chat_id = ?')->execute(array($id));
        hs_audit($user['login'], "telegram: chat ro'yxatdan olindi", $row['title'] . " ({$id})");
        hs_flash("«{$row['title']}» ro'yxatdan olib tashlandi. Botga qayta /start yozilsa yoki bot qayta qo'shilsa, yana paydo bo'ladi.");
    } elseif ($action === 'qoshish') {
        // Chat ID (-100…) yoki ochiq guruh/kanal manzili (@nom yoki t.me/nom).
        $raw = trim(hs_post('chat'));
        $raw = preg_replace('#^(https?://)?t\.me/#i', '@', $raw);
        if (!preg_match('/^(-?\d{5,20}|@[A-Za-z0-9_]{4,64})$/', $raw)) {
            hs_flash("Chat ID (masalan -1001234567890) yoki @nom kiriting.", 'err');
        } else {
            list($ok, $chat) = hs_tg_api('getChat', array('chat_id' => $raw));
            if (!$ok) {
                hs_flash("Topilmadi: {$chat}. Bot o'sha guruhda bo'lishi, odam esa botga oldin /start yozgan bo'lishi kerak.", 'err');
            } else {
                hs_tg_upsert($chat, 'member');
                hs_audit($user['login'], "telegram: chat qo'lda qo'shildi", hs_tg_chat_title($chat));
                hs_flash('«' . hs_tg_chat_title($chat) . "» ro'yxatga qo'shildi. Arizalarni yoqishni unutmang.");
            }
        }
    }
    hs_redirect('/admin/telegram.php');
}

/* Bot va webhook holati. Har ochilishda Telegram'ga bormaslik uchun 60 soniya keshlanadi. */
$info = hs_cache_get('tg:info');
if (!is_array($info)) {
    list($okMe, $me) = hs_tg_api('getMe');
    list($okWh, $wh) = hs_tg_api('getWebhookInfo');
    $info = array('me' => $okMe ? $me : null, 'meError' => $okMe ? '' : (string) $me, 'wh' => $okWh ? $wh : null);
    if ($okMe) {
        hs_cache_set('tg:info', $info, 60);
    }
}
$botName = $info['me'] && !empty($info['me']['username']) ? $info['me']['username'] : '';
$wh = $info['wh'];
$connected = $wh && isset($wh['url']) && $wh['url'] === hs_tg_webhook_url();
$otherHook = $wh && !empty($wh['url']) && !$connected;
if ($connected && (empty($wh['allowed_updates']) || !in_array('callback_query', $wh['allowed_updates'], true))) {
    list($upOk) = hs_tg_connect_webhook();
    hs_db()->prepare('DELETE FROM cache WHERE key = ?')->execute(array('tg:info'));
    if ($upOk) {
        hs_flash("Bot yangilandi: endi ruxsat so'rovlarini Telegram'dagi tugmalar bilan hal qilasiz.");
    }
}

$chats = hs_db()->query("SELECT * FROM tg_chats ORDER BY leads DESC, status = 'member' DESC, added_at")->fetchAll();
$active = 0;
foreach ($chats as $c) {
    if ((int) $c['leads'] && $c['status'] === 'member') {
        $active++;
    }
}
$branches = hs_branch_names();

hs_page_start('Telegram', $user);

/* ---- bot holati ---- */
echo '<section class="card"><div class="card-head"><h2>Bot</h2>';
if ($botName !== '') {
    echo '<a class="btn outline small" href="https://t.me/' . h($botName) . '" target="_blank" rel="noopener noreferrer">' . hs_icon('send') . ' @' . h($botName) . '</a>';
}
echo '</div>';
if (!$info['me']) {
    echo '<p class="flash flash-err">Bot javob bermadi: ' . h($info['meError']) . '</p>';
} else {
    echo '<ul class="checklist">';
    echo '<li><span class="state ' . ($connected ? 'on' : 'off') . '">' . hs_icon($connected ? 'check' : 'clock') . '</span><div><b>'
        . ($connected ? 'Ulangan — yangi chatlar ro\'yxatga o\'zi tushadi' : ($otherHook ? 'Bot boshqa manzilga ulangan' : 'Hali ulanmagan')) . '</b><small>'
        . ($connected ? 'Bot guruhga qo\'shilishi yoki unga /start yozilishi bilan shu yerda paydo bo\'ladi.' : 'Ulanmaguncha yangi guruh va odamlar ro\'yxatga o\'zi tushmaydi (qo\'lda qo\'shish ishlaydi).')
        . '</small></div></li>';
    if ($wh && !empty($wh['last_error_message'])) {
        echo '<li><span class="state off">' . hs_icon('x') . '</span><div><b>Telegram oxirgi marta yetkaza olmadi</b><small>'
            . h($wh['last_error_message']) . (!empty($wh['last_error_date']) ? ' · ' . h(date('d.m H:i', (int) $wh['last_error_date'])) : '') . '</small></div></li>';
    }
    $adminNames = array();
    foreach ($chats as $c) {
        if ((int) $c['admin'] === 1 && $c['status'] === 'member') {
            $adminNames[] = $c['title'];
        }
    }
    echo '<li><span class="state ' . ($adminNames ? 'on' : 'off') . '">' . hs_icon($adminNames ? 'check' : 'clock') . '</span><div><b>'
        . ($adminNames ? 'Boshqaruvchi: ' . h(implode(', ', $adminNames)) : 'Boshqaruvchi tanlanmagan') . '</b><small>'
        . ($adminNames ? 'Yangi odam yoki guruh qo\'shilsa, Telegram\'da tugmali so\'rov keladi. Botga /royxat yozsangiz — hamma chatlarni o\'sha yerda yoqib-o\'chirasiz.' : 'So\'rovlar hozircha secrets.php dagi chatga boradi. Pastdagi ro\'yxatdan o\'zingizni "★ Boshqaruvchi" qiling.')
        . '</small></div></li>';
    echo '<li><span class="state ' . ($active ? 'on' : 'off') . '">' . hs_icon($active ? 'check' : 'clock') . '</span><div><b>Arizalar '
        . ($active ? $active . ' ta chatga boradi' : 'hech kimga bormayapti') . '</b><small>'
        . ($active ? 'Pastdagi ro\'yxatda yoqilganlar.' : 'Ariza baribir panelda saqlanadi, lekin Telegram\'ga xabar ketmaydi. Pastda kamida bitta chatni yoqing.') . '</small></div></li>';
    echo '</ul>';
    echo '<form method="post" action="/admin/telegram.php" class="actions"' . ($otherHook ? ' data-confirm="Bot hozir boshqa manzilga ulangan (' . h($wh['url']) . '). Shu saytga o\'tkazilsinmi?"' : '') . '>'
        . hs_csrf_field() . '<input type="hidden" name="amal" value="ulash">'
        . '<button class="btn' . ($connected ? ' outline small' : '') . '" type="submit">' . ($connected ? 'Qayta ulash' : 'Botni ulash') . '</button></form>';
}
echo '</section>';

/* ---- chatlar ---- */
echo '<section class="card"><div class="card-head"><h2>Arizalar kimga boradi</h2><span class="muted">' . count($chats) . ' ta chat</span></div>';
if (!$chats) {
    echo '<p class="empty">Ro\'yxat bo\'sh. Pastdagi yo\'riqnoma bo\'yicha odam yoki guruh qo\'shing.</p>';
} else {
    echo '<div class="chat-list">';
    foreach ($chats as $c) {
        $cid = h($c['chat_id']);
        $on = (int) $c['leads'] === 1;
        $left = $c['status'] !== 'member';
        echo '<div class="chat-row' . ($on && !$left ? ' is-on' : '') . ($left ? ' is-left' : '') . '">';
        echo '<div class="chat-main"><div class="chat-title"><b>' . h($c['title'] !== '' ? $c['title'] : $c['chat_id']) . '</b> <span class="pill ' . ($c['type'] === 'private' ? 'st-qongiroq' : 'st-rad') . '">' . h(hs_tg_type_label($c['type'])) . '</span>';
        if ($left) {
            echo ' <span class="pill pill-err">bot chiqarilgan</span>';
        }
        if ((int) $c['admin'] === 1) {
            echo ' <span class="pill admin-pill">★ Boshqaruvchi</span>';
        }
        echo '</div><small>' . ($c['username'] !== '' ? '@' . h($c['username']) . ' · ' : '') . 'ID ' . $cid
            . ($c['last_sent_at'] ? ' · oxirgi xabar ' . h(date('d.m H:i', strtotime($c['last_sent_at']))) : '') . '</small>';
        if ($c['last_error'] !== '') {
            echo '<small class="closed-note">Xato: ' . h($c['last_error']) . '</small>';
        }
        echo '</div>';

        echo '<div class="chat-controls">';
        // Yoqish tugmasi: yashirin 0 + checkbox 1 — belgilanmasa 0 yuboriladi.
        echo '<form method="post" action="/admin/telegram.php" class="switch-form">' . hs_csrf_field()
            . '<input type="hidden" name="amal" value="arizalar"><input type="hidden" name="id" value="' . $cid . '"><input type="hidden" name="leads" value="0">'
            . '<label class="switch"><input type="checkbox" name="leads" value="1" data-autosubmit' . ($on ? ' checked' : '') . ($left ? ' disabled' : '') . '><span class="switch-ui" aria-hidden="true"></span><span>Arizalar</span></label>'
            . '<button class="btn outline small js-hide" type="submit">OK</button></form>';

        echo '<form method="post" action="/admin/telegram.php" class="branch-form">' . hs_csrf_field()
            . '<input type="hidden" name="amal" value="filial"><input type="hidden" name="id" value="' . $cid . '">'
            . '<select name="filial" data-autosubmit aria-label="' . h($c['title']) . ' — qaysi filial arizalari"><option value="">Barcha filiallar</option>';
        foreach ($branches as $k => $v) {
            echo '<option value="' . h($k) . '"' . ((string) $c['branch'] === (string) $k ? ' selected' : '') . '>📍 Filial rahbari: ' . h($v) . '</option>';
        }
        echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';

        echo '<div class="actions tight">';
        if ($c['type'] === 'private' && !$left) {
            $isAdm = (int) $c['admin'] === 1;
            echo '<form class="inline-form" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="admin"><input type="hidden" name="id" value="' . $cid . '"><input type="hidden" name="admin" value="' . ($isAdm ? '0' : '1') . '">'
                . '<button class="btn outline small" type="submit" title="' . ($isAdm ? 'Ruxsat so\'rovlari endi unga kelmaydi' : 'Ruxsat so\'rovlari Telegram\'da shu odamga keladi') . '">' . ($isAdm ? '☆ Boshqaruvchilikdan olish' : '★ Boshqaruvchi') . '</button></form>';
        }
        echo '<form class="inline-form" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sinov"><input type="hidden" name="id" value="' . $cid . '"><button class="btn outline small" type="submit"' . ($left ? ' disabled' : '') . '>Sinov xabari</button></form>';
        echo '<form class="inline-form" method="post" action="/admin/telegram.php" data-confirm="«' . h($c['title']) . '» ro\'yxatdan olib tashlansinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . $cid . '"><button class="btn danger small" type="submit">O\'chirish</button></form>';
        echo '</div></div></div>';
    }
    echo '</div>';
}
echo '</section>';

/* ---- eslatma va zaxira ---- */
$cronAt = (int) hs_setting('tasks_last_cron', '0');
$cronOk = $cronAt > time() - 20 * 60;
$cronCmd = 'php ' . str_replace('\\', '/', realpath(__DIR__ . '/cron/vazifalar.php'));
$checked = function ($k) {
    return hs_task_setting($k) === '1' ? ' checked' : '';
};
$hourOpts = function ($name, $cur, $from, $to) {
    $h = '<select id="t-' . $name . '" name="' . $name . '">';
    for ($i = $from; $i <= $to; $i++) {
        $h .= '<option value="' . $i . '"' . ((int) $cur === $i ? ' selected' : '') . '>' . sprintf('%02d:00', $i % 24) . '</option>';
    }
    return $h . '</select>';
};
echo '<form class="card" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="vazifalar">';
echo '<div class="part-head"><span class="part-ico">' . hs_icon('clock') . '</span><div><h2>Eslatma va zaxira</h2><p class="muted">Ariza javobsiz qolmasin, ma\'lumot yo\'qolmasin</p></div></div>';

echo '<label class="switch toggle-row"><input type="hidden" name="remind_on" value="0"><input type="checkbox" name="remind_on" value="1"' . $checked('remind_on') . '><span class="switch-ui" aria-hidden="true"></span><span>Javobsiz ariza eslatmasi</span></label>';
echo '<div class="grid grid-4">';
echo '<div><label for="t-remind_m1">1-eslatma (daqiqa)</label><input id="t-remind_m1" type="number" name="remind_m1" min="1" max="240" value="' . h(hs_task_setting('remind_m1')) . '"><p class="hint">Ariza kelgan chatlarga: "hali hech kim olmadi".</p></div>';
echo '<div><label for="t-remind_m2">2-eslatma (daqiqa)</label><input id="t-remind_m2" type="number" name="remind_m2" min="2" max="480" value="' . h(hs_task_setting('remind_m2')) . '"><p class="hint">Boshqaruvchiga: "hali javob yo\'q".</p></div>';
echo '<div><label for="t-work_from">Ish boshlanishi</label>' . $hourOpts('work_from', hs_task_setting('work_from'), 0, 23) . '</div>';
echo '<div><label for="t-work_to">Ish tugashi</label>' . $hourOpts('work_to', hs_task_setting('work_to'), 1, 24) . '<p class="hint">Kechasi eslatma yuborilmaydi; tungi ariza ertalab hisoblanadi.</p></div>';
echo '</div>';

echo '<label class="switch toggle-row"><input type="hidden" name="backup_on" value="0"><input type="checkbox" name="backup_on" value="1"' . $checked('backup_on') . '><span class="switch-ui" aria-hidden="true"></span><span>Har kuni arizalar zaxirasini yuborish</span></label>';
echo '<div class="grid grid-4"><div><label for="t-backup_hour">Soat</label>' . $hourOpts('backup_hour', hs_task_setting('backup_hour'), 0, 23) . '</div>';
echo '<div class="span-3"><p class="hint">Boshqaruvchiga Telegram\'da Excel\'da ochiladigan fayl keladi — hamma arizalar, holati va izohlari bilan. Parol va kalitlar faylga kirmaydi.'
    . (hs_setting('backup_last_at', '') !== '' ? ' Oxirgi zaxira: ' . h(date('d.m.Y H:i', strtotime(hs_setting('backup_last_at')))) . '.' : '') . '</p></div></div>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';

echo '<div class="card cron-card' . ($cronOk ? ' ok' : '') . '"><div class="part-head"><span class="part-ico' . ($cronOk ? '' : ' warn') . '">' . hs_icon($cronOk ? 'check' : 'clock') . '</span><div>';
if ($cronOk) {
    echo '<h2>Vaqt bo\'yicha vazifalar ishlayapti</h2><p class="muted">Oxirgi tekshiruv: ' . h(date('H:i', $cronAt)) . '. Eslatma va zaxira vaqtida ketadi.</p>';
} else {
    echo '<h2>Hostingda Cron sozlanmagan</h2><p class="muted">Hozir eslatmalar faqat panel ochilganda yoki botga kimdir yozganda tekshiriladi — tunda va jim paytlarda kechikadi.</p>';
}
echo '</div></div>';
if (!$cronOk) {
    echo '<ol class="steps"><li>ahost panelida <b>Cron Jobs</b> (Cron vazifalari) bo\'limini oching.</li>'
        . '<li>Vaqt: <b>har 5 daqiqada</b> (<span class="code">*/5 * * * *</span>).</li>'
        . '<li>Buyruq (to\'liq nusxalang):<br><span class="code">' . h($cronCmd) . '</span></li>'
        . '<li>Saqlang. 5 daqiqadan keyin bu karta yashil bo\'ladi.</li></ol>';
}
echo '<form method="post" action="/admin/telegram.php" class="actions">' . hs_csrf_field() . '<input type="hidden" name="amal" value="zaxira"><button class="btn outline small" type="submit">💾 Zaxirani hozir yuborish</button></form></div>';

/* ---- qanday qo'shiladi ---- */
$botLink = $botName !== '' ? '<a href="https://t.me/' . h($botName) . '" target="_blank" rel="noopener noreferrer">@' . h($botName) . '</a>' : 'botni';
echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Qanday qo\'shiladi</h2><ol class="steps">';
echo '<li><b>Odam (operator, menejer).</b> U Telegram\'da ' . $botLink . ' ni ochib <b>Start</b> ni bossin. Sizga Telegram\'da tugmali so\'rov keladi — "✅ Ruxsat" ni bossangiz bo\'ldi, saytga kirish shart emas.</li>';
echo '<li><b>Guruh (yopiq bo\'lsa ham).</b> Botni guruhga a\'zo qilib qo\'shing. Guruh shu yerda o\'zi chiqadi, sizga Telegram\'da xabar ham keladi.</li>';
echo '<li><b>Bot avvaldan turgan guruh.</b> Ro\'yxatda ko\'rinmasa, guruhga <span class="code">/start' . ($botName !== '' ? '@' . h($botName) : '') . '</span> deb yozing.</li>';
echo '<li><b>Filial guruhi.</b> "Barcha filiallar" o\'rniga filialni tanlang — guruhga faqat o\'sha filial arizalari boradi.</li>';
echo '<li><b>Botning o\'zidan boshqarish.</b> Botga <span class="code">/royxat</span> deb yozing — hamma chatlar tugma bo\'lib chiqadi, bosib yoqasiz yoki o\'chirasiz. Bu faqat boshqaruvchilarda ishlaydi.</li>';
echo '</ol><p class="hint">Yangi chat avtomatik yoqilmaydi: botni kimdir begona guruhga qo\'shsa ham, mijozlar raqami u yerga ketmaydi.</p></section>';

echo '<form class="card" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
echo '<h2>Qo\'lda qo\'shish</h2><p class="muted">Chat ID ni bilsangiz yoki guruh/kanal ochiq bo\'lsa.</p>';
echo '<label for="chat">Chat ID yoki @nom</label><input id="chat" type="text" name="chat" required maxlength="80" placeholder="-1001234567890 yoki @guruh_nomi" autocomplete="off">';
echo '<div class="actions"><button class="btn" type="submit">Qo\'shish</button></div></form>';
echo '</div>';

hs_page_end();
