<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

$user = hs_ij_login();
$manage = hs_ij_can_manage($user);
$db = hs_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    if (!$manage) {
        hs_ij_forbidden($user);
    }
    if (hs_post('amal') === 'yangi') {
        $name = mb_substr(hs_post('name'), 0, 120);
        $below = max(0, min(10, (int) hs_post('floors_below')));
        $above = max(0, min(200, (int) hs_post('floors_above')));
        if ($name === '') {
            hs_flash('Obyekt nomini yozing.', 'err');
            hs_redirect('/ijara/#yangi');
        }
        if ($below + $above === 0) {
            hs_flash('Kamida bitta qavat bo\'lsin.', 'err');
            hs_redirect('/ijara/#yangi');
        }
        $area = hs_ij_num(hs_post('floor_area'));
        $db->beginTransaction();
        $st = $db->prepare('INSERT INTO ij_objects(name, address, land_area, floors_below, floors_above, note, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute(array($name, mb_substr(hs_post('address'), 0, 250), hs_ij_num(hs_post('land_area')), $below, $above, mb_substr(hs_post('note'), 0, 1000), hs_now(), hs_now()));
        $id = (int) $db->lastInsertId();
        $st = $db->prepare('INSERT INTO ij_floors(object_id, level, area) VALUES(?, ?, ?)');
        for ($l = -$below; $l <= $above; $l++) {
            if ($l !== 0) {
                $st->execute(array($id, $l, $area));
            }
        }
        $db->commit();
        hs_ij_sync_zones($id, array(HS_IJ_KOCHA => hs_post('zona_kocha') === '1', HS_IJ_BOSHQA => hs_post('zona_boshqa') === '1'));
        hs_audit($user['login'], 'ijara_obyekt', 'yangi: ' . $name);
        require_once __DIR__ . '/../admin/_lib/content.php';
        $files = hs_files_list('rasm');
        if ($files && ($e = hs_ij_photo_save($user, $id, $files[0])) !== null) {
            hs_flash('Surat saqlanmadi: ' . $e, 'err');
        }
        hs_flash("Obyekt qo'shildi. Endi har bir qavat maydonini tekshiring.");
        hs_redirect('/ijara/obyekt.php?id=' . $id . '#qavatlar');
    }
    hs_redirect('/ijara/');
}

hs_session_release();
$rate = hs_ij_rate();
$objects = $db->query('SELECT * FROM ij_objects ORDER BY name')->fetchAll();
$sumTotal = 0.0;
$sumUsed = 0.0;
$sumTenants = 0;
$floorsAll = 0;
$floorsRented = 0;
$floorsFull = 0;
$monthStart = date('Y-m-01');
$st = $db->prepare("SELECT object_id, SUM(amount_uzs) s FROM ij_cash WHERE direction = 'in' AND paid_at >= ? GROUP BY object_id");
$st->execute(array($monthStart));
$monthIn = array();
foreach ($st->fetchAll() as $r) {
    $monthIn[(int) $r['object_id']] = (float) $r['s'];
}
foreach ($objects as $i => $o) {
    $stats = hs_ij_object_stats($o['id']);
    $objects[$i]['stats'] = $stats;
    $objects[$i]['cash'] = hs_ij_cash_balance($rate, $o['id']);
    $sumTotal += $stats['total'];
    $sumUsed += $stats['used'];
    $sumTenants += $stats['tenants'];
    foreach (hs_ij_floors($o['id']) as $f) {
        if ((float) $f['area'] <= 0) {
            continue;
        }
        $floorsAll++;
        if ($f['used'] > 0) {
            $floorsRented++;
        }
        if ($f['free'] <= 0.001) {
            $floorsFull++;
        }
    }
}
$debtors = 0;
foreach ($db->query('SELECT * FROM ij_tenants WHERE active = 1')->fetchAll() as $t) {
    $b = hs_ij_balance($t);
    if ($b['debt'] > ($t['rent_currency'] === 'USD' ? 0.5 : 500)) {
        $debtors++;
    }
}
$bal = hs_ij_cash_balance($rate);

hs_page_start('Ijara', $user, 'Bugun: ' . date('d.m.Y'));

echo '<div class="kpis">';
echo hs_kpi('building', count($objects), 'Obyektlar', $floorsAll . ' ta qavat / joy');
echo hs_kpi('check', $floorsRented . ' / ' . $floorsAll, 'Ijaraga berilgan qavatlar', $floorsFull . ' tasi to\'liq band', $floorsRented < $floorsAll ? 'yellow' : 'green');
echo hs_kpi('box', hs_ij_area($sumUsed), 'Berilgan maydon', $sumTotal > 0 ? 'jami ' . h(hs_ij_area($sumTotal)) . ' dan ' . round($sumUsed / $sumTotal * 100) . '%, bo\'sh ' . h(hs_ij_area($sumTotal - $sumUsed)) : '');
echo hs_kpi('users', $sumTenants, 'Faol ijarachilar', $debtors > 0 ? '<span class="ij-out">' . $debtors . ' tasida qarz</span>' : 'qarzdor yo\'q', $debtors > 0 ? 'yellow' : 'green');
echo '</div>';

echo '<div class="grid grid-2 stretch">';
echo hs_ij_balance_card($bal, $rate, 'Kassada hozir — hamma binolar');
echo '<section class="card ij-rate" data-ij-rate><div class="card-head"><h2>Dollar kursi</h2><span class="muted" data-ij-rate-at>' . ($rate ? h($rate['source']) : '') . '</span></div>';
echo '<div data-ij-rate-body>' . hs_ij_rate_card($rate) . '</div>';
echo '<p class="hint">Sahifa ochiq tursa, kurs har 5 daqiqada o\'zi yangilanadi.</p></section>';
echo '</div>';

