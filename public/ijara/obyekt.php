<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

$user = hs_ij_login();
$manage = hs_ij_can_manage($user);
$db = hs_db();

$id = (int) (hs_post('id') !== '' ? hs_post('id') : hs_get('id'));
$o = hs_ij_object($id);
if (!$o) {
    hs_flash('Obyekt topilmadi.', 'err');
    hs_redirect('/ijara/');
}
$back = '/ijara/obyekt.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    if (!$manage) {
        hs_ij_forbidden($user, $back);
    }
    $action = hs_post('amal');

    if ($action === 'obyekt') {
        $name = mb_substr(hs_post('name'), 0, 120);
        $below = max(0, min(10, (int) hs_post('floors_below')));
        $above = max(0, min(200, (int) hs_post('floors_above')));
        if ($name === '' || $below + $above === 0) {
            hs_flash('Nomi bo\'sh bo\'lmasin va kamida bitta qavat bo\'lsin.', 'err');
            hs_redirect($back . '#malumot');
        }
        // Olib tashlanadigan qavatlarda hech kim (hatto ijarasi tugagan ijarachi ham) turmagan bo'lishi kerak.
        $st = $db->prepare('SELECT f.level FROM ij_floors f JOIN ij_spaces s ON s.floor_id = f.id WHERE f.object_id = ? AND f.level < ' . HS_IJ_KOCHA . ' AND (f.level < ? OR f.level > ?) LIMIT 1');
        $st->execute(array($id, -$below, $above));
        $busy = $st->fetchColumn();
        if ($busy !== false) {
            hs_flash(hs_ij_level_name($busy) . " da ijarachi joyi bor — avval uni boshqa qavatga o'tkazing yoki ijarachini o'chiring.", 'err');
            hs_redirect($back . '#malumot');
        }
        $db->beginTransaction();
        $db->prepare('UPDATE ij_objects SET name = ?, address = ?, land_area = ?, floors_below = ?, floors_above = ?, note = ?, updated_at = ? WHERE id = ?')
            ->execute(array($name, mb_substr(hs_post('address'), 0, 250), hs_ij_num(hs_post('land_area')), $below, $above, mb_substr(hs_post('note'), 0, 1000), hs_now(), $id));
        $db->prepare('DELETE FROM ij_floors WHERE object_id = ? AND level < ' . HS_IJ_KOCHA . ' AND (level < ? OR level > ?)')->execute(array($id, -$below, $above));
        $ins = $db->prepare('INSERT OR IGNORE INTO ij_floors(object_id, level, area) VALUES(?, ?, 0)');
        for ($l = -$below; $l <= $above; $l++) {
            if ($l !== 0) {
                $ins->execute(array($id, $l));
            }
        }
        $db->commit();
        $zoneErr = hs_ij_sync_zones($id, array(HS_IJ_KOCHA => hs_post('zona_kocha') === '1', HS_IJ_BOSHQA => hs_post('zona_boshqa') === '1'));
        hs_audit($user['login'], 'ijara_obyekt', 'tahrir: ' . $name);
        hs_flash($zoneErr === null ? 'Saqlandi.' : 'Saqlandi, lekin: ' . $zoneErr, $zoneErr === null ? 'ok' : 'warn');
        hs_redirect($back);
    }

    if ($action === 'rasm') {
        require_once __DIR__ . '/../admin/_lib/content.php';
        $files = hs_files_list('rasm');
        $e = $files ? hs_ij_photo_save($user, $id, $files[0]) : 'Suratni tanlang.';
        hs_flash($e === null ? 'Bino surati saqlandi.' : $e, $e === null ? 'ok' : 'err');
        hs_redirect($back);
    }

    if ($action === 'rasm_ochirish') {
        hs_ij_photo_delete($user, $id);
        hs_flash('Surat olib tashlandi.');
        hs_redirect($back);
    }

    if ($action === 'qavatlar') {
        $areas = isset($_POST['area']) && is_array($_POST['area']) ? $_POST['area'] : array();
        $floors = hs_ij_floors($id);
        $warn = array();
        $st = $db->prepare('UPDATE ij_floors SET area = ? WHERE id = ? AND object_id = ?');
        foreach ($areas as $fid => $v) {
            $fid = (int) $fid;
            if (!isset($floors[$fid]) || is_array($v)) {
                continue;
            }
            $a = hs_ij_num($v);
            if ($a + 0.001 < $floors[$fid]['used']) {
                $warn[] = hs_ij_level_name($floors[$fid]['level']) . ': ijarachilarga ' . hs_ij_area($floors[$fid]['used']) . ' berilgan, maydon undan kam bo\'lolmaydi';
                continue;
            }
            $st->execute(array($a, $fid, $id));
        }
        hs_audit($user['login'], 'ijara_obyekt', 'qavat maydonlari: ' . $o['name']);
        hs_flash($warn ? "Qolganlari saqlandi. O'zgarmadi:\n" . implode("\n", $warn) : 'Qavat maydonlari saqlandi.', $warn ? 'warn' : 'ok');
        hs_redirect($back . '#qavatlar');
    }

    if ($action === 'ijarachi') {
        $f = hs_ij_tenant_fields();
        $problem = hs_ij_tenant_problem($f);
        if ($problem !== null) {
            hs_flash($problem, 'err');
            hs_redirect($back . '#yangi-ijarachi');
        }
        list($spaces, $err) = hs_ij_read_spaces($id);
        if ($err !== null) {
            hs_flash($err, 'err');
            hs_redirect($back . '#yangi-ijarachi');
        }
        $db->beginTransaction();
        $db->prepare('INSERT INTO ij_tenants(object_id, name, phone, activity, rent, rent_currency, months, start_date, end_date, active, note, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)')
            ->execute(array($id, $f['name'], $f['phone'], $f['activity'], $f['rent'], $f['rent_currency'], $f['months'], $f['start_date'], $f['end_date'], $f['note'], hs_now(), hs_now()));
        $tid = (int) $db->lastInsertId();
        hs_ij_save_spaces($tid, $spaces);
        $db->commit();
        // Shartnoma skani shu formaning o'zida yuklanishi mumkin.
        require_once __DIR__ . '/../admin/_lib/content.php';
        $fileErrs = array();
        foreach (hs_files_list('fayl') as $file) {
            $e = hs_ij_file_save($user, $tid, $file, 'Ijara shartnomasi');
            if ($e !== null) {
                $fileErrs[] = $file['name'] . ': ' . $e;
            }
        }
        hs_audit($user['login'], 'ijara_ijarachi', 'yangi: ' . $f['name'] . ' (' . $o['name'] . ')');
        if ($fileErrs) {
            hs_flash("Ijarachi qo'shildi, lekin shartnoma fayli saqlanmadi:\n" . implode("\n", $fileErrs), 'err');
        }
        hs_flash($spaces ? 'Ijarachi qo\'shildi.' : 'Ijarachi qo\'shildi, lekin unga joy belgilanmadi — ijarachi sahifasida qavat va maydonni kiriting.', $spaces ? 'ok' : 'warn');
        hs_redirect('/ijara/ijarachi.php?id=' . $tid);
    }

    if ($action === 'ochirish') {
        $st = $db->prepare('SELECT COUNT(*) FROM ij_tenants WHERE object_id = ?');
        $st->execute(array($id));
        if ((int) $st->fetchColumn() > 0) {
            hs_flash("Obyektda ijarachilar (yoki ularning to'lov tarixi) bor — o'chirib bo'lmaydi.", 'err');
            hs_redirect($back);
        }
        $db->prepare('DELETE FROM ij_objects WHERE id = ?')->execute(array($id));
        hs_audit($user['login'], 'ijara_obyekt', "o'chirildi: " . $o['name']);
        hs_flash('Obyekt o\'chirildi.');
        hs_redirect('/ijara/');
    }
    hs_redirect($back);
}

