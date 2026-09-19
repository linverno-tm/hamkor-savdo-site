<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/metrika.php';
require_once __DIR__ . '/_lib/leads.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    if ($action === 'metrika_ulash') {
        // Token ba'zan "#access_token=...&token_type=..." havola ko'rinishida nusxalanadi.
        $raw = hs_post('token');
        if (preg_match('/access_token=([^&\s]+)/', $raw, $m)) {
            $raw = $m[1];
        }
        $token = preg_replace('/\s+/', '', $raw);
        if (!preg_match('/^[A-Za-z0-9_\-.]{20,200}$/', $token)) {
            hs_flash("Token noto'g'ri ko'rinishda. Yandex sahifasidagi uzun kodni to'liq nusxalang.", 'err');
        } else {
            $problem = hs_metrika_check_token($token);
            if ($problem !== null) {
                hs_flash($problem, 'err');
            } else {
                hs_set_setting('metrika_token', $token);
                hs_db()->exec("DELETE FROM cache WHERE key LIKE 'ym:%'");
                hs_audit($user['login'], 'Metrika ulandi');
                hs_flash("Metrika ulandi. Statistika endi shu yerda ko'rinadi.");
            }
        }
    } elseif ($action === 'metrika_yangilash') {
        // Keshni tashlaymiz — keyingi yuklashda raqamlar Metrika'dan qayta olinadi.
        hs_db()->exec("DELETE FROM cache WHERE key LIKE 'ym:%'");
        hs_flash('Yangilandi.');
    } elseif ($action === 'metrika_uzish') {
        hs_db()->prepare('DELETE FROM settings WHERE key = ?')->execute(array('metrika_token'));
        hs_db()->exec("DELETE FROM cache WHERE key LIKE 'ym:%'");
        hs_audit($user['login'], 'Metrika uzildi');
        hs_flash('Metrika uzildi.');
    }
    // Davr saqlanadi: "Hozir yangilash" 30 kunlik ko'rinishdan bosilsa, o'sha yerga qaytadi.
    $davr = hs_get('davr', '');
    hs_redirect('/admin/statistika.php' . (preg_match('/^(bugun|kecha|7|30)$/', $davr) ? '?davr=' . $davr : ''));
}

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

echo '<div class="seg">';
foreach ($periods as $k => $v) {
    echo $k === $p ? '<span>' . h($v[0]) . '</span>' : '<a href="/admin/statistika.php?davr=' . h($k) . '">' . h($v[0]) . '</a>';
}
echo '</div>';

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

/* Metrika'ga murojaat ulash kartasidan OLDIN qilinadi, chunki natijasi
   o'sha kartani ko'rsatish-ko'rsatmaslikni hal qiladi.
   Saqlangan token ishlamay qolishi oddiy hol: Yandex'da ruxsat bekor
   qilinadi, yoki token boshqa akkauntdan olingan bo'lib chiqadi. Ilgari
   bunday paytda panel tuzoqqa aylanardi — token "bor" hisoblangani uchun
   ulash formasi yashirin qolardi, natijada uni almashtirishning yo'li
   yo'q edi. Endi token sinovdan o'tmasa, forma qaytadi: yangisini
   qo'yish eskisining ustiga yozadi. */
$err = '';
$ov = hs_metrika_ready() ? hs_metrika_overview($d1, $d2, $err) : null;
$tokenYaroqsiz = hs_metrika_ready() && $err !== '';
if ($err !== '') {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
}

if (!hs_metrika_ready() || $tokenYaroqsiz) {
    $clientId = preg_match('/^[a-f0-9]{32}$/', hs_get('client_id')) ? hs_get('client_id') : '';
    echo '<section class="card"><h2>Yandex Metrika\'ni ulash</h2>';
    echo '<p>Bir marta qilinadi (taxminan 3 daqiqa). Metrika ochilgan Yandex akkauntingiz bilan kirgan bo\'lishingiz kerak.</p>';

    echo '<h3>1-qadam. Yandex\'da ilova yaratish</h3><ol>';
    echo '<li><a href="https://oauth.yandex.ru/client/new/id/" target="_blank" rel="noopener noreferrer">oauth.yandex.ru/client/new</a> ni oching.</li>';
    echo '<li>Nomi: <span class="code">HAMKOR SAVDO admin</span>. Platforma: <b>Веб-сервисы</b>.</li>';
    echo '<li>Redirect URI maydoniga aynan shuni yozing: <span class="code">https://oauth.yandex.ru/verification_code</span></li>';
    echo '<li>Ruxsatlar (Доступ к данным) ichidan faqat <b>Получение статистики</b> (Яндекс Метрика) ni belgilang.</li>';
    echo '<li>"Создать приложение" ni bosing va chiqqan <b>ClientID</b> ni nusxalang.</li></ol>';

    echo '<h3>2-qadam. Tokenni olish</h3>';
    echo '<form method="get" action="/admin/statistika.php" class="filters"><div><label for="client_id">ClientID</label><input id="client_id" type="text" name="client_id" maxlength="32" value="' . h($clientId) . '" placeholder="32 belgili kod" autocomplete="off"></div><div class="actions"><button class="btn outline" type="submit">Davom etish</button></div></form>';
    if ($clientId !== '') {
        $url = 'https://oauth.yandex.ru/authorize?response_type=token&client_id=' . rawurlencode($clientId);
        echo '<p><a class="btn" href="' . h($url) . '" target="_blank" rel="noopener noreferrer">Yandex\'da ruxsat berish</a></p>';
        echo '<p class="hint">Ochilgan sahifada "Разрешить" ni bosing — uzun kod (token) chiqadi. Uni nusxalang.</p>';
    }

    echo '<h3>3-qadam. Tokenni shu yerga qo\'yish</h3>';
    echo '<form method="post" action="/admin/statistika.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="metrika_ulash">';
    echo '<label for="token">Token</label><input id="token" type="password" name="token" required maxlength="400" autocomplete="off" spellcheck="false">';
    echo '<p class="hint">Token tekshiriladi va faqat serverda saqlanadi. Uni hech kimga (jumladan menga ham) yubormang.</p>';
    echo '<div class="actions"><button class="btn" type="submit">Tekshirish va ulash</button></div></form>';
    echo '<p class="muted">Metrika\'da maqsadlar bo\'lsin: <span class="code">phone_click</span>, <span class="code">telegram_click</span>, <span class="code">lead_sent</span> (JavaScript-событие).</p>';
    echo '</section>';
}

