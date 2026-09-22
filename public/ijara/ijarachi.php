<?php
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';

$user = hs_ij_login();
$manage = hs_ij_can_manage($user);
$db = hs_db();

$id = (int) (hs_post('id') !== '' ? hs_post('id') : hs_get('id'));
$t = hs_ij_tenant($id);
if (!$t) {
    hs_flash('Ijarachi topilmadi.', 'err');
    hs_redirect('/ijara/');
}
$o = hs_ij_object($t['object_id']);
$back = '/ijara/ijarachi.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    // Ishchi: pul qabul qiladi va fayl yuklaydi. Qolgani — boshliqniki.
    if (!$manage && !in_array($action, array('tolov', 'fayl'), true)) {
        hs_ij_forbidden($user, $back);
    }

    if ($action === 'tolov') {
        $err = hs_ij_cash_save($user, 'in', $id);
        hs_flash($err === null ? 'Pul qabul qilindi va kassaga qo\'shildi.' : $err, $err === null ? 'ok' : 'err');
        hs_redirect($back . '#tolovlar');
    }

    if ($action === 'yozuv_ochirish') {
        $err = hs_ij_cash_delete($user, (int) hs_post('yozuv'));
        hs_flash($err === null ? 'Yozuv o\'chirildi.' : $err, $err === null ? 'ok' : 'err');
        hs_redirect($back . '#tolovlar');
    }

    if ($action === 'fayl') {
        require_once __DIR__ . '/../admin/_lib/content.php';
        $files = hs_files_list('fayl');
        if (!$files) {
            hs_flash('Faylni tanlang.', 'err');
            hs_redirect($back . '#fayllar');
        }
        $errs = array();
        foreach ($files as $file) {
            $e = hs_ij_file_save($user, $id, $file, hs_post('title'));
            if ($e !== null) {
                $errs[] = $file['name'] . ': ' . $e;
            }
        }
        $saved = count($files) - count($errs);
        hs_flash($errs ? ($saved > 0 ? $saved . " ta fayl saqlandi.\n" : '') . implode("\n", $errs) : $saved . ' ta fayl saqlandi.', $errs ? 'err' : 'ok');
        hs_redirect($back . '#fayllar');
    }

    if ($action === 'fayl_ochirish') {
        $e = hs_ij_file_delete($user, (int) hs_post('fayl_id'), $id);
        hs_flash($e === null ? 'Fayl o\'chirildi.' : $e, $e === null ? 'ok' : 'err');
        hs_redirect($back . '#fayllar');
    }

    if ($action === 'tahrir') {
        $f = hs_ij_tenant_fields();
        $problem = hs_ij_tenant_problem($f);
        if ($problem !== null) {
            hs_flash($problem, 'err');
            hs_redirect($back . '#tahrir');
        }
        list($spaces, $err) = hs_ij_read_spaces($t['object_id'], $id);
        if ($err !== null && (int) $t['active'] === 1) {
            hs_flash($err, 'err');
            hs_redirect($back . '#tahrir');
        }
        $db->beginTransaction();
        $db->prepare('UPDATE ij_tenants SET name = ?, phone = ?, activity = ?, rent = ?, rent_currency = ?, months = ?, start_date = ?, end_date = ?, note = ?, updated_at = ? WHERE id = ?')
            ->execute(array($f['name'], $f['phone'], $f['activity'], $f['rent'], $f['rent_currency'], $f['months'], $f['start_date'], $f['end_date'], $f['note'], hs_now(), $id));
        if ((int) $t['active'] === 1) {
            hs_ij_save_spaces($id, $spaces);
        }
        $db->commit();
        hs_audit($user['login'], 'ijara_ijarachi', 'tahrir: ' . $f['name']);
        hs_flash('Saqlandi.');
        hs_redirect($back);
    }

    if ($action === 'tugatish') {
        // Qarz shu kungacha hisoblanadi — muddatidan oldin chiqib ketsa ham.
        $end = $t['end_date'] !== '' && $t['end_date'] < date('Y-m-d') ? $t['end_date'] : date('Y-m-d');
        $db->prepare('UPDATE ij_tenants SET active = 0, end_date = ?, updated_at = ? WHERE id = ?')->execute(array($end, hs_now(), $id));
        hs_audit($user['login'], 'ijara_ijarachi', 'ijara tugadi: ' . $t['name']);
        hs_flash('Ijara tugatildi — joylari bo\'sh deb ko\'rsatiladi. To\'lov tarixi saqlanadi.');
        hs_redirect($back);
    }

    if ($action === 'qaytarish') {
        // Joyi shu orada boshqaga berilgan bo'lishi mumkin — tekshiramiz.
        $floors = hs_ij_floors($t['object_id']);
        foreach (hs_ij_spaces($id) as $fid => $a) {
            if (!isset($floors[$fid]) || $a > $floors[$fid]['free'] + 0.001) {
                hs_flash("Uning eski joyi endi band. Avval «Tahrirlash» da boshqa joy bering.", 'err');
                hs_redirect($back);
            }
        }
        $db->prepare('UPDATE ij_tenants SET active = 1, updated_at = ? WHERE id = ?')->execute(array(hs_now(), $id));
        hs_audit($user['login'], 'ijara_ijarachi', 'qayta faol: ' . $t['name']);
        hs_flash('Ijarachi yana faol.');
        hs_redirect($back);
    }

    if ($action === 'ochirish') {
        $st = $db->prepare('SELECT COUNT(*) FROM ij_cash WHERE tenant_id = ?');
        $st->execute(array($id));
        if ((int) $st->fetchColumn() > 0) {
            hs_flash("Bu ijarachidan pul olingan — tarix yo'qolmasligi uchun o'chirilmaydi. «Ijarani tugatish» ni bosing.", 'err');
            hs_redirect($back);
        }
        $db->prepare('DELETE FROM ij_tenants WHERE id = ?')->execute(array($id));
        hs_audit($user['login'], 'ijara_ijarachi', "o'chirildi: " . $t['name']);
        hs_flash('Ijarachi o\'chirildi.');
        hs_redirect('/ijara/obyekt.php?id=' . (int) $t['object_id']);
    }
    hs_redirect($back);
}

