<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/leads.php';
require_once __DIR__ . '/_lib/metrika.php';
require_once __DIR__ . '/_lib/content.php';
require_once __DIR__ . '/_lib/tgchats.php';

$user = hs_require_login();
$owner = hs_is_owner($user);
$db = hs_db();
list($scopeSql, $scopeArgs) = hs_lead_scope($user);

$today = date('Y-m-d');
$st = $db->prepare("SELECT COUNT(*) FROM leads WHERE substr(created_at,1,10) = ? {$scopeSql}");
$st->execute(array_merge(array($today), $scopeArgs));
$leadsToday = (int) $st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM leads WHERE status = 'yangi' {$scopeSql}");
$st->execute($scopeArgs);
$leadsNew = (int) $st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM leads WHERE created_at >= ? AND status = 'sotildi' {$scopeSql}");
$st->execute(array_merge(array(date('Y-m-d', strtotime('-29 day')) . ' 00:00:00'), $scopeArgs));
$sold30 = (int) $st->fetchColumn();

$days = array();
for ($i = 6; $i >= 0; $i--) {
    $days[date('Y-m-d', strtotime("-{$i} day"))] = 0;
}
$st = $db->prepare("SELECT substr(created_at,1,10) d, COUNT(*) c FROM leads WHERE created_at >= ? {$scopeSql} GROUP BY d");
$st->execute(array_merge(array(key($days) . ' 00:00:00'), $scopeArgs));
foreach ($st->fetchAll() as $r) {
    $days[$r['d']] = (int) $r['c'];
}
$leadChart = array();
foreach ($days as $d => $c) {
    $leadChart[] = array(date('d.m', strtotime($d)), $c);
}

$st = $db->prepare("SELECT * FROM leads WHERE 1=1 {$scopeSql} ORDER BY id DESC LIMIT 6");
$st->execute($scopeArgs);
$latest = $st->fetchAll();

$m = null;
$mError = '';
if ($owner && hs_metrika_ready()) {
    // Metrika javobini kutayotganda sessiya qulfi boshqa bo'limlarni to'sib qo'ymasin.
    hs_session_release();
    $m = hs_metrika_overview('today', 'today', $mError);
}

$hour = (int) date('G');
$greet = $hour < 11 ? 'Xayrli tong' : ($hour < 18 ? 'Xayrli kun' : 'Xayrli kech');
hs_page_start('Bosh sahifa', $user, $greet . ', ' . $user['name'] . '. Bugun: ' . date('d.m.Y'));

if ($mError !== '') {
    echo '<p class="flash flash-warn">' . h($mError) . '</p>';
}

echo '<div class="kpis">';
echo hs_kpi('inbox', $leadsToday, 'Bugungi arizalar', '<a href="/admin/arizalar.php?dan=' . $today . '">ko\'rish →</a>');
echo hs_kpi('clock', $leadsNew, 'Javob kutayotgan', $leadsNew > 0 ? '<a href="/admin/arizalar.php?holat=yangi">qo\'ng\'iroq qilish →</a>' : 'hammasiga javob berilgan', $leadsNew > 0 ? 'yellow' : 'green');
if ($owner) {
    if ($m) {
        echo hs_kpi('eye', $m['visits'], 'Bugungi tashriflar', (int) $m['users'] . ' ta odam');
        echo hs_kpi('phone', $m['goals']['phone_click'] === null ? '—' : $m['goals']['phone_click'], '"Qo\'ng\'iroq" bosildi', $m['goals']['telegram_click'] !== null ? 'Telegram\'ga: ' . (int) $m['goals']['telegram_click'] : '');
    } else {
        echo hs_kpi('eye', '—', 'Bugungi tashriflar', '<a href="/admin/statistika.php">Metrika\'ni ulash →</a>', 'muted');
        echo hs_kpi('phone', '—', '"Qo\'ng\'iroq" bosildi', '<a href="/admin/statistika.php">Metrika\'ni ulash →</a>', 'muted');
    }
} else {
    echo hs_kpi('check', $sold30, 'Sotildi (30 kun)', '', 'green');
}
echo '</div>';

