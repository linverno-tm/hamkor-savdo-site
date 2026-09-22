<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';

hs_security_headers();
hs_session_start();
if (hs_active_block()) {
    hs_render_blocked();
}
if (hs_current_user()) {
    hs_redirect('/ijara/');
}

$error = '';
// Shu qurilmada oxirgi marta kirgan login — maydon o'zi to'lib turadi, faqat parol yoziladi.
// Cookie faqat shu qurilmada va faqat /ijara/ uchun: boshqa qurilmada loginlar ko'rinmaydi.
$login = isset($_COOKIE['hs_ijara_login']) && preg_match('/^[A-Za-z0-9_.]{3,40}$/', (string) $_COOKIE['hs_ijara_login']) ? (string) $_COOKIE['hs_ijara_login'] : '';
$back = hs_safe_return(hs_get('qayt'), '/ijara/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $login = mb_substr(hs_post('login'), 0, 80);
    $password = isset($_POST['parol']) ? (string) $_POST['parol'] : '';
    $back = hs_safe_return(hs_post('qayt'), '/ijara/');
    if ($login === '' || $password === '') {
        $error = 'Login va parolni kiriting.';
    } else {
        $res = hs_attempt_login($login, mb_substr($password, 0, 200));
        if ($res['ok']) {
            setcookie('hs_ijara_login', $res['user']['login'], array(
                'expires' => time() + 365 * 86400,
                'path' => '/ijara/',
                'secure' => hs_is_https(),
                'httponly' => true,
                'samesite' => 'Strict',
            ));
            hs_redirect($back);
        }
        if ($res['message'] === 'blocked') {
            hs_render_blocked();
        }
        $error = $res['message'];
    }
}

hs_head('Kirish — Ijara');
echo '<body class="center">';
echo '<form class="card narrow auth-card" method="post" action="/ijara/login.php" autocomplete="on">';
echo '<div class="auth-top"><div class="auth-icon">' . hs_icon('building') . '</div>' . hs_theme_toggle() . '</div><h1>IJARA</h1><p class="muted">Boshqaruv paneliga kirish</p>';
if ($error !== '') {
    echo '<p class="flash flash-err">' . h($error) . '</p>';
}
echo hs_csrf_field();
echo '<input type="hidden" name="qayt" value="' . h($back) . '">';
echo '<label for="login">Login</label><input id="login" name="login" type="text" autocomplete="username" required maxlength="80" value="' . h($login) . '"' . ($login === '' ? ' autofocus' : '') . '>';
echo '<label for="parol">Parol</label><div class="pw-wrap"><input id="parol" name="parol" type="password" autocomplete="current-password" required maxlength="200"' . ($login !== '' ? ' autofocus' : '') . '><button type="button" class="pw-toggle" data-toggle-password="parol">Ko\'rsatish</button></div>';
echo '<div class="actions"><button class="btn" type="submit">Kirish</button></div>';
echo '<p class="hint">5 marta noto\'g\'ri kiritilsa, bu qurilma bloklanadi.</p>';
echo '</form></body></html>';
