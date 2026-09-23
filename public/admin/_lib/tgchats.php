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
            'getWebhookInfo' => array('url' => hs_setting('tg_webhook_secret', '') !== '' ? hs_tg_webhook_url() : '', 'allowed_updates' => array('message', 'channel_post', 'edited_channel_post', 'my_chat_member', 'callback_query')),
            'getChatAdministrators' => array(array('user' => array('id' => 777, 'is_bot' => false, 'first_name' => 'Xodim'), 'status' => 'administrator')),
            'getChatMember' => array('status' => 'administrator'),
            'getChat' => array('id' => isset($params['chat_id']) ? $params['chat_id'] : 0, 'type' => 'supergroup', 'title' => 'Sinov guruhi'),
            'sendMessage' => array('message_id' => mt_rand(1000, 999999)),
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

/**
 * Boshqaruvchilar: ruxsat so'rovlari shularga boradi va faqat ular
 * botdagi tugmalarni bosib chatlarni yoqa oladi. Panelda hech kim
 * belgilanmagan bo'lsa — secrets.php dagi chat (eskicha).
 */
function hs_tg_admin_ids()
{
    $ids = hs_db()->query("SELECT chat_id FROM tg_chats WHERE admin = 1 AND type = 'private' AND status = 'member'")->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) {
        $fallback = trim((string) hs_config('admin_chat_id', hs_config('chat_id', '')));
        if ($fallback !== '' && strpos($fallback, '-') !== 0) {
            $ids = array($fallback);
        }
    }
    return array_map('strval', $ids);
}

function hs_tg_is_admin($userId)
{
    return in_array((string) $userId, hs_tg_admin_ids(), true);
}

function hs_tg_get_chat($id)
{
    $st = hs_db()->prepare('SELECT * FROM tg_chats WHERE chat_id = ?');
    $st->execute(array((string) $id));
    return $st->fetch() ?: null;
}

/** Filial nomi to'liq: "Asaka — Makro supermarketi, 2-qavat". */
function hs_tg_branch_short($id)
{
    if ($id === '') {
        return 'Barcha filiallar';
    }
    $names = hs_branch_names();
    return isset($names[$id]) ? $names[$id] : $id;
}

/** Tugma uchun qisqa nom: shahar; bir shaharda ikki filial bo'lsa — mo'ljalning birinchi so'zi ham. */
function hs_tg_branch_label($id)
{
    $names = hs_branch_names();
    unset($names['boshqa-viloyat']);
    if (!isset($names[$id])) {
        return $id;
    }
    $city = preg_replace('/\s—.*$/u', '', $names[$id]);
    $same = 0;
    foreach ($names as $n) {
        if (preg_replace('/\s—.*$/u', '', $n) === $city) {
            $same++;
        }
    }
    return $same > 1 ? $city . ' · ' . preg_replace('/[\s,].*$/u', '', trim(preg_replace('/^.*—\s*/u', '', $names[$id]))) : $city;
}

/** Filial tugmalari, ikkitadan qatorda. $data — filial id sidan callback_data yasaydi. */
function hs_tg_branch_rows($data, $current = null, $prefix = '📍 ')
{
    $names = hs_branch_names();
    unset($names['boshqa-viloyat']);
    $rows = array();
    $pair = array();
    foreach (array_keys($names) as $bid) {
        $pair[] = array('text' => ($current === $bid ? '✔ ' : $prefix) . hs_tg_branch_label($bid), 'callback_data' => $data($bid));
        if (count($pair) === 2) {
            $rows[] = $pair;
            $pair = array();
        }
    }
    if ($pair) {
        $rows[] = $pair;
    }
    return $rows;
}

/** Ruxsat so'rovi tugmalari: hammasi / bitta filial (filial rahbari) / rad etish. */
function hs_tg_request_keyboard($chatId)
{
    $rows = array(array(array('text' => '✅ Ruxsat — barcha filiallar', 'callback_data' => 'a:' . $chatId . ':')));
    $rows = array_merge($rows, hs_tg_branch_rows(function ($bid) use ($chatId) {
        return 'a:' . $chatId . ':' . $bid;
    }, null, '📍 Faqat '));
    $rows[] = array(array('text' => '❌ Rad etish', 'callback_data' => 'r:' . $chatId));
    return json_encode(array('inline_keyboard' => $rows), JSON_UNESCAPED_UNICODE);
}

/** /royxat: har bir chat — bitta tugma, bosilsa o'sha chatning sozlamasi ochiladi. */
function hs_tg_list_keyboard()
{
    $rows = array();
    foreach (hs_db()->query("SELECT * FROM tg_chats WHERE status = 'member' ORDER BY leads DESC, title")->fetchAll() as $c) {
        $label = ((int) $c['leads'] ? '🟢 ' : '⚪ ') . mb_substr($c['title'], 0, 26) . ($c['type'] !== 'private' ? ' 👥' : '')
            . ($c['branch'] !== '' ? ' · 📍' . hs_tg_branch_label($c['branch']) : '');
        $rows[] = array(array('text' => $label, 'callback_data' => 'c:' . $c['chat_id']));
    }
    return json_encode(array('inline_keyboard' => $rows), JSON_UNESCAPED_UNICODE);
}

