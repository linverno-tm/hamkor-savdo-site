<?php
/**
 * Kompyuterda saytni hostingdagidek ishga tushirish uchun yo'naltiruvchi.
 *
 *   php -S localhost:8000 -t out tools/mahalliy-server.php
 *
 * `php -S` ni tanlash sababi: sayt statik HTML dan iborat bo'lsa ham,
 * ariza formasi (api/lead.php) va butun admin panel PHP da yozilgan.
 * Oddiy statik server bilan ular ishlamaydi.
 *
 * Bu fayl `public/.htaccess` dagi ikkita qoidani takrorlaydi, chunki
 * `php -S` .htaccess ni umuman o'qimaydi:
 *
 *   1. Next'ning sahifa ma'lumot fayllari. Brauzer
 *      `__next.filiallar.__PAGE__.txt` so'raydi, `next build` esa uni
 *      `__next.filiallar/__PAGE__.txt` qilib yozadi — nuqta o'rniga papka.
 *      Tuzatilmasa, har sahifada bir necha 404 chiqadi.
 *   2. Papka so'ralganda ichidagi index.html berish.
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^(.*/)__next\.(.+)\.__PAGE__\.txt$#', $uri, $m)) {
    $file = $root . $m[1] . '__next.' . str_replace('.', '/', $m[2]) . '/__PAGE__.txt';
    if (is_file($file)) {
        header('Content-Type: text/plain; charset=utf-8');
        readfile($file);
        return true;
    }
}

$path = $root . $uri;

if ($uri !== '/' && is_file($path)) {
    return false; // php -S fayilni o'zi bersin
}

// Papka so'ralsa: sayt sahifalari index.html da, admin panel esa index.php da
// (hostingda buni Apache'ning DirectoryIndex sozlamasi hal qiladi).
if (is_dir($path)) {
    $dir = rtrim($path, '/');
    if (is_file($dir . '/index.php')) {
        $_SERVER['SCRIPT_FILENAME'] = $dir . '/index.php';
        require $dir . '/index.php';
        return true;
    }
    if (is_file($dir . '/index.html')) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($dir . '/index.html');
        return true;
    }
}

// Topilmagan manzil — hostingdagi kabi 404 sahifasi.
$notFound = $root . (strpos($uri, '/uz-kr/') === 0 ? '/uz-kr/404.html' : '/404.html');
if (is_file($notFound)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    readfile($notFound);
    return true;
}

return false;
