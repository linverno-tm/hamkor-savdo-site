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
 * Bu fayl `public/.htaccess` dagi qoidalarni takrorlaydi, chunki
 * `php -S` .htaccess ni umuman o'qimaydi:
 *
 *   1. Next'ning sahifa ma'lumot fayllari. Brauzer
 *      `__next.filiallar.__PAGE__.txt` so'raydi, `next build` esa uni
 *      `__next.filiallar/__PAGE__.txt` qilib yozadi — nuqta o'rniga papka.
 *      Tuzatilmasa, har sahifada bir necha 404 chiqadi.
 *   2. Papka so'ralganda ichidagi index.html berish.
 *   3. Yopiq fayllar (`Require all denied`) va xavfsizlik sarlavhalari.
 *      Bular avval bu yerda yo'q edi va `tools/joylashuvni-tekshir.mjs`
 *      mahalliy serverda har safar yolg'on xato ko'rsatardi — ya'ni
 *      tekshiruvni ishga tushirishdan oldin har gal saytni hostingga
 *      yuklash kerak bo'lardi. Endi ikkalasi bir xil javob beradi.
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- public/.htaccess dagi xavfsizlik sarlavhalari ---
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

// --- Kalitlar va ichki ma'lumot fayllari hech qachon ochilmasin ---
// Ro'yxatlar public/.htaccess va public/admin/.htaccess bilan bir xil
// bo'lishi shart. Ularni o'zgartirsangiz, bu yerni ham o'zgartiring.
$yopiqNom = array('secrets.php', 'secrets.example.php', 'sayt.json', 'manifest.json', '.ftp-deploy-sync-state.json');
$yopiq =
    in_array(basename($uri), $yopiqNom, true)
    // admin/.htaccess: RewriteRule ^(_lib|_data|cron)(/|$) - [F,L]
    || preg_match('#^/admin/(_lib|_data|cron)(/|$)#', $uri)
    // admin/.htaccess: <FilesMatch "\.(sqlite|...)$">
    || preg_match('#^/admin/.*\.(sqlite|sqlite-wal|sqlite-shm|log|json|md)$#', $uri);

if ($yopiq) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden\n";
    return true;
}

// --- Keshlash: HTML hech qachon, statik hamma narsa uzoq ---
// Statik faylni `return false` bilan php -S ga topshirsak, u bu yerda
// qo'yilgan sarlavhalarni tashlab yuboradi. Shuning uchun keshlanadigan
// fayllarni o'zimiz beramiz — faqat shunda mahalliy javob hostingdagiga
// to'liq o'xshaydi.
if (preg_match('#\.(?:js|css|woff2)$#', $uri) && is_file($root . $uri)) {
    $turlar = array('js' => 'text/javascript', 'css' => 'text/css', 'woff2' => 'font/woff2');
    $kengaytma = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Type: ' . $turlar[$kengaytma] . '; charset=utf-8');
    readfile($root . $uri);
    return true;
}
header('Cache-Control: public, max-age=0, must-revalidate');

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