function hs_tg_list_text()
{
    return "📋 Arizalar kimga boradi\n\n🟢 arizalar boradi · ⚪ bormaydi\n👥 guruh · 📍 filial rahbari (faqat o'sha filial arizalari)\n\nSozlash uchun bosing.";
}

/** Bitta chat sozlamasi: yoqish/o'chirish va qaysi filialga rahbar. */
function hs_tg_chat_card($row)
{
    $on = (int) $row['leads'] === 1;
    $text = ($row['type'] !== 'private' ? '👥 ' : '👤 ') . $row['title'] . ($row['username'] !== '' ? ' (@' . $row['username'] . ')' : '')
        . "\n\nArizalar: " . ($on ? '🟢 boradi' : '⚪ bormaydi')
        . "\nQaysi arizalar: " . ($row['branch'] === '' ? 'hamma filiallarniki' : '📍 faqat ' . hs_tg_branch_short($row['branch']) . ' (filial rahbari)')
        . "\n\n\"Farqi yo'q\" deb yuborilgan arizalar hammaga boradi.";
    $id = $row['chat_id'];
    $rows = array(array(array('text' => $on ? '⚪ Arizalarni to\'xtatish' : '🟢 Arizalarni yoqish', 'callback_data' => 't:' . $id)));
    $rows[] = array(array('text' => ($row['branch'] === '' ? '✔ ' : '') . 'Hamma filiallar', 'callback_data' => 'b:' . $id . ':'));
    $rows = array_merge($rows, hs_tg_branch_rows(function ($bid) use ($id) {
        return 'b:' . $id . ':' . $bid;
    }, $row['branch'], '📍 '));
    $rows[] = array(array('text' => '⬅ Ro\'yxatga qaytish', 'callback_data' => 'l'));
    return array($text, json_encode(array('inline_keyboard' => $rows), JSON_UNESCAPED_UNICODE));
}

/** Yangi chat haqida boshqaruvchilarga tugmali so'rov. */
function hs_tg_ask_admins($chat, $by = '')
{
    $isGroup = $chat['type'] !== 'private';
    $text = ($isGroup ? "👥 Bot yangi guruhga qo'shildi\n\n" : "👤 Botga yangi odam yozdi\n\n")
        . hs_tg_chat_title($chat) . (isset($chat['username']) ? ' (@' . $chat['username'] . ')' : '')
        . ($by !== '' ? "\nQo'shgan: " . $by : '')
        . "\n\nSaytdan kelgan arizalar bu " . ($isGroup ? 'guruhga' : 'odamga') . " ham yuborilsinmi?\n📍 Filial tanlasangiz — u o'sha filial rahbari bo'ladi va faqat o'sha filial arizalarini oladi.";
    foreach (hs_tg_admin_ids() as $aid) {
        if ((string) $aid === (string) $chat['id']) {
            continue;
        }
        hs_tg_api('sendMessage', array('chat_id' => $aid, 'text' => $text, 'reply_markup' => hs_tg_request_keyboard($chat['id'])));
    }
}

/* ======================= ARIZA XABARI ======================= */

/** Holat: bazadagi nom -> [tugma matni, qisqa kod]. Kod callback_data uchun (64 bayt chegarasi). */
function hs_tg_status_buttons()
{
    return array(
        'yangi' => array('🆕 Yangi', 'y'),
        'qongiroq' => array("📞 Qo'ng'iroq qilindi", 'q'),
        'sotildi' => array('✅ Sotildi', 's'),
        'rad' => array('❌ Rad etildi', 'r'),
    );
}

/**
 * Manba — odam tilida. Saytdagi skript yuboradi:
 * src = instagram|google|...; src_info = "source=..;medium=cpc;campaign=..;ref=host/path;land=/sahifa".
 */
function hs_tg_source_text($source, $detail)
{
    $names = array('instagram' => 'Instagram', 'telegram' => 'Telegram', 'google' => 'Google qidiruv', 'yandex' => 'Yandex qidiruv',
        'facebook' => 'Facebook', 'togridan' => "To'g'ridan-to'g'ri (manzilni yozib yoki saqlangan havoladan)");
    $d = array();
    foreach (explode(';', (string) $detail) as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) === 2 && $kv[1] !== '') {
            $d[$kv[0]] = $kv[1];
        }
    }
    $src = (string) $source;
    $out = $src === '' ? "noma'lum" : (isset($names[$src]) ? $names[$src] : $src);
    $extra = array();
    $ad = (isset($d['medium']) && preg_match('/cpc|ppc|paid|ads?|target|reklama/i', $d['medium'])) || isset($d['click']);
    if ($ad) {
        $extra[] = 'reklama' . (isset($d['click']) ? ' (' . str_replace(array('gclid', 'yclid', 'fbclid'), array('Google Ads', 'Yandex Direkt', 'Facebook/Instagram'), $d['click']) . ')' : '');
    } elseif (isset($d['medium'])) {
        $extra[] = $d['medium'];
    }
    if (isset($d['campaign'])) {
        $extra[] = 'kampaniya: ' . $d['campaign'];
    }
    if (isset($d['content'])) {
        $extra[] = "e'lon: " . $d['content'];
    }
    if (isset($d['ref']) && strpos($d['ref'], (string) $src) === false) {
        $extra[] = 'sahifa: ' . $d['ref'];
    }
    if (isset($d['land']) && $d['land'] !== '/') {
        $extra[] = 'birinchi ochgani: ' . $d['land'];
    }
    return $out . ($extra ? ' · ' . implode(' · ', $extra) : '');
}