$active = (int) $t['active'] === 1;
$b = hs_ij_balance($t);
$rate = hs_ij_rate();
$spaces = hs_ij_spaces($id);
$floorsAll = hs_ij_floors($t['object_id'], $id);
$rows = hs_ij_cash_rows('c.tenant_id = ?', array($id), 200);

hs_page_start($t['name'], $user, $o['name'] . ($t['activity'] !== '' ? ' · ' . $t['activity'] : ''));
echo '<p class="crumbs"><a href="/ijara/obyekt.php?id=' . (int) $o['id'] . '">← ' . h($o['name']) . '</a></p>';
if (!$active) {
    echo '<p class="flash flash-warn">Bu ijarachining ijarasi tugagan. Joylari bo\'sh hisoblanadi.</p>';
}

$eps = $t['rent_currency'] === 'USD' ? 0.5 : 500;
echo '<div class="kpis">';
echo hs_kpi('wallet', hs_ij_money($t['rent'], $t['rent_currency']), 'Oylik ijara (kelishilgan)', h(hs_ij_term_text($t)));
echo hs_kpi('building', hs_ij_area(array_sum($spaces)), 'Egallagan joyi', count($spaces) . ' ta qavatda');
echo hs_kpi('check', hs_ij_money($b['paid'], $t['rent_currency']), 'Ijaraga to\'lagan', $b['months'] . ' oy uchun ' . h(hs_ij_money($b['due'], $t['rent_currency'])) . ' bo\'lishi kerak' . ($b['paid_until'] !== '' ? '<br><span class="ij-in">' . h(date('d.m.Y', strtotime($b['paid_until']))) . ' gacha to\'langan</span>' : ''));
if ($b['debt'] > $eps) {
    echo hs_kpi('clock', hs_ij_money($b['debt'], $t['rent_currency']), 'Qarzi', 'bugungi holat', 'yellow');
} else {
    echo hs_kpi('check', $b['debt'] < -$eps ? hs_ij_money(-$b['debt'], $t['rent_currency']) : '0', $b['debt'] < -$eps ? 'Oldindan to\'lagan' : 'Qarzi yo\'q', '', 'green');
}
echo '</div>';
if ($active && $t['end_date'] !== '') {
    $left = (int) (new DateTime(date('Y-m-d')))->diff(new DateTime($t['end_date']))->format('%r%a');
    if ($left <= HS_IJ_WARN_DAYS) {
        echo '<p class="flash ' . ($left <= 7 ? 'flash-err' : 'flash-warn') . '">🔔 Shartnoma ' . h(date('d.m.Y', strtotime($t['end_date']))) . ' da tugaydi — ' . h(hs_ij_expiry_text($left)) . '.' . ($manage ? ' <a href="#tahrir">Uzaytirish →</a>' : '') . '</p>';
    }
}
if (!hs_ij_files($id)) {
    echo '<p class="flash flash-warn">📎 Ijara shartnomasi hali yuklanmagan. <a href="#fayllar">Shartnomani yuklash →</a></p>';
}

