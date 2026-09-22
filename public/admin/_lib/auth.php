<?php
/**
 * Kirish, sessiya va qurilma bloki.
 *
 * Qoidalar (egasi tasdiqlagan, 2026-09-17):
 *  - login + parol;
 *  - bitta qurilma YOKI bitta IP dan ketma-ket 5 ta xato -> muddatsiz blok;
 *  - blokni faqat egasi ochadi: Telegram'dagi havola orqali yoki paneldan.
 *
 * "Qurilma" = `hs_dev` cookie (10 yil). Cookie o'chirilsa qurilma yangidek
 * ko'rinadi, shuning uchun IP ham alohida sanaladi — ikkalasidan biri
 * bloklangan bo'lsa kirish yopiq.
 *
 * Egasining hisobi `secrets.php` da (admin_login / admin_hash) — bazaga
 * bog'liq emas, baza buzilsa ham egasi kira oladi. Operatorlar bazada.
 */

const HS_MAX_FAILS = 5;
const HS_IDLE_SECONDS = 28800;      // 8 soat harakatsizlik
const HS_ABSOLUTE_SECONDS = 86400;  // 24 soatdan keyin qayta kirish

function hs_device_id()
{
    static $dev = null;
    if ($dev !== null) {
        return $dev;
    }
    $cur = isset($_COOKIE['hs_dev']) ? (string) $_COOKIE['hs_dev'] : '';
    if (!preg_match('/^[a-f0-9]{48}$/', $cur)) {
        $cur = hs_random_hex(24);
        setcookie('hs_dev', $cur, array(
            'expires' => time() + 10 * 365 * 86400,
            'path' => hs_area_root(),
            'secure' => hs_is_https(),
            'httponly' => true,
            'samesite' => 'Strict',
        ));
        $_COOKIE['hs_dev'] = $cur;
    }
    $dev = $cur;
    return $dev;
}

function hs_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = hs_data_dir() . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    session_save_path($dir);
    // Ijara paneli — boshqa nom va boshqa yo'l: ikki panel sessiyasi bir-birini ko'rmaydi.
    session_name(hs_area() === 'ijara' ? 'hs_ijara' : 'hs_admin');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) HS_ABSOLUTE_SECONDS);
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => hs_area_root(),
        'secure' => hs_is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ));
    session_start();
}

function hs_csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = hs_random_hex(32);
    }
    return $_SESSION['csrf'];
}

function hs_csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . h(hs_csrf_token()) . '">';
}

/**
 * Sessiyani sekin tashqi so'rovdan (Metrika) OLDIN yopish.
 *
 * PHP sessiya faylini so'rov tugaguncha qulflab turadi. Sahifa Metrika'dan
 * javob kutib turganda (u yerda pauzali qayta urinishlar ham bor) foydalanuvchi
 * boshqa bo'limni bossa, yangi so'rov session_start() da qulfni kutib qoladi —
 * bo'limlar orasida o'tish shuning uchun sekin edi.
 *
 * Yopishdan oldin sessiyaga yozilishi kerak bo'lgan ikki narsa bajariladi:
 * CSRF token (keyin chiziladigan formalar uchun) va flash xabarlar olib
 * qo'yiladi — hs_flash() ularni sessiyadan emas, shu yerdan qaytaradi.
 * Faqat GET da chaqiring: POST ishlovchilari sessiyaga yozadi.
 */
function hs_session_release()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    hs_csrf_token();
    $GLOBALS['hs_flash_olingan'] = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    session_write_close();
}

function hs_require_post_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    $sent = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit("So'rov eskirgan. Sahifani yangilab, qaytadan urinib ko'ring.");
    }
}

/* ---------------- bloklar ---------------- */

function hs_active_block()
{
    $st = hs_db()->prepare('SELECT * FROM blocks WHERE unblocked_at IS NULL AND (device = ? OR ip = ?) ORDER BY id DESC LIMIT 1');
    $st->execute(array(hs_device_id(), hs_ip()));
    $row = $st->fetch();
    return $row ?: null;
}

