<?php
require __DIR__ . '/_lib/bootstrap.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    if (hs_post('amal') === 'ochish') {
        $ok = hs_unblock((int) hs_post('id'), $user['login']);
        hs_flash($ok ? 'Blok ochildi.' : 'Blok topilmadi yoki allaqachon ochilgan.', $ok ? 'ok' : 'err');
    }
    hs_redirect('/admin/bloklar.php');
}

$active = hs_db()->query('SELECT * FROM blocks WHERE unblocked_at IS NULL ORDER BY id DESC')->fetchAll();
$history = hs_db()->query('SELECT * FROM blocks WHERE unblocked_at IS NOT NULL ORDER BY id DESC LIMIT 30')->fetchAll();
$attempts = hs_db()->query('SELECT * FROM login_attempts ORDER BY id DESC LIMIT 100')->fetchAll();

hs_page_start('Bloklar', $user);

echo '<section class="card"><h2>Hozir bloklangan (' . count($active) . ')</h2>';
if (!$active) {
    echo '<p class="muted">Bloklangan qurilma yo\'q.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>IP</th><th>Qurilma</th><th>Kiritilgan login</th><th></th></tr></thead><tbody>';
    foreach ($active as $b) {
        echo '<tr><td class="nowrap">' . h(date('d.m.Y H:i', strtotime($b['created_at']))) . '</td><td>' . h($b['ip']) . '</td><td>' . h(hs_describe_agent($b['user_agent'])) . '</td><td>' . h($b['last_login']) . '</td>';
        echo '<td><form method="post" action="/admin/bloklar.php" data-confirm="Blok ochilsinmi? Bu qurilma yana 5 marta urinish imkoniga ega bo\'ladi.">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochish"><input type="hidden" name="id" value="' . (int) $b['id'] . '"><button class="btn outline small" type="submit">Blokni ochish</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

$labels = array('ok' => array('pill-ok', 'kirdi'), 'fail' => array('pill-err', "noto'g'ri"), 'blocked' => array('st-rad', 'bloklangan'));
echo '<section class="card"><h2>Oxirgi 100 ta kirish urinishi</h2><div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>Login</th><th>Natija</th><th>IP</th><th>Qurilma</th></tr></thead><tbody>';
foreach ($attempts as $a) {
    $l = isset($labels[$a['result']]) ? $labels[$a['result']] : array('', $a['result']);
    echo '<tr><td class="nowrap">' . h(date('d.m H:i:s', strtotime($a['created_at']))) . '</td><td>' . h($a['login']) . '</td><td><span class="pill ' . $l[0] . '">' . h($l[1]) . '</span></td><td>' . h($a['ip']) . '</td><td>' . h(hs_describe_agent($a['user_agent'])) . '</td></tr>';
}
echo '</tbody></table></div></section>';

if ($history) {
    echo '<section class="card"><h2>Ochilgan bloklar</h2><div class="table-wrap"><table><thead><tr><th>Bloklangan</th><th>IP</th><th>Ochilgan</th><th>Kim ochdi</th></tr></thead><tbody>';
    foreach ($history as $b) {
        echo '<tr><td class="nowrap">' . h($b['created_at']) . '</td><td>' . h($b['ip']) . '</td><td class="nowrap">' . h($b['unblocked_at']) . '</td><td>' . h($b['unblocked_by']) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';
}

hs_page_end();
