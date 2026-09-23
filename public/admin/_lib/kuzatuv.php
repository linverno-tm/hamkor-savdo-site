<?php
/**
 * Raqobat kuzatuvi — boshqa do'konlarning ochiq Telegram kanallarini o'qish.
 *
 * Nima qiladi:
 *   1. Kanalning OCHIQ sahifasidan (https://t.me/s/<kanal>) yangi postlarni oladi.
 *      Buning uchun bot ham, akkaunt ham, ruxsat ham kerak emas — bu sahifani
 *      istalgan odam brauzerda ocha oladi. Yopiq guruhga kirish yo'li bu yerda
 *      YO'Q va ataylab qo'shilmagan.
 *   2. Har postni AI tahlil qiladi: aksiya turi, chegirma foizi, muddatli to'lov
 *      sharti, tugash sanasi. Narx faqat rasm yoki videoda bo'lsa — bo'sh qoladi,
 *      o'ylab topilmaydi.
 *   3. Xulosani ALOHIDA botning ALOHIDA guruhiga yuboradi.
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

/* ============================= postlarni olish ============================= */

/**
 * Bitta kanaldan yangi postlar. Birinchi marta — oxirgi bir sahifa (~20 post),
 * keyin faqat oxirgi ko'rilganidan yangilari.
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
    $ins = hs_db()->prepare('INSERT OR IGNORE INTO rq_posts (channel, post_id, url, text, media, posted_at, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($posts as $p) {
        $max = max($max, (int) $p['id']);
        if ((int) $p['id'] <= $last) {
            continue;
        }
        $ins->execute(array($username, (int) $p['id'], hs_mb_post_url($username, (int) $p['id']),
            $p['text'], isset($p['media']) ? $p['media'] : '', $p['at'], hs_now()));
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
            'discount' => array('type' => 'integer'),
            'instalment' => array('type' => 'string'),
            'ends_at' => array('type' => 'string'),
            'important' => array('type' => 'boolean'),
        ),
        'required' => array('kind', 'brand', 'model', 'summary', 'price', 'months', 'discount', 'instalment', 'ends_at', 'important'),
        'additionalProperties' => false,
    );
}

function hs_rq_system_prompt()
{
    $oy = 12;
    $c = hs_content_published();
    if (is_array($c) && isset($c['settings']['installmentMonthsMax'])) {
        $oy = (int) $c['settings']['installmentMonthsMax'];
    }
    return "Sen HAMKOR SAVDO (Andijon: texnika, tilla, mebel, skuterlar) uchun raqobat tahlilchisisan. "
        . "Senga boshqa do'konning Telegram e'loni beriladi. Vazifang — undagi FAKTLARNI ajratib olish.\n\n"
        . "Qoidalar:\n"
        . "- Faqat matnda YOZILGANINI yoz. Narx, foiz yoki sana matnda bo'lmasa — bo'sh qoldir, taxmin qilma.\n"
        . "- Ko'p e'lonlarda narx faqat rasm yoki videoda bo'ladi; bu normal, bo'sh qoldir.\n"
        . "- model: mahsulot nomi va modeli matnda qanday yozilgan bo'lsa shundayligicha. Yo'q bo'lsa bo'sh.\n"
        . "- price: OYIGA to'lanadigan summa, so'mda, faqat raqam (\"262.000 SOMDAN\" -> 262000). Yo'q bo'lsa 0.\n"
        . "- months: necha oyga (\"12 OYGA\" -> 12). Yo'q bo'lsa 0.\n"
        . "- kind: bitta mahsulotning narxi e'lon qilingan bo'lsa 'mahsulot'.\n"
        . "- discount: eng katta chegirma foizi, raqamda (masalan 60). Yo'q bo'lsa 0.\n"
        . "- instalment: muddatli to'lov sharti matnda qanday yozilgan bo'lsa shundayligicha (masalan \"0-0-6\", \"24 oygacha\"). Yo'q bo'lsa bo'sh.\n"
        . "- ends_at: aksiya tugash sanasi YYYY-MM-DD ko'rinishida. Yo'q bo'lsa bo'sh.\n"
        . "- summary: 3-6 so'z, faqat mavzu (masalan \"maishiy texnika va smartfonlar\"). Gap tuzma, reklama gapini ko'chirma, raqamlarni bu yerga yozma.\n"
        . "- important: true — agar bu bizga darhol ta'sir qiladigan narsa bo'lsa: "
        . "chegirma 30% dan katta, muddatli to'lov bizning {$oy} oyimizdan uzoqroq yoki boshlang'ich to'lovsiz, "
        . "yangi do'kon ochilishi, yoki tarmoq bo'ylab katta aksiya. Oddiy mahsulot e'loni bo'lsa false.";
}

/** Bitta postni tahlil qilish. Qaytadi: [massiv yoki null, xato]. */
/*
 * Bitta e'lonni AI ga yuborish.
 *
 * Matn yuborishdan oldin telefon raqamlari o'chiriladi (hs_mb_scrub). Kanal
 * e'lonlarida bu do'konning o'z raqami bo'ladi, lekin GURUHlarda oddiy
 * mijozlar ham yozadi va raqamini qoldiradi — ularning raqami tahlil uchun
 * kerak emas, demak umuman yuborilmasligi kerak.
 */
