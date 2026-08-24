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

$MAX_NAME = 80;
$MAX_PHONE = 24;
$MAX_NOTE = 500;
$PHONE_MIN_DIGITS = 9;
/** Bitta IP dan shuncha soniyada bittadan ko'p ariza qabul qilinmaydi. */
$THROTTLE_SECONDS = 20;

$PHONE_DISPLAY = '+998 74 342 08 80';
$PHONE_LINK = '+998743420880';

/* Har bir filial uchun nom va xeshteg. Xeshteg call-center guruhida
   qidirish uchun: operator #asaka deb qidirsa, faqat o'z filialining
   arizalarini ko'radi. */
$FILIALLAR = array(
    'shahrixon-ozodbek'  => array("Shahrixon — Ozodbek savdo markazi", '#shahrixon_ozodbek'),
    'shahrixon-bog'      => array("Shahrixon — Markaziy istirohat bog'i yonida", '#shahrixon_bog'),
    'asaka-umid'         => array("Asaka — Makro supermarketi, 2-qavat", '#asaka'),
    'andijon-amir-temur' => array("Andijon — Amir Temur shoh ko'chasi, 62", '#andijon'),
);

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
    header('Location: /rahmat/', true, 303);
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_fail(405, "Bu sahifa to'g'ridan-to'g'ri ochilmaydi.");
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
if (!is_file($configFile)) {
    hs_fail(503, "Ariza qabul qilish vaqtincha ishlamayapti. Iltimos, telefon orqali bog'laning.");
}
$secrets = require $configFile;
$token = isset($secrets['token']) ? $secrets['token'] : '';
$chatId = isset($secrets['chat_id']) ? $secrets['chat_id'] : '';
if ($token === '' || $chatId === '') {
    hs_fail(503, "Ariza qabul qilish vaqtincha ishlamayapti. Iltimos, telefon orqali bog'laning.");
}

$known = isset($FILIALLAR[$branch]);
$filialNomi = $known ? $FILIALLAR[$branch][0] : 'tanlanmagan';
$filialTag  = $known ? $FILIALLAR[$branch][1] : '#filial_tanlanmagan';

/* Filial birinchi qatorda va xeshteg bilan — operator qaysi filialga
   tegishli ekanini bir qarashda ko'radi. */
$lines = array(
    '🟣 YANGI ARIZA — ' . $filialTag,
    '',
    '📍 Filial: ' . $filialNomi,
    '👤 Ism: ' . $name,
    '📞 Telefon: ' . $tel,
);
if ($note !== '') {
    $lines[] = "💬 So'rovi: " . $note;
}
$lines[] = '';
$lines[] = '🕒 ' . date('d.m.Y H:i') . ($page !== '' ? ' · ' . $page : '');

/* parse_mode berilmaydi — foydalanuvchi matni hech qachon belgilash sifatida
   talqin qilinmasin. */
$payload = http_build_query(array(
    'chat_id' => $chatId,
    'text' => implode("\n", $lines),
    'disable_web_page_preview' => 'true',
));

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
$sent = false;

if (function_exists('curl_init')) {
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

if (!$sent) {
    // Tokenni hech qachon logga yozmaymiz — Telegram javobi uni qaytarishi mumkin.
    error_log('HAMKOR SAVDO: Telegramga ariza yuborib bolmadi.');
    hs_fail(502, "Ariza yuborilmadi. Iltimos, telefon orqali bog'laning.");
}

hs_ok();
