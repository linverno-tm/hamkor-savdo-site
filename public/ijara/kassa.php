<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

$user = hs_ij_login();
$manage = hs_ij_can_manage($user);
$db = hs_db();

$objectId = (int) (hs_post('obyekt') !== '' ? hs_post('obyekt') : hs_get('obyekt'));
$object = $objectId > 0 ? hs_ij_object($objectId) : null;
if (!$object) {
    $objectId = 0;
}
$back = '/ijara/kassa.php' . ($objectId ? '?obyekt=' . $objectId : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    if ($action === 'kirim') {
        $err = hs_ij_cash_save($user, 'in', (int) hs_post('tenant_id'));
        hs_flash($err === null ? 'Pul qabul qilindi.' : $err, $err === null ? 'ok' : 'err');
        hs_redirect($back);
    }
    if (!$manage) {
        hs_ij_forbidden($user, $back);
    }
    if ($action === 'chiqim') {
        $err = hs_ij_cash_save($user, 'out');
        hs_flash($err === null ? 'Chiqim yozildi.' : $err, $err === null ? 'ok' : 'err');
    } elseif ($action === 'yozuv_ochirish') {
        $err = hs_ij_cash_delete($user, (int) hs_post('yozuv'));
        hs_flash($err === null ? 'Yozuv o\'chirildi.' : $err, $err === null ? 'ok' : 'err');
    }
    hs_redirect($back);
}

hs_session_release();
$rate = hs_ij_rate();
$period = hs_ij_period();
$sum = hs_ij_summary($period, $objectId);
$objects = $db->query('SELECT * FROM ij_objects ORDER BY name')->fetchAll();

/** Joriy filtrlarni saqlagan holda havola. */
$link = function ($changes) use ($objectId, $period) {
    $q = array('obyekt' => $objectId ?: null, 'davr' => $period['key']);
    if ($period['key'] === 'boshqa') {
        $q['dan'] = $period['from'];
        $q['gacha'] = $period['to'];
    }
    $q = array_filter(array_merge($q, $changes), function ($v) {
        return $v !== null && $v !== '' && $v !== 0;
    });
    return '/ijara/kassa.php' . ($q ? '?' . http_build_query($q) : '');
};

hs_page_start($object ? 'Kassa — ' . $object['name'] : 'Kassa', $user, $object ? 'Faqat shu binoning puli' : 'Hamma binolar birga. Binoni tanlasangiz — faqat uning kassasi.');

// Bino tanlash
echo '<nav class="seg" aria-label="Bino">';
echo $objectId === 0 ? '<span>Hamma binolar</span>' : '<a href="' . h($link(array('obyekt' => null))) . '">Hamma binolar</a>';
foreach ($objects as $o) {
    echo (int) $o['id'] === $objectId ? '<span>' . h($o['name']) . '</span>' : '<a href="' . h($link(array('obyekt' => (int) $o['id']))) . '">' . h($o['name']) . '</a>';
}
echo '</nav>';

// Qoldiq va kurs
echo '<div class="grid grid-2 stretch">';
echo hs_ij_balance_card(hs_ij_cash_balance($rate, $objectId ?: null), $rate, $object ? $object['name'] . ' kassasida hozir' : 'Kassada hozir — hamma binolar', '');
echo '<section class="card ij-rate" data-ij-rate><div class="card-head"><h2>Dollar kursi</h2><span class="muted" data-ij-rate-at>' . ($rate ? h($rate['source']) : '') . '</span></div>';
echo '<div data-ij-rate-body>' . hs_ij_rate_card($rate) . '</div></section>';
echo '</div>';

if ($objectId === 0 && count($objects) > 1) {
    echo '<section class="card"><h2>Har bir bino kassasi</h2><div class="table-wrap"><table><thead><tr><th>Bino</th><th class="right">Naqd so\'m</th><th class="right">Naqd dollar</th><th class="right">Hisob raqamda</th><th class="right">Jami (so\'mda)</th></tr></thead><tbody>';
    foreach ($objects as $o) {
        $c = hs_ij_cash_balance($rate, $o['id']);
        $w = $c['wallets'];
        echo '<tr><td><a href="' . h($link(array('obyekt' => (int) $o['id']))) . '"><b>' . h($o['name']) . '</b></a></td>';
        echo '<td class="right nowrap">' . h(hs_ij_money($w['uzs_naqd'], 'UZS')) . '</td><td class="right nowrap">' . h(hs_ij_money($w['usd_naqd'], 'USD')) . '</td>';
        echo '<td class="right nowrap">' . h(hs_ij_money($w['uzs_hisob'], 'UZS')) . (abs($w['usd_hisob']) > 0.001 ? '<br><small>' . h(hs_ij_money($w['usd_hisob'], 'USD')) . '</small>' : '') . '</td>';
        echo '<td class="right nowrap"><b>' . h(hs_ij_money($c['total_uzs'], 'UZS')) . '</b></td></tr>';
    }
    echo '</tbody></table></div></section>';
}

