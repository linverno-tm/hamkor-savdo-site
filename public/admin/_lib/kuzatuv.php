<?php
/**
 * Raqobat kuzatuvi — boshqa do'konlarning ochiq Telegram kanallarini o'qish.
 *
 * Nima qiladi:
 *   1. Kanalning OCHIQ sahifasidan (https://t.me/s/<kanal>) yangi postlarni oladi.
 *      Buning uchun bot ham, akkaunt ham, ruxsat ham kerak emas — bu sahifani
 *      istalgan odam brauzerda ocha oladi. Yopiq guruhga kirish yo'li bu yerda
 *      YO'Q va ataylab qo'shilmagan.
 *   2. Har postni AI tahlil qiladi — MATNI VA RASMI bilan. Raqobatchilar narxni
 *      ko'pincha faqat rasmga yozadi ("12 OYGA 209 000 so'mdan"), matnda esa
 *      "changyutkich" degan so'z xolos. Ko'rinmagan narx o'ylab topilmaydi.
 *   3. ALOHIDA botning ALOHIDA guruhiga yuboradi: narxlarni to'plab (yarim soatda
 *      bir marta yoki kuniga bir marta) va jiddiy o'zgarishni darhol. Narxsiz
 *      e'lon (hazil rolik, "12 oy muddatga" degan umumiy gap) Telegram'ga
 *      yuborilmaydi — egasi so'radi: "menga qiziqmas unaqa e'lonlari".
 *
 * Nega alohida bot: mijozlar boti mijozlar guruhlarida o'tiradi. Bitta sozlama
 * xatosi bilan raqobatchi tahlili o'sha yerga tushib ketmasligi uchun bu bo'lim
 * mijozlar botining tokeniga ham, chat ro'yxatiga ham umuman tegmaydi.
 *
 * Nimani qilmaydi: ularning rasm/videosini ko'chirib olmaydi (mualliflik huquqi),
 * narxlarni o'zi o'zgartirmaydi — qaror odamniki.
 */

require_once __DIR__ . '/mijozbot.php';

/** Kuniga shuncha postdan ortig'i AI ga yuborilmaydi (bepul tarif chegarasi). */
define('HS_RQ_AI_LIMIT', 40);

function hs_rq_setting($key)
{
    $defaults = array(
        'on' => '0',
        'token' => '',
        'chat_id' => '',
        'chat_title' => '',
        'digest_hour' => '9',
        // darhol — yangi narxlar yarim soatda bir marta bitta xabarda; kunlik — digest_hour da.
        'narx_rejim' => 'darhol',
        'alerts' => '1',
        'alert_discount' => '30',
    );
    return (string) hs_setting('rq_' . $key, isset($defaults[$key]) ? $defaults[$key] : '');
}

function hs_rq_token()
{
    return trim(hs_rq_setting('token'));
}

function hs_rq_chat()
{
    return trim(hs_rq_setting('chat_id'));
}

function hs_rq_on()
{
    return hs_rq_setting('on') === '1' && hs_rq_token() !== '' && hs_rq_chat() !== '';
}

/* ============================== ikkinchi bot ============================== */

