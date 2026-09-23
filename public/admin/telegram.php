<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/tgchats.php';
require_once __DIR__ . '/_lib/tasks.php';
require_once __DIR__ . '/_lib/mijozbot.php';

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
    } elseif ($action === 'rol' && $row) {
        $r = hs_post('rol');
        if (!in_array($r, array('', 'mijozlar', 'katalog'), true) || ($r !== '' && $row['type'] === 'private')) {
            hs_flash("Noto'g'ri tanlov.", 'err');
        } else {
            hs_mb_set_role($id, $r);
            hs_audit($user['login'], 'telegram: chat roli', $row['title'] . ': ' . ($r !== '' ? $r : 'oddiy'));
            hs_flash($r === 'mijozlar' ? "«{$row['title']}» — mijozlar guruhi: bot savollarga javob beradi, arizalar bu yerga yuborilmaydi."
                : ($r === 'katalog' ? "«{$row['title']}» — mahsulot kanali: postlari katalogga tushadi." : "«{$row['title']}» — oddiy chat."));
        }
    } elseif ($action === 'mb_sozlama') {
        hs_set_setting('mb_on', hs_post('mb_on') === '1' ? '1' : '0');
        hs_set_setting('mb_mode', hs_post('mb_mode') === 'faol' ? 'faol' : 'kuzatish');
        hs_set_setting('mb_notify', hs_post('mb_notify') === 'hammasi' ? 'hammasi' : 'kerak');
        hs_set_setting('mb_remind_m', (string) max(5, min(240, (int) hs_post('mb_remind_m'))));
        hs_set_setting('mb_provider', hs_post('mb_provider') === 'gemini' ? 'gemini' : 'claude');
        $gm = hs_post('mb_gemini_model');
        hs_set_setting('mb_gemini_model', in_array($gm, array('gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.1-flash-lite'), true) ? $gm : HS_MB_GEMINI_DEFAULT_MODEL);
        $model = hs_post('mb_model');
        hs_set_setting('mb_model', in_array($model, array('claude-opus-5', 'claude-sonnet-5', 'claude-haiku-4-5'), true) ? $model : HS_MB_DEFAULT_MODEL);
        foreach (array('mb_group', 'mb_channel') as $k) {
            $v = ltrim(preg_replace('#^(https?://)?t\.me/#i', '', hs_post($k)), '@');
            if (preg_match('/^[A-Za-z0-9_]{4,64}$/', $v)) {
                hs_set_setting($k, $v);
            }
        }
        hs_audit($user['login'], 'mijozlar guruhi sozlamasi');
        hs_flash('Saqlandi.');
    } elseif ($action === 'mb_kalit') {
        $key = trim(hs_post('mb_key'));
        // Qaysi xizmatniki ekani kalitning o'zidan ko'rinadi: Anthropic "sk-ant-",
        // Google AI Studio "AIza" bilan boshlanadi. Shu sababli bitta maydon yetadi.
        if (preg_match('/^sk-ant-[A-Za-z0-9_\-]{20,200}$/', $key)) {
            hs_set_setting('mb_claude_key', $key);
            hs_audit($user['login'], 'mijozlar guruhi: Claude kaliti saqlandi', '…' . substr($key, -4));
            hs_flash('Claude kaliti saqlandi. "Sinab ko\'rish" bilan tekshiring.');
        } elseif (preg_match('/^AIza[A-Za-z0-9_\-]{20,100}$/', $key)) {
            hs_set_setting('mb_gemini_key', $key);
            hs_audit($user['login'], 'mijozlar guruhi: Gemini kaliti saqlandi', '…' . substr($key, -4));
            hs_flash('Gemini kaliti saqlandi. Provayderni "Gemini" ga o\'tkazing va "Sinab ko\'rish" bilan tekshiring.');
        } else {
            hs_flash("Kalit formati noto'g'ri. Claude kaliti sk-ant-… , Google AI Studio kaliti AIza… bilan boshlanadi.", 'err');
        }
    } elseif ($action === 'mb_kalit_ochir') {
        $qaysi = hs_mb_provider() === 'gemini' ? 'mb_gemini_key' : 'mb_claude_key';
        hs_db()->prepare('DELETE FROM settings WHERE key = ?')->execute(array($qaysi));
        hs_audit($user['login'], "mijozlar guruhi: " . (hs_mb_provider() === 'gemini' ? 'Gemini' : 'Claude') . " kaliti o'chirildi");
        hs_flash("Kalit o'chirildi. Bot endi oddiy so'z qidiruvi bilan ishlaydi va savollarni operatorga yuboradi.");
    } elseif ($action === 'mb_katalog') {
        list($n, $err) = hs_mb_backfill(hs_mb_setting('channel'), 15);
        hs_audit($user['login'], 'mijozlar guruhi: katalog yangilandi', $err !== '' ? $err : "{$n} ta post");
        hs_flash($err !== '' ? $err : "Kanaldan {$n} ta post katalogga yozildi. Jami: " . hs_mb_catalog_count() . ' ta.', $err !== '' ? 'err' : 'ok');
    } elseif ($action === 'mb_sinov') {
        $savol = mb_substr(hs_post('savol'), 0, 500);
        if ($savol === '') {
            hs_flash('Savol yozing.', 'err');
        } else {
            $rows = hs_mb_catalog_rows();
            list($d, $err) = hs_mb_ask_ai($savol, '', $rows);
            if ($d === null) {
                hs_flash('Claude ishlamadi: ' . $err, 'err');
            } else {
                $byN = array();
                foreach ($rows as $r) {
                    $byN[(int) $r['n']] = $r;
                }
                $found = array();
                foreach ((array) $d['post_ids'] as $n) {
                    if (isset($byN[(int) $n])) {
                        $found[] = mb_substr(preg_split('/\R/u', trim($byN[(int) $n]['text']))[0], 0, 60) . ' — ' . $byN[(int) $n]['url'];
                    }
                }
                hs_flash(($d['is_question'] ? 'Savol' : 'Savol emas (bot jim turadi)') . ' · ' . $d['kind'] . ($d['needs_operator'] ? ' · operatorga yuboriladi' : '')
                    . "\nJavob: " . $d['reply'] . ($found ? "\nPostlar: " . implode(' | ', $found) : "\nMos post topilmadi."));
            }
        }
    } elseif ($action === 'arizalar' && $row) {
        $on = hs_post('leads') === '1' ? 1 : 0;
        if ($on && $row['role'] !== '') {
            hs_flash("«{$row['title']}» — " . ($row['role'] === 'mijozlar' ? 'mijozlar guruhi' : 'mahsulot kanali') . ": unga arizalar (mijozlar raqami) yuborilmaydi.", 'err');
            hs_redirect('/admin/telegram.php');
        }
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
// Eski ulanishda keyin qo'shilgan voqea turlari yo'q bo'lsa — qayta ulaymiz (tugmalar, kanal postlarini tahrirlash).
if ($connected && (empty($wh['allowed_updates']) || array_diff(array('callback_query', 'edited_channel_post'), $wh['allowed_updates']))) {
    list($upOk) = hs_tg_connect_webhook();
    hs_db()->prepare('DELETE FROM cache WHERE key = ?')->execute(array('tg:info'));
    if ($upOk) {
        hs_flash("Bot ulanishi yangilandi: tugmalar va mahsulot kanali postlari endi to'liq keladi.");
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

/* ---- mijozlar guruhi yordamchisi ---- */
$mbGroup = null;
$mbChannel = null;
foreach ($chats as $c) {
    if ($c['role'] === 'mijozlar' && $c['status'] === 'member' && !$mbGroup) {
        $mbGroup = $c;
    } elseif ($c['role'] === 'katalog' && $c['status'] === 'member' && !$mbChannel) {
        $mbChannel = $c;
    }
}
$mbAdmin = null;
if ($mbGroup && $info['me']) {
    $mbAdmin = hs_cache_get('mb:botstatus:' . $mbGroup['chat_id']);
    if (!is_string($mbAdmin)) {
        list($okM, $mem) = hs_tg_api('getChatMember', array('chat_id' => $mbGroup['chat_id'], 'user_id' => $info['me']['id']));
        $mbAdmin = $okM && isset($mem['status']) ? (string) $mem['status'] : '';
        hs_cache_set('mb:botstatus:' . $mbGroup['chat_id'], $mbAdmin, 300);
    }
}
$mbKey = hs_mb_key();
$mbCount = hs_mb_catalog_count();
$mbStats = hs_db()->query("SELECT COUNT(*) AS jami, SUM(status = 'javob') AS javob, SUM(status = 'kuzatish') AS kuzatish, SUM(needs_operator = 1) AS operator, SUM(answered_by <> '') AS xodim, SUM(lead_id IS NOT NULL) AS ariza
    FROM mb_questions WHERE created_at > '" . date('Y-m-d H:i:s', time() - 7 * 86400) . "' AND status <> 'tashlandi'")->fetch();
$okIco = function ($ok) {
    return '<span class="state ' . ($ok ? 'on' : 'off') . '">' . hs_icon($ok ? 'check' : 'clock') . '</span>';
};
echo '<section class="card"><div class="part-head"><span class="part-ico">' . hs_icon('users') . '</span><div><h2>Mijozlar guruhi yordamchisi</h2>'
    . '<p class="muted">Guruhdagi "X bormi? narxi?" savollariga bot kanal postlari bilan javob beradi, aniq javob kerak bo\'lsa — xodimlarga yuboradi</p></div></div>';
echo '<ul class="checklist">';
$groupAt = '@' . h(hs_mb_setting('group'));
echo '<li>' . $okIco($mbGroup && ($mbAdmin === 'administrator' || $mbAdmin === 'creator')) . '<div><b>'
    . ($mbGroup ? 'Guruh: ' . h($mbGroup['title']) . ($mbAdmin === 'administrator' || $mbAdmin === 'creator' ? ' — bot admin' : ' — bot admin EMAS') : 'Bot ' . $groupAt . ' guruhida hali yo\'q') . '</b><small>'
    . ($mbGroup ? ($mbAdmin === 'administrator' || $mbAdmin === 'creator' ? 'Bot barcha xabarlarni ko\'radi. Arizalar (mijozlar raqami) bu guruhga yuborilmaydi.' : 'Barcha xabarlarni ko\'rishi uchun botni guruhda admin qiling (qo\'shimcha huquq shart emas).')
        : 'Botni ' . $groupAt . ' guruhiga qo\'shing va admin qiling. Guruh o\'zi "mijozlar guruhi" deb belgilanadi — unga salom ham, ariza ham yuborilmaydi.') . '</small></div></li>';
$faol = hs_mb_setting('mode') === 'faol';
echo '<li>' . $okIco(true) . '<div><b>' . (hs_mb_setting('on') !== '1' ? "Rejim: o'chirilgan" : ($faol ? 'Rejim: ✅ Faol — bot guruhga javob yozadi' : "Rejim: 🧪 Kuzatish — bot guruhga yozmaydi")) . '</b><small>'
    . ($faol ? "Aniq javob kerak bo'lsa, savol xodimlarga ham keladi." : "Har bir savol va botning javob loyihasi xodimlar chatiga keladi. Javoblar to'g'ri ekaniga ishonch hosil qilgach, pastda \"Faol\" ni tanlang.") . '</small></div></li>';
echo '<li>' . $okIco($mbCount > 0) . '<div><b>Katalog: ' . $mbCount . ' ta post</b><small>'
    . 'Manba: @' . h(hs_mb_setting('channel')) . ($mbChannel ? ' (bot kanalda — yangi postlar o\'zi tushadi)' : ' (bot kanalda emas — postlar har 6 soatda kanalning ochiq sahifasidan yig\'iladi; darhol tushishi uchun botni kanalga admin qiling)')
    . ' va guruhdagi xodimlarning narxli postlari.' . (hs_setting('mb_backfill_at', '') !== '' ? ' Oxirgi yig\'ish: ' . h(date('d.m H:i', strtotime(hs_setting('mb_backfill_at')))) . '.' : '') . '</small></div></li>';
$gemini = hs_mb_provider() === 'gemini';
$aiKey = $gemini ? hs_mb_gemini_key() : $mbKey;
$aiNom = $gemini ? 'Gemini' : 'Claude';
$aiModel = $gemini ? hs_mb_gemini_model() : hs_mb_setting('model');
echo '<li>' . $okIco($aiKey !== '') . '<div><b>' . ($aiKey !== '' ? h($aiNom) . ' ulangan (…' . h(substr($aiKey, -4)) . ') · ' . h($aiModel) : h($aiNom) . ' kaliti kiritilmagan') . '</b><small>'
    . ($aiKey !== ''
        ? 'Savollarni lotin/kirill, xato yozuvda ham tushunadi.' . ($gemini ? ' Bepul tarifda so\'rovlar soni cheklangan va Google matnlarni o\'z mahsulotlarini yaxshilash uchun ishlatadi — shuning uchun telefon raqamlari yuborishdan oldin o\'chiriladi.' : '')
        : 'Kalitsiz bot oddiy so\'z qidiruvi bilan ishlaydi va har bir savolni operatorga yuboradi. Kalit: ' . ($gemini ? 'aistudio.google.com → Get API key' : 'console.anthropic.com → API Keys') . '.') . '</small></div></li>';
echo '<li>' . $okIco(true) . '<div><b>Oxirgi 7 kun: ' . (int) $mbStats['jami'] . ' ta savol</b><small>'
    . ((int) $mbStats['kuzatish'] ? 'Kuzatishda (guruhga yozilmadi): ' . (int) $mbStats['kuzatish'] . ' · ' : '')
    . 'Bot javob berdi: ' . (int) $mbStats['javob'] . ' · operator kerak: ' . (int) $mbStats['operator'] . ' · xodim javob berdi: ' . (int) $mbStats['xodim'] . ' · raqam qoldirdi (ariza): ' . (int) $mbStats['ariza'] . '</small></div></li>';
echo '</ul>';

echo '<form method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_sozlama">';
echo '<label class="switch toggle-row"><input type="hidden" name="mb_on" value="0"><input type="checkbox" name="mb_on" value="1"' . (hs_mb_setting('on') === '1' ? ' checked' : '') . '><span class="switch-ui" aria-hidden="true"></span><span>Guruhdagi savollarga javob berish</span></label>';
echo '<div class="grid grid-4">';
echo '<div class="span-2"><label for="mb_mode">Rejim</label><select id="mb_mode" name="mb_mode"><option value="kuzatish"' . (hs_mb_setting('mode') !== 'faol' ? ' selected' : '') . '>🧪 Kuzatish — guruhga yozmaydi, javob loyihasini xodimlarga ko\'rsatadi</option><option value="faol"' . (hs_mb_setting('mode') === 'faol' ? ' selected' : '') . '>✅ Faol — guruhdagi savolga o\'zi javob beradi</option></select><p class="hint">Avval bir necha kun kuzating: bot javoblari xodimlar chatiga keladi. To\'g\'ri bo\'lsa — Faol.</p></div>';
echo '<div><label for="mb_group">Mijozlar guruhi</label><input id="mb_group" type="text" name="mb_group" maxlength="80" value="@' . h(hs_mb_setting('group')) . '"></div>';
echo '<div><label for="mb_channel">Mahsulot kanali</label><input id="mb_channel" type="text" name="mb_channel" maxlength="80" value="@' . h(hs_mb_setting('channel')) . '"></div>';
echo '<div><label for="mb_notify">Xodimlarga yuborish</label><select id="mb_notify" name="mb_notify"><option value="kerak"' . (hs_mb_setting('notify') === 'kerak' ? ' selected' : '') . '>Operator kerak bo\'lganda</option><option value="hammasi"' . (hs_mb_setting('notify') === 'hammasi' ? ' selected' : '') . '>Har bir savol</option></select><p class="hint">Arizalar keladigan "barcha filiallar" chatlariga.</p></div>';
echo '<div><label for="mb_remind_m">Javobsiz eslatma (daqiqa)</label><input id="mb_remind_m" type="number" name="mb_remind_m" min="5" max="240" value="' . h(hs_mb_setting('remind_m')) . '"></div>';
echo '<div><label for="mb_provider">AI xizmati</label><select id="mb_provider" name="mb_provider">'
    . '<option value="claude"' . (!$gemini ? ' selected' : '') . '>Claude (Anthropic) — pullik</option>'
    . '<option value="gemini"' . ($gemini ? ' selected' : '') . '>Gemini (Google AI Studio) — bepul tarifi bor</option>'
    . '</select><p class="hint">Har ikkalasining kaliti alohida saqlanadi, almashtirish bir bosishda.</p></div>';
echo '<div><label for="mb_gemini_model">Gemini modeli</label><select id="mb_gemini_model" name="mb_gemini_model">';
foreach (array('gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite — eng tez, eng arzon', 'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite', 'gemini-3.5-flash' => 'Gemini 3.5 Flash — aniqroq') as $gv => $gl) {
    echo '<option value="' . h($gv) . '"' . (hs_mb_gemini_model() === $gv ? ' selected' : '') . '>' . h($gl) . '</option>';
}
echo '</select><p class="hint">Faqat "AI xizmati: Gemini" bo\'lganda ishlatiladi.</p></div>';
echo '<div class="span-2"><label for="mb_model">Claude modeli</label><select id="mb_model" name="mb_model">';
foreach (array('claude-opus-5' => 'Claude Opus 5 — eng aniq (tavsiya)', 'claude-sonnet-5' => 'Claude Sonnet 5 — arzonroq', 'claude-haiku-4-5' => 'Claude Haiku 4.5 — eng arzon, tez') as $mv => $ml) {
    echo '<option value="' . h($mv) . '"' . (hs_mb_setting('model') === $mv ? ' selected' : '') . '>' . h($ml) . '</option>';
}
echo '</select><p class="hint">Bitta savol taxminan: Opus 5 — 2–7 sent, Sonnet 5 — 1–3 sent, Haiku — ~1 sent (katalog hajmiga bog\'liq). Aniq xarajat — console.anthropic.com da.</p></div>';
echo '</div><div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';

echo '<div class="grid grid-2">';
echo '<form method="post" action="/admin/telegram.php" autocomplete="off">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_kalit">'
    . '<label for="mb_key">API kaliti' . ($aiKey !== '' ? ' (almashtirish)' : '') . '</label><input id="mb_key" type="password" name="mb_key" required maxlength="260" autocomplete="off" spellcheck="false" placeholder="sk-ant-… yoki AIza…">'
    . '<p class="hint">Qaysi xizmatniki ekani kalitning o\'zidan aniqlanadi: sk-ant-… — Claude, AIza… — Google AI Studio.</p>'
    . '<p class="hint">Faqat serverdagi bazada saqlanadi, sahifada qayta ko\'rsatilmaydi.</p><div class="actions"><button class="btn outline small" type="submit">Kalitni saqlash</button></div></form>';
if ($mbKey !== '') {
    echo '<form method="post" action="/admin/telegram.php" class="actions" data-confirm="Claude kaliti o\'chirilsinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_kalit_ochir"><button class="btn danger small" type="submit">Kalitni o\'chirish</button></form>';
}
echo '<form method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_sinov">'
    . '<label for="savol">Sinab ko\'rish (guruhga hech narsa yuborilmaydi)</label><input id="savol" type="text" name="savol" maxlength="500" placeholder="masalan: Assalomu alaykum planshetlar ham bormi">'
    . '<div class="actions"><button class="btn outline small" type="submit"' . ($mbKey === '' ? ' disabled' : '') . '>Sinash</button></div></form>';
echo '</div>';
echo '<form method="post" action="/admin/telegram.php" class="actions">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_katalog"><button class="btn outline small" type="submit">🔄 Katalogni kanaldan yangilash</button></form>';

$recent = hs_db()->query("SELECT * FROM mb_questions WHERE status <> 'tashlandi' ORDER BY id DESC LIMIT 15")->fetchAll();
if ($recent) {
    echo '<h3>Oxirgi savollar</h3><div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>Mijoz</th><th>Savol</th><th>Bot javobi</th><th>Holat</th></tr></thead><tbody>';
    foreach ($recent as $q) {
        if ($q['answered_by'] !== '') {
            $state = '<span class="pill st-sotildi">✅ ' . h($q['answered_by']) . '</span>';
        } elseif ((int) $q['lead_id'] > 0) {
            $state = '<span class="pill st-qongiroq">📞 ariza #' . (int) $q['lead_id'] . '</span>';
        } elseif ($q['status'] === 'xato') {
            $state = '<span class="pill pill-err">xato</span>';
        } elseif ($q['status'] === 'kuzatish') {
            $state = '<span class="pill st-yangi">🧪 kuzatishda</span>';
        } elseif ((int) $q['needs_operator']) {
            $state = '<span class="pill st-yangi">operator kerak</span>';
        } else {
            $state = '<span class="pill st-sotildi">bot javob berdi</span>';
        }
        echo '<tr><td>' . h(date('d.m H:i', strtotime($q['created_at']))) . '</td><td>' . h($q['user_name']) . '</td><td>' . h(mb_substr($q['text'], 0, 120)) . '</td><td><small>' . h(mb_substr($q['reply'], 0, 140)) . ($q['post_ids'] !== '' ? ' · ' . count(explode(',', $q['post_ids'])) . ' post' : '') . ($q['error'] !== '' ? '<br><span class="closed-note">' . h(mb_substr($q['error'], 0, 120)) . '</span>' : '') . '</small></td><td>' . $state . '</td></tr>';
    }
    echo '</tbody></table></div>';
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
        if ($c['role'] !== '') {
            echo ' <span class="pill st-yangi">' . ($c['role'] === 'mijozlar' ? '👥 Mijozlar guruhi' : '📣 Mahsulot kanali') . '</span>';
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
            . '<label class="switch"' . ($c['role'] !== '' ? ' title="Mijozlar guruhi / mahsulot kanaliga arizalar yuborilmaydi"' : '') . '><input type="checkbox" name="leads" value="1" data-autosubmit' . ($on ? ' checked' : '') . ($left || $c['role'] !== '' ? ' disabled' : '') . '><span class="switch-ui" aria-hidden="true"></span><span>Arizalar</span></label>'
            . '<button class="btn outline small js-hide" type="submit">OK</button></form>';

        if ($c['type'] !== 'private') {
            echo '<form method="post" action="/admin/telegram.php" class="branch-form">' . hs_csrf_field()
                . '<input type="hidden" name="amal" value="rol"><input type="hidden" name="id" value="' . $cid . '">'
                . '<select name="rol" data-autosubmit aria-label="' . h($c['title']) . ' — roli">';
            foreach (array('' => 'Xodimlar chati (arizalar mumkin)', 'mijozlar' => '👥 Mijozlar guruhi — bot javob beradi', 'katalog' => '📣 Mahsulot kanali — katalog') as $rv => $rl) {
                echo '<option value="' . h($rv) . '"' . ($c['role'] === $rv ? ' selected' : '') . '>' . h($rl) . '</option>';
            }
            echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';
        }

        // Mijozlar guruhi / mahsulot kanaliga ariza bormaydi — filial tanlovi ham kerak emas.
        if ($c['role'] === '') {
            echo '<form method="post" action="/admin/telegram.php" class="branch-form">' . hs_csrf_field()
                . '<input type="hidden" name="amal" value="filial"><input type="hidden" name="id" value="' . $cid . '">'
                . '<select name="filial" data-autosubmit aria-label="' . h($c['title']) . ' — qaysi filial arizalari"><option value="">Barcha filiallar</option>';
            foreach ($branches as $k => $v) {
                echo '<option value="' . h($k) . '"' . ((string) $c['branch'] === (string) $k ? ' selected' : '') . '>📍 Filial rahbari: ' . h($v) . '</option>';
            }
            echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';
        }

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
echo '<div><label for="t-remind_m2">2-eslatma (daqiqa)</label><input id="t-remind_m2" type="number" name="remind_m2" min="2" max="480" value="' . h(hs_task_setting('remind_m2')) . '"><p class="hint">Ariza turgan guruhlarga va boshqaruvchiga: "hali javob yo\'q" (kimdir olgan bo\'lsa ham).</p></div>';
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
