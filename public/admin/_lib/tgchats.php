<?php
/**
 * Telegram: bot turgan chatlar va arizalar kimga borishi.
 *
 * Telegram'da "bot qaysi guruhlarda turibdi" degan so'rov YO'Q. Bot buni
 * faqat voqea kelganda biladi: guruhga qo'shildi, kimdir /start yozdi.
 * Shuning uchun api/telegram.php (webhook) har bir shunday voqeani tg_chats
 * jadvaliga yozadi, panel esa shu ro'yxatdan kimga ariza borishini tanlaydi.
 *
 * Yangi chat avtomatik YOQILMAYDI: botni istalgan odam o'z guruhiga qo'sha
 * oladi, va u darhol mijozlar telefonini ko'ra boshlamasligi kerak.
 */

/** Telegram Bot API chaqiruvi. Qaytaradi: [ok, natija yoki xato matni, javob massivi]. */
function hs_tg_api($method, $params = array())
{
    $token = (string) hs_config('token', '');
    if ($token === '') {
        return array(false, 'Bot kaliti (token) secrets.php da yo\'q.', array());
    }
    if (getenv('HS_DRY_RUN') === '1') {
        // Faqat mahalliy sinov: hech narsa yuborilmaydi, javob o'xshatiladi.
        @file_put_contents(hs_data_dir() . '/telegram.log', '[' . hs_now() . "] {$method} " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        $fake = array(
            'getMe' => array('id' => 1, 'is_bot' => true, 'username' => 'sinov_bot'),
            'getWebhookInfo' => array('url' => hs_setting('tg_webhook_secret', '') !== '' ? hs_tg_webhook_url() : ''),
            'getChat' => array('id' => isset($params['chat_id']) ? $params['chat_id'] : 0, 'type' => 'supergroup', 'title' => 'Sinov guruhi'),
        );
        return array(true, isset($fake[$method]) ? $fake[$method] : array(), array('ok' => true));
    }
    list($code, $body) = hs_http(
        'POST',
        'https://api.telegram.org/bot' . $token . '/' . $method,
        array('Content-Type: application/x-www-form-urlencoded'),
        http_build_query($params),
        15
    );
    if ($code === 0) {
        return array(false, "Telegram'ga ulanib bo'lmadi.", array());
    }
    $j = json_decode((string) $body, true);
    if (!is_array($j)) {
        return array(false, "Telegram tushunarsiz javob qaytardi (HTTP {$code}).", array());
    }
    if (empty($j['ok'])) {
        return array(false, isset($j['description']) ? (string) $j['description'] : "Xato (HTTP {$code})", $j);
    }
    return array(true, isset($j['result']) ? $j['result'] : array(), $j);
}

/**
 * Birinchi marta: secrets.php dagi chat_id ro'yxatga yoqilgan holda tushadi —
 * panel ochilgan zahoti arizalar hozirgidek kelishda davom etadi.
 */
function hs_tg_seed()
{
    if (hs_setting('tg_seeded', '') === '1') {
        return;
    }
    $id = trim((string) hs_config('chat_id', ''));
    if ($id !== '') {
        $st = hs_db()->prepare("INSERT OR IGNORE INTO tg_chats(chat_id, type, title, leads, added_at, updated_at) VALUES(?, ?, ?, 1, ?, ?)");
        $st->execute(array($id, strpos($id, '-') === 0 ? 'group' : 'private', 'secrets.php dagi chat', hs_now(), hs_now()));
        // Haqiqiy nomini Telegram'dan olamiz; olinmasa "secrets.php dagi chat" bo'lib qoladi.
        list($ok, $chat) = hs_tg_api('getChat', array('chat_id' => $id));
        if ($ok && isset($chat['id'], $chat['type'])) {
            hs_tg_upsert($chat, 'member');
        }
    }
    hs_set_setting('tg_seeded', '1');
}

function hs_tg_chat_title($chat)
{
    if (!empty($chat['title'])) {
        return (string) $chat['title'];
    }
    $n = trim((isset($chat['first_name']) ? $chat['first_name'] : '') . ' ' . (isset($chat['last_name']) ? $chat['last_name'] : ''));
    return $n !== '' ? $n : (isset($chat['username']) ? '@' . $chat['username'] : (string) $chat['id']);
}

/** Chatni ro'yxatga yozadi yoki yangilaydi. "Arizalar yoqilgan" sozlamasiga tegmaydi. */
function hs_tg_upsert($chat, $status = 'member')
{
    $id = (string) $chat['id'];
    $db = hs_db();
    $st = $db->prepare('SELECT chat_id FROM tg_chats WHERE chat_id = ?');
    $st->execute(array($id));
    $isNew = $st->fetchColumn() === false;
    if ($isNew) {
        $db->prepare('INSERT INTO tg_chats(chat_id, type, title, username, status, added_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?)')
            ->execute(array($id, (string) $chat['type'], hs_tg_chat_title($chat), isset($chat['username']) ? (string) $chat['username'] : '', $status, hs_now(), hs_now()));
    } else {
        $db->prepare('UPDATE tg_chats SET type = ?, title = ?, username = ?, status = ?, updated_at = ? WHERE chat_id = ?')
            ->execute(array((string) $chat['type'], hs_tg_chat_title($chat), isset($chat['username']) ? (string) $chat['username'] : '', $status, hs_now(), $id));
    }
    return $isNew;
}

/** Guruh superguruhga aylanganda Telegram chat ID sini almashtiradi — sozlamalar saqlanib qolsin. */
function hs_tg_migrate($oldId, $newId)
{
    $db = hs_db();
    $st = $db->prepare('SELECT COUNT(*) FROM tg_chats WHERE chat_id = ?');
    $st->execute(array((string) $newId));
    if ((int) $st->fetchColumn() > 0) {
        $db->prepare('DELETE FROM tg_chats WHERE chat_id = ?')->execute(array((string) $newId));
    }
    $db->prepare("UPDATE tg_chats SET chat_id = ?, type = 'supergroup', updated_at = ? WHERE chat_id = ?")
        ->execute(array((string) $newId, hs_now(), (string) $oldId));
}

/** Webhook'dan kelgan bitta voqea. */
function hs_tg_handle_update($u)
{
    // Bot guruh/kanalga qo'shildi, chiqarildi yoki shaxsiy chatda bloklandi.
    if (isset($u['my_chat_member']['chat'])) {
        $chat = $u['my_chat_member']['chat'];
        $new = isset($u['my_chat_member']['new_chat_member']['status']) ? $u['my_chat_member']['new_chat_member']['status'] : '';
        $in = in_array($new, array('member', 'administrator', 'creator', 'restricted'), true);
        $isNew = hs_tg_upsert($chat, $in ? 'member' : 'left');
        if ($in && $isNew && $chat['type'] !== 'private') {
            $who = isset($u['my_chat_member']['from']) ? hs_tg_chat_title($u['my_chat_member']['from']) : "noma'lum";
            hs_telegram_send("🤖 Bot yangi chatga qo'shildi\n\n" . hs_tg_chat_title($chat) . "\nQo'shgan: {$who}\n\nArizalar bu yerga faqat admin panelda yoqilgandan keyin keladi:\n" . hs_site_url() . '/admin/telegram.php');
            hs_tg_api('sendMessage', array('chat_id' => $chat['id'], 'text' => "Salom! HAMKOR SAVDO boti ulandi.\nAdmin panelda ruxsat berilgach, saytdan kelgan arizalar shu yerga yuboriladi."));
        }
        return;
    }
    $msg = isset($u['message']) ? $u['message'] : (isset($u['channel_post']) ? $u['channel_post'] : null);
    if (!$msg || !isset($msg['chat'])) {
        return;
    }
    if (isset($msg['migrate_to_chat_id'])) {
        hs_tg_migrate($msg['chat']['id'], $msg['migrate_to_chat_id']);
        return;
    }
    $chat = $msg['chat'];
    $text = isset($msg['text']) ? (string) $msg['text'] : '';
    // Bot avvaldan turgan guruhni ro'yxatga olish: /start yoki /start@bot_nomi.
    if (preg_match('#^/start(@\w+)?(\s|$)#', $text) || $chat['type'] === 'private') {
        $isNew = hs_tg_upsert($chat, 'member');
        if (preg_match('#^/start#', $text)) {
            $st = hs_db()->prepare('SELECT leads FROM tg_chats WHERE chat_id = ?');
            $st->execute(array((string) $chat['id']));
            $on = (int) $st->fetchColumn() === 1;
            hs_tg_api('sendMessage', array(
                'chat_id' => $chat['id'],
                'text' => $on
                    ? "✅ Bu chat ro'yxatda, arizalar shu yerga kelyapti."
                    : "👋 Chat ro'yxatga olindi.\nAdmin panelda ruxsat berilgach, saytdan kelgan arizalar shu yerga yuboriladi.",
            ));
            if ($isNew && $chat['type'] === 'private') {
                hs_telegram_send("👤 Botga yangi odam yozdi: " . hs_tg_chat_title($chat) . (isset($chat['username']) ? ' (@' . $chat['username'] . ')' : '') . "\n\nUnga arizalar yuborish uchun panelda yoqing:\n" . hs_site_url() . '/admin/telegram.php');
            }
        }
    }
}

/** Arizalar yoqilgan chatlar soni (filialidan qat'i nazar). */
function hs_tg_active_count()
{
    hs_tg_seed();
    return (int) hs_db()->query("SELECT COUNT(*) FROM tg_chats WHERE leads = 1 AND status = 'member'")->fetchColumn();
}

/** Shu filial arizasi kimlarga borishi kerak. */
function hs_tg_lead_recipients($branch)
{
    hs_tg_seed();
    $st = hs_db()->prepare("SELECT * FROM tg_chats WHERE leads = 1 AND status = 'member' AND (branch = '' OR branch = ?) ORDER BY added_at");
    $st->execute(array((string) $branch));
    return $st->fetchAll();
}

/**
 * Bitta chatga xabar. Chat ID o'zgargan (superguruh) yoki bot chiqarilgan
 * bo'lsa — ro'yxat o'zi tuzatiladi, xato panelda ko'rinadi.
 */
function hs_tg_send_to($chatId, $text)
{
    list($ok, $res, $raw) = hs_tg_api('sendMessage', array('chat_id' => $chatId, 'text' => $text, 'disable_web_page_preview' => 'true'));
    if (!$ok && isset($raw['parameters']['migrate_to_chat_id'])) {
        $newId = (string) $raw['parameters']['migrate_to_chat_id'];
        hs_tg_migrate($chatId, $newId);
        $chatId = $newId;
        list($ok, $res, $raw) = hs_tg_api('sendMessage', array('chat_id' => $chatId, 'text' => $text, 'disable_web_page_preview' => 'true'));
    }
    $db = hs_db();
    if ($ok) {
        $db->prepare("UPDATE tg_chats SET last_sent_at = ?, last_error = '' WHERE chat_id = ?")->execute(array(hs_now(), (string) $chatId));
    } else {
        $err = mb_substr((string) $res, 0, 200);
        // 403: bot guruhdan chiqarilgan yoki odam botni bloklagan.
        $gone = isset($raw['error_code']) && (int) $raw['error_code'] === 403;
        $db->prepare('UPDATE tg_chats SET last_error = ?' . ($gone ? ", status = 'left'" : '') . ' WHERE chat_id = ?')->execute(array($err, (string) $chatId));
        error_log('HAMKOR SAVDO: Telegram chatga xabar ketmadi (' . $chatId . ')');
    }
    return $ok;
}

/** Arizani barcha mos qabul qiluvchilarga yuboradi. Qaytaradi: nechta chatga yetdi. */
function hs_tg_send_lead($text, $branch)
{
    $sent = 0;
    foreach (hs_tg_lead_recipients($branch) as $c) {
        if (hs_tg_send_to($c['chat_id'], $text)) {
            $sent++;
        }
    }
    return $sent;
}

/** Bazadagi arizadan Telegram xabari (paneldan qayta yuborish uchun). */
function hs_tg_lead_text($lead, $note = '')
{
    $names = hs_branch_names();
    $b = (string) $lead['branch'];
    // Xeshteglar api/lead.php dagi $FILIALLAR bilan bir xil — operatorlar ular bo'yicha qidiradi.
    $tags = array('asaka-umid' => '#asaka', 'andijon-amir-temur' => '#andijon');
    $tag = $b === '' ? '#filial_tanlanmagan' : (isset($tags[$b]) ? $tags[$b] : '#' . str_replace('-', '_', $b));
    $lines = array(
        ((int) $lead['special'] ? "🟡 BIZDA YO'Q MAHSULOT — " . $tag . ' #maxsus_buyurtma' : '🟣 ARIZA — ' . $tag) . ($note !== '' ? ' (' . $note . ')' : ''),
        '',
        '📍 Filial: ' . ($b !== '' ? (isset($names[$b]) ? $names[$b] : $b) : 'tanlanmagan'),
        '👤 Ism: ' . $lead['name'],
        '📞 Telefon: ' . $lead['phone'],
    );
    if (trim((string) $lead['note']) !== '') {
        $lines[] = "💬 So'rovi: " . $lead['note'];
    }
    if ((string) $lead['source'] !== '') {
        $lines[] = '🧭 Qayerdan kelgan: ' . $lead['source'];
    }
    $lines[] = '';
    $lines[] = '🕒 ' . date('d.m.Y H:i', strtotime($lead['created_at']));
    $lines[] = '🗂 Admin panelda: ' . hs_site_url() . '/admin/ariza.php?id=' . (int) $lead['id'];
    return implode("\n", $lines);
}

/** Webhook manzili va maxfiy kaliti. Kalit bazada, birinchi ulashda yaratiladi. */
function hs_tg_webhook_secret()
{
    $s = (string) hs_setting('tg_webhook_secret', '');
    if ($s === '') {
        $s = hs_random_hex(24);
        hs_set_setting('tg_webhook_secret', $s);
    }
    return $s;
}

function hs_tg_webhook_url()
{
    return hs_site_url() . '/api/telegram.php';
}

function hs_tg_connect_webhook()
{
    return hs_tg_api('setWebhook', array(
        'url' => hs_tg_webhook_url(),
        'secret_token' => hs_tg_webhook_secret(),
        'allowed_updates' => json_encode(array('message', 'channel_post', 'my_chat_member')),
        // Oxirgi 24 soatdagi voqealar ham kelsin: bot yaqinda qo'shilgan guruhlar ro'yxatga tushadi.
        'drop_pending_updates' => 'false',
    ));
}

function hs_tg_type_label($type)
{
    $m = array('private' => 'Shaxsiy', 'group' => 'Guruh', 'supergroup' => 'Guruh', 'channel' => 'Kanal');
    return isset($m[$type]) ? $m[$type] : $type;
}