$floors = hs_ij_floors($id);
$stats = hs_ij_object_stats($id);
$st = $db->prepare('SELECT * FROM ij_tenants WHERE object_id = ? ORDER BY active DESC, name');
$st->execute(array($id));
$tenants = $st->fetchAll();
$levelById = array();
foreach ($floors as $fid => $f) {
    $levelById[$fid] = (int) $f['level'];
}

hs_page_start($o['name'], $user, $o['address'] !== '' ? $o['address'] : 'Ijara obyekti');
echo '<p class="crumbs"><a href="/ijara/">← Barcha obyektlar</a></p>';

/* ---- Bino surati ---- */
$photo = hs_ij_photo_url($o);
if ($photo !== '' || $manage) {
    echo '<section class="card ij-hero">';
    echo $photo !== '' ? '<img class="ij-hero-img" src="' . h($photo) . '" alt="' . h($o['name']) . '">' : '<div class="ij-hero-empty">' . hs_icon('image') . '<span>Bino surati hali yo\'q</span></div>';
    if ($manage) {
        echo '<div class="ij-hero-tools"><form method="post" action="/ijara/obyekt.php" enctype="multipart/form-data" class="ij-photo-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="rasm"><input type="hidden" name="id" value="' . $id . '">';
        echo '<label for="o-rasm" class="visually-hidden">Bino surati</label><input id="o-rasm" type="file" name="rasm" accept="image/jpeg,image/png,image/webp" required><button class="btn small" type="submit">' . hs_icon('upload') . ' ' . ($photo !== '' ? 'Almashtirish' : 'Surat yuklash') . '</button></form>';
        if ($photo !== '') {
            echo '<form method="post" action="/ijara/obyekt.php" class="inline-form" data-confirm="Surat olib tashlansinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="rasm_ochirish"><input type="hidden" name="id" value="' . $id . '"><button class="btn danger small" type="submit">O\'chirish</button></form>';
        }
        echo '</div>';
    }
    echo '</section>';
}

