<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/metrika.php';
require_once __DIR__ . '/_lib/leads.php';

$user = hs_require_login(true);

$periods = array(
    'bugun' => array('Bugun', 'today', 'today', 0),
    'kecha' => array('Kecha', 'yesterday', 'yesterday', 1),
    '7' => array('7 kun', '6daysAgo', 'today', 6),
    '30' => array('30 kun', '29daysAgo', 'today', 29),
);
$p = hs_get('davr', '7');
if (!isset($periods[$p])) {
    $p = '7';
}
list($pLabel, $d1, $d2, $back) = $periods[$p];

hs_page_start('Statistika', $user);

echo '<div class="actions">';
foreach ($periods as $k => $v) {
    echo $k === $p ? '<span class="btn small">' . h($v[0]) . '</span>' : '<a class="btn outline small" href="/admin/statistika.php?davr=' . h($k) . '">' . h($v[0]) . '</a>';
}
echo '</div><br>';

// Arizalar bazadan — Metrika bo'lmasa ham ishlaydi.
$from = date('Y-m-d', strtotime("-{$back} day")) . ' 00:00:00';
$to = $p === 'kecha' ? date('Y-m-d', strtotime('-1 day')) . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
$st = hs_db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at BETWEEN ? AND ?');
$st->execute(array($from, $to));
$leadCount = (int) $st->fetchColumn();
$st = hs_db()->prepare('SELECT source, COUNT(*) c FROM leads WHERE created_at BETWEEN ? AND ? GROUP BY source ORDER BY c DESC');
$st->execute(array($from, $to));
$leadSources = array();
foreach ($st->fetchAll() as $r) {
    $leadSources[] = array(hs_source_label($r['source']), (int) $r['c']);
}
$st = hs_db()->prepare('SELECT branch, COUNT(*) c, SUM(status = \'sotildi\') s FROM leads WHERE created_at BETWEEN ? AND ? GROUP BY branch ORDER BY c DESC');
$st->execute(array($from, $to));
$branchRows = $st->fetchAll();
$names = hs_branch_names();

if (!hs_metrika_ready()) {
    echo '<section class="card"><h2>Yandex Metrika ulanmagan</h2>';
    echo '<p>Tashriflar, manbalar va shaharlarni shu yerda ko\'rish uchun bir martalik token kerak:</p><ol>';
    echo '<li><a href="https://oauth.yandex.ru/client/new" target="_blank" rel="noopener noreferrer">oauth.yandex.ru</a> da yangi ilova yarating, ruxsatlardan <b>Yandex.Metrika: получение статистики</b> ni belgilang.</li>';
    echo '<li>Tokenni oling va hostingdagi <span class="code">api/secrets.php</span> ga <span class="code">\'metrika_token\' => \'...\'</span> qatorini qo\'shing.</li>';
    echo '<li>Metrika\'da maqsadlar bo\'lsin: <span class="code">phone_click</span>, <span class="code">telegram_click</span>, <span class="code">lead_sent</span> (JavaScript-событие).</li>';
    echo '</ol><p class="muted">Tokenni hech kimga (jumladan menga ham) yubormang — faqat secrets.php ga yozing.</p></section>';
}

$err = '';
$ov = hs_metrika_ready() ? hs_metrika_overview($d1, $d2, $err) : null;
if ($err !== '') {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
}

echo '<div class="grid grid-4">';
if ($ov) {
    echo '<div class="stat"><b>' . $ov['visits'] . '</b><span>Tashriflar</span></div>';
    echo '<div class="stat"><b>' . $ov['users'] . '</b><span>Odamlar</span></div>';
    echo '<div class="stat"><b>' . h($ov['goals']['phone_click'] === null ? '—' : $ov['goals']['phone_click']) . '</b><span>"Qo\'ng\'iroq" bosildi</span></div>';
    echo '<div class="stat"><b>' . h($ov['goals']['telegram_click'] === null ? '—' : $ov['goals']['telegram_click']) . '</b><span>Telegram\'ga o\'tishdi</span></div>';
}
echo '<div class="stat"><b>' . $leadCount . '</b><span>Arizalar</span></div>';
if ($ov && $ov['visits'] > 0) {
    $conv = round((($leadCount + (int) $ov['goals']['phone_click']) / $ov['visits']) * 100, 1);
    echo '<div class="stat"><b>' . $conv . '%</b><span>Konversiya (qo\'ng\'iroq + ariza / tashrif)</span></div>';
}
echo '</div>';