/** Bazadagi arizadan Telegram xabari. Holat qatori tugma bosilganda yangilanadi. */
function hs_tg_lead_text($lead, $note = '')
{
    $names = hs_branch_names();
    $b = (string) $lead['branch'];
    // Xeshteglar api/lead.php dagi $FILIALLAR bilan bir xil — operatorlar ular bo'yicha qidiradi.
    $tags = array('asaka-umid' => '#asaka', 'andijon-amir-temur' => '#andijon');
    $tag = $b === '' ? '#filial_tanlanmagan' : (isset($tags[$b]) ? $tags[$b] : '#' . str_replace('-', '_', $b));
    $lines = array(
        ((int) $lead['special'] ? "🟡 BIZDA YO'Q MAHSULOT — " . $tag . ' #maxsus_buyurtma' : '🟣 ARIZA #' . (int) $lead['id'] . ' — ' . $tag) . ($note !== '' ? ' (' . $note . ')' : ''),
        '',
        '📍 Filial: ' . ($b !== '' ? (isset($names[$b]) ? $names[$b] : $b) : "farqi yo'q (hamma filiallarga yuborildi)"),
        '👤 Ism: ' . $lead['name'],
        '📞 Telefon: ' . $lead['phone'],
    );
    if ((int) $lead['special']) {
        $lines[] = "🔎 Mahsulot do'konda yo'q — topib berish so'ralmoqda";
    }
    if (trim((string) $lead['note']) !== '') {
        $lines[] = "💬 So'rovi: " . $lead['note'];
    }
    $lines[] = '🧭 Qayerdan: ' . hs_tg_source_text($lead['source'], isset($lead['source_detail']) ? $lead['source_detail'] : '');
    if (!empty($lead['ym_client'])) {
        $lines[] = '🔍 Metrika ID: ' . $lead['ym_client'];
    }
    $lines[] = '';
    $lines[] = '🕒 ' . date('d.m.Y H:i', strtotime($lead['created_at']));
    $st = hs_lead_statuses();
    $status = isset($st[$lead['status']]) ? $st[$lead['status']] : $lead['status'];
    $by = (string) $lead['updated_by'];
    $lines[] = '📌 Holat: ' . $status . ($lead['status'] !== 'yangi' && $by !== '' ? ' — ' . $by . ($lead['updated_at'] ? ', ' . date('d.m H:i', strtotime($lead['updated_at'])) : '') : '');
    if (!empty($lead['claimed_by'])) {
        $lines[] = '🙋 Oldi: ' . $lead['claimed_by'] . (!empty($lead['claimed_at']) ? ', ' . date('d.m H:i', strtotime($lead['claimed_at'])) : '');
    }
    return implode("\n", $lines);
}

/** Ariza ostidagi tugmalar: holatlar (joriysi ✔ bilan) va paneldagi sahifa. */
function hs_tg_lead_keyboard($lead)
{
    $row1 = array();
    $row2 = array();
    $i = 0;
    foreach (hs_tg_status_buttons() as $key => $b) {
        $btn = array('text' => ($lead['status'] === $key ? '✔ ' : '') . $b[0], 'callback_data' => 's:' . (int) $lead['id'] . ':' . $b[1]);
        if ($i++ < 2) {
            $row1[] = $btn;
        } else {
            $row2[] = $btn;
        }
    }
    $rows = array($row1, $row2);
    if (empty($lead['claimed_by']) && $lead['status'] === 'yangi') {
        array_unshift($rows, array(array('text' => '🙋 Men oldim', 'callback_data' => 'm:' . (int) $lead['id'])));
    }
    // Telegram faqat https havolani tugma qiladi (mahalliy sinovda http — tugmasiz).
    if (strpos(hs_site_url(), 'https://') === 0) {
        $rows[] = array(array('text' => '🗂 Panelda ochish', 'url' => hs_site_url() . '/admin/ariza.php?id=' . (int) $lead['id']));
    }
    return json_encode(array('inline_keyboard' => $rows), JSON_UNESCAPED_UNICODE);
}

function hs_tg_get_lead($id)
{
    $st = hs_db()->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute(array((int) $id));
    return $st->fetch() ?: null;
}

/**
 * Bitta chatga xabar (kerak bo'lsa tugmalari bilan). Chat ID o'zgargan (superguruh) yoki bot chiqarilgan
 * bo'lsa — ro'yxat o'zi tuzatiladi, xato panelda ko'rinadi.
 * Qaytaradi: yuborilgan xabar ID si yoki false.
 */