echo '<div class="kpis">';
echo hs_kpi('building', hs_ij_area($stats['total']), 'Ijaraga beriladigan maydon', count($floors) . ' ta qavat' . ((float) $o['land_area'] > 0 ? ', yer ' . h(hs_ij_area($o['land_area'])) : ''));
echo hs_kpi('check', hs_ij_area($stats['used']), 'Band', $stats['total'] > 0 ? round($stats['used'] / $stats['total'] * 100) . '%' : '');
echo hs_kpi('box', hs_ij_area($stats['free']), "Bo'sh", '', $stats['free'] > 0 ? 'yellow' : 'green');
echo hs_kpi('users', $stats['tenants'], 'Faol ijarachilar');
echo '</div>';

/* ---- Qavatlar ---- */
echo $manage ? '<form id="qavatlar" class="card" method="post" action="/ijara/obyekt.php">' . hs_csrf_field() : '<section id="qavatlar" class="card">';
echo $manage ? '<input type="hidden" name="amal" value="qavatlar"><input type="hidden" name="id" value="' . $id . '">' : '';
echo '<div class="card-head"><h2>Qavatlar</h2><span class="muted">yuqoridan pastga</span></div>';
echo '<div class="table-wrap"><table class="ij-floors"><thead><tr><th>Qavat</th><th>Maydon, kv.m</th><th>Band / bo\'sh</th><th>Kim egallagan</th></tr></thead><tbody>';
foreach ($floors as $fid => $f) {
    $who = array();
    foreach ($f['tenants'] as $t) {
        $who[] = '<a href="/ijara/ijarachi.php?id=' . $t['id'] . '">' . h($t['name']) . '</a> <small class="muted">' . h(hs_ij_area($t['area'])) . '</small>';
    }
    echo '<tr' . ((int) $f['level'] < 0 ? ' class="ij-below"' : '') . '><td class="nowrap"><b>' . h(hs_ij_level_name($f['level'])) . '</b></td>';
    $areaVal = (float) $f['area'] > 0 ? rtrim(rtrim(number_format((float) $f['area'], 2, '.', ''), '0'), '.') : '';
    echo $manage ? '<td><input class="ij-area" type="text" inputmode="decimal" name="area[' . $fid . ']" value="' . h($areaVal) . '" aria-label="' . h(hs_ij_level_name($f['level'])) . ' maydoni"></td>' : '<td class="nowrap">' . h(hs_ij_area($f['area'])) . '</td>';
    echo '<td class="occ"><span class="nowrap">' . h(hs_ij_area($f['used'])) . ' / <b>' . h(hs_ij_area($f['free'])) . '</b></span>' . hs_ij_bar($f['used'], (float) $f['area']) . '</td>';
    echo '<td>' . ($who ? implode('<br>', $who) : '<span class="pill st-yangi">Bo\'sh</span>') . '</td></tr>';
}
echo '</tbody></table></div>';
echo $manage ? '<div class="actions"><button class="btn" type="submit">Maydonlarni saqlash</button><span class="hint">Qavat soni pastdagi «Obyekt ma\'lumotlari» bo\'limida o\'zgaradi.</span></div></form>' : '</section>';

