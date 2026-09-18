<?php
/**
 * HAMKOR SAVDO admin panel — umumiy yuklovchi.
 *
 * Har bir sahifa birinchi qatorda shu faylni chaqiradi. PHP 7.4+ bilan mos:
 * `match`, `?->`, named arguments, `str_contains` ishlatilmaydi.
 *
 * Sozlamalar `api/secrets.php` dan o'qiladi (git'ga tushmaydi). Mahalliy
 * sinov uchun HS_CONFIG_EXTRA muhit o'zgaruvchisi qo'shimcha faylni ko'rsatishi
 * mumkin — hostingda bu o'zgaruvchini tashqaridan o'rnatib bo'lmaydi.
 */

if (defined('HS_ADMIN')) {
    return;
}
define('HS_ADMIN', true);

date_default_timezone_set('Asia/Tashkent');
mb_internal_encoding('UTF-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function hs_config($key = null, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = array();
        $file = __DIR__ . '/../../api/secrets.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $cfg = $loaded;
            }
        }
        $extra = getenv('HS_CONFIG_EXTRA');
        if ($extra && is_file($extra)) {
            $more = require $extra;
            if (is_array($more)) {
                $cfg = array_merge($cfg, $more);
            }
        }
    }
    if ($key === null) {
        return $cfg;
    }
    return array_key_exists($key, $cfg) ? $cfg[$key] : $default;
}

function hs_data_dir()
{
    $dir = hs_config('data_dir') ?: (getenv('HS_DATA_DIR') ?: __DIR__ . '/../_data');
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    // Veb-server bu papkani hech qachon bermasin (asosiy himoya admin/.htaccess da).
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Require all denied\n");
    }
    return $dir;
}

function hs_site_url()
{
    return rtrim(hs_config('site_url', 'https://hamkorsavdo.uz'), '/');
}

/**
 * So'rov https orqali kelganmi.
 *
 * Uchinchi shart — proksi uchun: hosting sertifikatni old tomonda ushlab,
 * PHP ga oddiy http bilan uzatsa, birinchi ikkalasi bo'sh qoladi. O'shanda
 * sessiya "cookie" si `Secure` belgisisiz qolar va HSTS yuborilmas edi.
 * Saytning .htaccess fayli ham yo'naltirishda aynan shu sarlavhaga qaraydi.
 *
 * X-Forwarded-Proto ni soxtalashtirish mumkin, lekin bu yerda undan foyda
 * yo'q: yolg'on "https" cookie ga `Secure` qo'shadi, ya'ni brauzer uni
 * http orqali umuman yubormaydi — hujumchi o'z ishini buzadi.
 */
function hs_is_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function hs_ip()
{
    // Faqat REMOTE_ADDR: X-Forwarded-For ni istalgan odam soxtalashtira oladi.
    return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function hs_user_agent()
{
    return isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 300) : '';
}

function hs_now()
{
    return date('Y-m-d H:i:s');
}

function hs_random_hex($bytes = 32)
{
    return bin2hex(random_bytes($bytes));
}

function hs_redirect($path)
{
    header('Location: ' . $path, true, 303);
    exit;
}

function hs_post($key, $default = '')
{
    return isset($_POST[$key]) && !is_array($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function hs_get($key, $default = '')
{
    return isset($_GET[$key]) && !is_array($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function hs_security_headers()
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, max-age=0');
    // Shu qator admin/.htaccess da ham bor — u yerda saytning umumiy
    // sozlamasi buni almashtirib yubormasligi uchun. Birini o'zgartirsangiz,
    // ikkinchisini ham o'zgartiring.
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
    if (hs_is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/** Faqat panel ichidagi yo'l — ochiq redirect (open redirect) bo'lmasin. */
function hs_safe_return($path, $fallback = '/admin/')
{
    if (is_string($path) && preg_match('#^/admin/[a-z0-9_\-./?=&%]*$#i', $path) && strpos($path, '//') === false) {
        return $path;
    }
    return $fallback;
}

function hs_flash($message = null, $kind = 'ok')
{
    if ($message !== null) {
        $_SESSION['flash'][] = array($kind, $message);
        return array();
    }
    $all = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $all;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
