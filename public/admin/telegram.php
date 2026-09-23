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
    // Saqlagandan keyin o'sha tabga qaytamiz.
    $bolimi = array('mb_sozlama' => 'mijozlar', 'mb_sinov' => 'mijozlar', 'mb_katalog' => 'mijozlar',
        'ai_sozlama' => 'ai', 'mb_kalit' => 'ai', 'mb_kalit_ochir' => 'ai', 'vazifalar' => 'eslatma', 'zaxira' => 'eslatma');
    $qaytish = '/admin/telegram.php' . (isset($bolimi[$action]) ? '?bolim=' . $bolimi[$action] : '');
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
        hs_set_setting('mb_wait_s', (string) max(0, min(180, (int) hs_post('mb_wait_s'))));
        foreach (array('mb_group', 'mb_channel') as $k) {
            $v = ltrim(preg_replace('#^(https?://)?t\.me/#i', '', hs_post($k)), '@');
            if (preg_match('/^[A-Za-z0-9_]{4,64}$/', $v)) {
                hs_set_setting($k, $v);
            }
        }
        hs_audit($user['login'], 'mijozlar guruhi sozlamasi');
        hs_flash('Saqlandi.');
    } elseif ($action === 'ai_sozlama') {
        hs_set_setting('mb_provider', hs_post('mb_provider') === 'gemini' ? 'gemini' : 'claude');
        $gm = hs_post('mb_gemini_model');
        hs_set_setting('mb_gemini_model', in_array($gm, array('gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.1-flash-lite'), true) ? $gm : HS_MB_GEMINI_DEFAULT_MODEL);
        $model = hs_post('mb_model');
        hs_set_setting('mb_model', in_array($model, array('claude-opus-5', 'claude-sonnet-5', 'claude-haiku-4-5'), true) ? $model : HS_MB_DEFAULT_MODEL);
        hs_audit($user['login'], 'AI sozlamasi', hs_mb_provider());
        hs_flash('Saqlandi.');
    } elseif ($action === 'mb_kalit') {
        $key = trim(hs_post('mb_key'));
        // Qaysi xizmatniki ekani kalitning o'zidan ko'rinadi: Anthropic "sk-ant-",
        // Google AI Studio "AIza" bilan boshlanadi. Shu sababli bitta maydon yetadi.
        if (preg_match('/^sk-ant-[A-Za-z0-9_\-]{20,200}$/', $key)) {
            hs_set_setting('mb_claude_key', $key);
            hs_audit($user['login'], 'mijozlar guruhi: Claude kaliti saqlandi', '…' . substr($key, -4));
            // Faol xizmatning kaliti yo'q bo'lsa — shu kalitning xizmatiga o'tamiz, aks holda
            // kalit saqlangan-u, bot ishlamay turaverardi (shunday bo'lgan edi).
            if (hs_mb_provider() === 'gemini' && hs_mb_gemini_key() === '') {
                hs_set_setting('mb_provider', 'claude');
            }
            hs_flash('Claude kaliti saqlandi' . (hs_mb_provider() === 'claude' ? ' va ishlatilyapti' : " — hozir Gemini ishlatilyapti, almashtirish pastda") . '.');
        // Google AI Studio kalitlari ikki ko'rinishda: eskisi "AIza…",
        // yangisi "AQ.…". Ikkalasi ham qabul qilinadi.
        } elseif (preg_match('/^(AIza[A-Za-z0-9_\-]{20,100}|AQ\.[A-Za-z0-9_.\-]{20,150})$/', $key)) {
            hs_set_setting('mb_gemini_key', $key);
            hs_audit($user['login'], 'mijozlar guruhi: Gemini kaliti saqlandi', '…' . substr($key, -4));
            if (hs_mb_provider() === 'claude' && hs_mb_key() === '') {
                hs_set_setting('mb_provider', 'gemini');
            }
            hs_flash('Gemini kaliti saqlandi' . (hs_mb_provider() === 'gemini' ? ' va ishlatilyapti' : " — hozir Claude ishlatilyapti, almashtirish pastda") . '.');
        } else {
            hs_flash("Kalit formati noto'g'ri. Claude kaliti sk-ant-… , Google AI Studio kaliti AIza… yoki AQ.… bilan boshlanadi.", 'err');
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
                hs_flash((hs_mb_provider() === 'gemini' ? 'Gemini' : 'Claude') . ' ishlamadi: ' . $err, 'err');
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
            hs_redirect($qaytish);
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
    } elseif ($action === 'sinov' && $row && $row['role'] !== '') {
        hs_flash("«{$row['title']}» — " . ($row['role'] === 'mijozlar' ? 'mijozlar guruhi' : 'mahsulot kanali')
            . ": u yerni mijozlar o'qiydi, sinov xabari yuborilmaydi.", 'err');
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
    hs_redirect($qaytish);
}

/*
 * Bo'limlar (tablar). Ilgari arizalar, mijozlar boti, AI kaliti va eslatmalar
 * bitta uzun sahifada edi — nima qayerdaligi chalkashib ketardi. Endi har biri
 * alohida tab va o'z faylida (_lib/telegram/). Har tab faqat o'ziga keragini
 * yuklaydi: Telegram'dan bot holatini so'rash faqat kerakli tablarda.
 */
$bolimlar = array(
    'arizalar' => array('Arizalar', 'inbox'),
    'mijozlar' => array('Mijozlar boti', 'users'),
    'ai' => array('AI kaliti', 'settings'),
    'eslatma' => array('Eslatma va zaxira', 'clock'),
);
$bolim = hs_get('bolim', 'arizalar');
if (!isset($bolimlar[$bolim])) {
    $bolim = 'arizalar';
}

if ($bolim === 'arizalar' || $bolim === 'mijozlar') {
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

/* Tab yonidagi qisqa holat — Telegram'ga so'rov yubormasdan, bazadan. */
$aiBor = hs_mb_provider() === 'gemini' ? hs_mb_gemini_key() !== '' : hs_mb_key() !== '';
$belgi = array(
    'arizalar' => $active ? $active . ' ta chat' : 'hech kimga',
    'mijozlar' => hs_mb_setting('on') !== '1' ? "o'chiq" : (hs_mb_setting('mode') === 'faol' ? 'faol' : 'kuzatish'),
    'ai' => $aiBor ? (hs_mb_provider() === 'gemini' ? 'Gemini' : 'Claude') : "kalit yo'q",
    'eslatma' => (int) hs_setting('tasks_last_cron', '0') > time() - 20 * 60 ? 'ishlayapti' : "cron yo'q",
);
$ogoh = array('arizalar' => !$active, 'mijozlar' => false, 'ai' => !$aiBor, 'eslatma' => (int) hs_setting('tasks_last_cron', '0') <= time() - 20 * 60);
echo '<nav class="tabs" aria-label="Telegram bo&#39;limlari">';
foreach ($bolimlar as $k => $b) {
    echo '<a class="tab' . ($k === $bolim ? ' active' : '') . '" href="/admin/telegram.php' . ($k === 'arizalar' ? '' : '?bolim=' . $k) . '"'
        . ($k === $bolim ? ' aria-current="page"' : '') . '>' . hs_icon($b[1]) . '<span>' . h($b[0]) . '</span>'
        . '<small class="tab-note' . ($ogoh[$k] ? ' warn' : '') . '">' . h($belgi[$k]) . '</small></a>';
}
echo '</nav>';

$okIco = function ($ok) {
    return '<span class="state ' . ($ok ? 'on' : 'off') . '">' . hs_icon($ok ? 'check' : 'clock') . '</span>';
};
require __DIR__ . '/_lib/telegram/' . $bolim . '.php';

hs_page_end();
