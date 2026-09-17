<?php
/**
 * Admin panel egasining login va parolini o'rnatadi.
 *
 *   php tools/admin-parol.php
 *
 * Parol hech qayerga ochiq yozilmaydi: public/api/secrets.php ga faqat
 * password_hash() natijasi (qaytarib bo'lmaydigan xesh) yoziladi.
 * Keyin secrets.php ni hostingdagi public_html/api/ ga yuklang.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$file = __DIR__ . '/../public/api/secrets.php';
if (!is_file($file)) {
    fwrite(STDERR, "public/api/secrets.php topilmadi. Avval secrets.example.php dan nusxa oling.\n");
    exit(1);
}

function ask($label, $hidden = false)
{
    fwrite(STDOUT, $label);
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $v = trim((string) fgets(STDIN));
        system('stty echo');
        fwrite(STDOUT, "\n");
        return $v;
    }
    return trim((string) fgets(STDIN));
}

$login = ask('Login [Linverno]: ');
if ($login === '') {
    $login = 'Linverno';
}
if (!preg_match('/^[A-Za-z0-9_.]{3,40}$/', $login)) {
    fwrite(STDERR, "Login faqat lotin harf, raqam, _ va nuqtadan iborat bo'lsin.\n");
    exit(1);
}
if (DIRECTORY_SEPARATOR !== '/') {
    fwrite(STDOUT, "(Windows'da parol yozilayotganda ekranda ko'rinadi — atrofingizga qarang.)\n");
}
$p1 = ask('Parol: ', true);
$p2 = ask('Parolni takrorlang: ', true);
if ($p1 === '' || $p1 !== $p2) {
    fwrite(STDERR, "Parollar mos kelmadi.\n");
    exit(1);
}
if (mb_strlen($p1) < 10) {
    fwrite(STDERR, "Parol kamida 10 belgi bo'lsin.\n");
    exit(1);
}

$hash = password_hash($p1, PASSWORD_DEFAULT);
$src = file_get_contents($file);
// admin_hash_set_at: paneldan o'zgartirilgan paroldan yangiroq ekanini bildiradi
// (unutilgan parolni shu vosita bilan tiklash uchun).
$lines = array(
    'admin_login' => "    'admin_login' => " . var_export($login, true) . ',',
    'admin_hash' => "    'admin_hash' => " . var_export($hash, true) . ',',
    'admin_hash_set_at' => "    'admin_hash_set_at' => " . time() . ',',
);
foreach ($lines as $key => $line) {
    $pattern = "/^\s*'" . $key . "'\s*=>.*$/m";
    if (preg_match($pattern, $src)) {
        $src = preg_replace($pattern, str_replace('$', '\$', $line), $src);
    } else {
        $pos = strrpos($src, ');');
        $src = substr($src, 0, $pos) . $line . "\n" . substr($src, $pos);
    }
}
file_put_contents($file, $src);

// Tekshiruv: fayl to'g'ri PHP bo'lib qolganmi.
$cfg = include $file;
if (!is_array($cfg) || !password_verify($p1, $cfg['admin_hash'])) {
    fwrite(STDERR, "Xatolik: secrets.php ni tekshiring.\n");
    exit(1);
}
fwrite(STDOUT, "Tayyor. Login: {$login}. secrets.php ni hostingdagi public_html/api/ ga yuklang.\n");
