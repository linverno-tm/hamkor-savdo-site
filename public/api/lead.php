<?php
/**
 * HAMKOR SAVDO — saytdagi ariza formasini qabul qilib, Telegramga yuboradi.
 *
 * Ikki xil javob qaytaradi:
 *   - oddiy forma yuborilsa  -> /rahmat/ sahifasiga o'tkazadi (JavaScriptsiz ham ishlaydi)
 *   - fetch() so'rovi bo'lsa -> JSON qaytaradi, sahifa javobni o'z joyida ko'rsatadi
 *
 * Xatolik yuz bersa /rahmat/ ga o'tkazmaydi, o'zi xabar chiqaradi — chunki
 * /rahmat/ sahifasi doim "qabul qilindi" deb yozadi va bu yolg'on bo'lib qolardi.
 *
 * Sozlash: secrets.example.php dan nusxa olib, shu papkaga secrets.php nomi
 * bilan qo'ying va bot ma'lumotlarini yozing.
 *
 * PHP 7.4 va undan yuqori versiyalarda ishlaydi.
 */

date_default_timezone_set('Asia/Tashkent');

$MAX_NAME = 80;
$MAX_PHONE = 24;
$MAX_NOTE = 500;
$PHONE_MIN_DIGITS = 9;
/** Bitta IP dan shuncha soniyada bittadan ko'p ariza qabul qilinmaydi. */
$THROTTLE_SECONDS = 20;

$PHONE_DISPLAY = '+998 33 342 08 80';
$PHONE_LINK = '+998333420880';

/* Har bir filial uchun nom va xeshteg. Xeshteg call-center guruhida
   qidirish uchun: operator #asaka deb qidirsa, faqat o'z filialining
   arizalarini ko'radi.
   Asosiy manba — build paytida yasalgan api/sayt.json (admin panelda
   qo'shilgan filial ham shu yerga tushadi). Pastdagi ro'yxat — zaxira. */
$FILIALLAR = array(
    'shahrixon-ozodbek'  => array("Shahrixon — Ozodbek savdo markazi", '#shahrixon_ozodbek'),
    'shahrixon-bog'      => array("Shahrixon — Markaziy istirohat bog'i yonida", '#shahrixon_bog'),
    'asaka-umid'         => array("Asaka — Makro supermarketi, 2-qavat", '#asaka'),
    'andijon-amir-temur' => array("Andijon — Amir Temur shoh ko'chasi, 62", '#andijon'),
    /* Filial emas, lekin ro'yxatdagi variant: boshqa viloyatdagi mijoz.
       Bunday arizada operator avval yetkazish shartlarini aytishi kerak. */
    'boshqa-viloyat'     => array('Boshqa viloyat — yetkazib berish', '#boshqa_viloyat'),
);
$saytJson = __DIR__ . '/sayt.json';
if (is_file($saytJson)) {
    $sayt = json_decode((string) file_get_contents($saytJson), true);
    if (is_array($sayt) && !empty($sayt['branches'])) {
        $fromSite = array();
        foreach ($sayt['branches'] as $b) {
            $tag = isset($FILIALLAR[$b['id']]) ? $FILIALLAR[$b['id']][1] : '#' . str_replace('-', '_', $b['id']);
            $fromSite[$b['id']] = array($b['city'] . ' — ' . $b['landmark'], $tag);
        }
        $fromSite['boshqa-viloyat'] = $FILIALLAR['boshqa-viloyat'];
        $FILIALLAR = $fromSite;
    }
}

/** mbstring bo'lmasa ham ishlashi uchun. */
function hs_cut($s, $len)
{
    return function_exists('mb_substr') ? mb_substr($s, 0, $len, 'UTF-8') : substr($s, 0, $len);
}
function hs_len($s)
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function hs_wants_json()
{
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    return strpos($accept, 'application/json') !== false;
}

/** Xatolikni foydalanuvchiga ko'rsatadi va to'xtaydi. */
function hs_fail($status, $message)
{
    global $PHONE_DISPLAY, $PHONE_LINK;
    http_response_code($status);
    if (hs_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => $message), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $tel = htmlspecialchars($PHONE_LINK, ENT_QUOTES, 'UTF-8');
    $shown = htmlspecialchars($PHONE_DISPLAY, ENT_QUOTES, 'UTF-8');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex">'
        . '<title>Ariza yuborilmadi — HAMKOR SAVDO</title><style>'
        . 'body{margin:0;min-height:100vh;display:grid;place-items:center;background:#5a3089;'
        . 'color:#fff;font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;padding:2rem}'
        . '.box{max-width:34rem;text-align:center}'
        . 'h1{font-size:1.9rem;line-height:1.2;margin:0 0 .75rem}'
        . 'p{color:#cbb8e0;margin:0 0 1.75rem}'
        . 'a{display:inline-block;margin:.25rem;padding:.85rem 1.5rem;border-radius:999px;'
        . 'text-decoration:none;font-weight:700}'
        . '.y{background:#dfd01f;color:#1a1130}'
        . '.o{border:2px solid rgba(255,255,255,.5);color:#fff}'
        . '</style></head><body><div class="box">'
        . '<h1>Ariza yuborilmadi</h1><p>' . $safe . '</p>'
        . '<a class="y" href="tel:' . $tel . '">' . $shown . '</a>'
        . '<a class="o" href="/">Bosh sahifaga qaytish</a>'
        . '</div></body></html>';
    exit;
}

