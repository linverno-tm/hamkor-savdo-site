<?php
require __DIR__ . '/_lib/bootstrap.php';

hs_security_headers();
hs_session_start();
if (hs_active_block()) {
    hs_render_blocked();
}
if (hs_current_user()) {
    hs_redirect('/admin/');
}

$error = '';
$login = '';
$back = hs_safe_return(hs_get('qayt'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $login = mb_substr(hs_post('login'), 0, 80);
    $password = isset($_POST['parol']) ? (string) $_POST['parol'] : '';
    $back = hs_safe_return(hs_post('qayt'));
    if ($login === '' || $password === '') {
        $error = 'Login va parolni kiriting.';
    } else {
        $res = hs_attempt_login($login, mb_substr($password, 0, 200));
        if ($res['ok']) {
            hs_redirect($back);
        }
        if ($res['message'] === 'blocked') {
            hs_render_blocked();
        }
        $error = $res['message'];
    }
}

echo '<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<meta name="robots" content="noindex, nofollow"><title>Kirish — HAMKOR SAVDO admin</title><script src="/admin/assets/admin.js?v=3" defer></script>';
echo '<link rel="stylesheet" href="/admin/assets/admin.css?v=3"></head><body class="center">';
echo '<form class="card narrow auth-card" method="post" action="/admin/login.php" autocomplete="on">';
echo '<div class="auth-icon">' . hs_icon('shield') . '</div><h1>HAMKOR SAVDO</h1><p class="muted">Boshqaruv paneliga kirish</p>';
if (!hs_owner_configured()) {
    echo '<p class="flash flash-warn">Egasining paroli hali o\'rnatilmagan. Kompyuterda loyiha papkasida <span class="code">php tools/admin-parol.php</span> ni ishga tushiring.</p>';
}
if ($error !== '') {
    echo '<p class="flash flash-err">' . h($error) . '</p>';
}
echo hs_csrf_field();
echo '<input type="hidden" name="qayt" value="' . h($back) . '">';
echo '<label for="login">Login</label><input id="login" name="login" type="text" autocomplete="username" required maxlength="80" value="' . h($login) . '">';
echo '<label for="parol">Parol</label><div class="pw-wrap"><input id="parol" name="parol" type="password" autocomplete="current-password" required maxlength="200"><button type="button" class="pw-toggle" data-toggle-password="parol">Ko\'rsatish</button></div>';
echo '<div class="actions"><button class="btn" type="submit">Kirish</button></div>';
echo '<p class="hint">5 marta noto\'g\'ri kiritilsa, bu qurilma bloklanadi.</p>';
echo '</form></body></html>';