echo '<div class="grid grid-2 stretch">';
echo '<section class="card"><div class="card-head"><h2>Arizalar — oxirgi 7 kun</h2><span class="muted">' . array_sum($days) . ' ta</span></div>' . hs_bar_chart($leadChart, 'Kunlik arizalar') . '</section>';
if ($m && !empty($m['daily7'])) {
    echo '<section class="card"><div class="card-head"><h2>Tashriflar — oxirgi 7 kun</h2><a class="btn outline small" href="/admin/statistika.php">Batafsil</a></div>' . hs_bar_chart($m['daily7'], 'Kunlik tashriflar') . '</section>';
}
echo '</div>';

if ($owner) {
    // Sozlash holati — nima ishlayapti, nima qolgan. Hammasi ulangach yashiriladi.
    $checks = array(
        array(true, 'Admin panel va parol', 'Kirish himoyasi va 5 urinishdan keyin blok ishlayapti.', ''),
        array((string) hs_config('token', '') !== '' && hs_tg_active_count() > 0, 'Telegram bot', 'Arizalar kimga borishi — Telegram bo\'limida.', '/admin/telegram.php'),
        array(true, 'Arizalar bazasi', 'Har bir ariza saqlanadi, Telegram ishlamasa ham yo\'qolmaydi.', ''),
        array(hs_metrika_ready(), 'Yandex Metrika', 'Tashriflar, manbalar, shaharlar.', '/admin/statistika.php'),
        array(hs_repo_mode() !== 'none', 'Saytni paneldan tahrirlash', hs_repo_mode() === 'local' ? 'Mahalliy sinov rejimi.' : 'GitHub tokeni orqali avtomatik nashr.', '/admin/nashr.php'),
        array(hs_setting('report_last', '') !== '', 'Kunlik Telegram hisoboti', hs_setting('report_last', '') !== '' ? 'Oxirgi: ' . hs_setting('report_last') : 'Hostingda Cron sozlangach ishlaydi.', '/admin/sozlamalar.php'),
    );
    $done = 0;
    foreach ($checks as $c) {
        $done += $c[0] ? 1 : 0;
    }
    if ($done < count($checks)) {
        echo '<section class="card"><div class="card-head"><h2>Sozlash holati</h2><span class="muted">' . $done . ' / ' . count($checks) . '</span></div><ul class="checklist cols">';
        foreach ($checks as $c) {
            $title = $c[3] !== '' && !$c[0] ? '<a href="' . h($c[3]) . '">' . h($c[1]) . '</a>' : h($c[1]);
            echo '<li><span class="state ' . ($c[0] ? 'on' : 'off') . '">' . hs_icon($c[0] ? 'check' : 'clock') . '</span><div><b>' . $title . '</b><small>' . h($c[2]) . '</small></div></li>';
        }
        echo '</ul></section>';
    }
}

echo '<section class="card"><div class="card-head"><h2>Oxirgi arizalar</h2><a class="btn outline small" href="/admin/arizalar.php">Barchasi</a></div>';
hs_render_leads_table($latest);
echo '</section>';

echo '<section class="card"><div class="card-head"><h2>Bo\'limlar</h2></div><div class="tiles">';
foreach (hs_nav_groups() as $items) {
    foreach ($items as $it) {
        if ($it[0] === '/admin/' || !hs_nav_visible($user, $it[3])) {
            continue;
        }
        echo '<a class="tile" href="' . h($it[0]) . '">' . hs_icon($it[2]) . '<span><b>' . h($it[1]) . '</b><small class="muted">' . h($it[4]) . '</small></span></a>';
    }
}
echo '</div></section>';

hs_page_end();