function hs_fail_count()
{
    $st = hs_db()->prepare("SELECT
        (SELECT COUNT(*) FROM login_attempts WHERE result = 'fail' AND cleared = 0 AND device = :d),
        (SELECT COUNT(*) FROM login_attempts WHERE result = 'fail' AND cleared = 0 AND ip = :ip)");
    $st->execute(array(':d' => hs_device_id(), ':ip' => hs_ip()));
    $row = $st->fetch(PDO::FETCH_NUM);
    return max((int) $row[0], (int) $row[1]);
}

function hs_record_attempt($login, $result)
{
    $st = hs_db()->prepare('INSERT INTO login_attempts(created_at, login, ip, device, user_agent, result) VALUES(?, ?, ?, ?, ?, ?)');
    $st->execute(array(hs_now(), mb_substr((string) $login, 0, 80), hs_ip(), hs_device_id(), hs_user_agent(), $result));
}

function hs_clear_attempts($device, $ip)
{
    $st = hs_db()->prepare("UPDATE login_attempts SET cleared = 1 WHERE result = 'fail' AND (device = ? OR ip = ?)");
    $st->execute(array($device, $ip));
}

function hs_create_block($lastLogin)
{
    $token = hs_random_hex(32);
    $st = hs_db()->prepare('INSERT INTO blocks(created_at, ip, device, user_agent, last_login, token_hash) VALUES(?, ?, ?, ?, ?, ?)');
    $st->execute(array(hs_now(), hs_ip(), hs_device_id(), hs_user_agent(), mb_substr((string) $lastLogin, 0, 80), hash('sha256', $token)));
    $id = (int) hs_db()->lastInsertId();

    hs_audit('tizim', 'blok', 'IP ' . hs_ip() . ', ' . hs_describe_agent(hs_user_agent()));
    hs_telegram_send(implode("\n", array(
        '🚫 ADMIN PANEL: QURILMA BLOKLANDI',
        '',
        HS_MAX_FAILS . " marta noto'g'ri login/parol kiritildi.",
        'Oxirgi kiritilgan login: ' . ($lastLogin !== '' ? $lastLogin : '—'),
        'IP: ' . hs_ip(),
        'Qurilma: ' . hs_describe_agent(hs_user_agent()),
        'Vaqt: ' . date('d.m.Y H:i'),
        '',
        "Bu siz bo'lsangiz yoki blokni ochmoqchi bo'lsangiz:",
        hs_site_url() . '/admin/unblock.php?t=' . $token,
        '',
        "Siz bo'lmasangiz — hech narsa qilmang, blok abadiy qoladi.",
    )));
    return $id;
}

function hs_unblock($blockId, $actor)
{
    $st = hs_db()->prepare('SELECT * FROM blocks WHERE id = ? AND unblocked_at IS NULL');
    $st->execute(array((int) $blockId));
    $b = $st->fetch();
    if (!$b) {
        return false;
    }
    $up = hs_db()->prepare('UPDATE blocks SET unblocked_at = ?, unblocked_by = ?, token_hash = ? WHERE id = ?');
    $up->execute(array(hs_now(), $actor, '', (int) $blockId));
    hs_clear_attempts($b['device'], $b['ip']);
    hs_audit($actor, 'blokdan chiqarish', 'IP ' . $b['ip']);
    return true;
}

/* ---------------- kirish ---------------- */

function hs_owner_configured()
{
    return hs_config('admin_login', '') !== '' && hs_owner_hash() !== '';
}

/**
 * Egasining amaldagi paroli xeshi.
 *
 * Ikki manba: secrets.php (tools/admin-parol.php yozadi) va paneldagi
 * "Parolni o'zgartirish" (bazaga yozadi). Qaysi biri YANGIROQ bo'lsa, o'sha
 * amalda — shunda panelda o'zgartirilgan parolni unutsangiz ham, kompyuterdan
 * admin-parol.php bilan yangi parol qo'yib, kirishni tiklay olasiz.
 */
function hs_owner_hash()
{
    $fileHash = (string) hs_config('admin_hash', '');
    $fileTime = (int) hs_config('admin_hash_set_at', 0);
    $dbHash = (string) hs_setting('owner_hash', '');
    $dbTime = (int) hs_setting('owner_hash_set_at', '0');
    if ($dbHash !== '' && $dbTime > $fileTime) {
        return $dbHash;
    }
    return $fileHash;
}

/**
 * Parol talabi. Egasi va savdo operatorlari: kamida 10 belgi, harf va raqam.
 * Ijara xodimlari: egasining qarori bilan kamida 4 belgi (5 xato urinishdan keyin
 * qurilma bloklanishi baribir ishlaydi).
 */
function hs_password_problem($p, $kind = '')
{
    if (strpos((string) $kind, 'ijara_') === 0) {
        return mb_strlen($p) < 4 ? "Parol kamida 4 belgi bo'lsin." : null;
    }
    if (mb_strlen($p) < 10) {
        return "Parol kamida 10 belgi bo'lsin.";
    }
    if (!preg_match('/\d/', $p) || !preg_match('/\pL/u', $p)) {
        return "Parolda harf ham, raqam ham bo'lsin.";
    }
    return null;
}

/**
 * @return array{ok:bool, message:string, user?:array}
 */
function hs_attempt_login($login, $password)
{
    if (hs_active_block()) {
        hs_record_attempt($login, 'blocked');
        return array('ok' => false, 'message' => 'blocked');
    }

    $user = null;
    $ownerLogin = (string) hs_config('admin_login', '');
    $ownerHash = hs_owner_hash();
    // Vaqt bo'yicha farq bo'lmasin: foydalanuvchi topilmasa ham xesh tekshiriladi.
    $dummy = password_hash('hs-timing-dummy', PASSWORD_DEFAULT);

    if ($ownerLogin !== '' && hash_equals(mb_strtolower($ownerLogin), mb_strtolower($login))) {
        if ($ownerHash !== '' && password_verify($password, $ownerHash)) {
            $user = array('login' => $ownerLogin, 'name' => $ownerLogin, 'role' => 'owner', 'branch' => '', 'id' => 0);
        }
    } else {
        $st = hs_db()->prepare('SELECT * FROM users WHERE login = ? AND active = 1');
        $st->execute(array($login));
        $row = $st->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            $user = array('login' => $row['login'], 'name' => $row['name'] ?: $row['login'], 'role' => hs_user_kind($row), 'branch' => $row['branch'], 'id' => (int) $row['id']);
        } elseif (!$row) {
            password_verify($password, $dummy);
        }
    }

    if ($user !== null && !hs_role_allowed_here($user['role'])) {
        $user = null;
    }
    if ($user === null) {
        hs_record_attempt($login, 'fail');
        // Parolni topish tezligini pasaytiradi.
        sleep(1);
        $fails = hs_fail_count();
        if ($fails >= HS_MAX_FAILS) {
            hs_create_block($login);
            return array('ok' => false, 'message' => 'blocked');
        }
        $left = HS_MAX_FAILS - $fails;
        return array('ok' => false, 'message' => "Login yoki parol noto'g'ri. Yana {$left} ta urinish qoldi — keyin bu qurilma bloklanadi.");
    }

    hs_record_attempt($login, 'ok');
    hs_clear_attempts(hs_device_id(), hs_ip());
    session_regenerate_id(true);
    $_SESSION = array(
        'user' => $user,
        'dev' => hs_device_id(),
        'started' => time(),
        'last' => time(),
        'csrf' => hs_random_hex(32),
    );
    hs_audit($user['login'], 'kirish', 'IP ' . hs_ip() . ', ' . hs_describe_agent(hs_user_agent()));
    // Ijara paneliga kirish savdo xodimlari chatiga chiqmasin: faqat egasining shaxsiy
    // chati (admin_chat_id) sozlangan bo'lsa, o'shanga boradi.
    $notify = hs_area() !== 'ijara' || (string) hs_config('admin_chat_id', '') !== '';
    $notify && hs_telegram_send(implode("\n", array(
        hs_area() === 'ijara' ? '🔐 Ijara paneliga kirildi' : '🔐 Admin panelga kirildi',
        'Login: ' . $user['login'],
        'IP: ' . hs_ip(),
        'Qurilma: ' . hs_describe_agent(hs_user_agent()),
        'Vaqt: ' . date('d.m.Y H:i'),
    )));
    return array('ok' => true, 'message' => '', 'user' => $user);
}

