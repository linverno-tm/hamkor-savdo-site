<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/tgchats.php';

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
            echo '<option value="' . h($k) . '"' . ((string) $c['branch'] === (string) $k ? ' selected' : '') . '>Faqat: ' . h($v) . '</option>';
        }
        echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';

        echo '<div class="actions tight">';
        echo '<form class="inline-form" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sinov"><input type="hidden" name="id" value="' . $cid . '"><button class="btn outline small" type="submit"' . ($left ? ' disabled' : '') . '>Sinov xabari</button></form>';
        echo '<form class="inline-form" method="post" action="/admin/telegram.php" data-confirm="«' . h($c['title']) . '» ro\'yxatdan olib tashlansinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . $cid . '"><button class="btn danger small" type="submit">O\'chirish</button></form>';
        echo '</div></div></div>';
    }
    echo '</div>';
}
echo '</section>';

/* ---- qanday qo'shiladi ---- */
$botLink = $botName !== '' ? '<a href="https://t.me/' . h($botName) . '" target="_blank" rel="noopener noreferrer">@' . h($botName) . '</a>' : 'botni';
echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Qanday qo\'shiladi</h2><ol class="steps">';
echo '<li><b>Odam (operator, menejer).</b> U Telegram\'da ' . $botLink . ' ni ochib <b>Start</b> ni bossin. Ro\'yxatda paydo bo\'ladi — keyin "Arizalar" ni yoqasiz.</li>';
echo '<li><b>Guruh (yopiq bo\'lsa ham).</b> Botni guruhga a\'zo qilib qo\'shing. Guruh shu yerda o\'zi chiqadi, sizga Telegram\'da xabar ham keladi.</li>';
echo '<li><b>Bot avvaldan turgan guruh.</b> Ro\'yxatda ko\'rinmasa, guruhga <span class="code">/start' . ($botName !== '' ? '@' . h($botName) : '') . '</span> deb yozing.</li>';
echo '<li><b>Filial guruhi.</b> "Barcha filiallar" o\'rniga filialni tanlang — guruhga faqat o\'sha filial arizalari boradi.</li>';
echo '</ol><p class="hint">Yangi chat avtomatik yoqilmaydi: botni kimdir begona guruhga qo\'shsa ham, mijozlar raqami u yerga ketmaydi.</p></section>';

echo '<form class="card" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
echo '<h2>Qo\'lda qo\'shish</h2><p class="muted">Chat ID ni bilsangiz yoki guruh/kanal ochiq bo\'lsa.</p>';
echo '<label for="chat">Chat ID yoki @nom</label><input id="chat" type="text" name="chat" required maxlength="80" placeholder="-1001234567890 yoki @guruh_nomi" autocomplete="off">';
echo '<div class="actions"><button class="btn" type="submit">Qo\'shish</button></div></form>';
echo '</div>';

hs_page_end();