/* ---- Ijarachilar ---- */
echo '<section class="card"><div class="card-head"><h2>Ijarachilar</h2>' . ($manage ? '<a class="btn small" href="#yangi-ijarachi">+ Yangi ijarachi</a>' : '') . '</div>';
if (!$tenants) {
    echo '<p class="muted">Hali ijarachi yo\'q.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Ijarachi</th><th>Qavatlar</th><th>Maydon</th><th>Oylik ijara</th><th>Muddat</th><th>Shartnoma</th><th>Holat</th></tr></thead><tbody>';
    foreach ($tenants as $t) {
        $sp = hs_ij_spaces($t['id']);
        $lv = array();
        foreach (array_keys($sp) as $fid) {
            if (isset($levelById[$fid])) {
                $lv[] = $levelById[$fid];
            }
        }
        rsort($lv);
        $b = hs_ij_balance($t);
        echo '<tr><td><a href="/ijara/ijarachi.php?id=' . (int) $t['id'] . '"><b>' . h($t['name']) . '</b></a>' . ($t['activity'] !== '' ? '<br><small class="muted">' . h($t['activity']) . '</small>' : '') . '</td>';
        echo '<td>' . ($lv ? h(implode(', ', $lv)) . ' <small class="muted">(' . count($lv) . ' ta)</small>' : '—') . '</td>';
        echo '<td class="nowrap">' . h(hs_ij_area(array_sum($sp))) . '</td>';
        echo '<td class="nowrap">' . ((float) $t['rent'] > 0 ? h(hs_ij_money($t['rent'], $t['rent_currency'])) : '—') . '</td>';
        $soon = (int) $t['active'] === 1 && $t['end_date'] !== '' && $t['end_date'] <= date('Y-m-d', strtotime('+' . HS_IJ_WARN_DAYS . ' day'));
        echo '<td><small' . ($soon ? ' class="ij-out"' : '') . '>' . ($soon ? '🔔 ' : '') . h(hs_ij_term_text($t)) . '</small></td>';
        $nf = count(hs_ij_files($t['id']));
        echo '<td>' . ($nf > 0 ? '<a class="pill pill-ok" href="/ijara/ijarachi.php?id=' . (int) $t['id'] . '#fayllar">📎 ' . $nf . ' ta fayl</a>' : '<a class="pill st-yangi" href="/ijara/ijarachi.php?id=' . (int) $t['id'] . '#fayllar">Yuklanmagan</a>') . '</td>';
        echo '<td>' . ((int) $t['active'] === 1 ? hs_ij_debt_pill($b, $t['rent_currency']) : '<span class="pill st-rad">Ijara tugagan</span>') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

if (!$manage) {
    hs_page_end();
    exit;
}

/* ---- Yangi ijarachi ---- */
echo '<form id="yangi-ijarachi" class="card" method="post" action="/ijara/obyekt.php" enctype="multipart/form-data">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="ijarachi"><input type="hidden" name="id" value="' . $id . '">';
echo '<h2>Yangi ijarachi</h2>';
echo '<div class="grid grid-3"><div><label for="t-name">Ismi yoki firma nomi</label><input id="t-name" type="text" name="name" required maxlength="120"></div>';
echo '<div><label for="t-phone">Telefon</label><input id="t-phone" type="tel" name="phone" maxlength="40" inputmode="tel" placeholder="+998"></div>';
echo '<div><label for="t-act">Faoliyati</label><input id="t-act" type="text" name="activity" maxlength="120" placeholder="Masalan: kiyim do\'koni, ofis"></div></div>';
echo '<fieldset class="ij-deal"><legend>Kelishuv</legend><div class="grid grid-3">';
echo '<div><label for="t-rent">Oylik ijara haqi</label><div class="ij-money"><input id="t-rent" type="text" name="rent" inputmode="decimal" required placeholder="Masalan: 5 000 000"><select name="rent_currency" aria-label="Valyuta"><option value="UZS">so\'m</option><option value="USD">dollar</option></select></div></div>';
echo '<div><label for="t-months">Necha oyga</label><input id="t-months" type="number" name="months" min="1" max="600" required placeholder="Masalan: 12"></div>';
echo '<div><label for="t-start">Boshlanish sanasi</label><input id="t-start" type="date" name="start_date" required value="' . date('Y-m-d') . '"></div>';
echo '</div><p class="hint">Tugash sanasi o\'zi hisoblanadi. Qarzdorlik shu summa va sanadan boshlab har oy hisoblanadi.</p></fieldset>';
echo '<p class="label">Qaysi qavatda necha kv.m oladi</p>';
if (!$floors) {
    echo '<p class="muted">Obyektda qavat yo\'q.</p>';
} else {
    echo '<div class="ij-spaces">';
    foreach ($floors as $fid => $f) {
        echo '<label class="ij-space"><span><b>' . h(hs_ij_level_name($f['level'])) . '</b><small>bo\'sh ' . h(hs_ij_area($f['free'])) . '</small></span>';
        echo '<input type="text" inputmode="decimal" name="joy[' . $fid . ']" placeholder="kv.m"' . ($f['free'] <= 0 ? ' disabled' : '') . '></label>';
    }
    echo '</div><p class="hint">Olmaydigan qavatni bo\'sh qoldiring. Butun qavatni olsa — bo\'sh maydonning hammasini yozing.</p>';
}
echo '<label for="t-file">📎 Ijara shartnomasi (PDF, rasm, Word yoki Excel)</label><input id="t-file" type="file" name="fayl[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif,.doc,.docx,.xls,.xlsx">';
echo '<p class="hint">Ixtiyoriy — keyin ijarachi sahifasida ham yuklash mumkin. Bir nechta sahifa rasmini birdaniga tanlasa bo\'ladi, har biri 20 MB gacha.</p>';
echo '<label for="t-note">Izoh</label><textarea id="t-note" name="note" maxlength="1000" placeholder="Shartnoma raqami, kafolat puli va h.k."></textarea>';
echo '<div class="actions"><button class="btn" type="submit">Ijarachini qo\'shish</button></div></form>';

/* ---- Obyekt ma'lumotlari ---- */
echo '<details id="malumot" class="card"><summary class="summary-head">Obyekt ma\'lumotlari</summary>';
echo '<form method="post" action="/ijara/obyekt.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="obyekt"><input type="hidden" name="id" value="' . $id . '">';
echo '<div class="grid grid-2"><div><label for="o-name">Nomi</label><input id="o-name" type="text" name="name" required maxlength="120" value="' . h($o['name']) . '"></div>';
echo '<div><label for="o-addr">Manzil</label><input id="o-addr" type="text" name="address" maxlength="250" value="' . h($o['address']) . '"></div></div>';
echo '<div class="grid grid-3"><div><label for="o-land">Egallagan yer maydoni, kv.m</label><input id="o-land" type="text" name="land_area" inputmode="decimal" value="' . h((float) $o['land_area'] > 0 ? (float) $o['land_area'] : '') . '"></div>';
echo '<div><label for="o-below">Yerto\'la qavatlari</label><select id="o-below" name="floors_below">';
for ($i = 0; $i <= 5; $i++) {
    echo '<option value="' . $i . '"' . ((int) $o['floors_below'] === $i ? ' selected' : '') . '>' . ($i === 0 ? "Yo'q" : $i . ' ta (−1' . ($i > 1 ? ' … −' . $i : '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div><label for="o-above">Yer usti qavatlari</label><input id="o-above" type="number" name="floors_above" min="0" max="200" value="' . (int) $o['floors_above'] . '"></div></div>';
$hasZone = array();
foreach ($floors as $fz) {
    $hasZone[(int) $fz['level']] = true;
}
echo '<div class="ij-zones"><label class="inline"><input type="checkbox" name="zona_kocha" value="1"' . (isset($hasZone[HS_IJ_KOCHA]) ? ' checked' : '') . '> Ko\'cha — bino oldidagi tashqi joylar (prilavka, kiosk)</label>';
echo '<label class="inline"><input type="checkbox" name="zona_boshqa" value="1"' . (isset($hasZone[HS_IJ_BOSHQA]) ? ' checked' : '') . '> Boshqalar — bankomat, antenna va h.k.</label></div>';
echo '<p class="hint">Qavat qo\'shilsa, maydoni 0 bo\'ladi — yuqoridagi jadvalda kiriting. Qavat olib tashlansa, unda ijarachi turmagan bo\'lishi kerak.</p>';
echo '<label for="o-note">Izoh</label><textarea id="o-note" name="note" maxlength="1000">' . h($o['note']) . '</textarea>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form></details>';

if (!$tenants) {
    echo '<form class="card danger-zone" method="post" action="/ijara/obyekt.php" data-confirm="«' . h($o['name']) . '» o\'chirilsinmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div><h2>Obyektni o\'chirish</h2><p class="muted">Faqat ijarachisi yo\'q obyektni o\'chirish mumkin.</p></div>';
    echo '<button class="btn danger" type="submit">O\'chirish</button></form>';
}

hs_page_end();
