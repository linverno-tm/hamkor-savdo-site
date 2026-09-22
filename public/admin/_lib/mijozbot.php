<?php
/**
 * Mijozlar guruhi yordamchisi — arizalar boti (@murojatlarXS_bot) ning ikkinchi vazifasi.
 *
 *  - Mijozlar guruhida (@hamkorsavdouz_mijozlari) kimdir "X bormi? narxi?" deb yozsa,
 *    bot o'sha xabarga javob qaytaradi: mos mahsulot postlari (havola), qisqa izoh va
 *    "Operator bilan bog'lanish" tugmasi. Savolni Claude tushunadi (lotin/kirill, xato yozuv).
 *  - Aniq javob kerak bo'lsa — savol arizalar keladigan xodimlar chatiga "Javob berildi"
 *    tugmasi bilan tushadi; javobsiz qolsa, eslatiladi.
 *  - "Operator bilan bog'lanish" -> bot lichkasi -> telefon raqami -> oddiy ARIZA
 *    (leads jadvali), ya'ni "Men oldim", holatlar va eslatmalar hozirgidek ishlaydi.
 *  - Katalog: mahsulot kanali (@hamkorsavdouz) postlari va guruhdagi xodimlarning narxli
 *    postlari. Kanal ochiq bo'lgani uchun eski postlar t.me/s/ sahifasidan ham yig'iladi.
 *
 * Xavfsizlik: mijozlar guruhi va katalog kanaliga ariza (mijozlar telefoni) HECH QACHON
 * yuborilmaydi — tg_chats.role bo'yicha hs_tg_lead_recipients() ularni chiqarib tashlaydi.
 * Claude kaliti faqat bazada (settings), brauzerga va logga chiqmaydi.
 */

require_once __DIR__ . '/tgchats.php';
require_once __DIR__ . '/content.php';

define('HS_MB_API', 'https://api.anthropic.com/v1/messages');
define('HS_MB_DEFAULT_MODEL', 'claude-opus-5');
/** Katalogdan AI ga beriladigan eng ko'p post (eng yangilari). */
define('HS_MB_INDEX_LIMIT', 220);

/* ============================ sozlamalar ============================ */

function hs_mb_setting($key)
{
    $c = hs_content_published();
    $s = is_array($c) && isset($c['settings']) ? $c['settings'] : array();
    $defaults = array(
        'on' => '1',
        // kuzatish — guruhga YOZMAYDI, javob loyihasini xodimlarga ko'rsatadi (boshlash uchun xavfsiz);
        // faol — guruhdagi savolga o'zi javob beradi. Egasi panelda almashtiradi.
        'mode' => 'kuzatish',
        'group' => isset($s['telegramCustomers']) ? (string) $s['telegramCustomers'] : 'hamkorsavdouz_mijozlari',
        'channel' => isset($s['telegram']) ? (string) $s['telegram'] : 'hamkorsavdouz',
        'model' => HS_MB_DEFAULT_MODEL,
        'notify' => 'kerak',     // kerak — faqat operator kerak bo'lganda; hammasi — har bir savol
        'remind_m' => '15',      // xodimlar javob bermasa, shuncha daqiqada eslatma
    );
    return (string) hs_setting('mb_' . $key, $defaults[$key]);
}

function hs_mb_key()
{
    return trim((string) hs_setting('mb_claude_key', ''));
}

function hs_mb_bot_username()
{
    $u = hs_cache_get('mb:botname');
    if (is_string($u) && $u !== '') {
        return $u;
    }
    list($ok, $me) = hs_tg_api('getMe');
    $u = $ok && !empty($me['username']) ? (string) $me['username'] : '';
    if ($u !== '') {
        hs_cache_set('mb:botname', $u, 86400);
    }
    return $u;
}

/** Chat shu sozlamadagi mijozlar guruhimi (ID bo'yicha rol yoki @nom bo'yicha). */
function hs_mb_is_customer_chat($chat, $row = null)
{
    if ($row === null && isset($chat['id'])) {
        $row = hs_tg_get_chat($chat['id']);
    }
    if ($row && isset($row['role']) && $row['role'] === 'mijozlar') {
        return true;
    }
    $want = strtolower(ltrim(hs_mb_setting('group'), '@'));
    return $want !== '' && isset($chat['username']) && strtolower((string) $chat['username']) === $want;
}

function hs_mb_is_catalog_chat($chat, $row = null)
{
    if ($row === null && isset($chat['id'])) {
        $row = hs_tg_get_chat($chat['id']);
    }
    if ($row && isset($row['role']) && $row['role'] === 'katalog') {
        return true;
    }
    $want = strtolower(ltrim(hs_mb_setting('channel'), '@'));
    return $want !== '' && isset($chat['username']) && strtolower((string) $chat['username']) === $want;
}

function hs_mb_set_role($chatId, $role)
{
    hs_db()->prepare('UPDATE tg_chats SET role = ?, leads = CASE WHEN ? = \'\' THEN leads ELSE 0 END, updated_at = ? WHERE chat_id = ?')
        ->execute(array($role, $role, hs_now(), (string) $chatId));
}

/** Bot mijozlar guruhi yoki mahsulot kanaliga qo'shildi — boshqaruvchilarga nima qilish kerakligi. */
function hs_mb_notify_admins_role($chat, $role, $status, $by = '')
{
    $admin = $status === 'administrator' || $status === 'creator';
    $title = hs_tg_chat_title($chat) . (isset($chat['username']) ? ' (@' . $chat['username'] . ')' : '');
    if ($role === 'mijozlar') {
        $text = "👥 Bot mijozlar guruhiga qo'shildi: " . $title . ($by !== '' ? "\nQo'shgan: " . $by : '')
            . "\n\nBu guruhga arizalar (mijozlar raqami) yuborilMAYDI — bot faqat savollarga javob beradi."
            . ($admin ? "\n\n✅ Bot guruhda admin — barcha xabarlarni ko'radi." : "\n\n⚠️ Bot barcha xabarlarni ko'rishi uchun uni guruhda ADMIN qiling (qo'shimcha huquq shart emas).");
    } else {
        $text = "📣 Bot mahsulot kanaliga qo'shildi: " . $title
            . "\n\nYangi postlar katalogga o'zi tushadi, mijozlar savoliga shu postlar bilan javob beriladi. Arizalar bu kanalga yuborilmaydi."
            . ($admin ? '' : "\n\n⚠️ Postlarni ko'rishi uchun botni kanalda ADMIN qiling.");
    }
    foreach (hs_tg_admin_ids() as $aid) {
        hs_tg_api('sendMessage', array('chat_id' => $aid, 'text' => $text));
    }
}