function hs_tg_send_to($chatId, $text, $markup = null)
{
    $params = array('chat_id' => $chatId, 'text' => $text, 'disable_web_page_preview' => 'true');
    if ($markup !== null) {
        $params['reply_markup'] = $markup;
    }
    list($ok, $res, $raw) = hs_tg_api('sendMessage', $params);
    if (!$ok && isset($raw['parameters']['migrate_to_chat_id'])) {
        $newId = (string) $raw['parameters']['migrate_to_chat_id'];
        hs_tg_migrate($chatId, $newId);
        $chatId = $newId;
        $params['chat_id'] = $chatId;
        list($ok, $res, $raw) = hs_tg_api('sendMessage', $params);
    }
    $db = hs_db();
    if ($ok) {
        $db->prepare("UPDATE tg_chats SET last_sent_at = ?, last_error = '' WHERE chat_id = ?")->execute(array(hs_now(), (string) $chatId));
        return isset($res['message_id']) ? (int) $res['message_id'] : true;
    }
    $err = mb_substr((string) $res, 0, 200);
    // 403: bot guruhdan chiqarilgan yoki odam botni bloklagan.
    $gone = isset($raw['error_code']) && (int) $raw['error_code'] === 403;
    $db->prepare('UPDATE tg_chats SET last_error = ?' . ($gone ? ", status = 'left'" : '') . ' WHERE chat_id = ?')->execute(array($err, (string) $chatId));
    error_log('HAMKOR SAVDO: Telegram chatga xabar ketmadi (' . $chatId . ')');
    return false;
}

/** Arizani bitta chatga tugmalari bilan yuboradi va xabar ID sini eslab qoladi. */
function hs_tg_deliver_to($lead, $chatId, $note = '')
{
    $mid = hs_tg_send_to($chatId, hs_tg_lead_text($lead, $note), hs_tg_lead_keyboard($lead));
    if ($mid === false) {
        return false;
    }
    if (is_int($mid)) {
        hs_db()->prepare('INSERT OR REPLACE INTO tg_lead_msgs(lead_id, chat_id, message_id) VALUES(?, ?, ?)')->execute(array((int) $lead['id'], (string) $chatId, $mid));
    }
    return true;
}

/** Arizani barcha mos qabul qiluvchilarga yuboradi. Qaytaradi: nechta chatga yetdi. */
function hs_tg_deliver_lead($leadId, $note = '')
{
    $lead = hs_tg_get_lead($leadId);
    if (!$lead) {
        return 0;
    }
    $sent = 0;
    foreach (hs_tg_lead_recipients($lead['branch']) as $c) {
        if (hs_tg_deliver_to($lead, $c['chat_id'], $note)) {
            $sent++;
        }
    }
    if ($sent > 0) {
        hs_db()->prepare('UPDATE leads SET telegram_sent = 1 WHERE id = ?')->execute(array((int) $leadId));
    }
    return $sent;
}

/** Holat o'zgargach — ariza yuborilgan hamma chatdagi xabarni yangilaydi (kim, qachon, tugmalar). */
function hs_tg_sync_lead($leadId)
{
    $lead = hs_tg_get_lead($leadId);
    if (!$lead) {
        return;
    }
    $st = hs_db()->prepare('SELECT chat_id, message_id FROM tg_lead_msgs WHERE lead_id = ?');
    $st->execute(array((int) $leadId));
    $text = hs_tg_lead_text($lead);
    $kb = hs_tg_lead_keyboard($lead);
    foreach ($st->fetchAll() as $m) {
        hs_tg_api('editMessageText', array('chat_id' => $m['chat_id'], 'message_id' => $m['message_id'], 'text' => $text, 'reply_markup' => $kb, 'disable_web_page_preview' => 'true'));
    }
}

/**
 * Ariza tugmasi bosildi. Boshqaruvchi bo'lishi shart emas — ariza kelgan
 * chatdagi har kim (filial rahbari, operator, guruh a'zosi) holatni belgilay
 * oladi. Tekshiruv: xabar aynan biz shu arizani yuborgan chat va xabar bo'lsin.
 */
function hs_tg_is_lead_message($q, $leadId)
{
    $chatId = isset($q['message']['chat']['id']) ? (string) $q['message']['chat']['id'] : '';
    $msgId = isset($q['message']['message_id']) ? (int) $q['message']['message_id'] : 0;
    // Eslatma xabari ariza xabariga javob bo'lib keladi — uning tugmalari ham o'sha ariza uchun.
    $replyTo = isset($q['message']['reply_to_message']['message_id']) ? (int) $q['message']['reply_to_message']['message_id'] : 0;
    $st = hs_db()->prepare('SELECT COUNT(*) FROM tg_lead_msgs WHERE lead_id = ? AND chat_id = ? AND message_id IN (?, ?)');
    $st->execute(array((int) $leadId, $chatId, $msgId, $replyTo));
    return (int) $st->fetchColumn() > 0;
}

/** Eslatma ostidagi tugmalar: faqat natija (Yangi'siz, "Men oldim"siz). */
function hs_tg_result_keyboard($leadId)
{
    $row = array();
    foreach (hs_tg_status_buttons() as $key => $b) {
        if ($key !== 'yangi') {
            $row[] = array('text' => $b[0], 'callback_data' => 's:' . (int) $leadId . ':' . $b[1]);
        }
    }
    return json_encode(array('inline_keyboard' => array($row)), JSON_UNESCAPED_UNICODE);
}

/** Arizani olgan odamni xabarda belgilash (HTML): @username yoki ismi orqali havola. */
function hs_tg_mention_html($lead)
{
    $who = (string) $lead['claimed_by'];
    if ($who !== '' && $who[0] === '@') {
        return htmlspecialchars($who, ENT_QUOTES, 'UTF-8');
    }
    $uid = isset($lead['claimed_uid']) ? (string) $lead['claimed_uid'] : '';
    $name = htmlspecialchars($who !== '' ? $who : 'Siz', ENT_QUOTES, 'UTF-8');
    return ctype_digit($uid) ? '<a href="tg://user?id=' . $uid . '">' . $name . '</a>' : $name;
}