function hs_rq_ai($post)
{
    $text = "Kanal: @" . $post['channel'] . "\nSana: " . $post['posted_at']
        . "\nMedia: " . ($post['media'] !== '' ? $post['media'] : 'yo\'q')
        . "\n\nE'lon matni:\n\"\"\"" . mb_substr(hs_mb_scrub($post['text']), 0, 2000) . "\"\"\"";
    if (getenv('HS_MB_FAKE_AI')) {
        $fake = json_decode((string) @file_get_contents(getenv('HS_MB_FAKE_AI')), true);
        @file_put_contents(hs_data_dir() . '/kuzatuv-ai.log', $text . "\n\n", FILE_APPEND);
        return array(is_array($fake) ? $fake : null, is_array($fake) ? '' : "fake yo'q");
    }
    return hs_mb_provider() === 'gemini' ? hs_rq_ai_gemini($text) : hs_rq_ai_claude($text);
}

function hs_rq_ai_gemini($text)
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
        'contents' => array(array('role' => 'user', 'parts' => array(array('text' => $text)))),
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

function hs_rq_ai_claude($text)
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
        'messages' => array(array('role' => 'user', 'content' => $text)),
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

/** Tahlil qilinmagan postlar. Qaytadi: nechtasi tahlil qilindi. */
function hs_rq_analyze($limit = 10)
{
    $st = hs_db()->prepare("SELECT * FROM rq_posts WHERE analyzed = 0 AND ai_error = '' ORDER BY posted_at DESC LIMIT ?");
    $st->execute(array((int) $limit));
    $rows = $st->fetchAll();
    $n = 0;
    foreach ($rows as $p) {
        list($d, $err) = hs_rq_ai($p);
        if ($d === null) {
            $u = hs_db()->prepare('UPDATE rq_posts SET ai_error = ? WHERE channel = ? AND post_id = ?');
            $u->execute(array(mb_substr($err, 0, 200), $p['channel'], $p['post_id']));
            // Kalit yo'q yoki limit tugagan bo'lsa qolganini ham urinib o'tirmaymiz.
            break;
        }
        $chegara = (int) hs_rq_setting('alert_discount');
        $important = !empty($d['important']) || (int) $d['discount'] >= $chegara ? 1 : 0;
        $u = hs_db()->prepare('UPDATE rq_posts SET analyzed = 1, ai_error = \'\', kind = ?, brand = ?, model = ?, summary = ?, price = ?, months = ?, discount = ?, instalment = ?, ends_at = ?, important = ? WHERE channel = ? AND post_id = ?');
        $u->execute(array(
            (string) $d['kind'], mb_substr((string) $d['brand'], 0, 80),
            mb_substr(isset($d['model']) ? (string) $d['model'] : '', 0, 160),
            mb_substr((string) $d['summary'], 0, 400),
            isset($d['price']) ? (int) $d['price'] : 0, isset($d['months']) ? (int) $d['months'] : 0,
            (int) $d['discount'], mb_substr((string) $d['instalment'], 0, 60), mb_substr((string) $d['ends_at'], 0, 10),
            $important, $p['channel'], $p['post_id'],
        ));
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
 * Bizning shartimiz bilan taqqoslash — faqat oy soni aniq bo'lsa.
 * Maqsad: xabarni o'qigan odam "bu bizga yaxshimi yomonmi" deb o'ylab
 * o'tirmasin, javob bir qatorda tursin.
 */
function hs_rq_compare($p)
{
    $ular = (int) $p['months'];
    if ($ular <= 0 && $p['instalment'] !== '' && preg_match('/(\d{1,2})/u', $p['instalment'], $m)) {
        $ular = (int) $m[1];
    }
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
    return "Bizda ham {$biz} oy — teng.";
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

/** Kunlik xulosadagi bitta qator — ixcham, bitta e'lon bitta satr. */
function hs_rq_line($p)
{
    $q = array();
    if ((int) $p['price'] > 0) {
        $q[] = 'oyiga ' . hs_rq_som($p['price']) . ((int) $p['months'] > 0 ? ' × ' . (int) $p['months'] . ' oy' : '');
    }
    if ((int) $p['discount'] > 0) {
        $q[] = (int) $p['discount'] . '% chegirma';
    }
    if ($p['instalment'] !== '' && (int) $p['price'] === 0) {
        $q[] = $p['instalment'];
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
    $rows = hs_db()->query('SELECT * FROM rq_posts WHERE analyzed = 1 AND important = 1 AND alerted = 0 ORDER BY posted_at LIMIT 5')->fetchAll();
    $belgila = hs_db()->prepare('UPDATE rq_posts SET alerted = 1, digested = 1 WHERE channel = ? AND post_id = ?');
    /* Bitta aksiyani do'kon bir necha post qilib chiqaradi: matni har xil,
       sharti bir xil. Har biriga alohida ogohlantirish yuborilsa, guruhni
       bir haftada hech kim o'qimay qo'yadi. Sharti bir xil e'lon ikkinchi
       marta yuborilmaydi — u jimgina kunlik xulosaga tushadi. */
    $xuddishu = hs_db()->prepare('SELECT COUNT(*) FROM rq_posts WHERE channel = ? AND alerted = 1
        AND discount = ? AND instalment = ? AND ends_at = ? AND posted_at > ?');
    $n = 0;
    foreach ($rows as $p) {
        $xuddishu->execute(array($p['channel'], (int) $p['discount'], $p['instalment'], $p['ends_at'],
            date('Y-m-d H:i:s', time() - 14 * 86400)));
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

/** Kunlik xulosa — belgilangan soatda, kuniga bir marta, do'konlar bo'yicha. */
function hs_rq_digest($force = false)
{
    if (!hs_rq_on()) {
        return false;
    }
    if (!$force) {
        if ((int) date('G') < (int) hs_rq_setting('digest_hour')) {
            return false;
        }
        if (hs_setting('rq_digest_last', '') === date('Y-m-d')) {
            return false;
        }
    }
    $rows = hs_db()->query('SELECT * FROM rq_posts WHERE digested = 0 AND analyzed = 1 ORDER BY channel, posted_at')->fetchAll();
    hs_set_setting('rq_digest_last', date('Y-m-d'));
    if (!$rows) {
        return false;
    }
    $guruhlangan = array();
    foreach ($rows as $p) {
        if ($p['kind'] === 'boshqa') {
            continue;
        }
        $guruhlangan[$p['channel']][] = hs_rq_line($p);
    }
    if ($guruhlangan) {
        $qismlar = array();
        $jami = 0;
        foreach ($guruhlangan as $kanal => $qatorlar) {
            $jami += count($qatorlar);
            $qismlar[] = hs_rq_channel_title($kanal) . "\n" . implode("\n", $qatorlar);
        }
        $text = "Raqobatchilar — " . date('d.m.Y') . "\n\n" . implode("\n\n", $qismlar)
            . "\n\nJami {$jami} ta e'lon.";
        list($ok) = hs_rq_send($text);
        if (!$ok) {
            return false;
        }
    }
    $u = hs_db()->prepare('UPDATE rq_posts SET digested = 1 WHERE digested = 0 AND analyzed = 1');
    $u->execute();
    return (bool) $guruhlangan;
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

/** Dastur yuborgan xabarlarni yozish. Qaytadi: [yozilgan, o'tkazilgan]. */
function hs_rq_ingest($posts)
{
    $ins = hs_db()->prepare("INSERT OR IGNORE INTO rq_posts (channel, post_id, via, url, text, media, posted_at, fetched_at) VALUES (?, ?, 'guruh', ?, ?, ?, ?, ?)");
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
        $ts = isset($p['posted_at']) ? strtotime((string) $p['posted_at']) : 0;
        $media = isset($p['media']) && in_array($p['media'], array('foto', 'video'), true) ? $p['media'] : '';
        $ins->execute(array($src, $id, 'https://t.me/' . $src . '/' . $id,
            mb_substr($text, 0, 4000), $media, $ts ? date('Y-m-d H:i:s', $ts) : hs_now(), hs_now()));
        $n += $ins->rowCount() > 0 ? 1 : 0;
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

/** Cron: yig'ish → tahlil → ogohlantirish → kunlik xulosa. */
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
    hs_rq_digest();
    return $done;
}