/** Telegram API — mijozlar botiniki emas, kuzatuv botining tokeni bilan. */
function hs_rq_api($method, $params = array())
{
    $token = hs_rq_token();
    if ($token === '') {
        return array(null, 'Kuzatuv botining tokeni kiritilmagan.');
    }
    list($code, $body) = hs_http(
        'POST',
        'https://api.telegram.org/bot' . $token . '/' . $method,
        array('Content-Type: application/x-www-form-urlencoded'),
        http_build_query($params),
        20
    );
    $j = json_decode((string) $body, true);
    if ($code !== 200 || !is_array($j) || empty($j['ok'])) {
        // Tokenni xato matniga qo'shmaymiz.
        $msg = is_array($j) && isset($j['description']) ? (string) $j['description'] : 'javob yo\'q';
        return array(null, "Telegram HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    return array(isset($j['result']) ? $j['result'] : true, '');
}

function hs_rq_bot_username()
{
    if (hs_rq_token() === '') {
        return '';
    }
    $cached = hs_cache_get('rq:bot');
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }
    list($me, $err) = hs_rq_api('getMe');
    $name = $err === '' && isset($me['username']) ? (string) $me['username'] : '';
    if ($name !== '') {
        hs_cache_set('rq:bot', $name, 3600);
    }
    return $name;
}

function hs_rq_send($text)
{
    $chat = hs_rq_chat();
    if ($chat === '') {
        return array(false, 'Guruh tanlanmagan.');
    }
    if (getenv('HS_DRY_RUN') === '1') {
        @file_put_contents(hs_data_dir() . '/kuzatuv.log', '[' . hs_now() . "] to {$chat}\n{$text}\n\n", FILE_APPEND);
        return array(true, '');
    }
    list($res, $err) = hs_rq_api('sendMessage', array(
        'chat_id' => $chat,
        'text' => mb_substr($text, 0, 4000),
        'disable_web_page_preview' => 'true',
    ));
    return array($err === '', $err);
}

/**
 * Bot qaysi guruhlarda ekanini aniqlash.
 *
 * Kuzatuv botiga webhook o'rnatmaymiz — u hech kimga javob bermaydi, faqat
 * yozadi. Shuning uchun guruh raqamini getUpdates orqali bir marta olamiz:
 * botni guruhga qo'shganingizda Telegram shu ro'yxatga yozib qo'yadi.
 */
function hs_rq_find_chats()
{
    list($upd, $err) = hs_rq_api('getUpdates', array('limit' => 100, 'timeout' => 0));
    if ($err !== '') {
        return array(array(), $err);
    }
    $out = array();
    foreach ((array) $upd as $u) {
        foreach (array('message', 'my_chat_member', 'channel_post', 'edited_message') as $k) {
            if (!isset($u[$k]['chat']['id'])) {
                continue;
            }
            $c = $u[$k]['chat'];
            $id = (string) $c['id'];
            $title = isset($c['title']) ? $c['title'] : trim((isset($c['first_name']) ? $c['first_name'] : '') . ' ' . (isset($c['last_name']) ? $c['last_name'] : ''));
            $out[$id] = array('id' => $id, 'title' => $title !== '' ? $title : $id, 'type' => isset($c['type']) ? $c['type'] : '');
        }
    }
    return array(array_values($out), '');
}

/* ================================ kanallar ================================ */

function hs_rq_channels($onlyActive = false)
{
    $sql = 'SELECT * FROM rq_channels' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY active DESC, username';
    return hs_db()->query($sql)->fetchAll();
}

/** Kanalni qo'shishdan oldin haqiqatan ochiq va mavjudligini tekshiramiz. */
function hs_rq_channel_add($username)
{
    $username = ltrim(trim((string) $username), "@");
    $username = preg_replace("#^https?://t[.]me/(s/)?#i", "", $username);
    $username = trim($username, "/");
    if (!preg_match("/^[A-Za-z0-9_]{4,64}$/", $username)) {
        return array(false, "Nomi noto'g'ri. Masalan: ishonch yoki t.me/ishonch");
    }
    $st = hs_db()->prepare("SELECT COUNT(*) FROM rq_channels WHERE username = ?");
    $st->execute(array($username));
    if ((int) $st->fetchColumn() > 0) {
        return array(false, "@" . $username . " ro'yxatda bor.");
    }
    $ua = array("User-Agent: Mozilla/5.0 (HamkorSavdo kuzatuv)");
    list($code, $html) = hs_http("GET", "https://t.me/s/" . $username, $ua, null, 20);
    $kind = "kanal";
    $title = "";
    if ($code === 200 && $html !== "" && strpos($html, "tgme_widget_message_wrap") !== false) {
        if (preg_match('#<meta property="og:title" content="([^"]*)"#', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, "UTF-8"));
        }
    } else {
        /* Guruhning ochiq sahifasi yo'q — Telegram guruhlarga t.me/s/ bermaydi.
           Bunday manbani kompyuterdagi o'quvchi dastur o'qiydi. */
        list($c2, $page) = hs_http("GET", "https://t.me/" . $username, $ua, null, 20);
        if ($c2 !== 200 || $page === "" || strpos($page, "tgme_page_extra") === false) {
            return array(false, "Bunday kanal yoki guruh topilmadi (HTTP {$code}/{$c2}).");
        }
        if (preg_match('#<meta property="og:title" content="([^"]*)"#', $page, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, "UTF-8"));
        }
        $kind = "guruh";
    }
    $ins = hs_db()->prepare("INSERT INTO rq_channels (username, title, kind, active, added_at) VALUES (?, ?, ?, 1, ?)");
    $ins->execute(array($username, $title, $kind, hs_now()));
    return array(true, ($title !== "" ? $title : "@" . $username) . " qo'shildi"
        . ($kind === "guruh" ? " — bu GURUH, uni kompyuterdagi o'quvchi dastur o'qiydi." : "."));
}

/* ================================= rasmlar ================================= */