function hs_tg_presser($q)
{
    return isset($q['from']['username']) ? '@' . $q['from']['username'] : hs_tg_chat_title($q['from']);
}

/**
 * "Men oldim": ariza shu odamniki bo'ladi, boshqalar ikkinchi marta qo'ng'iroq
 * qilmaydi. Ikki kishi bir vaqtda bossa — birinchisi yutadi (UPDATE ... WHERE claimed_by = '').
 */
function hs_tg_handle_claim($q, $leadId)
{
    $answer = function ($text) use ($q) {
        hs_tg_api('answerCallbackQuery', array('callback_query_id' => $q['id'], 'text' => $text, 'show_alert' => 'false'));
    };
    if (!hs_tg_is_lead_message($q, $leadId)) {
        $answer('Bu xabar eskirgan — arizani paneldan oling.');
        return;
    }
    $who = hs_tg_presser($q);
    $uid = isset($q['from']['id']) ? (string) $q['from']['id'] : '';
    $st = hs_db()->prepare("UPDATE leads SET claimed_by = ?, claimed_uid = ?, claimed_at = ? WHERE id = ? AND claimed_by = ''");
    $st->execute(array($who, $uid, hs_now(), (int) $leadId));
    if ($st->rowCount() === 0) {
        $lead = hs_tg_get_lead($leadId);
        $answer($lead ? 'Bu arizani ' . $lead['claimed_by'] . ' allaqachon olgan.' : 'Ariza topilmadi.');
        hs_tg_sync_lead($leadId);
        return;
    }
    hs_audit('telegram ' . $who, 'ariza olindi (botdan)', "#{$leadId}");
    hs_tg_sync_lead($leadId);
    $answer('🙋 Ariza sizniki. Mijozga qo\'ng\'iroq qiling!');
}

function hs_tg_handle_status($q, $leadId, $code)
{
    $answer = function ($text) use ($q) {
        hs_tg_api('answerCallbackQuery', array('callback_query_id' => $q['id'], 'text' => $text));
    };
    if (!hs_tg_is_lead_message($q, $leadId)) {
        $answer('Bu xabar eskirgan — arizani paneldan o\'zgartiring.');
        return;
    }
    $map = array();
    foreach (hs_tg_status_buttons() as $key => $b) {
        $map[$b[1]] = $key;
    }
    $lead = hs_tg_get_lead($leadId);
    if (!$lead || !isset($map[$code])) {
        $answer('Ariza topilmadi.');
        return;
    }
    $new = $map[$code];
    if ($lead['status'] === $new) {
        $answer('Holat allaqachon: ' . hs_lead_statuses()[$new]);
        return;
    }
    $from = $q['from'];
    $who = isset($from['username']) ? '@' . $from['username'] : hs_tg_chat_title($from);
    hs_db()->prepare('UPDATE leads SET status = ?, updated_at = ?, updated_by = ? WHERE id = ?')->execute(array($new, hs_now(), $who, (int) $leadId));
    $uid = isset($from['id']) ? (string) $from['id'] : '';
    hs_db()->prepare("UPDATE leads SET claimed_by = ?, claimed_uid = ?, claimed_at = ? WHERE id = ? AND claimed_by = ''")->execute(array($who, $uid, hs_now(), (int) $leadId));
    hs_audit('telegram ' . $who, 'ariza holati (botdan)', "#{$leadId}: {$lead['status']} -> {$new}");
    hs_tg_sync_lead($leadId);
    // Eslatma xabaridan bosilgan bo'lsa — uning tugmalari endi kerak emas, natija yozib qo'yiladi.
    if (isset($q['message']['reply_to_message'])) {
        hs_tg_api('editMessageReplyMarkup', array(
            'chat_id' => (string) $q['message']['chat']['id'],
            'message_id' => (int) $q['message']['message_id'],
            'reply_markup' => json_encode(array('inline_keyboard' => array(array(array('text' => '✔ ' . hs_lead_statuses()[$new] . ' — ' . $who, 'callback_data' => 'x')))), JSON_UNESCAPED_UNICODE),
        ));
    }
    $answer(hs_lead_statuses()[$new]);
}

