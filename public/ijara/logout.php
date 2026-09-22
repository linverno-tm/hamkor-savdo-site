<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';

hs_security_headers();
hs_session_start();
hs_require_post_csrf();
$u = hs_current_user();
if ($u) {
    hs_audit($u['login'], 'chiqish (ijara)');
}
hs_logout();
hs_redirect('/ijara/login.php');