function hs_logout()
{
    $_SESSION = array();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    setcookie(session_name(), '', array('expires' => time() - 3600, 'path' => hs_area_root(), 'secure' => hs_is_https(), 'httponly' => true, 'samesite' => 'Strict'));
}

function hs_current_user()
{
    if (empty($_SESSION['user'])) {
        return null;
    }
    $now = time();
    if ((isset($_SESSION['dev']) && $_SESSION['dev'] !== hs_device_id())
        || $now - (int) $_SESSION['last'] > HS_IDLE_SECONDS
        || $now - (int) $_SESSION['started'] > HS_ABSOLUTE_SECONDS) {
        hs_logout();
        return null;
    }
    // Foydalanuvchi o'chirilgan yoki to'xtatilgan bo'lsa — darhol chiqariladi.
    // Turi (savdo operatori / ijara) o'zgartirilsa ham darhol kuchga kiradi.
    $u = $_SESSION['user'];
    if ($u['role'] !== 'owner') {
        $st = hs_db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute(array((int) $u['id']));
        $row = $st->fetch();
        if (!$row || (int) $row['active'] !== 1) {
            hs_logout();
            return null;
        }
        $_SESSION['user']['branch'] = $row['branch'];
        $_SESSION['user']['role'] = hs_user_kind($row);
    }
    $_SESSION['last'] = $now;
    return $_SESSION['user'];
}