/** Tugma bosildi. */
function hs_tg_handle_callback($q)
{
    $from = isset($q['from']['id']) ? (string) $q['from']['id'] : '';
    $data = isset($q['data']) ? (string) $q['data'] : '';
    $answer = function ($text) use ($q) {
        hs_tg_api('answerCallbackQuery', array('callback_query_id' => $q['id'], 'text' => $text));
    };
    // Ariza holati — ariza kelgan chatdagi har kim.
    if (preg_match('/^s:(\d+):([a-z])$/', $data, $m)) {
        hs_tg_handle_status($q, (int) $m[1], $m[2]);
        return;
    }
    if (preg_match('/^m:(\d+)$/', $data, $m)) {
        hs_tg_handle_claim($q, (int) $m[1]);
        return;
    }
    // Eslatmadagi "✔ Sotildi — @kim" yozuvi — shunchaki belgi.
    if ($data === 'x') {
        $answer('Natija belgilangan.');
        return;
    }
    // Mijozlar guruhi savoli: "✅ Javob berildi" — savol kelgan xodimlar chatidagi har kim.
    if (preg_match('/^mq:(\d+)$/', $data, $m)) {
        require_once __DIR__ . '/mijozbot.php';
        hs_mb_handle_callback($q, (int) $m[1]);
        return;
    }
    // Qolgani — faqat boshqaruvchi.
    if (!hs_tg_is_admin($from)) {
        $answer("Bu tugmani faqat boshqaruvchi bosa oladi.");
        return;
    }
    $who = isset($q['from']['username']) ? '@' . $q['from']['username'] : hs_tg_chat_title($q['from']);
    $msgChat = isset($q['message']['chat']['id']) ? $q['message']['chat']['id'] : null;
    $msgId = isset($q['message']['message_id']) ? $q['message']['message_id'] : null;
    $db = hs_db();
    $showCard = function ($row) use ($msgChat, $msgId) {
        if ($msgChat === null || $msgId === null) {
            return;
        }
        list($text, $kb) = hs_tg_chat_card($row);
        hs_tg_api('editMessageText', array('chat_id' => $msgChat, 'message_id' => $msgId, 'text' => $text, 'reply_markup' => $kb));
    };

    if (preg_match('/^a:(-?\d+):([a-z0-9-]*)$/', $data, $m) || preg_match('/^r:(-?\d+)$/', $data, $m)) {
        $row = hs_tg_get_chat($m[1]);
        if (!$row) {
            $answer("Bu chat ro'yxatda yo'q (panelda o'chirilgan).");
            return;
        }
        $approve = $data[0] === 'a';
        if ($approve && $row['role'] !== '') {
            // Mijozlar guruhi / mahsulot kanali — mijozlar telefoni u yerga ketmasin.
            $answer($row['role'] === 'mijozlar' ? "Bu mijozlar guruhi — unga arizalar yuborilmaydi." : "Bu mahsulot kanali — unga arizalar yuborilmaydi.");
            return;
        }
        $branch = $approve ? $m[2] : $row['branch'];
        if ($branch !== '' && !isset(hs_branch_names()[$branch])) {
            $branch = '';
        }
        $db->prepare('UPDATE tg_chats SET leads = ?, branch = ?, updated_at = ? WHERE chat_id = ?')->execute(array($approve ? 1 : 0, $branch, hs_now(), $row['chat_id']));
        hs_audit('telegram ' . $who, $approve ? 'telegram: arizalar yoqildi (botdan)' : 'telegram: rad etildi (botdan)', $row['title'] . ' (' . $row['chat_id'] . ')' . ($approve ? ', ' . hs_tg_branch_short($branch) : ''));
        $result = $approve ? ($branch === '' ? '✅ Ruxsat berildi — barcha filiallar' : '✅ ' . hs_tg_branch_short($branch) . ' filiali rahbari') : '❌ Rad etildi';
        if ($msgChat !== null && $msgId !== null) {
            hs_tg_api('editMessageText', array(
                'chat_id' => $msgChat,
                'message_id' => $msgId,
                'text' => (isset($q['message']['text']) ? preg_replace('/\n\nSaytdan kelgan arizalar.*$/su', '', $q['message']['text']) : $row['title']) . "\n\n" . $result . "\n(" . $who . ', ' . date('d.m H:i') . ")",
            ));
        }
        if ($approve) {
            hs_tg_api('sendMessage', array('chat_id' => $row['chat_id'], 'text' => "✅ Ruxsat berildi. Saytdan kelgan arizalar endi shu yerga keladi"
                . ($branch !== '' ? ":\n📍 " . hs_tg_branch_short($branch) . " filiali arizalari va filial tanlanmagan (\"farqi yo'q\") arizalar." : '.')
                . "\n\nHar bir ariza ostidagi tugma bilan holatini belgilang: qo'ng'iroq qilindi, sotildi yoki rad etildi."));
        }
        $answer($result);
        return;
    }

    if ($data === 'l') {
        if ($msgChat !== null && $msgId !== null) {
            hs_tg_api('editMessageText', array('chat_id' => $msgChat, 'message_id' => $msgId, 'text' => hs_tg_list_text(), 'reply_markup' => hs_tg_list_keyboard()));
        }
        $answer('');
        return;
    }

    if (preg_match('/^c:(-?\d+)$/', $data, $m)) {
        $row = hs_tg_get_chat($m[1]);
        if (!$row) {
            $answer("Bu chat ro'yxatda yo'q.");
            return;
        }
        $showCard($row);
        $answer('');
        return;
    }

    if (preg_match('/^t:(-?\d+)$/', $data, $m)) {
        $row = hs_tg_get_chat($m[1]);
        if (!$row) {
            $answer("Bu chat ro'yxatda yo'q.");
            return;
        }
        $on = (int) $row['leads'] ? 0 : 1;
        if ($on && $row['role'] !== '') {
            $answer('Bu ' . ($row['role'] === 'mijozlar' ? 'mijozlar guruhi' : 'mahsulot kanali') . ' — arizalar yuborilmaydi.');
            return;
        }
        $db->prepare('UPDATE tg_chats SET leads = ?, updated_at = ? WHERE chat_id = ?')->execute(array($on, hs_now(), $row['chat_id']));
        hs_audit('telegram ' . $who, $on ? 'telegram: arizalar yoqildi (botdan)' : "telegram: arizalar o'chirildi (botdan)", $row['title'] . ' (' . $row['chat_id'] . ')');
        $showCard(hs_tg_get_chat($row['chat_id']));
        $answer(($on ? '🟢 Yoqildi: ' : '⚪ To\'xtatildi: ') . $row['title']);
        return;
    }

    // Filial rahbarini biriktirish: shu chatga faqat tanlangan filial arizalari boradi.
    if (preg_match('/^b:(-?\d+):([a-z0-9-]*)$/', $data, $m)) {
        $row = hs_tg_get_chat($m[1]);
        $branch = $m[2];
        if (!$row || ($branch !== '' && !isset(hs_branch_names()[$branch]))) {
            $answer('Topilmadi.');
            return;
        }
        $db->prepare('UPDATE tg_chats SET branch = ?, updated_at = ? WHERE chat_id = ?')->execute(array($branch, hs_now(), $row['chat_id']));
        hs_audit('telegram ' . $who, 'telegram: filial rahbari (botdan)', $row['title'] . ': ' . hs_tg_branch_short($branch));
        $showCard(hs_tg_get_chat($row['chat_id']));
        if ($branch !== '' && (int) $row['leads'] === 1 && $branch !== $row['branch']) {
            hs_tg_api('sendMessage', array('chat_id' => $row['chat_id'], 'text' => "📍 Siz " . hs_tg_branch_short($branch) . " filiali uchun biriktirildingiz. Endi shu filial arizalari va filial tanlanmagan arizalar keladi."));
        }
        $answer($branch === '' ? 'Hamma filiallar' : '📍 ' . hs_tg_branch_label($branch));
        return;
    }
    $answer('Eskirgan tugma.');
}