if ($ov) {
    $missing = array_keys(array_filter($ov['goals'], 'is_null'));
    if ($missing) {
        echo '<p class="flash flash-warn">Metrika\'da bu maqsad(lar) topilmadi: <span class="code">' . h(implode(', ', $missing)) . '</span> — "Цели" bo\'limida JavaScript-событие sifatida qo\'shing.</p>';
    }
}

if ($ov) {
    echo '<div class="grid grid-2">';
    echo '<section class="card"><h2>Tashriflar — oxirgi 7 kun</h2>' . hs_bar_chart($ov['daily7'], 'Tashriflar') . '</section>';

    $src = array();
    foreach (hs_metrika_breakdown('ym:s:lastTrafficSource', $d1, $d2, 10, $err) as $r) {
        $src[] = array(hs_traffic_source_label($r['id'], $r['name']), $r['value']);
    }
    echo '<section class="card"><h2>Qayerdan kelishdi</h2>' . hs_share_list($src) . '</section>';

    $social = array();
    foreach (hs_metrika_breakdown('ym:s:lastSocialNetwork', $d1, $d2, 8, $err) as $r) {
        if ($r['id'] !== '' && $r['id'] !== 'null') {
            $social[] = array($r['name'], $r['value']);
        }
    }
    echo '<section class="card"><h2>Ijtimoiy tarmoqlar</h2>' . hs_share_list($social) . '</section>';

    $cities = array();
    foreach (hs_metrika_breakdown('ym:s:regionCity', $d1, $d2, 10, $err) as $r) {
        $cities[] = array($r['name'] !== '' ? $r['name'] : "Aniqlanmagan", $r['value']);
    }
    echo '<section class="card"><h2>Shaharlar</h2>' . hs_share_list($cities) . '</section>';

    $devices = array();
    foreach (hs_metrika_breakdown('ym:s:deviceCategory', $d1, $d2, 5, $err) as $r) {
        $devices[] = array(hs_device_label($r['id'], $r['name']), $r['value']);
    }
    echo '<section class="card"><h2>Qurilmalar</h2>' . hs_share_list($devices) . '</section>';

    echo '<section class="card"><h2>Eng ko\'p ko\'rilgan sahifalar</h2>' . hs_share_list(hs_metrika_top_pages($d1, $d2, $err)) . '</section>';
    echo '</div>';
}

echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Arizalar qayerdan kelgan</h2>' . hs_share_list($leadSources) . '</section>';
echo '<section class="card"><h2>Filiallar bo\'yicha</h2>';
if (!$branchRows) {
    echo '<p class="muted">Bu davrda ariza yo\'q.</p>';
} else {
    echo '<table><thead><tr><th>Filial</th><th class="right">Arizalar</th><th class="right">Sotildi</th><th class="right">%</th></tr></thead><tbody>';
    foreach ($branchRows as $r) {
        $label = $r['branch'] === '' ? 'Tanlanmagan' : (isset($names[$r['branch']]) ? $names[$r['branch']] : $r['branch']);
        $pct = $r['c'] > 0 ? round(($r['s'] / $r['c']) * 100) : 0;
        echo '<tr><td>' . h($label) . '</td><td class="right">' . (int) $r['c'] . '</td><td class="right">' . (int) $r['s'] . '</td><td class="right">' . $pct . '%</td></tr>';
    }
    echo '</tbody></table>';
}
echo '</section></div>';

echo '<p class="muted">Davr: ' . h($pLabel) . '. Metrika ma\'lumoti 10 daqiqada bir yangilanadi.</p>';
hs_page_end();