echo '<div class="grid grid-2 stretch">';
echo '<section class="card"><h2>Egallagan joylari</h2>';
if (!$spaces) {
    echo '<p class="muted">Joy belgilanmagan. «Tahrirlash» da qavat va maydonni kiriting.</p>';
} else {
    echo '<ul class="checklist">';
    foreach ($spaces as $fid => $a) {
        $lvl = isset($floorsAll[$fid]) ? hs_ij_level_name($floorsAll[$fid]['level']) : 'Qavat';
        $of = isset($floorsAll[$fid]) && (float) $floorsAll[$fid]['area'] > 0 ? ' <small class="muted">qavat ' . h(hs_ij_area($floorsAll[$fid]['area'])) . '</small>' : '';
        echo '<li><b>' . h($lvl) . '</b> — ' . h(hs_ij_area($a)) . $of . '</li>';
    }
    echo '</ul>';
}
echo '<dl class="kv ij-kv">';
if ($t['phone'] !== '') {
    echo '<dt>Telefon</dt><dd><a href="tel:' . h(preg_replace('/[^\d+]/', '', $t['phone'])) . '">' . h($t['phone']) . '</a></dd>';
}
if ($t['note'] !== '') {
    echo '<dt>Izoh</dt><dd>' . nl2br(h($t['note'])) . '</dd>';
}
echo '</dl></section>';

echo '<section class="card"><h2>Pul qabul qilish</h2>';
echo hs_ij_cash_form('in', '/ijara/ijarachi.php', array('amal' => 'tolov', 'id' => $id), $rate, null, null, $t);
echo '</section></div>';

echo '<section id="tolovlar" class="card"><div class="card-head"><h2>To\'lovlar</h2><span class="muted">' . count($rows) . ' ta</span></div>';
echo hs_ij_cash_table($rows, '/ijara/ijarachi.php?id=' . $id, false, $manage);
echo '</section>';

/* ---- Fayllar ---- */
$docs = hs_ij_files($id);
echo '<section id="fayllar" class="card"><div class="card-head"><h2>Shartnoma va hujjatlar</h2><span class="muted">' . count($docs) . ' ta</span></div>';
if (!$docs) {
    echo '<p class="muted">Hali fayl yo\'q. Shartnomani skaner qilib (PDF yoki rasm) shu yerga yuklang.</p>';
} else {
    echo '<ul class="ij-files">';
    foreach ($docs as $d) {
        $url = '/ijara/fayl.php?id=' . (int) $d['id'];
        echo '<li><span class="ij-file-ico">' . h(hs_ij_file_badge($d['mime'])) . '</span>';
        echo '<div><a href="' . h($url) . '" target="_blank" rel="noopener"><b>' . h($d['title'] !== '' ? $d['title'] : $d['orig_name']) . '</b></a>';
        echo '<small class="muted">' . h(($d['title'] !== '' ? $d['orig_name'] . ' · ' : '') . hs_ij_size($d['size']) . ' · ' . date('d.m.Y', strtotime($d['uploaded_at']))) . '</small></div>';
        echo '<span class="ij-file-act"><a class="btn outline small" href="' . h($url) . '" target="_blank" rel="noopener">' . (hs_ij_file_inline($d['mime']) ? 'Ko\'rish' : 'Ochish') . '</a><a class="btn outline small" href="' . h($url . '&yuklab=1') . '">Yuklab olish</a> ';
        echo !$manage ? '</span></li>' : '<form class="inline-form" method="post" action="/ijara/ijarachi.php" data-confirm="Fayl o\'chirilsinmi? Qaytarib bo\'lmaydi.">' . hs_csrf_field() . '<input type="hidden" name="amal" value="fayl_ochirish"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="fayl_id" value="' . (int) $d['id'] . '"><button class="btn danger small" type="submit" aria-label="O\'chirish">' . hs_icon('x') . '</button></form></span></li>';
    }
    echo '</ul>';
}
echo '<form method="post" action="/ijara/ijarachi.php" enctype="multipart/form-data" class="ij-upload">' . hs_csrf_field() . '<input type="hidden" name="amal" value="fayl"><input type="hidden" name="id" value="' . $id . '">';
echo '<div class="grid grid-2"><div><label for="f-title">Nomi</label><input id="f-title" type="text" name="title" maxlength="120" placeholder="Masalan: Ijara shartnomasi 2026"></div>';
echo '<div><label for="f-file">Fayl (PDF, rasm, HEIC, Word, Excel — 20 MB gacha)</label><input id="f-file" type="file" name="fayl[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif,.doc,.docx,.xls,.xlsx"></div></div>';
echo '<p class="hint">Bir nechta sahifa rasmini birdaniga tanlash mumkin. Fayllar faqat panel orqali, sizga ko\'rinadi.</p>';
echo '<div class="actions"><button class="btn" type="submit">' . hs_icon('upload') . ' Yuklash</button></div></form></section>';