// Davr — shu blok va pastdagi daftar davr almashganda sahifani qayta yuklamay yangilanadi (admin.js, data-ij-live).
echo '<div data-ij-live="davr">';
echo '<section class="card"><div class="card-head"><h2>' . h($period['label']) . '</h2>';
echo '<a class="btn outline small" href="' . h('/ijara/hisobot.php?' . http_build_query(array_filter(array('obyekt' => $objectId ?: null, 'davr' => $period['key'], 'dan' => $period['key'] === 'boshqa' ? $period['from'] : null, 'gacha' => $period['key'] === 'boshqa' ? $period['to'] : null)))) . '">' . hs_icon('upload') . ' Excel hisobot</a></div>';
echo '<nav class="seg" aria-label="Davr">';
foreach (array('oy' => 'Bu oy', 'otgan' => "O'tgan oy", 'yil' => 'Bu yil', 'hammasi' => 'Hammasi') as $k => $v) {
    echo $period['key'] === $k ? '<span>' . h($v) . '</span>' : '<a href="' . h($link(array('davr' => $k, 'dan' => null, 'gacha' => null))) . '">' . h($v) . '</a>';
}
echo '</nav>';
echo '<form class="ij-period" method="get" action="/ijara/kassa.php">' . ($objectId ? '<input type="hidden" name="obyekt" value="' . $objectId . '">' : '') . '<input type="hidden" name="davr" value="boshqa">';
echo '<div><label for="p-dan">Dan</label><input id="p-dan" type="date" name="dan" value="' . h($period['from']) . '"></div><div><label for="p-gacha">Gacha</label><input id="p-gacha" type="date" name="gacha" value="' . h($period['to']) . '"></div>';
echo '<button class="btn outline" type="submit">Ko\'rsatish</button></form>';

echo '<div class="kpis ij-kpis">';
echo hs_kpi('check', hs_ij_short($sum['in']), 'Kirim', $sum['count'] . ' ta yozuv', 'green');
echo hs_kpi('logout', hs_ij_short($sum['out']), 'Chiqim', '', $sum['out'] > 0 ? 'yellow' : '');
echo hs_kpi('wallet', hs_ij_short($sum['in'] - $sum['out']), 'Farqi (kirim − chiqim)');
echo '</div></section>';

echo '<div class="grid grid-2 stretch">';
$rows = array();
foreach ($sum['by_method'] as $m) {
    $note = $m['currency'] === 'USD' ? hs_ij_money($m['amount'], 'USD') . ', o\'rtacha kurs ' . hs_ij_fmt($m['amount'] > 0 ? $m['uzs'] / $m['amount'] : 0, 2) : '';
    $rows[] = array(hs_ij_method_label($m['currency'], $m['method']), $m['uzs'], $note);
}
echo '<section class="card"><h2>Pul qanday tushdi</h2>' . hs_ij_share($rows, $sum['in']) . '</section>';
$rows = array();
foreach ($sum['by_purpose_in'] as $k => $u) {
    $rows[] = array(hs_ij_purpose_name('in', $k), $u);
}
echo '<section class="card"><h2>Nima uchun olindi</h2>' . hs_ij_share($rows, $sum['in']);
if ($sum['by_purpose_out']) {
    $rows = array();
    foreach ($sum['by_purpose_out'] as $k => $u) {
        $rows[] = array(hs_ij_purpose_name('out', $k), $u);
    }
    echo '<h3 class="ij-sub">Chiqim</h3>' . hs_ij_share($rows, $sum['out']);
}
echo '</section></div>';