/** Guruh adminlari (xodimlar) — ularning xabarlariga bot javob bermaydi. 1 soat keshlanadi. */
function hs_mb_staff_ids($chatId)
{
    $key = 'mb:admins:' . $chatId;
    $ids = hs_cache_get($key);
    if (is_array($ids)) {
        return $ids;
    }
    list($ok, $res) = hs_tg_api('getChatAdministrators', array('chat_id' => $chatId));
    $ids = array();
    if ($ok && is_array($res)) {
        foreach ($res as $m) {
            if (isset($m['user']['id'])) {
                $ids[] = (string) $m['user']['id'];
            }
        }
    }
    hs_cache_set($key, $ids, $ok ? 3600 : 300);
    return $ids;
}

/** Keyinroq (Telegram'ga javob qaytgandan keyin) bajariladigan ish — webhook kutib qolmasin. */
function hs_mb_defer($fn)
{
    static $registered = false;
    $GLOBALS['hs_mb_deferred'][] = $fn;
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function(function () {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        }
        @set_time_limit(120);
        foreach ($GLOBALS['hs_mb_deferred'] as $job) {
            try {
                $job();
            } catch (Throwable $e) {
                error_log('HAMKOR SAVDO: mijozbot xatosi: ' . $e->getMessage());
            }
        }
    });
}

/* ============================== katalog ============================== */

/** Mahsulot posti belgisi: narx/oylik to'lov so'zi va raqam. */
function hs_mb_is_product_text($text)
{
    $t = mb_strtolower((string) $text);
    return mb_strlen($t) >= 15
        && preg_match('/\d[\d\s.,]{2,}/u', $t)
        && preg_match("/(so['‘’ʻ`]?m|сўм|сум|нарх|narx|oyga|ойга|oy\/|ой\/|oyiga|ойига|muddat|муддат|\\$)/u", $t);
}

function hs_mb_post_url($username, $postId)
{
    return $username !== '' ? 'https://t.me/' . $username . '/' . (int) $postId : '';
}

function hs_mb_catalog_add($source, $postId, $url, $text, $photo, $postedAt)
{
    $text = trim(preg_replace("/[ \t]+/u", ' ', (string) $text));
    if ($text === '') {
        return false;
    }
    hs_db()->prepare('INSERT OR REPLACE INTO mb_posts(source, post_id, url, text, photo, posted_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?)')
        ->execute(array((string) $source, (int) $postId, (string) $url, mb_substr($text, 0, 2000), $photo ? 1 : 0, $postedAt, hs_now()));
    return true;
}

function hs_mb_msg_text($msg)
{
    if (isset($msg['text'])) {
        return (string) $msg['text'];
    }
    return isset($msg['caption']) ? (string) $msg['caption'] : '';
}

/**
 * Xabardan katalogga: kanal posti, kanaldan guruhga uzatilgan post yoki xodimning narxli posti.
 * Qaytaradi: true — katalogga yozildi.
 */
function hs_mb_catalog_from_message($msg, $isChannel)
{
    $text = hs_mb_msg_text($msg);
    $photo = isset($msg['photo']) || isset($msg['video']);
    $at = date('Y-m-d H:i:s', isset($msg['date']) ? (int) $msg['date'] : time());
    // Kanaldan uzatilgan (guruhda yoki boshqa joyda) — asl post havolasi bilan.
    if (!$isChannel && isset($msg['forward_origin']['type']) && $msg['forward_origin']['type'] === 'channel') {
        $o = $msg['forward_origin'];
        $u = isset($o['chat']['username']) ? (string) $o['chat']['username'] : '';
        if (!hs_mb_is_product_text($text)) {
            return false;
        }
        return hs_mb_catalog_add($u !== '' ? '@' . $u : (string) $o['chat']['id'], (int) $o['message_id'], hs_mb_post_url($u, $o['message_id']), $text, $photo, $at);
    }
    if (!hs_mb_is_product_text($text)) {
        return false;
    }
    $chat = $msg['chat'];
    $u = isset($chat['username']) ? (string) $chat['username'] : '';
    return hs_mb_catalog_add($u !== '' ? '@' . $u : (string) $chat['id'], (int) $msg['message_id'], hs_mb_post_url($u, $msg['message_id']), $text, $photo, $at);
}

/**
 * Ochiq kanalning eski postlari: https://t.me/s/<kanal> (har sahifada ~20 post).
 * Qaytaradi: [yozilgan postlar soni, xato matni yoki ''].
 */
function hs_mb_backfill($channel, $pages = 10)
{
    $channel = ltrim(trim((string) $channel), '@');
    if (!preg_match('/^[A-Za-z0-9_]{4,64}$/', $channel)) {
        return array(0, "Kanal nomi noto'g'ri.");
    }
    $before = 0;
    $saved = 0;
    for ($p = 0; $p < $pages; $p++) {
        $url = 'https://t.me/s/' . $channel . ($before ? '?before=' . $before : '');
        list($code, $html) = hs_http('GET', $url, array('User-Agent: Mozilla/5.0 (HamkorSavdo katalog)'), null, 20);
        if ($code !== 200 || $html === '') {
            return array($saved, $p === 0 ? "Kanal sahifasi ochilmadi (HTTP {$code})." : '');
        }
        $posts = hs_mb_parse_channel_page($html, $channel);
        if (!$posts) {
            break;
        }
        $min = PHP_INT_MAX;
        foreach ($posts as $post) {
            $min = min($min, $post['id']);
            // Faqat narxli postlar: tabrik, mijoz fikri kabi postlar javobga aralashmasin.
            if (hs_mb_is_product_text($post['text'])) {
                if (hs_mb_catalog_add('@' . $channel, $post['id'], hs_mb_post_url($channel, $post['id']), $post['text'], $post['photo'], $post['at'])) {
                    $saved++;
                }
            }
        }
        if ($min <= 1 || $min === $before) {
            break;
        }
        $before = $min;
    }
    hs_set_setting('mb_backfill_at', hs_now());
    return array($saved, '');
}