/**
 * Har bir himoyalangan sahifaning boshida. Bloklangan qurilma panelning
 * hech bir sahifasini ko'rmaydi.
 */
function hs_require_login($ownerOnly = false)
{
    hs_security_headers();
    hs_session_start();
    if (hs_active_block()) {
        hs_render_blocked();
    }
    $user = hs_current_user();
    // Sessiya boshqa panelniki bo'lib chiqsa (bo'lmasligi kerak — cookie yo'li alohida) — chiqarib yuboriladi.
    if ($user && !hs_role_allowed_here($user['role'])) {
        hs_logout();
        $user = null;
    }
    if (!$user) {
        $back = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : hs_area_root();
        hs_redirect(hs_area_root() . 'login.php?qayt=' . rawurlencode(hs_safe_return($back)));
    }
    if ($ownerOnly && $user['role'] !== 'owner') {
        http_response_code(403);
        hs_page_start('Ruxsat yo\'q', $user);
        echo '<div class="card"><p>Bu bo\'lim faqat egasi uchun.</p></div>';
        hs_page_end();
        exit;
    }
    return $user;
}

/**
 * Kim qaysi panelga kira oladi: /admin/ — egasi va savdo operatorlari;
 * /ijara/ — egasi va ijara xodimlari. Boshqa paneldagi login bu yerda
 * "login yoki parol noto'g'ri" deb qaytariladi — panel borligi ham bilinmaydi.
 */
function hs_role_allowed_here($role)
{
    if ($role === 'owner') {
        return true;
    }
    return hs_area() === 'ijara' ? strpos((string) $role, 'ijara_') === 0 : $role === 'operator';
}

function hs_is_owner($user)
{
    return $user && $user['role'] === 'owner';
}

/**
 * users.kind: 'operator' — savdo operatori (arizalar); 'ijara_boshliq' — ijara
 * bo'limining boshlig'i (joy, ijarachi qo'shadi, hammasini ko'radi); 'ijara_ishchi' —
 * pul qabul qiladi, shartnoma yuklaydi. Ijara xodimlari savdo bo'limlarini ko'rmaydi.
 */
function hs_user_kind($row)
{
    $k = isset($row['kind']) ? (string) $row['kind'] : '';
    return in_array($k, array('ijara_boshliq', 'ijara_ishchi'), true) ? $k : 'operator';
}

function hs_user_kinds()
{
    return array(
        'operator' => 'Savdo operatori',
        'ijara_boshliq' => "Ijara boshlig'i",
        'ijara_ishchi' => 'Ijara ishchisi',
    );
}

function hs_is_ijara_user($user)
{
    return $user && strpos((string) $user['role'], 'ijara_') === 0;
}