echo '<div class="kpis">';
if ($ov) {
    $g = $ov['goals'];
    echo hs_kpi('eye', $ov['visits'], 'Tashriflar', (int) $ov['users'] . ' ta odam');
    echo hs_kpi('phone', $g['phone_click'] === null ? '—' : $g['phone_click'], '"Qo\'ng\'iroq" bosildi');
    echo hs_kpi('send', $g['telegram_click'] === null ? '—' : $g['telegram_click'], 'Telegram\'ga o\'tishdi');
}
$conv = ($ov && $ov['visits'] > 0)
    ? round((($leadCount + (int) $ov['goals']['phone_click']) / $ov['visits']) * 100, 1) . '% konversiya'
    : '';
echo hs_kpi('inbox', $leadCount, 'Arizalar', $conv, 'green');
echo '</div>';

if ($ov && $ov['visits'] === 0 && $p === 'bugun') {
    echo '<p class="flash flash-warn">Hozircha tashrif yo\'q. Metrika faqat haqiqiy saytda — hamkorsavdo.uz da ishlaydi; sayt hostingga chiqqandan keyin raqamlar shu yerda paydo bo\'ladi.</p>';
}

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

/* Ilgari bu yerda shunchaki "10 daqiqada bir yangilanadi" deb turardi.
   Odam uchun bu hech narsa bildirmaydi: raqam eskimi, yangimi, kutish
   kerakmi yoki nimadir buzuqmi — bilib bo'lmasdi. Endi aniq vaqt
   ko'rsatiladi va kutmasdan yangilash tugmasi bor. */
$keshVaqti = null;
if (hs_metrika_ready()) {
    $st = hs_db()->prepare("SELECT MAX(expires_at) FROM cache WHERE key LIKE 'ym:%'");
    $st->execute();
    $exp = (int) $st->fetchColumn();
    if ($exp > time()) {
        $keshVaqti = $exp;
    }
}

echo '<div class="row-between">';
echo '<p class="muted">Davr: ' . h($pLabel) . '.';
if ($keshVaqti !== null) {
    echo ' Ma\'lumot ' . h(date('H:i', $keshVaqti - 600)) . ' da olingan, ';
    echo h(date('H:i', $keshVaqti)) . ' da o\'zi yangilanadi.';
} elseif (hs_metrika_ready()) {
    echo ' Ma\'lumot hozir Metrika\'dan olindi.';
}
echo '</p>';
if (hs_metrika_ready()) {
    echo '<form method="post" action="/admin/statistika.php?davr=' . h($p) . '">' . hs_csrf_field()
        . '<input type="hidden" name="amal" value="metrika_yangilash">'
        . '<button class="btn outline small" type="submit">Hozir yangilash</button></form>';
}
echo '</div>';
/* Shart bazadagi tokenga qaraydi, `hs_metrika_ready()` ga emas. Faqat
   bazadagisini o'chira olamiz — secrets.php dagisi faylda yozilgan va
   uni paneldan olib tashlab bo'lmaydi. Ilgari bu yerda ready() tekshirib
   ko'rilardi, ya'ni tugma token yaroqsiz bo'lgan paytda ham "bor" deb
   hisoblanardi, lekin ko'rinmay qolgan holatlar bo'lgan. */
if ((string) hs_setting('metrika_token', '') !== '') {
    echo '<form method="post" action="/admin/statistika.php" data-confirm="Metrika uzilsinmi? Statistika ko\'rinmay qoladi.">' . hs_csrf_field() . '<input type="hidden" name="amal" value="metrika_uzish"><button class="btn danger small" type="submit">Metrika\'ni uzish</button></form>';
}
hs_page_end();