echo '<section class="card"><div class="card-head"><h2>Binolar</h2>' . ($manage ? '<a class="btn small" href="#yangi">+ Yangi obyekt</a>' : '') . '</div>';
if (!$objects) {
    echo '<p class="muted">Hali obyekt yo\'q.' . ($manage ? ' Pastdagi formadan birinchisini qo\'shing: nomi, qavatlari va har qavat maydoni.' : '') . '</p>';
} else {
    echo '<div class="ij-objects">';
    foreach ($objects as $o) {
        $s = $o['stats'];
        $c = $o['cash'];
        $url = '/ijara/obyekt.php?id=' . (int) $o['id'];
        $photo = hs_ij_photo_url($o);
        $pct = $s['total'] > 0 ? round($s['used'] / $s['total'] * 100) : 0;
        echo '<article class="ij-obj">';
        echo '<a class="ij-obj-photo" href="' . h($url) . '">' . ($photo !== '' ? '<img src="' . h($photo) . '" alt="" loading="lazy">' : '<span class="ij-obj-nophoto">' . hs_icon('building') . '</span>') . '<span class="ij-obj-pct">' . $pct . '% band</span></a>';
        echo '<div class="ij-obj-body"><h3><a href="' . h($url) . '">' . h($o['name']) . '</a></h3>' . ($o['address'] !== '' ? '<p class="muted">' . h($o['address']) . '</p>' : '');
        echo '<div class="ij-obj-occ"><span>Band <b>' . h(hs_ij_area($s['used'])) . '</b></span><span>Bo\'sh <b>' . h(hs_ij_area($s['free'])) . '</b></span></div>' . hs_ij_bar($s['used'], $s['total']);
        echo '<dl class="ij-obj-kv"><dt>Ijarachilar</dt><dd>' . $s['tenants'] . '</dd><dt>Kassada</dt><dd><b>' . h(hs_ij_money($c['total_uzs'], 'UZS')) . '</b>' . (abs($c['usd']) > 0.001 ? ' <small class="muted">($' . h(hs_ij_fmt($c['usd'], 0)) . ')</small>' : '') . '</dd>';
        echo '<dt>Bu oy tushdi</dt><dd>' . h(hs_ij_money(isset($monthIn[(int) $o['id']]) ? $monthIn[(int) $o['id']] : 0, 'UZS')) . '</dd></dl>';
        echo '<div class="actions tight"><a class="btn small" href="' . h($url) . '">Ochish</a><a class="btn outline small" href="/ijara/kassa.php?obyekt=' . (int) $o['id'] . '">Kassa</a></div></div></article>';
    }
    echo '</div>';
}
echo '</section>';

$last = hs_ij_cash_rows('1 = 1', array(), 8);
echo '<section class="card"><div class="card-head"><h2>Oxirgi pul harakatlari</h2><a class="btn outline small" href="/ijara/kassa.php">Hammasi</a></div>';
echo hs_ij_cash_table($last, '/ijara/kassa.php', true, $manage);
echo '</section>';

if ($manage) {
    echo '<details id="yangi" class="card"><summary class="summary-head">+ Yangi obyekt qo\'shish</summary>';
    echo '<form method="post" action="/ijara/" enctype="multipart/form-data">' . hs_csrf_field() . '<input type="hidden" name="amal" value="yangi">';
    echo '<div class="grid grid-2"><div><label for="name">Nomi</label><input id="name" type="text" name="name" required maxlength="120" placeholder="Masalan: Bobur ko\'chasidagi bino"></div>';
    echo '<div><label for="address">Manzil</label><input id="address" type="text" name="address" maxlength="250"></div></div>';
    echo '<div class="grid grid-2"><div><label for="land_area">Egallagan yer maydoni, kv.m</label><input id="land_area" type="text" name="land_area" inputmode="decimal" placeholder="Masalan: 600"><p class="hint">Ixtiyoriy. Ijaraga beriladigan maydon qavatlardan hisoblanadi.</p></div>';
    echo '<div><label for="floor_area">Har bir qavat maydoni, kv.m</label><input id="floor_area" type="text" name="floor_area" inputmode="decimal" placeholder="Masalan: 450"><p class="hint">Qavatlar har xil bo\'lsa, keyingi sahifada alohida o\'zgartirasiz.</p></div></div>';
    echo '<div class="grid grid-2"><div><label for="floors_below">Yerto\'la qavatlari (−1, −2 …)</label><select id="floors_below" name="floors_below">';
    for ($i = 0; $i <= 5; $i++) {
        echo '<option value="' . $i . '">' . ($i === 0 ? "Yo'q" : $i . ' ta (−1' . ($i > 1 ? ' … −' . $i : '') . ')') . '</option>';
    }
    echo '</select></div>';
    echo '<div><label for="floors_above">Yer usti qavatlari</label><input id="floors_above" type="number" name="floors_above" min="0" max="200" value="1" required></div></div>';
    echo '<div class="ij-zones"><label class="inline"><input type="checkbox" name="zona_kocha" value="1"> Ko\'cha — bino oldidagi tashqi joylar (prilavka, kiosk)</label><label class="inline"><input type="checkbox" name="zona_boshqa" value="1"> Boshqalar — bankomat, antenna va h.k.</label></div>';
    echo '<label for="rasm">Bino surati (ixtiyoriy)</label><input id="rasm" type="file" name="rasm" accept="image/jpeg,image/png,image/webp">';
    echo '<label for="note">Izoh</label><textarea id="note" name="note" maxlength="1000"></textarea>';
    echo '<div class="actions"><button class="btn" type="submit">Obyektni qo\'shish</button></div></form></details>';
}

hs_page_end();