// Ijarachilar bo'yicha: kelishilgan oylik, davrda to'lashi kerak bo'lgani, to'lagani va
// davr oxiridagi holat (qarz / to'langan / oldindan). Davrda umuman to'lamaganlar ham chiqadi.
$purposes = hs_ij_in_purposes();
$used = array();
foreach ($sum['tenants'] as $t) {
    foreach ($t['purposes'] as $k => $u) {
        $used[$k] = true;
    }
}
$cols = array_intersect_key($purposes, $used);
$pTo = $period['to'] !== '' ? $period['to'] : date('Y-m-d');
$st = $db->prepare("SELECT t.*, o.name object_name FROM ij_tenants t JOIN ij_objects o ON o.id = t.object_id
    WHERE t.start_date <> '' AND t.start_date <= ? AND (t.end_date = '' OR t.end_date >= ?)" . ($objectId ? ' AND t.object_id = ?' : '') . ' ORDER BY o.name, t.name');
$st->execute($objectId ? array($pTo, $period['from'], $objectId) : array($pTo, $period['from']));
$rowsT = array();
foreach ($st->fetchAll() as $t) {
    $rowsT[(int) $t['id']] = $t;
}
foreach (array_keys($sum['tenants']) as $tid) {
    if (!isset($rowsT[$tid]) && ($t = hs_ij_tenant($tid))) {
        $o2 = hs_ij_object($t['object_id']);
        $t['object_name'] = $o2 ? $o2['name'] : '';
        $rowsT[$tid] = $t;
    }
}
$debtors = 0;
echo '<section class="card"><div class="card-head"><h2>Kim qancha to\'ladi</h2><span class="muted">' . count($rowsT) . ' ta ijarachi</span></div>';
if (!$rowsT) {
    echo '<p class="muted">Bu davrda ijarachi yo\'q.</p>';
} else {
    echo '<div class="table-wrap"><table class="ij-who"><thead><tr><th>Ijarachi</th>' . ($objectId ? '' : '<th>Bino</th>') . '<th class="right">Kelishilgan oylik</th><th class="right">Davrda to\'lashi kerak</th>';
    foreach ($cols as $v) {
        echo '<th class="right">' . h($v) . '</th>';
    }
    echo '<th class="right">Jami to\'lagan</th><th>' . h($period['key'] === 'hammasi' ? 'Bugungi holat' : 'Davr oxirida') . '</th></tr></thead><tbody>';
    foreach ($rowsT as $tid => $t) {
        $paid = isset($sum['tenants'][$tid]) ? $sum['tenants'][$tid] : array('purposes' => array(), 'uzs' => 0.0, 'usd' => 0.0, 'n' => 0);
        $cur = $t['rent_currency'];
        $pd = hs_ij_period_due($t, $period['from'], $period['to']);
        $bal = hs_ij_balance($t, $pTo);
        if ($bal['debt'] > ($cur === 'USD' ? 0.5 : 500)) {
            $debtors++;
        }
        echo '<tr><td><a href="/ijara/ijarachi.php?id=' . (int) $tid . '"><b>' . h($t['name']) . '</b></a><br><small class="muted">' . ($paid['n'] > 0 ? $paid['n'] . ' ta to\'lov' : 'to\'lov yo\'q') . ((int) $t['active'] === 1 ? '' : ' · ijara tugagan') . '</small></td>' . ($objectId ? '' : '<td>' . h($t['object_name']) . '</td>');
        echo '<td class="right nowrap">' . h(hs_ij_money($t['rent'], $cur)) . '</td>';
        echo '<td class="right nowrap">' . ($pd['months'] > 0 ? h(hs_ij_money($pd['due'], $cur)) . '<br><small class="muted">' . $pd['months'] . ' oy</small>' : '<span class="muted">—</span>') . '</td>';
        foreach ($cols as $k => $v) {
            echo '<td class="right nowrap">' . (isset($paid['purposes'][$k]) ? h(hs_ij_money($paid['purposes'][$k], 'UZS')) : '<span class="muted">—</span>') . '</td>';
        }
        echo '<td class="right nowrap"><b>' . h(hs_ij_money($paid['uzs'], 'UZS')) . '</b>' . ($paid['usd'] > 0 ? '<br><small class="muted">shundan $' . h(hs_ij_fmt($paid['usd'], 0)) . '</small>' : '') . '</td>';
        echo '<td>' . hs_ij_debt_pill($bal, $cur) . ($bal['paid_until'] !== '' ? '<br><small class="muted">' . h(date('d.m.Y', strtotime($bal['paid_until']))) . ' gacha to\'langan</small>' : '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
    if ($debtors > 0) {
        echo '<p class="hint ij-out">' . $debtors . ' ta ijarachida ' . h($period['key'] === 'hammasi' ? 'bugun' : 'davr oxirida') . ' qarz bor.</p>';
    }
}
echo '</section>';
echo '</div>';

// Formalar
$st = $db->prepare('SELECT t.id, t.name, o.name object_name FROM ij_tenants t JOIN ij_objects o ON o.id = t.object_id WHERE t.active = 1' . ($objectId ? ' AND t.object_id = ?' : '') . ' ORDER BY o.name, t.name');
$st->execute($objectId ? array($objectId) : array());
$tenants = $st->fetchAll();
echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Pul qabul qilish</h2>';
echo $tenants ? hs_ij_cash_form('in', '/ijara/kassa.php', array('amal' => 'kirim', 'obyekt' => $objectId ?: ''), $rate, $tenants) : '<p class="muted">Faol ijarachi yo\'q.</p>';
echo '</section>';
if ($manage) {
    echo '<section class="card"><h2>Chiqim (kassadan pul olish)</h2><p class="hint">Masalan: investorga topshirildi, elektr uchun to\'landi, ta\'mirlash.</p>';
    echo hs_ij_cash_form('out', '/ijara/kassa.php', array('amal' => 'chiqim', 'obyekt' => $objectId ?: ''), $rate, null, $objectId ? array($object) : $objects);
    echo '</section>';
}
echo '</div>';

// Yozuvlar
echo '<div data-ij-live="daftar">';
list($where, $args) = hs_ij_cash_where($period, $objectId);
$page = max(1, (int) hs_get('sahifa', '1'));
$per = 50;
$pages = max(1, (int) ceil($sum['count'] / $per));
$page = min($page, $pages);
echo '<section class="card"><div class="card-head"><h2>Kassa daftari</h2><span class="muted">' . $sum['count'] . ' ta yozuv</span></div>';
echo hs_ij_cash_table(hs_ij_cash_rows($where, $args, $per, ($page - 1) * $per), $back, true, $manage);
echo hs_pager($page, $pages, function ($p) use ($link) {
    return $link(array('sahifa' => $p > 1 ? $p : null));
});
echo '</section>';
echo '</div>';

hs_page_end();