/** Webhook'dan kelgan bitta voqea. */
function hs_tg_handle_update($u)
{
    require_once __DIR__ . '/mijozbot.php';
    if (isset($u['callback_query']['id'])) {
        hs_tg_handle_callback($u['callback_query']);
        return;
    }
    // Bot guruh/kanalga qo'shildi, chiqarildi yoki shaxsiy chatda bloklandi.
    if (isset($u['my_chat_member']['chat'])) {
        $chat = $u['my_chat_member']['chat'];
        $new = isset($u['my_chat_member']['new_chat_member']['status']) ? $u['my_chat_member']['new_chat_member']['status'] : '';
        $in = in_array($new, array('member', 'administrator', 'creator', 'restricted'), true);
        $isNew = hs_tg_upsert($chat, $in ? 'member' : 'left');
        $who = isset($u['my_chat_member']['from']) ? hs_tg_chat_title($u['my_chat_member']['from']) : '';
        // Mijozlar guruhi / mahsulot kanali: arizalar bu yerga hech qachon yuborilmaydi va
        // guruhga "arizalar shu yerga keladi" degan salom ham yozilmaydi.
        $role = hs_mb_is_customer_chat($chat) ? 'mijozlar' : (hs_mb_is_catalog_chat($chat) ? 'katalog' : '');
        if ($role !== '') {
            hs_mb_set_role($chat['id'], $role);
            if ($in) {
                hs_mb_notify_admins_role($chat, $role, $new, $who);
            }
            return;
        }
        if ($in && $isNew && $chat['type'] !== 'private') {
            hs_tg_ask_admins($chat, $who);
            // Ochiq guruh yoki kanal (@nomi bor) — ko'pincha mijozlar ko'radigan joy: salom yozmaymiz.
            // Yopiq bo'lsa ham, katta guruh xodimlar chati emas — ehtimol mijozlar guruhi.
            // "Arizalar shu yerga yuboriladi" degan salomni yuzlab mijoz o'qib qolmasin.
            $katta = false;
            if (empty($chat['username'])) {
                list($okSoni, $soni) = hs_tg_api('getChatMemberCount', array('chat_id' => $chat['id']));
                $katta = $okSoni && is_numeric($soni) && (int) $soni > 30;
            }
            if (empty($chat['username']) && !$katta) {
                hs_tg_api('sendMessage', array('chat_id' => $chat['id'], 'text' => "Salom! HAMKOR SAVDO boti ulandi.\nBoshqaruvchi ruxsat bergach, saytdan kelgan arizalar shu yerga yuboriladi."));
            }
        }
        return;
    }
    // Mahsulot kanali posti (yangi yoki tahrirlangan) — katalogga.
    $post = isset($u['channel_post']) ? $u['channel_post'] : (isset($u['edited_channel_post']) ? $u['edited_channel_post'] : null);
    if ($post && isset($post['chat'])) {
        $row = hs_tg_get_chat($post['chat']['id']);
        if (hs_mb_is_catalog_chat($post['chat'], $row)) {
            if (!$row) {
                hs_tg_upsert($post['chat'], 'member');
            }
            if (!$row || $row['role'] !== 'katalog') {
                hs_mb_set_role($post['chat']['id'], 'katalog');
            }
            hs_mb_catalog_from_message($post, true);
            return;
        }
        if (isset($u['edited_channel_post'])) {
            return;
        }
    }
    // Boshqa kanal posti — avvalgidek (kanalga /start yozib ro'yxatga qo'shish mumkin).
    $msg = isset($u['message']) ? $u['message'] : $post;
    if (!$msg || !isset($msg['chat'])) {
        return;
    }
    if (isset($msg['migrate_to_chat_id'])) {
        hs_tg_migrate($msg['chat']['id'], $msg['migrate_to_chat_id']);
        return;
    }
    $chat = $msg['chat'];
    $text = isset($msg['text']) ? trim((string) $msg['text']) : '';
    $private = $chat['type'] === 'private';

    // Mijozlar guruhi: savollarga javob, xodim postlari katalogga. Arizalar oqimiga kirmaydi.
    if (!$private) {
        $row = hs_tg_get_chat($chat['id']);
        if (hs_mb_is_customer_chat($chat, $row)) {
            /* Nom yoki @manzil Telegram'da o'zgartirilsa (masalan "Hamkor Savdo
               mijozlari" -> "Hamkor Savdo Andijon"), panel eski nomni ko'rsatib
               turardi: mijozlar guruhi yozuvi faqat bot qo'shilganda yozilgan. */
            $username = isset($chat['username']) ? (string) $chat['username'] : '';
            if (!$row || $row['title'] !== hs_tg_chat_title($chat) || (string) $row['username'] !== $username) {
                hs_tg_upsert($chat, 'member');
            }
            if (!$row || $row['role'] !== 'mijozlar') {
                hs_mb_set_role($chat['id'], 'mijozlar');
            }
            hs_mb_handle_group_message($msg);
            return;
        }
    }
    // Guruhdagi "Operator bilan bog'lanish" tugmasidan kelgan mijoz (start=qN) va uning raqami.
    if ($private && hs_mb_handle_private($msg)) {
        return;
    }

    // Boshqaruvchi buyruqlari: /royxat — hamma chatlar tugma bilan.
    if ($private && hs_tg_is_admin($chat['id']) && preg_match('#^/(royxat|ro\'yxat|chatlar|list)\b#iu', $text)) {
        hs_tg_api('sendMessage', array('chat_id' => $chat['id'], 'text' => hs_tg_list_text(), 'reply_markup' => hs_tg_list_keyboard()));
        return;
    }

    // Bot avvaldan turgan guruhni ro'yxatga olish: /start yoki /start@bot_nomi.
    if (preg_match('#^/start(@\w+)?(\s|$)#', $text) || $private) {
        $isNew = hs_tg_upsert($chat, 'member');
        if (!preg_match('#^/start#', $text)) {
            return;
        }
        if ($private && hs_tg_is_admin($chat['id'])) {
            hs_tg_api('sendMessage', array('chat_id' => $chat['id'], 'text' => "👋 Siz boshqaruvchisiz.\n\nKimdir botga yozsa yoki botni guruhga qo'shsa, sizga ruxsat so'rovi keladi.\n\n/royxat — hamma chatlar, bosib yoqish/o'chirish."));
            return;
        }
        $row = hs_tg_get_chat($chat['id']);
        $on = $row && (int) $row['leads'] === 1;
        hs_tg_api('sendMessage', array(
            'chat_id' => $chat['id'],
            'text' => $on
                ? "✅ Bu chat ro'yxatda, arizalar shu yerga kelyapti."
                : "👋 So'rovingiz boshqaruvchiga yuborildi.\nRuxsat berilgach, saytdan kelgan arizalar shu yerga keladi.",
        ));
        if ($isNew) {
            hs_tg_ask_admins($chat);
        }
    }
}