/** Guruh postlari rasmlarining vaqtinchalik papkasi (veb orqali yopiq: _data). */
function hs_rq_rasm_dir()
{
    $dir = hs_data_dir() . '/rq-rasm';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

/** Fayl boshidan turini aniqlash. Rasm bo'lmasa — ''. */
function hs_rq_mime($b)
{
    if (strncmp($b, "\xFF\xD8\xFF", 3) === 0) {
        return 'image/jpeg';
    }
    if (strncmp($b, "\x89PNG", 4) === 0) {
        return 'image/png';
    }
    if (strncmp($b, 'RIFF', 4) === 0 && substr($b, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    return '';
}

/** O'quvchi dastur yuborgan rasmni saqlash. Qaytadi: fayl nomi yoki ''. */
function hs_rq_rasm_saqla($channel, $postId, $b64)
{
    $b = base64_decode((string) $b64, true);
    if ($b === false || strlen($b) > 1500000 || hs_rq_mime($b) === '') {
        return '';
    }
    $nom = preg_replace('/[^A-Za-z0-9_]/', '', $channel) . '-' . (int) $postId . '.img';
    return @file_put_contents(hs_rq_rasm_dir() . '/' . $nom, $b) ? $nom : '';
}

/**
 * Tahlil uchun rasm: [mime, base64] yoki null.
 * Kanal postida `rasm` — Telegram CDN manzili, guruh postida — saqlangan fayl nomi.
 */
function hs_rq_rasm_olish($post)
{
    $r = (string) $post['rasm'];
    if ($r === '') {
        return null;
    }
    if (strncmp($r, 'https://', 8) === 0) {
        list($code, $b) = hs_http('GET', $r, array('User-Agent: Mozilla/5.0 (HamkorSavdo kuzatuv)'), null, 20);
        if ($code !== 200 || strlen((string) $b) > 3000000) {
            return null;
        }
    } else {
        $f = hs_rq_rasm_dir() . '/' . basename($r);
        $b = is_file($f) ? (string) file_get_contents($f) : '';
    }
    $mime = hs_rq_mime((string) $b);
    return $mime !== '' ? array($mime, base64_encode($b)) : null;
}

/** Tahlildan keyin guruh rasmi o'chiriladi — raqobatchining rasmini saqlab yurmaymiz. */
function hs_rq_rasm_ochir($post)
{
    $r = (string) $post['rasm'];
    if ($r !== '' && strncmp($r, 'https://', 8) !== 0) {
        @unlink(hs_rq_rasm_dir() . '/' . basename($r));
    }
}

/**
 * Oldin RASMSIZ kelgan post endi rasm bilan kelsa — qayta tahlilga qo'yiladi.
 * Faqat yangi postlar (7 kun): eski narx endi kerak emas.
 * Oldin narx topilmagan bo'lsa, narx xabariga qaytadan kirishi uchun digested = 0.
 */
function hs_rq_rasm_qoshimcha($channel, $postId, $rasm)
{
    $st = hs_db()->prepare("UPDATE rq_posts SET rasm = ?, analyzed = 0, ai_error = '',
        digested = CASE WHEN price = 0 AND price_total = 0 THEN 0 ELSE digested END
        WHERE channel = ? AND post_id = ? AND rasm = '' AND posted_at > ?");
    $st->execute(array($rasm, $channel, (int) $postId, date('Y-m-d H:i:s', time() - 7 * 86400)));
    return $st->rowCount() > 0;
}

/* ============================= postlarni olish ============================= */

/**
 * Bitta kanaldan yangi postlar. Birinchi marta — oxirgi bir sahifa (~20 post),
 * keyin faqat oxirgi ko'rilganidan yangilari. Sahifadagi eski postlardan rasmi
 * yo'q holda saqlanganlariga rasm qo'shiladi (narxni rasmdan o'qish kiritilgan).
 */
function hs_rq_fetch($ch)
{
    $username = (string) $ch['username'];
    $last = (int) $ch['last_post_id'];
    list($code, $html) = hs_http('GET', 'https://t.me/s/' . $username, array('User-Agent: Mozilla/5.0 (HamkorSavdo kuzatuv)'), null, 20);
    if ($code !== 200 || $html === '') {
        $st = hs_db()->prepare('UPDATE rq_channels SET last_fetch = ?, last_error = ? WHERE id = ?');
        $st->execute(array(hs_now(), "sahifa ochilmadi (HTTP {$code})", $ch['id']));
        return array(0, "@{$username}: sahifa ochilmadi (HTTP {$code})");
    }
    $posts = hs_mb_parse_channel_page($html, $username);
    $saved = 0;
    $max = $last;
    $ins = hs_db()->prepare('INSERT OR IGNORE INTO rq_posts (channel, post_id, url, text, media, rasm, posted_at, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($posts as $p) {
        $max = max($max, (int) $p['id']);
        $rasm = isset($p['rasm']) ? (string) $p['rasm'] : '';
        if ((int) $p['id'] <= $last) {
            if ($rasm !== '') {
                hs_rq_rasm_qoshimcha($username, (int) $p['id'], $rasm);
            }
            continue;
        }
        $ins->execute(array($username, (int) $p['id'], hs_mb_post_url($username, (int) $p['id']),
            $p['text'], isset($p['media']) ? $p['media'] : '', $rasm, $p['at'], hs_now()));
        $saved += $ins->rowCount() > 0 ? 1 : 0;
    }
    $st = hs_db()->prepare('UPDATE rq_channels SET last_post_id = ?, last_fetch = ?, last_error = ? WHERE id = ?');
    $st->execute(array($max, hs_now(), '', $ch['id']));
    return array($saved, '');
}

function hs_rq_fetch_all()
{
    $n = 0;
    $errs = array();
    foreach (hs_rq_channels(true) as $ch) {
        if ($ch['kind'] !== 'kanal') {
            continue; // guruhni kompyuterdagi dastur yuboradi
        }
        list($k, $e) = hs_rq_fetch($ch);
        $n += $k;
        if ($e !== '') {
            $errs[] = $e;
        }
    }
    hs_set_setting('rq_last_fetch', (string) time());
    return array($n, $errs);
}

/* ================================== AI ================================== */

function hs_rq_schema()
{
    return array(
        'type' => 'object',
        'properties' => array(
            'kind' => array('type' => 'string', 'enum' => array('mahsulot', 'aksiya', 'yangi mahsulot', "do'kon yangiligi", 'boshqa')),
            'brand' => array('type' => 'string'),
            'model' => array('type' => 'string'),
            'summary' => array('type' => 'string'),
            'price' => array('type' => 'integer'),
            'months' => array('type' => 'integer'),
            'price_total' => array('type' => 'integer'),
            'discount' => array('type' => 'integer'),
            'instalment' => array('type' => 'string'),
            'ends_at' => array('type' => 'string'),
        ),
        'required' => array('kind', 'brand', 'model', 'summary', 'price', 'months', 'price_total', 'discount', 'instalment', 'ends_at'),
        'additionalProperties' => false,
    );
}

function hs_rq_system_prompt()
{
    return "Sen HAMKOR SAVDO (Andijon: texnika, tilla, mebel, skuterlar) uchun raqobat tahlilchisisan. "
        . "Senga boshqa do'konning Telegram e'loni beriladi: matni va, bo'lsa, RASMI. Vazifang — undagi FAKTLARNI ajratib olish.\n\n"
        . "Narx ko'pincha RASMDA bo'ladi, matnda emas. Rasmdagi yozuvlarni diqqat bilan o'qi.\n\n"
        . "Qoidalar:\n"
        . "- Faqat matnda yoki rasmda aniq YOZILGANINI yoz. Ko'rinmasa yoki o'qib bo'lmasa — 0 yoki bo'sh qoldir, taxmin qilma.\n"
        . "- price: OYIGA to'lanadigan summa, so'mda, faqat raqam. Rasmda \"12 OYGA 209 000 so'mdan\" -> price 209000, months 12. Matnda \"262.000 SOMDAN\" -> 262000.\n"
        . "- price_total: mahsulotning to'liq narxi — odatda oylik to'lov yonida alohida turgan kattaroq summa (masalan \"1 793 000\"). Yo'q bo'lsa 0.\n"
        . "- months: necha oyga. Yo'q bo'lsa 0.\n"
        . "- model: mahsulot brendi va nomi, rasmda yoki matnda qanday yozilgan bo'lsa (masalan \"AVALON changyutkich\"). Yo'q bo'lsa bo'sh.\n"
        . "- Bir e'londa bir nechta mahsulot bo'lsa — eng ko'zga tashlanadiganini yoz.\n"
        . "- kind: bitta mahsulot narxi — 'mahsulot'; chegirma yoki aksiya — 'aksiya'; yangi do'kon yoki filial — 'do'kon yangiligi'; "
        . "hazil rolik, tabrik, umumiy reklama — 'boshqa'.\n"
        . "- discount: eng katta chegirma foizi, raqamda (masalan 60). Yo'q bo'lsa 0.\n"
        . "- instalment: muddatli to'lov sharti qanday yozilgan bo'lsa shundayligicha (masalan \"0-0-6\", \"24 oygacha\"). Yo'q bo'lsa bo'sh.\n"
        . "- ends_at: aksiya tugash sanasi YYYY-MM-DD ko'rinishida. Yo'q bo'lsa bo'sh.\n"
        . "- summary: 3-6 so'z, faqat mavzu (masalan \"maishiy texnika va smartfonlar\"). Gap tuzma, reklama gapini ko'chirma, raqam yozma.";
}

/*
 * Bitta e'lonni AI ga yuborish — matni va rasmi bilan.
 *
 * Matn yuborishdan oldin telefon raqamlari o'chiriladi (hs_mb_scrub): guruhlarda
 * oddiy mijozlar ham raqamini qoldiradi, u tahlilga kerak emas.
 */
function hs_rq_ai($post)
{
    $text = "Kanal: @" . $post['channel'] . "\nSana: " . $post['posted_at']
        . "\nMedia: " . ($post['media'] !== '' ? $post['media'] : "yo'q")
        . "\n\nE'lon matni:\n\"\"\"" . mb_substr(hs_mb_scrub($post['text']), 0, 2000) . "\"\"\"";
    $img = hs_rq_rasm_olish($post);
    if ($img) {
        $text .= "\n\nRasm ilova qilingan — narxni undan o'qi.";
    }
    if (getenv('HS_MB_FAKE_AI')) {
        $fake = json_decode((string) @file_get_contents(getenv('HS_MB_FAKE_AI')), true);
        @file_put_contents(hs_data_dir() . '/kuzatuv-ai.log', $text . ($img ? "\n[rasm: " . $img[0] . ', ' . strlen($img[1]) . " belgi]" : '') . "\n\n", FILE_APPEND);
        return array(is_array($fake) ? $fake : null, is_array($fake) ? '' : "fake yo'q");
    }
    return hs_mb_provider() === 'gemini' ? hs_rq_ai_gemini($text, $img) : hs_rq_ai_claude($text, $img);
}

function hs_rq_ai_gemini($text, $img = null)
{
    $key = hs_mb_gemini_key();
    if ($key === '') {
        return array(null, 'Gemini kaliti kiritilmagan.');
    }
    $schema = hs_rq_schema();
    unset($schema['additionalProperties']);
    $schema['type'] = 'OBJECT';
    $schema['propertyOrdering'] = array_keys($schema['properties']);
    foreach ($schema['properties'] as $k => $v) {
        $schema['properties'][$k]['type'] = strtoupper($v['type']);
    }
    $body = array(
        'system_instruction' => array('parts' => array(array('text' => hs_rq_system_prompt()))),
        'contents' => array(array('role' => 'user', 'parts' => $img
            ? array(array('inline_data' => array('mime_type' => $img[0], 'data' => $img[1])), array('text' => $text))
            : array(array('text' => $text)))),
        'generationConfig' => array(
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
            'temperature' => 0.1,
            'maxOutputTokens' => 1200,
        ),
    );
    $url = HS_MB_GEMINI_API . rawurlencode(hs_mb_gemini_model()) . ':generateContent';
    list($code, $resp) = hs_http('POST', $url, array('Content-Type: application/json', 'x-goog-api-key: ' . $key), json_encode($body, JSON_UNESCAPED_UNICODE), 60);
    $j = json_decode((string) $resp, true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? (string) $j['error']['message'] : "javob yo'q";
        return array(null, "Gemini HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    $out = '';
    foreach (isset($j['candidates'][0]['content']['parts']) ? $j['candidates'][0]['content']['parts'] : array() as $part) {
        if (isset($part['text'])) {
            $out .= $part['text'];
        }
    }
    $d = json_decode($out, true);
    return is_array($d) && isset($d['kind']) ? array($d, '') : array(null, 'Gemini javobi tushunarsiz.');
}

function hs_rq_ai_claude($text, $img = null)
{
    $key = hs_mb_key();
    if ($key === '') {
        return array(null, 'Claude kaliti kiritilmagan.');
    }
    $model = hs_mb_setting('model');
    $body = array(
        'model' => $model,
        'max_tokens' => 1200,
        'system' => array(array('type' => 'text', 'text' => hs_rq_system_prompt())),
        'messages' => array(array('role' => 'user', 'content' => $img
            ? array(array('type' => 'image', 'source' => array('type' => 'base64', 'media_type' => $img[0], 'data' => $img[1])), array('type' => 'text', 'text' => $text))
            : $text)),
        'output_config' => array('effort' => 'low', 'format' => array('type' => 'json_schema', 'schema' => hs_rq_schema())),
    );
    $headers = array('Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01');
    if ($model === 'claude-opus-5') {
        $body['fallbacks'] = 'default';
        $headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
    }
    list($code, $resp) = hs_http('POST', HS_MB_API, $headers, json_encode($body, JSON_UNESCAPED_UNICODE), 60);
    $j = json_decode((string) $resp, true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? (string) $j['error']['message'] : "javob yo'q";
        return array(null, "Claude HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    $out = '';
    foreach (isset($j['content']) ? $j['content'] : array() as $block) {
        if (isset($block['type']) && $block['type'] === 'text') {
            $out .= $block['text'];
        }
    }
    $d = json_decode($out, true);
    return is_array($d) && isset($d['kind']) ? array($d, '') : array(null, 'Claude javobi tushunarsiz.');
}

/**
 * Muddatli to'lov necha oy: months, bo'lmasa sharti matnidagi eng katta son
 * ("0-0-12" -> 12, "12 oyga 0 so'm" -> 12, "24 oygacha" -> 24).
 */
function hs_rq_oy_soni($p)
{
    if ((int) $p['months'] > 0) {
        return (int) $p['months'];
    }
    $eng = 0;
    if (preg_match_all('/\d{1,2}/u', (string) $p['instalment'], $m)) {
        foreach ($m[0] as $n) {
            $eng = (int) $n <= 60 ? max($eng, (int) $n) : $eng;
        }
    }
    return $eng;
}

/** Tahlil qilinmagan postlar. Qaytadi: nechtasi tahlil qilindi. */
function hs_rq_analyze($limit = 10)
{
    $st = hs_db()->prepare("SELECT * FROM rq_posts WHERE analyzed = 0 AND ai_error = '' ORDER BY posted_at DESC LIMIT ?");
    $st->execute(array((int) $limit));
    $rows = $st->fetchAll();
    $n = 0;
    $u = hs_db()->prepare("UPDATE rq_posts SET analyzed = 1, ai_error = '', kind = ?, brand = ?, model = ?, summary = ?, price = ?, months = ?,
        price_total = ?, discount = ?, instalment = ?, ends_at = ?, important = ? WHERE channel = ? AND post_id = ?");
    foreach ($rows as $p) {
        list($d, $err) = hs_rq_ai($p);
        if ($d === null) {
            $e = hs_db()->prepare('UPDATE rq_posts SET ai_error = ? WHERE channel = ? AND post_id = ?');
            $e->execute(array(mb_substr($err, 0, 200), $p['channel'], $p['post_id']));
            // Kalit yo'q yoki limit tugagan bo'lsa qolganini ham urinib o'tirmaymiz.
            break;
        }
        $natija = array(
            'months' => isset($d['months']) ? (int) $d['months'] : 0,
            'instalment' => mb_substr((string) $d['instalment'], 0, 60),
        );
        /* "Jiddiy" — AI ning fikri emas, qat'iy qoida. Ilgari AI bergan belgi
           ishlatilardi va "changyutkich, 12 oy muddatga" ham "⚡ Diqqat" bo'lib
           kelardi. Endi faqat: chegirma chegaradan katta, muddat bizdan uzun
           yoki yangi do'kon. */
        $important = (int) $d['discount'] >= (int) hs_rq_setting('alert_discount')
            || hs_rq_oy_soni($natija) > hs_rq_oyimiz()
            || (string) $d['kind'] === "do'kon yangiligi" ? 1 : 0;
        $u->execute(array(
            (string) $d['kind'], mb_substr((string) $d['brand'], 0, 80),
            mb_substr(isset($d['model']) ? (string) $d['model'] : '', 0, 160),
            mb_substr((string) $d['summary'], 0, 400),
            isset($d['price']) ? (int) $d['price'] : 0, $natija['months'],
            isset($d['price_total']) ? (int) $d['price_total'] : 0,
            (int) $d['discount'], $natija['instalment'], mb_substr((string) $d['ends_at'], 0, 10),
            $important, $p['channel'], $p['post_id'],
        ));
        hs_rq_rasm_ochir($p);
        $n++;
    }
    return $n;
}

/* ============================ guruhga xabarlar ============================ */

function hs_rq_channel_title($username)
{
    static $map = null;
    if ($map === null) {
        $map = array();
        foreach (hs_rq_channels() as $c) {
            $map[$c['username']] = $c['title'] !== '' ? $c['title'] : '@' . $c['username'];
        }
    }
    return isset($map[$username]) ? $map[$username] : '@' . $username;
}

/** Bizning muddatli to'lovimiz necha oy (saytdagi sozlamadan). */
function hs_rq_oyimiz()
{
    $c = hs_content_published();
    return is_array($c) && isset($c['settings']['installmentMonthsMax'])
        ? (int) $c['settings']['installmentMonthsMax'] : 12;
}

/** "2026-09-27" -> "27-sentyabrgacha (4 kun)". Sana bo'lmasa bo'sh. */
function hs_rq_sana($iso)
{
    $ts = $iso !== '' ? strtotime($iso) : 0;
    if (!$ts) {
        return '';
    }
    $oylar = array('', 'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun',
        'iyul', 'avgust', 'sentyabr', 'oktyabr', 'noyabr', 'dekabr');
    $s = (int) date('j', $ts) . '-' . $oylar[(int) date('n', $ts)] . 'gacha';
    $qoldi = (int) floor(($ts - strtotime(date('Y-m-d'))) / 86400);
    if ($qoldi < 0) {
        return $s . ' (tugagan)';
    }
    if ($qoldi === 0) {
        return $s . ' (bugun oxirgi kun)';
    }
    if ($qoldi <= 14) {
        return $s . ' (' . $qoldi . ' kun qoldi)';
    }
    return $s;
}

function hs_rq_som($n)
{
    return number_format((int) $n, 0, '.', ' ') . " so'm";
}

/**
 * Bizning shartimiz bilan taqqoslash — faqat farq bo'lsa. "Teng" degan qator
 * hech narsa aytmaydi, shuning uchun yozilmaydi.
 */
function hs_rq_compare($p)
{
    $ular = hs_rq_oy_soni($p);
    if ($ular <= 0) {
        return '';
    }
    $biz = hs_rq_oyimiz();
    if ($ular > $biz) {
        return "Bizda {$biz} oy — ular uzunroq to'lov taklif qilyapti.";
    }
    if ($ular < $biz) {
        return "Bizda {$biz} oy — bizniki uzunroq.";
    }
    return '';
}

/** Birinchi harfni kattalashtirish (AI xulosani kichik harfda qaytaradi). */
function hs_rq_bosh_harf($s)
{
    return $s === '' ? '' : mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

/** Mahsulot nomi yoki mavzusi: eng qisqa aniq sarlavha. */
function hs_rq_sarlavha($p)
{
    if ($p['model'] !== '') {
        return $p['model'];
    }
    if ($p['summary'] !== '') {
        return hs_rq_bosh_harf($p['summary']);
    }
    $q = preg_split('/\R/u', trim($p['text']));
    return hs_rq_bosh_harf(mb_substr(trim($q[0]), 0, 70));
}

/**
 * Darhol ogohlantirish matni.
 *
 * Qoida: har bir fakt BIR marta va o'z nomi bilan tursin. Ilgari xulosa gapi
 * ham, pastdagi qator ham bir xil raqamni takrorlar edi va reklama tilida
 * yozilgan bo'lardi — o'qish qiyin edi.
 */
function hs_rq_alert_text($p)
{
    $s = '⚡ ' . hs_rq_channel_title($p['channel']) . "\n\n" . hs_rq_sarlavha($p) . "\n";
    if ((int) $p['price'] > 0) {
        $s .= "\nOyiga: " . hs_rq_som($p['price'])
            . ((int) $p['months'] > 0 ? ' × ' . (int) $p['months'] . ' oy' : '');
    }
    if ((int) $p['price_total'] > 0) {
        $s .= "\nNarxi: " . hs_rq_som($p['price_total']);
    }
    if ((int) $p['discount'] > 0) {
        $s .= "\nChegirma: " . (int) $p['discount'] . '% gacha';
    }
    if ($p['instalment'] !== '') {
        $s .= "\nMuddatli to'lov: " . $p['instalment'];
    }
    $sana = hs_rq_sana($p['ends_at']);
    if ($sana !== '') {
        $s .= "\nMuddat: " . $sana;
    }
    $taq = hs_rq_compare($p);
    if ($taq !== '') {
        $s .= "\n\n" . $taq;
    }
    return $s . "\n\n" . $p['url'];
}

/** Narxlar xabaridagi bitta qator — ixcham: nomi, oylik to'lov, to'liq narx, havola. */
function hs_rq_line($p)
{
    $q = array();
    if ((int) $p['price'] > 0) {
        $q[] = 'oyiga ' . hs_rq_som($p['price']) . ((int) $p['months'] > 0 ? ' × ' . (int) $p['months'] . ' oy' : '');
    }
    if ((int) $p['price_total'] > 0) {
        $q[] = 'narxi ' . hs_rq_som($p['price_total']);
    }
    if ((int) $p['discount'] > 0) {
        $q[] = (int) $p['discount'] . '% chegirma';
    }
    $sana = hs_rq_sana($p['ends_at']);
    if ($sana !== '') {
        $q[] = $sana;
    }
    return '• ' . hs_rq_sarlavha($p) . ($q ? "\n  " . implode(' · ', $q) : '') . "\n  " . $p['url'];
}

/** Muhim e'lonlar — darhol. Qaytadi: yuborilgan xabarlar soni. */
function hs_rq_alerts()
{
    if (!hs_rq_on() || hs_rq_setting('alerts') !== '1') {
        return 0;
    }
    /* Faqat so'nggi 2 kundagi e'lonlar. Yangi manba qo'shilganda uning oxirgi
       o'nlab posti birdaniga keladi — ba'zilari bir haftalik. Eski aksiyani
       "Diqqat" deb yuborish chalg'itadi; ular kunlik xulosaga tushadi. */
    $st = hs_db()->prepare('SELECT * FROM rq_posts WHERE analyzed = 1 AND important = 1 AND alerted = 0 AND posted_at > ? ORDER BY posted_at LIMIT 5');
    $st->execute(array(date('Y-m-d H:i:s', time() - 2 * 86400)));
    $rows = $st->fetchAll();
    $belgila = hs_db()->prepare('UPDATE rq_posts SET alerted = 1, digested = 1 WHERE channel = ? AND post_id = ?');
    /* Bitta aksiya bir necha post bo'lib chiqadi, ustiga bitta do'kon uni
       kanalida ham, har filial guruhida ham tashlaydi (ISHONCH: kanal va 3 ta
       guruh). Sharti bir xil e'lon ikkinchi marta yuborilmaydi — u jimgina
       kunlik xulosaga tushadi.
       Sharti: chegirma + oy soni + tugash sanasi. Sana bor bo'lsa, boshqa
       manbadagisi ham takror hisoblanadi — bir xil foiz, oy va sana tasodifan
       mos kelishi juda kam. Sana bo'lmasa — faqat o'sha manba ichida. */
    $xuddishu = hs_db()->prepare("SELECT COUNT(*) FROM rq_posts WHERE alerted = 1 AND posted_at > ?
        AND discount = ? AND months = ? AND ends_at = ? AND (channel = ? OR ends_at <> '')");
    $n = 0;
    foreach ($rows as $p) {
        $xuddishu->execute(array(date('Y-m-d H:i:s', time() - 14 * 86400),
            (int) $p['discount'], (int) $p['months'], $p['ends_at'], $p['channel']));
        if ((int) $xuddishu->fetchColumn() > 0) {
            $belgila->execute(array($p['channel'], $p['post_id']));
            continue;
        }
        list($ok) = hs_rq_send(hs_rq_alert_text($p));
        if (!$ok) {
            break;
        }
        $belgila->execute(array($p['channel'], $p['post_id']));
        $n++;
    }
    return $n;
}

/**
 * Matnni Telegram chegarasiga sig'adigan bo'laklarga ajratish.
 * Bo'lish faqat bo'limlar (do'konlar) va qatorlar orasida — e'lon o'rtasidan
 * kesilmaydi. Ilgari hammasi bitta xabar edi va 4000 belgida jimgina
 * kesilib qolardi: 80 ta e'londan yarmi yo'qolardi.
 */
function hs_rq_bolakla($bolimlar, $sarlavha, $chegara = 3800)
{
    $xabarlar = array();
    $joriy = $sarlavha;
    foreach ($bolimlar as $bolim) {
        $qism = "\n\n" . $bolim;
        if (mb_strlen($joriy . $qism) <= $chegara) {
            $joriy .= $qism;
            continue;
        }
        if (trim($joriy) !== '' && $joriy !== $sarlavha) {
            $xabarlar[] = $joriy;
            $joriy = '(davomi)';
            $qism = "\n\n" . $bolim;
        }
        // Bitta do'konning o'zi sig'masa — qatorlab.
        $joriy .= "\n";
        foreach (explode("\n", $bolim) as $qator) {
            if (mb_strlen($joriy . "\n" . $qator) > $chegara) {
                $xabarlar[] = $joriy;
                $joriy = '(davomi)';
            }
            $joriy .= "\n" . $qator;
        }
    }
    if (trim($joriy) !== '' && $joriy !== '(davomi)') {
        $xabarlar[] = $joriy;
    }
    return $xabarlar;
}

/**
 * Narxlar xabari. Egasiga raqobatchining NARXI kerak — shuning uchun:
 *  - narxsiz e'lonlar (hazil rolik, "12 oy muddatga" degan umumiy gap)
 *    Telegram'ga umuman yuborilmaydi, faqat panelda turadi;
 *  - narxlilari to'planib keladi: "darhol" rejimida yarim soatda bir marta
 *    (yangilari bo'lsa), "kunlik" rejimida belgilangan soatda;
 *  - faqat so'nggi 3 kundagilari — yangi manba qo'shilganda uning bir
 *    haftalik tarixi birdaniga guruhni to'ldirmasin.
 * Qaytadi: yuborilgan e'lonlar soni.
 */
function hs_rq_narxlar($force = false)
{
    if (!hs_rq_on()) {
        return 0;
    }
    hs_db()->exec('UPDATE rq_posts SET digested = 1 WHERE digested = 0 AND analyzed = 1 AND price = 0 AND price_total = 0');
    $st = hs_db()->prepare('UPDATE rq_posts SET digested = 1 WHERE digested = 0 AND analyzed = 1 AND posted_at <= ?');
    $st->execute(array(date('Y-m-d H:i:s', time() - 3 * 86400)));
    $kunlik = hs_rq_setting('narx_rejim') === 'kunlik';
    if (!$force) {
        if ($kunlik && ((int) date('G') < (int) hs_rq_setting('digest_hour') || hs_setting('rq_digest_last', '') === date('Y-m-d'))) {
            return 0;
        }
        if (!$kunlik && time() - (int) hs_setting('rq_narx_last', '0') < 1800) {
            return 0;
        }
    }
    $rows = hs_db()->query('SELECT * FROM rq_posts WHERE digested = 0 AND analyzed = 1 ORDER BY channel, posted_at LIMIT 200')->fetchAll();
    if ($kunlik) {
        hs_set_setting('rq_digest_last', date('Y-m-d'));
    }
    if (!$rows) {
        return 0;
    }
    $guruhlangan = array();
    foreach ($rows as $p) {
        $guruhlangan[$p['channel']][] = hs_rq_line($p);
    }
    $bolimlar = array();
    foreach ($guruhlangan as $kanal => $qatorlar) {
        $bolimlar[] = hs_rq_channel_title($kanal) . "\n" . implode("\n", $qatorlar);
    }
    $sarlavha = '💰 ' . ($kunlik ? 'Raqobatchilar narxlari — ' . date('d.m.Y') : 'Yangi narxlar') . ' · ' . count($rows) . ' ta';
    $xabarlar = hs_rq_bolakla($bolimlar, $sarlavha);
    foreach ($xabarlar as $i => $matn) {
        list($ok) = hs_rq_send($matn . (count($xabarlar) > 1 ? "\n\n[" . ($i + 1) . '/' . count($xabarlar) . ']' : ''));
        if (!$ok) {
            return 0;
        }
    }
    $b = hs_db()->prepare('UPDATE rq_posts SET digested = 1 WHERE channel = ? AND post_id = ?');
    foreach ($rows as $p) {
        $b->execute(array($p['channel'], $p['post_id']));
    }
    hs_set_setting('rq_narx_last', (string) time());
    return count($rows);
}

/* ====================== kompyuterdagi o'quvchi dastur ====================== */

/*
 * Guruhni bot o'qiy olmaydi — Telegram bunga yo'l bermaydi: bot faqat o'zi
 * a'zo bo'lgan chatni ko'radi. Shuning uchun do'kondagi kompyuterda kichik
 * dastur ishlaydi (tools/kuzatuv-oquvchi): u oddiy Telegram akkaunti bilan
 * ochiq guruhlarga a'zo bo'ladi, yangi xabarlarni o'qiydi va quyidagi kalit
 * bilan /api/kuzatuv.php ga yuboradi. Dastur hech qayerga hech narsa
 * yozmaydi, hech kimni qo'shmaydi — faqat o'qiydi.
 */

function hs_rq_ingest_key()
{
    return (string) hs_setting('rq_ingest_key', '');
}

function hs_rq_ingest_key_new()
{
    $k = hs_random_hex(24);
    hs_set_setting('rq_ingest_key', $k);
    return $k;
}

/**
 * Dastur yuborgan xabarlarni yozish. Qaytadi: [yozilgan, o'tkazilgan].
 * `rasm` — base64 (dastur 1000px gacha kichraytirib yuboradi); tahlildan keyin o'chiriladi.
 */
function hs_rq_ingest($posts)
{
    $ins = hs_db()->prepare("INSERT OR IGNORE INTO rq_posts (channel, post_id, via, url, text, media, rasm, posted_at, fetched_at) VALUES (?, ?, 'guruh', ?, ?, ?, ?, ?, ?)");
    $n = 0;
    $skip = 0;
    foreach ((array) $posts as $p) {
        $src = isset($p['source']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string) $p['source']) : '';
        $id = isset($p['post_id']) ? (int) $p['post_id'] : 0;
        $text = isset($p['text']) ? trim((string) $p['text']) : '';
        if ($src === '' || $id <= 0 || $text === '') {
            $skip++;
            continue;
        }
        $rasm = !empty($p['rasm']) ? hs_rq_rasm_saqla($src, $id, $p['rasm']) : '';
        $ts = isset($p['posted_at']) ? strtotime((string) $p['posted_at']) : 0;
        $media = isset($p['media']) && in_array($p['media'], array('foto', 'video'), true) ? $p['media'] : '';
        $ins->execute(array($src, $id, 'https://t.me/' . $src . '/' . $id,
            mb_substr($text, 0, 4000), $media, $rasm, $ts ? date('Y-m-d H:i:s', $ts) : hs_now(), hs_now()));
        if ($ins->rowCount() > 0) {
            $n++;
        } elseif ($rasm !== '' && hs_rq_rasm_qoshimcha($src, $id, $rasm)) {
            $n++; // oldin rasmsiz kelgan — endi rasmi bilan qayta tahlil qilinadi
        } elseif ($rasm !== '') {
            @unlink(hs_rq_rasm_dir() . '/' . $rasm);
        }
    }
    if ($n) {
        hs_set_setting('rq_ingest_at', hs_now());
    }
    return array($n, $skip);
}

/** Dasturga beriladigan ro'yxat: qaysi guruhlarni, qaysi xabardan keyin. */
function hs_rq_group_list()
{
    $out = array();
    $st = hs_db()->prepare('SELECT MAX(post_id) FROM rq_posts WHERE channel = ?');
    foreach (hs_rq_channels(true) as $c) {
        if ($c['kind'] !== 'guruh') {
            continue;
        }
        $st->execute(array($c['username']));
        $out[] = array('username' => $c['username'], 'title' => $c['title'], 'last_id' => (int) $st->fetchColumn());
    }
    return $out;
}

/** Cron: yig'ish → tahlil → jiddiylarini darhol → narxlar. */
function hs_rq_tasks()
{
    if (!hs_rq_on() || !hs_rq_channels(true)) {
        return 0;
    }
    $done = 0;
    // Sahifani tez-tez so'ramaymiz: yarim soatda bir marta yetarli.
    if (time() - (int) hs_setting('rq_last_fetch', '0') >= 1800) {
        list($n) = hs_rq_fetch_all();
        $done += $n;
    }
    hs_rq_analyze(10);
    hs_rq_alerts();
    hs_rq_narxlar();
    // Tahlil qilinmay qolgan guruh rasmlari (xato bo'lsa) 2 kundan ortiq yotmasin.
    foreach (glob(hs_rq_rasm_dir() . '/*.img') ?: array() as $f) {
        if (filemtime($f) < time() - 2 * 86400) {
            @unlink($f);
        }
    }
    return $done;
}
