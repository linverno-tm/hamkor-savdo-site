<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/leads.php';
require_once __DIR__ . '/_lib/metrika.php';

$user = hs_require_login();
$db = hs_db();
list($scopeSql, $scopeArgs) = hs_lead_scope($user);

$today = date('Y-m-d');
$st = $db->prepare("SELECT COUNT(*) FROM leads WHERE substr(created_at,1,10) = ? {$scopeSql}");
$st->execute(array_merge(array($today), $scopeArgs));
$leadsToday = (int) $st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM leads WHERE status = 'yangi' {$scopeSql}");
$st->execute($scopeArgs);
$leadsNew = (int) $st->fetchColumn();

$days = array();
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} day"));
    $days[$d] = 0;
}
$st = $db->prepare("SELECT substr(created_at,1,10) d, COUNT(*) c FROM leads WHERE created_at >= ? {$scopeSql} GROUP BY d");
$st->execute(array_merge(array(array_keys($days)[0] . ' 00:00:00'), $scopeArgs));
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
if (hs_is_owner($user) && hs_metrika_ready()) {
    $m = hs_metrika_overview('today', 'today', $mError);
}

hs_page_start('Bosh sahifa', $user);

echo '<div class="grid grid-4">';
if ($m) {
    echo '<div class="stat"><b>' . (int) $m['visits'] . '</b><span>Bugungi tashriflar</span></div>';
    echo '<div class="stat"><b>' . (int) $m['users'] . '</b><span>Bugungi odamlar</span></div>';
    echo '<div class="stat"><b>' . h($m['goals']['phone_click'] === null ? '—' : $m['goals']['phone_click']) . '</b><span>"Qo\'ng\'iroq" bosildi</span></div>';
}
echo '<div class="stat"><b>' . $leadsToday . '</b><span>Bugungi arizalar</span></div>';
echo '<div class="stat"><b>' . $leadsNew . '</b><span>Javob kutayotgan arizalar</span></div>';
echo '</div>';

if (hs_is_owner($user) && !hs_metrika_ready()) {
    echo '<p class="flash flash-warn">Tashriflar statistikasi uchun Yandex Metrika tokeni kerak — <a href="/admin/statistika.php">Statistika</a> bo\'limida ko\'rsatma bor.</p>';
} elseif ($mError !== '') {
    echo '<p class="flash flash-warn">' . h($mError) . '</p>';
}

echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Arizalar — oxirgi 7 kun</h2>' . hs_bar_chart($leadChart, 'Kunlik arizalar') . '</section>';
if ($m && !empty($m['daily7'])) {
    echo '<section class="card"><h2>Tashriflar — oxirgi 7 kun</h2>' . hs_bar_chart($m['daily7'], 'Kunlik tashriflar') . '</section>';
}
echo '</div>';

echo '<section class="card"><h2>Oxirgi arizalar</h2>';
hs_render_leads_table($latest);
echo '<div class="actions"><a class="btn outline" href="/admin/arizalar.php">Barcha arizalar</a></div></section>';

hs_page_end();