if (!$manage) {
    hs_page_end();
    exit;
}

/* ---- Tahrirlash ---- */
echo '<details id="tahrir" class="card"><summary class="summary-head">Tahrirlash: kelishuv, joylar, telefon</summary>';
echo '<form method="post" action="/ijara/ijarachi.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="tahrir"><input type="hidden" name="id" value="' . $id . '">';
echo '<div class="grid grid-3"><div><label for="t-name">Ismi yoki firma nomi</label><input id="t-name" type="text" name="name" required maxlength="120" value="' . h($t['name']) . '"></div>';
echo '<div><label for="t-phone">Telefon</label><input id="t-phone" type="tel" name="phone" maxlength="40" value="' . h($t['phone']) . '"></div>';
echo '<div><label for="t-act">Faoliyati</label><input id="t-act" type="text" name="activity" maxlength="120" value="' . h($t['activity']) . '"></div></div>';
echo '<fieldset class="ij-deal"><legend>Kelishuv</legend><div class="grid grid-3">';
echo '<div><label for="t-rent">Oylik ijara haqi</label><div class="ij-money"><input id="t-rent" type="text" name="rent" inputmode="decimal" required value="' . h(hs_ij_fmt($t['rent'], $t['rent_currency'] === 'USD' && abs($t['rent'] - round($t['rent'])) > 0.001 ? 2 : 0)) . '">';
echo '<select name="rent_currency" aria-label="Valyuta"><option value="UZS"' . ($t['rent_currency'] === 'UZS' ? ' selected' : '') . '>so\'m</option><option value="USD"' . ($t['rent_currency'] === 'USD' ? ' selected' : '') . '>dollar</option></select></div></div>';
echo '<div><label for="t-months">Necha oyga</label><input id="t-months" type="number" name="months" min="1" max="600" required value="' . (int) $t['months'] . '"></div>';
echo '<div><label for="t-start">Boshlanish sanasi</label><input id="t-start" type="date" name="start_date" required value="' . h($t['start_date']) . '"></div>';
echo '</div><p class="hint">Kelishuv uzaytirilsa — oylar sonini oshiring.</p></fieldset>';
if ($active) {
    echo '<p class="label">Qaysi qavatda necha kv.m</p><div class="ij-spaces">';
    foreach ($floorsAll as $fid => $f) {
        $cur = isset($spaces[$fid]) ? $spaces[$fid] : 0;
        echo '<label class="ij-space"><span><b>' . h(hs_ij_level_name($f['level'])) . '</b><small>bo\'sh ' . h(hs_ij_area($f['free'])) . '</small></span>';
        echo '<input type="text" inputmode="decimal" name="joy[' . $fid . ']" placeholder="kv.m" value="' . ($cur > 0 ? h(rtrim(rtrim(number_format($cur, 2, '.', ''), '0'), '.')) : '') . '"></label>';
    }
    echo '</div><p class="hint">«Bo\'sh» — shu ijarachining o\'z joyi bilan birga hisoblangan.</p>';
}
echo '<label for="t-note">Izoh</label><textarea id="t-note" name="note" maxlength="1000">' . h($t['note']) . '</textarea>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form></details>';

if ($active) {
    echo '<form class="card danger-zone" method="post" action="/ijara/ijarachi.php" data-confirm="«' . h($t['name']) . '» ijarasi tugatilsinmi? Joylari bo\'sh bo\'ladi.">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="tugatish"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div><h2>Ijarani tugatish</h2><p class="muted">Ijarachi chiqib ketganda. Joylari bo\'shaydi, to\'lov tarixi qoladi.</p></div>';
    echo '<button class="btn danger" type="submit">Tugatish</button></form>';
} else {
    echo '<form class="card danger-zone" method="post" action="/ijara/ijarachi.php">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="qaytarish"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div><h2>Qayta faollashtirish</h2><p class="muted">Adashib tugatilgan bo\'lsa.</p></div>';
    echo '<button class="btn outline" type="submit">Faollashtirish</button></form>';
}
if (!$rows) {
    echo '<form class="card danger-zone" method="post" action="/ijara/ijarachi.php" data-confirm="«' . h($t['name']) . '» butunlay o\'chirilsinmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div><h2>O\'chirish</h2><p class="muted">Adashib qo\'shilgan bo\'lsa. Pul olingan ijarachini o\'chirib bo\'lmaydi.</p></div>';
    echo '<button class="btn danger" type="submit">O\'chirish</button></form>';
}

hs_page_end();