/** t.me/s sahifasidan postlar: [id, text, photo, at]. */
function hs_mb_parse_channel_page($html, $channel)
{
    $out = array();
    $parts = preg_split('/<div class="tgme_widget_message_wrap/', $html);
    array_shift($parts);
    foreach ($parts as $part) {
        if (!preg_match('#data-post="' . preg_quote($channel, '#') . '/(\d+)"#i', $part, $m)) {
            continue;
        }
        $text = '';
        if (preg_match('#<div class="tgme_widget_message_text[^"]*"[^>]*>(.*?)</div>#s', $part, $t)) {
            $raw = preg_replace('#<br\s*/?>#i', "\n", $t[1]);
            $raw = preg_replace('#<i class="emoji"[^>]*><b>(.*?)</b></i>#s', '$1', $raw);
            $text = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        $at = hs_now();
        if (preg_match('#<time datetime="([^"]+)"#', $part, $d) && ($ts = strtotime($d[1]))) {
            $at = date('Y-m-d H:i:s', $ts);
        }
        if ($text === '') {
            continue;
        }
        $out[] = array('id' => (int) $m[1], 'text' => $text, 'photo' => strpos($part, 'tgme_widget_message_photo') !== false, 'at' => $at);
    }
    return $out;
}

/** AI uchun ixcham katalog: har post bir qator — "#N | sarlavha | narx qatorlari". */
function hs_mb_catalog_rows($limit = HS_MB_INDEX_LIMIT)
{
    // Eski postlar (narxi o'zgargan, aksiya tugagan) javobga tushmasin.
    $st = hs_db()->prepare('SELECT rowid AS n, * FROM mb_posts WHERE posted_at > ? ORDER BY posted_at DESC LIMIT ?');
    $st->execute(array(date('Y-m-d H:i:s', time() - 120 * 86400), (int) $limit));
    return $st->fetchAll();
}

function hs_mb_catalog_line($row)
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $row['text'])), 'strlen'));
    $title = isset($lines[0]) ? $lines[0] : '';
    $prices = array();
    foreach (array_slice($lines, 1) as $l) {
        if (hs_mb_is_product_text($l) || preg_match('/\d{3}[\s.,]?\d{3}/u', $l)) {
            $prices[] = $l;
        }
        if (count($prices) >= 4) {
            break;
        }
    }
    return '#' . (int) $row['n'] . ' | ' . date('d.m', strtotime($row['posted_at'])) . ' | ' . mb_substr($title, 0, 90)
        . ($prices ? ' | ' . mb_substr(implode('; ', $prices), 0, 180) : '');
}

function hs_mb_catalog_count()
{
    return (int) hs_db()->query('SELECT COUNT(*) FROM mb_posts')->fetchColumn();
}

/* ============================ do'kon faktlari ============================ */

function hs_mb_store_facts()
{
    $c = hs_content_published();
    if (!is_array($c)) {
        return '';
    }
    $s = $c['settings'];
    $oy = isset($s['installmentMonthsMax']) ? (int) $s['installmentMonthsMax'] : 12;
    $sub = function ($t) use ($s, $oy, $c) {
        return str_replace(array('{oy}', '{filial}', '{bepul_hudud}', '{mahsulot}', '**'),
            array((string) $oy, (string) count($c['branches']), isset($s['freeDeliveryArea']) ? $s['freeDeliveryArea'] : '', isset($s['productCount']) ? $s['productCount'] : '', ''), (string) $t);
    };
    $lines = array(
        "Do'kon: HAMKOR SAVDO — Andijon viloyatidagi savdo tarmog'i: texnika, tilla, mebel, skuterlar.",
        'Sayt: ' . hs_site_url() . ' (saytda ariza qoldirish mumkin).',
        "Umumiy telefon: " . $s['phoneDisplay'],
        // data/categories.ts dagi tasdiqlangan yo'nalishlar (egasi: skuterlar texnika bo'limida).
        "Sotiladigan yo'nalishlar (rasmiy): maishiy texnika (muzlatgich, kir yuvish mashinasi, konditsioner, televizor, pylesos, "
            . "gaz plita va h.k.), skuterlar, tilla taqinchoqlar, mebel. Jami " . (isset($s['productCount']) ? $s['productCount'] : '') . ' mahsulot.',
        "Muddatli to'lov: {$oy} oygacha. Hujjat: pasport va plastik karta.",
        "Bepul yetkazish va o'rnatish: " . (isset($s['freeDeliveryArea']) ? $s['freeDeliveryArea'] : '') . '; yetkazish hududi: ' . (isset($s['deliveryArea']) ? $s['deliveryArea'] : '') . '.',
        'Filiallar:',
    );
    foreach ($c['branches'] as $b) {
        if (!empty($b['closed'])) {
            continue;
        }
        $lines[] = '- ' . $b['city'] . ': ' . $b['address'] . '; tel ' . $b['phoneDisplay'] . '; ish vaqti ' . $b['hours'];
    }
    if (!empty($c['faq'])) {
        $lines[] = "Ko'p so'raladigan savollar (rasmiy javoblar):";
        foreach ($c['faq'] as $f) {
            $lines[] = '- S: ' . $sub($f['q']) . ' J: ' . $sub($f['a']);
        }
    }
    return implode("\n", $lines);
}

/* ================================ Claude ================================ */

function hs_mb_system_prompt()
{
    return "Sen HAMKOR SAVDO do'konining Telegram'dagi mijozlar guruhida ishlaydigan yordamchisisan. "
        . "Guruhda mijozlar mahsulot bormi, narxi, muddatli to'lov, filial, yetkazish haqida so'raydi. "
        . "Ular o'zbekcha lotin yoki kirill yozuvida, ko'pincha xato bilan yozadi (masalan \"kirmowina\" — kir yuvish mashinasi, "
        . "\"marazilnik\" — muzlatgich/morozilnik, \"kandisaner\" — konditsioner), ba'zan ruscha.\n\n"
        . "Vazifang — har bir yangi xabar bo'yicha qaror:\n"
        . "1. is_question: xabar do'konga savol yoki so'rovmi (mahsulot, narx, shartlar, manzil, operator). Salom, rahmat, "
        . "fikr, reklama, boshqa mijozga javob, mavzuga aloqasiz gap — false.\n"
        . "2. post_ids: KATALOG ichidan so'ralgan mahsulotga mos 0–3 ta postning raqami (#N dagi N). Faqat haqiqatan mosini tanla; "
        . "ishonching komil bo'lmasa — bo'sh qoldir. Yangi postlarni afzal ko'r. Muddati o'tgan aksiya postini (masalan \"23–28-iyun\" "
        . "va bugungi sana undan keyin) tanlama.\n"
        . "3. reply: mijozga qisqa javob (1–3 gap, 350 belgidan oshmasin), mijoz yozgan yozuvda (lotin yoki kirill). "
        . "Faqat DO'KON FAKTLARI va KATALOGda yozilganini ayt; narx, muddat, aniq model yoki brend borligini O'YLAB TOPMA. "
        . "So'ralgan mahsulot TURI rasmiy yo'nalishlarga kirsa (masalan skuter, kir mashina, muzlatgich, divan, uzuk) — "
        . "avval ishonch bilan \"Ha, bizda <tur> bor\" de. Keyin: mos post bo'lsa — \"quyidagi postlarni ko'ring\"; bo'lmasa — "
        . "\"aniq modellar va narxini operator yozib beradi\" yoki filialga kelib ko'rish mumkinligini ayt. "
        . "Muayyan model/brend (masalan \"S21 Ultra\", \"LG 9 kg\") so'ralsa va katalogda yo'q bo'lsa — borligini tasdiqlama, "
        . "operator aniqlab berishini ayt. Yo'nalishlarga kirmaydigan narsa so'ralsa — operator aniqlashini ayt. "
        . "Havola, telefon raqami yoki emoji qo'shma — tizim o'zi qo'shadi.\n"
        . "4. needs_operator: aniq narx/mavjudlik/limit/buyurtma kabi javobni faqat xodim bera oladigan bo'lsa yoki mos post topilmasa — true.\n"
        . "5. kind: mahsulot | shartlar | filial | yetkazish | ish | boshqa.\n\n"
        . "=== DO'KON FAKTLARI ===\n" . hs_mb_store_facts();
}

