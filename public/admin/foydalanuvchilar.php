<?php
require __DIR__ . '/_lib/bootstrap.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $db = hs_db();

    if ($action === 'qoshish') {
        $login = hs_post('login');
        $pass = isset($_POST['parol']) ? (string) $_POST['parol'] : '';
        if (!preg_match('/^[A-Za-z0-9_.]{3,40}$/', $login)) {
            hs_flash("Login 3–40 ta lotin harf, raqam, _ yoki nuqtadan iborat bo'lsin.", 'err');
        } elseif (strcasecmp($login, (string) hs_config('admin_login', '')) === 0) {
            hs_flash('Bu login egasiga tegishli.', 'err');
        } elseif (($pp = hs_password_problem($pass, hs_post('turi'))) !== null) {
            hs_flash($pp, 'err');
        } else {
            try {
                $kind = hs_user_kind(array('kind' => hs_post('turi')));
                $st = $db->prepare('INSERT INTO users(login, name, password_hash, branch, active, created_at, kind) VALUES(?, ?, ?, ?, 1, ?, ?)');
                $st->execute(array($login, mb_substr(hs_post('ism'), 0, 80), password_hash($pass, PASSWORD_DEFAULT), $kind === 'operator' ? preg_replace('/[^a-z0-9-]/', '', hs_post('filial')) : '', hs_now(), $kind));
                hs_audit($user['login'], "foydalanuvchi qo'shildi", $login . ' (' . $kind . ')');
                hs_flash("Qo'shildi: {$login}");
            } catch (PDOException $e) {
                hs_flash('Bunday login allaqachon bor.', 'err');
            }
        }
    } elseif ($action === 'holat') {
        $id = (int) hs_post('id');
        $active = hs_post('faol') === '1' ? 1 : 0;
        $db->prepare('UPDATE users SET active = ? WHERE id = ?')->execute(array($active, $id));
        hs_audit($user['login'], $active ? 'operator yoqildi' : "operator to'xtatildi", "#{$id}");
        hs_flash($active ? 'Operator yoqildi.' : "Operator to'xtatildi — darhol paneldan chiqariladi.");
    } elseif ($action === 'parol') {
        $id = (int) hs_post('id');
        $pass = isset($_POST['parol']) ? (string) $_POST['parol'] : '';
        $kst = $db->prepare('SELECT kind FROM users WHERE id = ?');
        $kst->execute(array($id));
        if (($pp = hs_password_problem($pass, (string) $kst->fetchColumn())) !== null) {
            hs_flash($pp, 'err');
        } else {
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute(array(password_hash($pass, PASSWORD_DEFAULT), $id));
            hs_audit($user['login'], "operator paroli o'zgartirildi", "#{$id}");
            hs_flash("Parol o'zgartirildi.");
        }
    } elseif ($action === 'filial') {
        $id = (int) hs_post('id');
        $kind = hs_user_kind(array('kind' => hs_post('turi')));
        $db->prepare('UPDATE users SET branch = ?, kind = ? WHERE id = ?')->execute(array($kind === 'operator' ? preg_replace('/[^a-z0-9-]/', '', hs_post('filial')) : '', $kind, $id));
        hs_audit($user['login'], 'foydalanuvchi turi/filiali', "#{$id} {$kind}");
        hs_flash('Saqlandi.');
    } elseif ($action === 'ochirish') {
        $id = (int) hs_post('id');
        $db->prepare('DELETE FROM users WHERE id = ?')->execute(array($id));
        hs_audit($user['login'], "operator o'chirildi", "#{$id}");
        hs_flash("Operator o'chirildi.");
    }
    hs_redirect('/admin/foydalanuvchilar.php');
}

$branches = hs_branch_names();
unset($branches['boshqa-viloyat']);
$users = hs_db()->query('SELECT * FROM users ORDER BY id')->fetchAll();

hs_page_start('Foydalanuvchilar', $user);

// Ilgari "paroli faqat secrets.php da" deb yozilgan edi — lekin uni Sozlamalar'dan ham o'zgartirsa bo'ladi.
echo '<section class="card"><h2>Egasi</h2><p><b>' . h(hs_config('admin_login', '—')) . '</b> — hamma bo\'limlar. Parolni <a href="/admin/sozlamalar.php#parol">Sozlamalar</a> bo\'limida o\'zgartirish mumkin. Unutilsa — kompyuterda <span class="code">php tools/admin-parol.php</span>.</p></section>';

$branchSelect = function ($current, $id = '') use ($branches) {
    $html = '<select name="filial"' . ($id !== '' ? ' id="' . $id . '"' : '') . '><option value="">Barcha filiallar</option>';
    foreach ($branches as $k => $v) {
        $html .= '<option value="' . h($k) . '"' . ($current === $k ? ' selected' : '') . '>' . h($v) . '</option>';
    }
    return $html . '</select>';
};