function hs_ok()
{
    if (hs_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => true), JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Kirill sahifadan kelgan odam kirill "rahmat" sahifasiga qaytsin.
    $ref = isset($_SERVER['HTTP_REFERER']) ? (string) parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) : '';
    header('Location: ' . (strpos($ref, '/uz-kr') === 0 ? '/uz-kr/rahmat/' : '/rahmat/'), true, 303);
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_fail(405, "Bu sahifa to'g'ridan-to'g'ri ochilmaydi.");
}

/* Ariza shu saytdagi formadan kelganini tekshiramiz.
 *
 * Bu spam uchun devor emas — Origin sarlavhasini soxtalashtirish qiyin emas.
 * Lekin boshqa saytga qo'yilgan forma yoki oddiy skript aynan shu yerda
 * to'xtaydi, va bu bir necha qator kodga arziydi.
 *
 * DIQQAT: sarlavha BO'LMASA o'tkazib yuboramiz. Ba'zi brauzerlar va ichki
 * tarmoq proksilari uni yubormaydi; yo'qligi uchun rad etsak, haqiqiy
 * mijozning arizasi yo'qoladi. Faqat sarlavha bor va BEGONA bo'lsa rad
 * etamiz.
 */
function hs_host($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    return is_string($host) ? strtolower($host) : '';
}

$ownHost = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$ownHost = preg_replace('/:\d+$/', '', $ownHost);
$allowedHosts = array($ownHost, 'hamkorsavdo.uz', 'www.hamkorsavdo.uz');

$senderHost = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $senderHost = hs_host($_SERVER['HTTP_ORIGIN']);
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $senderHost = hs_host($_SERVER['HTTP_REFERER']);
}

if ($senderHost !== '' && !in_array($senderHost, $allowedHosts, true)) {
    hs_fail(403, "Ariza qabul qilinmadi. Iltimos, saytdagi formadan foydalaning.");
}

/* Botlarga tuzoq: odam ko'rmaydigan maydon. To'ldirilgan bo'lsa, jimgina
   "muvaffaqiyat" qaytaramiz — bot moslashishni o'rganmasin. */
if (trim(isset($_POST['website']) ? $_POST['website'] : '') !== '') {
    hs_ok();
}

$name   = hs_cut(trim(isset($_POST['name']) ? $_POST['name'] : ''), $MAX_NAME);
$phone  = hs_cut(trim(isset($_POST['phone']) ? $_POST['phone'] : ''), $MAX_PHONE);
$note   = hs_cut(trim(isset($_POST['note']) ? $_POST['note'] : ''), $MAX_NOTE);
$branch = isset($_POST['branch']) ? $_POST['branch'] : '';
$page   = hs_cut(trim(isset($_POST['page']) ? $_POST['page'] : ''), 200);
/* Mijoz saytga qayerdan kelgan (instagram, google, telegram...) — brauzerdagi
   kichik skript to'ldiradi. Faqat xavfsiz belgilar qoladi. */
$source = isset($_POST['src']) ? strtolower(preg_replace('/[^a-z0-9._\-]/i', '', (string) $_POST['src'])) : '';
$source = substr($source, 0, 60);

if (hs_len($name) < 2) {
    hs_fail(422, "Ismingiz kiritilmagan. Iltimos, formani qayta to'ldiring.");
}
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < $PHONE_MIN_DIGITS) {
    hs_fail(422, "Telefon raqami to'liq kiritilmagan. Iltimos, qayta urinib ko'ring.");
}

/* Telegram +998... ko'rinishidagi raqamni o'zi bosiladigan qilib beradi,
   shuning uchun operator qo'lda ko'chirmasin. */
$tel = $phone;
if (strlen($digits) === 9) {
    $tel = '+998' . $digits;
} elseif (strlen($digits) === 12 && substr($digits, 0, 3) === '998') {
    $tel = '+' . $digits;
} elseif (strlen($digits) > 9) {
    $tel = '+' . $digits;
}

/* Oddiy tezlik cheklovi: bitta IP ketma-ket ariza yubormasin. */
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'nomalum';
$stamp = sys_get_temp_dir() . '/hs_lead_' . md5($ip);
if (is_file($stamp) && (time() - (int) filemtime($stamp)) < $THROTTLE_SECONDS) {
    hs_fail(429, "Arizangiz allaqachon yuborilgan. Biroz kuting yoki qo'ng'iroq qiling.");
}
@touch($stamp);