function hs_mb_catalog_block($rows)
{
    $lines = array();
    foreach ($rows as $r) {
        $lines[] = hs_mb_catalog_line($r);
    }
    return "=== KATALOG (eng yangisi birinchi) ===\n" . ($lines ? implode("\n", $lines) : "(katalog hali bo'sh)");
}

function hs_mb_schema()
{
    return array(
        'type' => 'object',
        'properties' => array(
            'is_question' => array('type' => 'boolean'),
            'kind' => array('type' => 'string', 'enum' => array('mahsulot', 'shartlar', 'filial', 'yetkazish', 'ish', 'boshqa')),
            'post_ids' => array('type' => 'array', 'items' => array('type' => 'integer')),
            'reply' => array('type' => 'string'),
            'needs_operator' => array('type' => 'boolean'),
        ),
        'required' => array('is_question', 'kind', 'post_ids', 'reply', 'needs_operator'),
        'additionalProperties' => false,
    );
}

/**
 * Claude'dan qaror. Qaytaradi: [qaror massivi yoki null, xato matni].
 * Model — sozlamadan (default claude-opus-5). Opus 5 da xavfsizlik rad etishi uchun
 * server tomondagi zaxira model (fallbacks: "default") yoqilgan.
 */
function hs_mb_ask_claude($text, $context, $rows)
{
    $key = hs_mb_key();
    if ($key === '') {
        return array(null, 'Claude kaliti kiritilmagan.');
    }
    $model = hs_mb_setting('model');
    // Sana system'da emas — aks holda katalog keshi har kuni buziladi.
    $user = 'Bugun: ' . date('d.m.Y') . ".\n\n"
        . ($context !== '' ? "Mijoz shu xabarga javoban yozgan:\n\"\"\"" . mb_substr($context, 0, 800) . "\"\"\"\n\n" : '')
        . "Mijoz xabari:\n\"\"\"" . mb_substr($text, 0, 1000) . "\"\"\"";
    $body = array(
        'model' => $model,
        'max_tokens' => 2000,
        'system' => array(
            array('type' => 'text', 'text' => hs_mb_system_prompt()),
            // Katalog kamdan-kam o'zgaradi — kesh (keyingi savollarda arzonroq).
            array('type' => 'text', 'text' => hs_mb_catalog_block($rows), 'cache_control' => array('type' => 'ephemeral')),
        ),
        'messages' => array(array('role' => 'user', 'content' => $user)),
        'output_config' => array(
            'effort' => 'low',
            'format' => array('type' => 'json_schema', 'schema' => hs_mb_schema()),
        ),
    );
    $headers = array(
        'Content-Type: application/json',
        'x-api-key: ' . $key,
        'anthropic-version: 2023-06-01',
    );
    if ($model === 'claude-opus-5') {
        $body['fallbacks'] = 'default';
        $headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
    }
    if (getenv('HS_MB_FAKE_AI')) {
        // Faqat mahalliy sinov: javob fayldan.
        $fake = json_decode((string) @file_get_contents(getenv('HS_MB_FAKE_AI')), true);
        @file_put_contents(hs_data_dir() . '/claude.log', json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        return array(is_array($fake) ? $fake : null, is_array($fake) ? '' : 'fake yo\'q');
    }
    list($code, $resp) = hs_http('POST', HS_MB_API, $headers, json_encode($body, JSON_UNESCAPED_UNICODE), 60);
    $j = json_decode((string) $resp, true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? (string) $j['error']['message'] : 'javob yo\'q';
        return array(null, "Claude HTTP {$code}: " . mb_substr($msg, 0, 200));
    }
    if (isset($j['stop_reason']) && $j['stop_reason'] === 'refusal') {
        return array(null, 'Claude javob berishni rad etdi.');
    }
    $out = '';
    foreach (isset($j['content']) ? $j['content'] : array() as $block) {
        if (isset($block['type']) && $block['type'] === 'text') {
            $out .= $block['text'];
        }
    }
    $d = json_decode($out, true);
    if (!is_array($d) || !isset($d['is_question'])) {
        return array(null, 'Claude javobi tushunarsiz' . (isset($j['stop_reason']) ? ' (' . $j['stop_reason'] . ')' : ''));
    }
    return array($d, '');
}

/* =============== AI siz zaxira: oddiy so'z bo'yicha qidiruv =============== */

function hs_mb_normalize($s)
{
    $s = mb_strtolower((string) $s);
    // 1) kirill -> lotin; 2) lotindagi xato yozuvlarni bir xil qilish. Ikki bosqich — kirill "ш" (-> "sh")
    // ham, lotin "sh"/"w" ham oxirida bir xil ("sx") bo'lishi uchun.
    $s = strtr($s, array('ў' => 'o', 'ғ' => 'g', 'қ' => 'k', 'ҳ' => 'h', 'ш' => 'sh', 'ч' => 'ch', 'ё' => 'yo', 'ю' => 'yu', 'я' => 'ya', 'ц' => 's',
        'щ' => 'sh', 'ж' => 'j', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'x', 'ы' => 'i', 'э' => 'e', 'ъ' => '', 'ь' => ''));
    $s = preg_replace("/['‘’ʻ`]/u", '', $s);
    return strtr($s, array('q' => 'k', 'h' => 'x', 'w' => 'sx'));
}

function hs_mb_keyword_search($text, $rows, $limit = 3)
{
    $stop = array('bormi', 'bor', 'narxi', 'narx', 'qancha', 'kancha', 'assalomu', 'alaykum', 'aleykum', 'salom', 'iltimos', 'ham', 'xam', 'bilan', 'rasmini', 'tashlab', 'bering', 'kerak', 'edi', 'sizlarda', 'silarda', 'nechi', 'oyga', 'pul');
    $words = array();
    foreach (preg_split('/[^\p{L}\p{N}]+/u', hs_mb_normalize($text)) as $w) {
        if (mb_strlen($w) >= 4 && !in_array($w, $stop, true)) {
            $words[] = mb_substr($w, 0, 5);
        }
    }
    if (!$words) {
        return array();
    }
    $scored = array();
    foreach ($rows as $r) {
        $t = hs_mb_normalize($r['text']);
        $score = 0;
        foreach ($words as $w) {
            if (mb_strpos($t, $w) !== false) {
                $score++;
            }
        }
        if ($score > 0) {
            $scored[] = array($score, (int) $r['n']);
        }
    }
    usort($scored, function ($a, $b) {
        return $b[0] - $a[0] ?: $b[1] - $a[1];
    });
    return array_map(function ($x) {
        return $x[1];
    }, array_slice($scored, 0, $limit));
}

/**
 * AI siz: savoldagi mahsulot turi rasmiy yo'nalishlarga kirsa — uning nomi ("skuterlar"), aks holda ''.
 * Kalitlar hs_mb_normalize() dan o'tgan (lotin, kichik harf) so'z boshlari.
 */
function hs_mb_known_type($text)
{
    $types = array(
        // Faqat saytda tasdiqlangan yo'nalishlar (data/categories.ts) — sotilishi aniq bo'lmagan turga "bor" demaymiz.
        'skuter' => 'skuterlar', 'skooter' => 'skuterlar', 'moped' => 'skuterlar',
        'kirmo' => 'kir yuvish mashinalari', 'kir yuv' => 'kir yuvish mashinalari', 'kirmash' => 'kir yuvish mashinalari', 'kir mash' => 'kir yuvish mashinalari', 'kir mosh' => 'kir yuvish mashinalari', 'stiraln' => 'kir yuvish mashinalari',
        'xolodil' => 'muzlatgichlar', 'xaladil' => 'muzlatgichlar', 'muzlat' => 'muzlatgichlar', 'morozil' => 'muzlatgichlar', 'marazil' => 'muzlatgichlar',
        'konditsi' => 'konditsionerlar', 'kondisi' => 'konditsionerlar', 'kandisa' => 'konditsionerlar', 'kanditsa' => 'konditsionerlar', 'kondits' => 'konditsionerlar',
        'televiz' => 'televizorlar', 'tilivi' => 'televizorlar', 'televi' => 'televizorlar',
        'pilesos' => 'changyutgichlar', 'pylesos' => 'changyutgichlar', 'plisos' => 'changyutgichlar', 'changyut' => 'changyutgichlar',
        'gaz plit' => 'gaz plitalar', 'gazplit' => 'gaz plitalar',
        'divan' => 'divanlar', 'krovat' => 'krovatlar', 'kravat' => 'krovatlar', 'shkaf' => 'shkaflar', 'mebel' => 'mebellar', 'stol stul' => 'stol-stullar',
        'tilla' => 'tilla taqinchoqlar', 'uzuk' => 'tilla taqinchoqlar', 'zira' => 'tilla taqinchoqlar', 'bilaguz' => 'tilla taqinchoqlar', 'sirg' => 'tilla taqinchoqlar',
    );
    $t = hs_mb_normalize($text);
    foreach ($types as $k => $name) {
        // Kalit ham xuddi shunday normallashtiriladi (masalan "sh" -> "sx"), aks holda lotin "mashina" topilmaydi.
        if (mb_strpos($t, hs_mb_normalize($k)) !== false) {
            return $name;
        }
    }
    return '';
}

/** AI siz ham ko'rinadigan savol belgisi. */
function hs_mb_looks_like_question($text)
{
    return (bool) preg_match('/\?|bormi|борми|bor mi|narx|нарх|qancha|қанча|канча|necha|неча|muddat|муддат|qayer|қаер|каер|dostavka|доставка|yetkaz|етказ|admin|админ|kerak|керак/iu', (string) $text);
}

/* ============================ guruhdagi xabar ============================ */

/**
 * Mijozlar guruhidagi yangi xabar. Xodimning narxli posti — katalogga; xodim mijozga
 * javob yozsa — savol "javob berildi"; mijoz xabari — savol sifatida navbatga.
 */
function hs_mb_handle_group_message($msg)
{
    $chat = $msg['chat'];
    $chatId = (string) $chat['id'];
    $from = isset($msg['from']) ? $msg['from'] : array();
    $fromId = isset($from['id']) ? (string) $from['id'] : '';
    $text = trim(hs_mb_msg_text($msg));
    $isForward = isset($msg['forward_origin']);

    // Guruh nomidan (anonim admin) yoki kanal nomidan yozilgan — xodim.
    $anon = isset($msg['sender_chat']['id']);
    $staff = $anon || in_array($fromId, hs_mb_staff_ids($chatId), true);
    if (!empty($from['is_bot'])) {
        return;
    }
    // Katalogga faqat xodimning posti yoki bizning kanaldan uzatilgan post (mijoz boshqa
    // do'kon kanalidan uzatsa — katalogga tushmaydi).
    $fromOurChannel = $isForward && isset($msg['forward_origin']['chat']['username'])
        && strtolower((string) $msg['forward_origin']['chat']['username']) === strtolower(ltrim(hs_mb_setting('channel'), '@'));
    if ($staff || $fromOurChannel) {
        hs_mb_catalog_from_message($msg, false);
    }
    if ($staff) {
        // Xodim mijoz xabariga reply qildi — o'sha savol javob olgan.
        if (isset($msg['reply_to_message']['message_id'])) {
            hs_mb_mark_answered_by_reply($chatId, (int) $msg['reply_to_message']['message_id'], hs_mb_who($from, $anon));
        }
        return;
    }
    if ($isForward || $text === '' || mb_strlen($text) < 3 || hs_mb_setting('on') !== '1') {
        return;
    }
    // Bir odam bir xil savolni ketma-ket yozsa (javob kutib) — faqat birinchisiga javob.
    $st = hs_db()->prepare("SELECT id FROM mb_questions WHERE chat_id = ? AND user_id = ? AND text = ? AND created_at > ? LIMIT 1");
    $st->execute(array($chatId, $fromId, $text, date('Y-m-d H:i:s', time() - 6 * 3600)));
    if ($st->fetchColumn() !== false) {
        return;
    }
    $context = '';
    if (isset($msg['reply_to_message'])) {
        $context = trim(hs_mb_msg_text($msg['reply_to_message']));
    }
    $name = trim((isset($from['first_name']) ? $from['first_name'] : '') . ' ' . (isset($from['last_name']) ? $from['last_name'] : ''));
    hs_db()->prepare('INSERT INTO mb_questions(created_at, chat_id, message_id, user_id, user_name, text, context) VALUES(?, ?, ?, ?, ?, ?, ?)')
        ->execute(array(hs_now(), $chatId, (int) $msg['message_id'], $fromId, $name !== '' ? $name : (isset($from['username']) ? '@' . $from['username'] : ''), mb_substr($text, 0, 2000), mb_substr($context, 0, 2000)));
    $qid = (int) hs_db()->lastInsertId();
    hs_mb_defer(function () use ($qid) {
        hs_mb_process_question($qid);
    });
}

function hs_mb_who($from, $anon = false)
{
    if ($anon) {
        return 'admin';
    }
    return isset($from['username']) ? '@' . $from['username'] : hs_tg_chat_title($from);
}

function hs_mb_get_question($id)
{
    $st = hs_db()->prepare('SELECT * FROM mb_questions WHERE id = ?');
    $st->execute(array((int) $id));
    return $st->fetch() ?: null;
}

/** Savolni AI (yoki zaxira qidiruv) bilan hal qilib, guruhga javob va kerak bo'lsa xodimlarga xabar. */
function hs_mb_process_question($qid)
{
    $q = hs_mb_get_question($qid);
    if (!$q || $q['status'] !== 'yangi') {
        return;
    }
    $rows = hs_mb_catalog_rows();
    $byN = array();
    foreach ($rows as $r) {
        $byN[(int) $r['n']] = $r;
    }
    list($d, $err) = hs_mb_ask_claude($q['text'], $q['context'], $rows);
    if ($d === null) {
        // AI yo'q yoki ishlamadi — oddiy qidiruv; savolga o'xshamasa jim turamiz.
        if (!hs_mb_looks_like_question($q['text'])) {
            hs_db()->prepare("UPDATE mb_questions SET status = 'tashlandi', error = ? WHERE id = ?")->execute(array($err, $qid));
            return;
        }
        $ids = hs_mb_keyword_search($q['text'] . ' ' . $q['context'], $rows);
        $type = hs_mb_known_type($q['text']);
        $reply = $type !== '' ? 'Ha, bizda ' . $type . ' bor! ' : 'Savolingiz uchun rahmat! ';
        $reply .= $ids ? "Mos bo'lishi mumkin bo'lgan postlar quyida, aniq model va narxini operator yozib beradi." : 'Aniq model va narxini operator tez orada yozib beradi.';
        $d = array('is_question' => true, 'kind' => $type !== '' ? 'mahsulot' : 'boshqa', 'post_ids' => $ids, 'needs_operator' => true, 'reply' => $reply);
    }
    if (empty($d['is_question'])) {
        hs_db()->prepare("UPDATE mb_questions SET status = 'tashlandi', kind = ?, error = ? WHERE id = ?")->execute(array((string) $d['kind'], $err, $qid));
        return;
    }
    $posts = array();
    foreach (array_slice((array) $d['post_ids'], 0, 3) as $n) {
        if (isset($byN[(int) $n]) && $byN[(int) $n]['url'] !== '') {
            $posts[] = $byN[(int) $n];
        }
    }
    $needs = !empty($d['needs_operator']) || !$posts;
    $reply = trim(mb_substr((string) $d['reply'], 0, 400));
    if ($reply === '') {
        $reply = $posts ? "Quyidagi postlarni ko'ring:" : 'Operator tez orada javob beradi.';
    }
    $lines = array($reply);
    if ($posts) {
        $lines[] = '';
        foreach ($posts as $i => $p) {
            $title = preg_split('/\R/u', trim($p['text']))[0];
            $lines[] = ($i + 1) . '. ' . mb_substr($title, 0, 70) . "\n" . $p['url'];
        }
    }
    $params = array(
        'chat_id' => $q['chat_id'],
        'text' => implode("\n", $lines),
        'reply_parameters' => json_encode(array('message_id' => (int) $q['message_id'], 'allow_sending_without_reply' => true)),
        'link_preview_options' => json_encode($posts ? array('url' => $posts[0]['url']) : array('is_disabled' => true)),
    );
    $bot = hs_mb_bot_username();
    if ($bot !== '') {
        $params['reply_markup'] = json_encode(array('inline_keyboard' => array(array(
            array('text' => "📞 Operator bilan bog'lanish", 'url' => 'https://t.me/' . $bot . '?start=q' . (int) $qid),
        ))), JSON_UNESCAPED_UNICODE);
    }
    $postIds = implode(',', array_map(function ($p) {
        return (int) $p['n'];
    }, $posts));
    // Kuzatish rejimi: guruhga hech narsa yozilmaydi, javob loyihasi faqat xodimlarga boradi.
    if (hs_mb_setting('mode') !== 'faol') {
        hs_db()->prepare("UPDATE mb_questions SET status = 'kuzatish', kind = ?, reply = ?, post_ids = ?, needs_operator = ?, error = ? WHERE id = ?")
            ->execute(array((string) $d['kind'], $params['text'], $postIds, $needs ? 1 : 0, $err, $qid));
        hs_mb_notify_staff($qid);
        return;
    }
    list($ok, $res) = hs_tg_api('sendMessage', $params);
    hs_db()->prepare('UPDATE mb_questions SET status = ?, kind = ?, reply = ?, post_ids = ?, bot_message_id = ?, needs_operator = ?, error = ? WHERE id = ?')
        ->execute(array($ok ? 'javob' : 'xato', (string) $d['kind'], $reply, $postIds, $ok && isset($res['message_id']) ? (int) $res['message_id'] : null, $needs ? 1 : 0, $ok ? $err : mb_substr((string) $res, 0, 200), $qid));
    if ($needs || hs_mb_setting('notify') === 'hammasi') {
        hs_mb_notify_staff($qid);
    }
}

/** Guruhdagi xabar havolasi (ochiq guruh — t.me/nom/ID, yopiq — t.me/c/ID/ID). */
function hs_mb_message_link($chatId, $messageId)
{
    $row = hs_tg_get_chat($chatId);
    if ($row && $row['username'] !== '') {
        return 'https://t.me/' . $row['username'] . '/' . (int) $messageId;
    }
    return 'https://t.me/c/' . preg_replace('/^-100/', '', (string) $chatId) . '/' . (int) $messageId;
}

function hs_mb_staff_text($q)
{
    $lines = array(
        '❓ MIJOZLAR GURUHIDA SAVOL #' . (int) $q['id'],
        '',
        '👤 ' . ($q['user_name'] !== '' ? $q['user_name'] : 'mijoz') . ':',
        '💬 ' . $q['text'],
    );
    if ($q['context'] !== '') {
        $lines[] = '↩️ Shu postga javoban: ' . mb_substr(preg_replace('/\s+/u', ' ', $q['context']), 0, 150);
    }
    if ($q['status'] === 'kuzatish') {
        $lines[] = '';
        $lines[] = "🧪 KUZATISH REJIMI — bot guruhga yozmadi. Faol bo'lganda shunday javob berardi:";
        $lines[] = $q['reply'];
        $lines[] = '';
        $lines[] = (int) $q['needs_operator'] ? '👉 Mijozga siz javob bering.' : "👉 Bot javobi to'g'rimi — tekshiring; mijozga baribir siz javob bering.";
    } elseif ($q['reply'] !== '') {
        $lines[] = '';
        $lines[] = '🤖 Bot javobi: ' . $q['reply'];
    }
    $lines[] = '';
    $lines[] = '🕒 ' . date('d.m.Y H:i', strtotime($q['created_at']));
    if ($q['answered_by'] !== '') {
        $lines[] = '✅ Javob berildi — ' . $q['answered_by'] . ($q['answered_at'] ? ', ' . date('d.m H:i', strtotime($q['answered_at'])) : '');
    } elseif ((int) $q['lead_id'] > 0) {
        $lines[] = '📞 Mijoz raqam qoldirdi — ariza #' . (int) $q['lead_id'];
    }
    return implode("\n", $lines);
}

function hs_mb_staff_keyboard($q)
{
    $rows = array(array(array('text' => '💬 Guruhda ochish', 'url' => hs_mb_message_link($q['chat_id'], $q['message_id']))));
    if ($q['answered_by'] === '') {
        $rows[] = array(array('text' => '✅ Javob berildi', 'callback_data' => 'mq:' . (int) $q['id']));
    }
    return json_encode(array('inline_keyboard' => $rows), JSON_UNESCAPED_UNICODE);
}

/** Savolni arizalar keladigan chatlarga yuboradi (filial rahbarlariga emas — faqat "barcha filiallar"). */
function hs_mb_notify_staff($qid)
{
    $q = hs_mb_get_question($qid);
    if (!$q) {
        return 0;
    }
    $sent = 0;
    foreach (hs_tg_lead_recipients('') as $c) {
        if ($c['branch'] !== '') {
            continue;
        }
        $mid = hs_tg_send_to($c['chat_id'], hs_mb_staff_text($q), hs_mb_staff_keyboard($q));
        if (is_int($mid)) {
            hs_db()->prepare('INSERT OR REPLACE INTO mb_q_msgs(q_id, chat_id, message_id) VALUES(?, ?, ?)')->execute(array((int) $qid, (string) $c['chat_id'], $mid));
            $sent++;
        }
    }
    return $sent;
}

function hs_mb_sync_staff($qid)
{
    $q = hs_mb_get_question($qid);
    if (!$q) {
        return;
    }
    $st = hs_db()->prepare('SELECT chat_id, message_id FROM mb_q_msgs WHERE q_id = ?');
    $st->execute(array((int) $qid));
    foreach ($st->fetchAll() as $m) {
        hs_tg_api('editMessageText', array('chat_id' => $m['chat_id'], 'message_id' => $m['message_id'], 'text' => hs_mb_staff_text($q), 'reply_markup' => hs_mb_staff_keyboard($q), 'disable_web_page_preview' => 'true'));
    }
}

function hs_mb_mark_answered($qid, $who)
{
    $st = hs_db()->prepare("UPDATE mb_questions SET answered_by = ?, answered_at = ? WHERE id = ? AND answered_by = ''");
    $st->execute(array((string) $who, hs_now(), (int) $qid));
    if ($st->rowCount() > 0) {
        hs_mb_sync_staff($qid);
        return true;
    }
    return false;
}

function hs_mb_mark_answered_by_reply($chatId, $replyToMessageId, $who)
{
    $st = hs_db()->prepare('SELECT id FROM mb_questions WHERE chat_id = ? AND (message_id = ? OR bot_message_id = ?) ORDER BY id DESC LIMIT 1');
    $st->execute(array((string) $chatId, (int) $replyToMessageId, (int) $replyToMessageId));
    $id = $st->fetchColumn();
    if ($id !== false) {
        hs_mb_mark_answered((int) $id, $who);
    }
}

/** Xodimlar chatidagi "✅ Javob berildi" tugmasi. */
function hs_mb_handle_callback($q, $qid)
{
    $chatId = isset($q['message']['chat']['id']) ? (string) $q['message']['chat']['id'] : '';
    $msgId = isset($q['message']['message_id']) ? (int) $q['message']['message_id'] : 0;
    $st = hs_db()->prepare('SELECT COUNT(*) FROM mb_q_msgs WHERE q_id = ? AND chat_id = ? AND message_id = ?');
    $st->execute(array((int) $qid, $chatId, $msgId));
    if ((int) $st->fetchColumn() === 0) {
        hs_tg_api('answerCallbackQuery', array('callback_query_id' => $q['id'], 'text' => 'Bu xabar eskirgan.'));
        return;
    }
    $who = hs_tg_presser($q);
    $ok = hs_mb_mark_answered($qid, $who);
    if (!$ok) {
        hs_mb_sync_staff($qid);
    }
    hs_tg_api('answerCallbackQuery', array('callback_query_id' => $q['id'], 'text' => $ok ? '✅ Belgilandi' : 'Allaqachon belgilangan.'));
}

/* ====================== bot lichkasi: raqam qoldirish ====================== */

/**
 * "Operator bilan bog'lanish" (t.me/bot?start=qN) va shundan keyingi telefon raqami.
 * Qaytaradi: true — xabar shu yerda hal bo'ldi (arizalar oqimiga o'tkazilmaydi).
 */
function hs_mb_handle_private($msg)
{
    $chatId = (string) $msg['chat']['id'];
    $text = isset($msg['text']) ? trim((string) $msg['text']) : '';
    if (preg_match('/^\/start\s+q(\d+)$/', $text, $m)) {
        $q = hs_mb_get_question((int) $m[1]);
        if (!$q) {
            hs_tg_api('sendMessage', array('chat_id' => $chatId, 'text' => "Savol topilmadi. Iltimos, " . hs_site_url() . " saytida ariza qoldiring."));
            return true;
        }
        hs_cache_set('mb:pending:' . $chatId, (int) $q['id'], 3600);
        // Bu odam — mijoz: keyingi yozishmalari arizalar chatlari ro'yxatiga tushmasin.
        hs_cache_set('mb:user:' . $chatId, 1, 30 * 86400);
        hs_tg_api('sendMessage', array(
            'chat_id' => $chatId,
            'text' => "Assalomu alaykum! Savolingiz bo'yicha operator siz bilan bog'lanadi.\n\n💬 " . mb_substr($q['text'], 0, 300)
                . "\n\nPastdagi tugmani bosib telefon raqamingizni yuboring.",
            'reply_markup' => json_encode(array('keyboard' => array(array(array('text' => '📱 Raqamni yuborish', 'request_contact' => true))), 'resize_keyboard' => true, 'one_time_keyboard' => true), JSON_UNESCAPED_UNICODE),
        ));
        return true;
    }
    if (!isset($msg['contact'])) {
        // Guruhdan kelgan mijozning boshqa xabarlari (rahmat va h.k.) — ro'yxatga olinmaydi,
        // /start esa odatdagidek ishlaydi (xodim bo'lib qolgan bo'lsa ham ro'yxatga tushadi).
        return $text !== '' && strpos($text, '/') !== 0 && hs_cache_get('mb:user:' . $chatId) && !hs_tg_get_chat($chatId);
    }
    $qid = hs_cache_get('mb:pending:' . $chatId);
    $c = $msg['contact'];
    // Faqat o'z raqami (tugma orqali) — birovning kontaktini ariza qilmaymiz. Guruhdan kelgan
    // mijozning ortiqcha kontakti (ariza allaqachon qoldirilgan) ham chatlar ro'yxatiga tushmasin.
    if (!$qid || !isset($c['user_id']) || (string) $c['user_id'] !== $chatId) {
        return $qid || (hs_cache_get('mb:user:' . $chatId) && !hs_tg_get_chat($chatId));
    }
    $q = hs_mb_get_question((int) $qid);
    $phone = preg_replace('/[^\d+]/', '', (string) $c['phone_number']);
    if ($phone !== '' && $phone[0] !== '+') {
        $phone = '+' . $phone;
    }
    $name = trim((isset($c['first_name']) ? $c['first_name'] : '') . ' ' . (isset($c['last_name']) ? $c['last_name'] : ''));
    $note = $q ? "Telegram guruhidagi savol: " . $q['text'] . ($q['context'] !== '' ? "\n(Post: " . mb_substr(preg_replace('/\s+/u', ' ', $q['context']), 0, 150) . ')' : '') : '';
    hs_db()->prepare('INSERT INTO leads(created_at, name, phone, branch, note, special, page, source, source_detail, ym_client) VALUES(?, ?, ?, ?, ?, 0, ?, ?, ?, ?)')
        ->execute(array(hs_now(), $name !== '' ? $name : 'Telegram mijozi', $phone, '', $note, 'telegram-guruh', 'telegram', 'medium=guruh;content=savol-' . (int) $qid, ''));
    $leadId = (int) hs_db()->lastInsertId();
    if ($q) {
        hs_db()->prepare('UPDATE mb_questions SET lead_id = ? WHERE id = ?')->execute(array($leadId, (int) $qid));
        hs_mb_sync_staff((int) $qid);
    }
    hs_db()->prepare('DELETE FROM cache WHERE key = ?')->execute(array('mb:pending:' . $chatId));
    hs_tg_deliver_lead($leadId, 'Telegram guruhidan');
    hs_tg_api('sendMessage', array('chat_id' => $chatId, 'text' => "✅ Rahmat! Raqamingiz qabul qilindi — operator tez orada qo'ng'iroq qiladi.",
        'reply_markup' => json_encode(array('remove_keyboard' => true))));
    return true;
}

/* ============================ vaqtli vazifalar ============================ */

/** Operator kerak bo'lgan, lekin javobsiz qolgan savollar bo'yicha bir martalik eslatma + katalogni yangilash. */
function hs_mb_tasks()
{
    $done = 0;
    if (hs_mb_setting('on') === '1' && function_exists('hs_is_work_time') && hs_is_work_time()) {
        $cut = date('Y-m-d H:i:s', time() - 60 * max(5, (int) hs_mb_setting('remind_m')));
        // Kuzatish rejimida bot javob bermagan — har bir savol xodimlarniki.
        $st = hs_db()->prepare("SELECT * FROM mb_questions WHERE answered_by = '' AND lead_id IS NULL AND reminded = 0
            AND ((status = 'javob' AND needs_operator = 1) OR status = 'kuzatish') AND created_at < ? AND created_at > ?");
        $st->execute(array($cut, date('Y-m-d H:i:s', time() - 86400)));
        foreach ($st->fetchAll() as $q) {
            hs_db()->prepare('UPDATE mb_questions SET reminded = 1 WHERE id = ?')->execute(array((int) $q['id']));
            $m = hs_db()->prepare('SELECT chat_id, message_id FROM mb_q_msgs WHERE q_id = ?');
            $m->execute(array((int) $q['id']));
            foreach ($m->fetchAll() as $row) {
                hs_tg_api('sendMessage', array('chat_id' => $row['chat_id'], 'text' => '⏰ Mijoz ' . max(1, (int) floor((time() - strtotime($q['created_at'])) / 60)) . " daqiqadan beri javob kutyapti (savol #" . (int) $q['id'] . ').',
                    'reply_parameters' => json_encode(array('message_id' => (int) $row['message_id'], 'allow_sending_without_reply' => true))));
                $done++;
            }
        }
    }
    // Kanal katalogi: 6 soatda bir marta oxirgi sahifa (webhook o'tkazib yuborgan postlar uchun).
    $last = strtotime((string) hs_setting('mb_backfill_at', '2000-01-01'));
    if (hs_mb_setting('on') === '1' && time() - $last > 6 * 3600) {
        hs_mb_backfill(hs_mb_setting('channel'), hs_mb_catalog_count() < 20 ? 15 : 1);
    }
    return $done;
}