/** Arizalar yoqilgan chatlar soni (filialidan qat'i nazar). */
function hs_tg_active_count()
{
    hs_tg_seed();
    return (int) hs_db()->query("SELECT COUNT(*) FROM tg_chats WHERE leads = 1 AND status = 'member' AND role = ''")->fetchColumn();
}

/**
 * Shu arizani kimlar oladi:
 *  - filial tanlangan — "hamma filiallar" chatlari va o'sha filial rahbari;
 *  - "farqi yo'q" yoki "boshqa viloyat" — hammasi, filial rahbarlari ham
 *    (hech kimga tegishli emas, birinchi bo'lib kim olsa o'sha qo'ng'iroq qiladi).
 */
function hs_tg_lead_recipients($branch)
{
    hs_tg_seed();
    $branch = (string) $branch;
    if ($branch === '' || $branch === 'boshqa-viloyat') {
        return hs_db()->query("SELECT * FROM tg_chats WHERE leads = 1 AND status = 'member' AND role = '' ORDER BY added_at")->fetchAll();
    }
    $st = hs_db()->prepare("SELECT * FROM tg_chats WHERE leads = 1 AND status = 'member' AND role = '' AND (branch = '' OR branch = ?) ORDER BY added_at");
    $st->execute(array($branch));
    return $st->fetchAll();
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
        'allowed_updates' => json_encode(array('message', 'channel_post', 'edited_channel_post', 'my_chat_member', 'callback_query')),
        // Oxirgi 24 soatdagi voqealar ham kelsin: bot yaqinda qo'shilgan guruhlar ro'yxatga tushadi.
        'drop_pending_updates' => 'false',
    ));
}

function hs_tg_type_label($type)
{
    $m = array('private' => 'Shaxsiy', 'group' => 'Guruh', 'supergroup' => 'Guruh', 'channel' => 'Kanal');
    return isset($m[$type]) ? $m[$type] : $type;
}