$configFile = __DIR__ . '/secrets.php';
$secrets = is_file($configFile) ? require $configFile : array();
$token = isset($secrets['token']) ? $secrets['token'] : '';
$chatId = isset($secrets['chat_id']) ? $secrets['chat_id'] : '';

$known = isset($FILIALLAR[$branch]);
$special = isset($_POST['special']) && $_POST['special'] !== '';

/* Ariza avval admin panel bazasiga yoziladi — Telegram ishlamay qolsa ham
   yo'qolmasin. Baza bo'lmasa (masalan panel hali o'rnatilmagan), jimgina
   faqat Telegram bilan davom etamiz. */
$leadId = 0;
$adminBoot = __DIR__ . '/../admin/_lib/bootstrap.php';
if (is_file($adminBoot)) {
    try {
        require_once $adminBoot;
        $ins = hs_db()->prepare('INSERT INTO leads(created_at, name, phone, branch, note, special, page, source) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
        $ins->execute(array(date('Y-m-d H:i:s'), $name, $tel, $known ? $branch : '', $note, $special ? 1 : 0, $page, $source));
        $leadId = (int) hs_db()->lastInsertId();
    } catch (Throwable $e) {
        error_log('HAMKOR SAVDO: ariza bazaga yozilmadi: ' . $e->getMessage());
    }
}

if (($token === '' || $chatId === '') && $leadId === 0) {
    hs_fail(503, "Ariza qabul qilish vaqtincha ishlamayapti. Iltimos, telefon orqali bog'laning.");
}
$filialNomi = $known ? $FILIALLAR[$branch][0] : 'tanlanmagan';
$filialTag  = $known ? $FILIALLAR[$branch][1] : '#filial_tanlanmagan';

/* Filial birinchi qatorda va xeshteg bilan — operator qaysi filialga
   tegishli ekanini bir qarashda ko'radi. */
/* Do'konda yo'q mahsulot uchun kelgan ariza — boshqacha ish oqimi: operator
   avval mahsulotni va narxini aniqlashi kerak, faqat keyin shartlarni aytadi.
   Shuning uchun u birinchi qatorda, xeshteg bilan ajratiladi. */
$sarlavha = '🟣 YANGI ARIZA — ' . $filialTag;
if ($special) {
    $sarlavha = '🟡 BIZDA YO\'Q MAHSULOT — ' . $filialTag . ' #maxsus_buyurtma';
}

$lines = array(
    $sarlavha,
    '',
    '📍 Filial: ' . $filialNomi,
    '👤 Ism: ' . $name,
    '📞 Telefon: ' . $tel,
);
if ($special) {
    $lines[] = '🔎 Mahsulot do\'konda yo\'q — topib berish so\'ralmoqda';
}
if ($note !== '') {
    $lines[] = "💬 So'rovi: " . $note;
}
if ($source !== '') {
    $lines[] = '🧭 Qayerdan kelgan: ' . $source;
}
$lines[] = '';
$lines[] = '🕒 ' . date('d.m.Y H:i') . ($page !== '' ? ' · ' . $page : '');
if ($leadId > 0) {
    $lines[] = '🗂 Admin panelda: https://hamkorsavdo.uz/admin/ariza.php?id=' . $leadId;
}

/* parse_mode berilmaydi — foydalanuvchi matni hech qachon belgilash sifatida
   talqin qilinmasin. */
$payload = http_build_query(array(
    'chat_id' => $chatId,
    'text' => implode("\n", $lines),
    'disable_web_page_preview' => 'true',
));

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
$sent = false;

if ($token === '' || $chatId === '') {
    // Telegram sozlanmagan, lekin ariza bazada saqlandi.
} elseif (getenv('HS_DRY_RUN') === '1') {
    $sent = true;
} elseif (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $sent = ($body !== false && $code === 200);
} else {
    $ctx = stream_context_create(array('http' => array(
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true,
    )));
    $body = @file_get_contents($url, false, $ctx);
    $sent = ($body !== false && strpos($body, '"ok":true') !== false);
}

if ($sent && $leadId > 0) {
    try {
        hs_db()->prepare('UPDATE leads SET telegram_sent = 1 WHERE id = ?')->execute(array($leadId));
    } catch (Throwable $e) {
    }
}

if (!$sent) {
    // Tokenni hech qachon logga yozmaymiz — Telegram javobi uni qaytarishi mumkin.
    error_log('HAMKOR SAVDO: Telegramga ariza yuborib bolmadi.');
    if ($leadId === 0) {
        hs_fail(502, "Ariza yuborilmadi. Iltimos, telefon orqali bog'laning.");
    }
}

hs_ok();