$kindSelect = function ($current, $id) {
    $html = '<select name="turi" id="' . $id . '">';
    foreach (hs_user_kinds() as $k => $v) {
        $html .= '<option value="' . h($k) . '"' . ($current === $k ? ' selected' : '') . '>' . h($v) . '</option>';
    }
    return $html . '</select>';
};

echo '<section class="card"><h2>Foydalanuvchilar</h2><ul class="muted">'
    . '<li><b>Savdo operatori</b> — faqat Bosh sahifa va Arizalar. Filial tanlansa — faqat o\'sha filial arizalari.</li>'
    . '<li><b>Ijara boshlig\'i</b> — faqat Ijara bo\'limi: joy, qavat, ijarachi qo\'shadi, kassadan chiqim qiladi, hammasini ko\'radi.</li>'
    . '<li><b>Ijara ishchisi</b> — faqat Ijara bo\'limi: pul qabul qiladi, shartnoma yuklaydi va yuklab oladi.</li></ul>'
    . '<p class="hint">Savdo operatorlari shu panelga (/admin/) kiradi. Ijara xodimlari alohida manzilga — <b>/ijara/</b> — kiradi va faqat ijarani ko\'radi. Bir paneldagi login ikkinchisida ishlamaydi.</p>';
if (!$users) {
    echo '<p class="muted">Hozircha foydalanuvchi yo\'q.</p>';
}
foreach ($users as $u) {
    $uk = hs_user_kind($u);
    $kinds = hs_user_kinds();
    echo '<div class="repeat-row"><h3>' . h($u['login']) . ($u['name'] ? ' — ' . h($u['name']) : '') . ' <span class="pill st-qongiroq">' . h($kinds[$uk]) . '</span> ' . ((int) $u['active'] ? '<span class="pill pill-ok">faol</span>' : '<span class="pill st-rad">to\'xtatilgan</span>') . '</h3>';
    echo '<div class="grid grid-3">';
    echo '<form method="post" action="/admin/foydalanuvchilar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="filial"><input type="hidden" name="id" value="' . (int) $u['id'] . '"><label for="u' . (int) $u['id'] . '-k">Turi</label>' . $kindSelect($uk, 'u' . (int) $u['id'] . '-k') . '<label for="u' . (int) $u['id'] . '-f">Filial (savdo operatori uchun)</label>' . $branchSelect($u['branch'], 'u' . (int) $u['id'] . '-f') . '<div class="actions"><button class="btn outline small" type="submit">Saqlash</button></div></form>';
    echo '<form method="post" action="/admin/foydalanuvchilar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="parol"><input type="hidden" name="id" value="' . (int) $u['id'] . '"><label for="u' . (int) $u['id'] . '-p">Yangi parol</label><input id="u' . (int) $u['id'] . '-p" type="password" name="parol" autocomplete="new-password" minlength="4" required><div class="actions"><button class="btn outline small" type="submit">Parolni o\'zgartirish</button></div></form>';
    echo '<div><p class="label">Boshqaruv</p><div class="actions">';
    echo '<form class="inline-form" method="post" action="/admin/foydalanuvchilar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . (int) $u['id'] . '"><input type="hidden" name="faol" value="' . ((int) $u['active'] ? '0' : '1') . '"><button class="btn outline small" type="submit">' . ((int) $u['active'] ? 'To\'xtatish' : 'Yoqish') . '</button></form>';
    echo '<form class="inline-form" method="post" action="/admin/foydalanuvchilar.php" data-confirm="Operator o\'chirilsinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . (int) $u['id'] . '"><button class="btn danger small" type="submit">O\'chirish</button></form>';
    echo '</div></div></div></div>';
}
echo '</section>';

echo '<form class="card" method="post" action="/admin/foydalanuvchilar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
echo '<h2>Yangi foydalanuvchi</h2><div class="grid grid-2">';
echo '<div><label for="yangi-turi">Turi</label>' . $kindSelect('operator', 'yangi-turi') . '</div><div></div>';
echo '<div><label for="login">Login</label><input id="login" type="text" name="login" required pattern="[A-Za-z0-9_.]{3,40}" autocomplete="off"></div>';
echo '<div><label for="ism">Ismi</label><input id="ism" type="text" name="ism" maxlength="80"></div>';
echo '<div><label for="parol">Parol</label><input id="parol" type="password" name="parol" required minlength="4" autocomplete="new-password"><p class="hint">Savdo operatori: kamida 10 belgi, harf va raqam. Ijara xodimi: kamida 4 belgi.</p></div>';
echo '<div><label for="yangi-filial">Filial (savdo operatori uchun)</label>' . $branchSelect('', 'yangi-filial') . '</div>';
echo '</div><div class="actions"><button class="btn" type="submit">Qo\'shish</button></div></form>';

hs_page_end();
