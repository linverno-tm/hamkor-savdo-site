<?php
/**
 * Ijara: bino va yer egasining obyektlari, qavatlari, ijarachilari va to'lovlari.
 *
 * Savdo (arizalar, sayt kontenti) bilan bog'liq emas — faqat shu panel va shu
 * bazada turgani uchun bu yerda. Hamma sahifalari faqat egasi uchun.
 *
 * Pul: har bir to'lov o'z valyutasida (so'm yoki dollar) saqlanadi, yoniga
 * o'sha kungi kurs va so'mdagi qiymati yoziladi. Kassa jami — so'mda.
 */

/** To'lov usullari: [kalit => nom]. */
function hs_ij_methods()
{
    return array(
        'naqd' => 'Naqd',
        'click' => 'Click',
        'payme' => 'Payme',
        'karta' => 'Plastik karta (terminal)',
        'otkazma' => "Bank o'tkazmasi",
    );
}

/** Kirim nima uchun olindi. Qarzdorlik "ijara" va "oldindan" bo'yicha hisoblanadi. */
function hs_ij_in_purposes()
{
    return array(
        'ijara' => 'Ijara haqi',
        'oldindan' => "Oldindan to'lov (avans)",
        'elektr' => 'Elektr energiyasi',
        'kommunal' => "Suv, gaz, chiqindi",
        'depozit' => 'Kafolat puli (depozit)',
        'boshqa' => 'Boshqa',
    );
}

/** Chiqim nima uchun. */
function hs_ij_out_purposes()
{
    return array(
        'topshirildi' => 'Investorga topshirildi',
        'elektr' => "Elektr uchun to'lov",
        'kommunal' => "Kommunal to'lovlar",
        'tamir' => "Ta'mirlash, xizmat",
        'soliq' => 'Soliq',
        'depozit' => 'Kafolat puli qaytarildi',
        'boshqa' => 'Boshqa xarajat',
    );
}

function hs_ij_purpose_name($direction, $key)
{
    $all = $direction === 'out' ? hs_ij_out_purposes() : hs_ij_in_purposes();
    return isset($all[$key]) ? $all[$key] : $key;
}

/**
 * Pul qayerda turadi: naqd so'm, naqd dollar yoki hisob raqamda (Click, Payme,
 * karta, o'tkazma — hammasi bankdagi hisobga tushadi).
 */
function hs_ij_wallet($currency, $method)
{
    if ($method === 'naqd') {
        return $currency === 'USD' ? 'usd_naqd' : 'uzs_naqd';
    }
    return $currency === 'USD' ? 'usd_hisob' : 'uzs_hisob';
}

function hs_ij_wallet_names()
{
    return array(
        'uzs_naqd' => "Naqd so'm",
        'usd_naqd' => 'Naqd dollar',
        'uzs_hisob' => "Hisob raqamda (so'm)",
        'usd_hisob' => 'Hisob raqamda (dollar)',
    );
}

/**
 * Kassa qoldig'i: har bir hamyonda kirim − chiqim, o'z valyutasida.
 * Har bir binoning kassasi alohida: $objectId berilsa — faqat o'sha bino.
 * Qaytaradi: [wallet => summa] va 'total_uzs' — dollar bugungi kurs bilan.
 */
function hs_ij_cash_balance($rate, $objectId = null)
{
    $out = array_fill_keys(array_keys(hs_ij_wallet_names()), 0.0);
    $st = hs_db()->prepare("SELECT direction, currency, method, SUM(amount) s FROM ij_cash" . ($objectId !== null ? ' WHERE object_id = ?' : '') . " GROUP BY direction, currency, method");
    $st->execute($objectId !== null ? array((int) $objectId) : array());
    $rows = $st->fetchAll();
    foreach ($rows as $r) {
        $w = hs_ij_wallet($r['currency'], $r['method']);
        $out[$w] += ($r['direction'] === 'out' ? -1 : 1) * (float) $r['s'];
    }
    $usd = $out['usd_naqd'] + $out['usd_hisob'];
    $buy = $rate ? (float) $rate['buy'] : 0;
    return array(
        'wallets' => $out,
        'usd' => $usd,
        'total_uzs' => $out['uzs_naqd'] + $out['uzs_hisob'] + $usd * $buy,
    );
}
function hs_ij_currencies()
{
    return array('UZS' => "so'm", 'USD' => 'dollar');
}

/** "12 500 000", "12500000", "1 250,5", "1,250.50" — hammasini songa aylantiradi. */
function hs_ij_num($s)
{
    $s = str_replace(array(' ', "\xc2\xa0", "'", '$'), '', trim((string) $s));
    if ($s === '') {
        return 0.0;
    }
    if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
        $s = str_replace(',', '', $s);
    } else {
        $s = str_replace(',', '.', $s);
    }
    return is_numeric($s) ? (float) $s : 0.0;
}

function hs_ij_fmt($n, $dec = 0)
{
    return number_format((float) $n, $dec, ',', ' ');
}

function hs_ij_money($amount, $currency)
{
    if ($currency === 'USD') {
        $dec = abs($amount - round($amount)) > 0.001 ? 2 : 0;
        return '$' . hs_ij_fmt($amount, $dec);
    }
    return hs_ij_fmt(round($amount)) . " so'm";
}

/** Katta so'm summasini qisqa ko'rinishda: 125,4 mln so'm. */
function hs_ij_short($uzs)
{
    $a = abs($uzs);
    if ($a >= 1e9) {
        return hs_ij_fmt($uzs / 1e9, 2) . " mlrd so'm";
    }
    if ($a >= 1e6) {
        return hs_ij_fmt($uzs / 1e6, 1) . " mln so'm";
    }
    return hs_ij_money($uzs, 'UZS');
}

function hs_ij_area($x)
{
    $dec = abs($x - round($x)) > 0.001 ? 1 : 0;
    return hs_ij_fmt($x, $dec) . ' kv.m';
}

/**
 * Qavatdan tashqari ikki maxsus joy (ij_floors.level): Ko'cha — bino oldidagi tashqi
 * savdo joylari (prilavka, kiosk); Boshqalar — bankomat, antenna va boshqa joylar.
 */
const HS_IJ_KOCHA = 900;
const HS_IJ_BOSHQA = 999;

function hs_ij_is_zone($level)
{
    return (int) $level >= HS_IJ_KOCHA;
}

function hs_ij_level_name($level)
{
    $level = (int) $level;
    if ($level === HS_IJ_KOCHA) {
        return "Ko'cha";
    }
    if ($level === HS_IJ_BOSHQA) {
        return 'Boshqalar';
    }
    return $level < 0 ? $level . " qavat (yerto'la)" : $level . '-qavat';
}

/** Tartib raqami: Ko'cha, -2, -1, 1, 2 … , Boshqalar. */
function hs_ij_level_order($level)
{
    $level = (int) $level;
    return $level === HS_IJ_KOCHA ? -1000 : $level;
}

/** Ulush chizig'i (CSP style="" ni taqiqlaydi — kenglik SVG atributida). */
function hs_ij_bar($part, $whole)
{
    $pct = $whole > 0 ? min(100, max(0, round($part / $whole * 100))) : 0;
    return '<svg class="share-bar" viewBox="0 0 100 6" preserveAspectRatio="none" aria-hidden="true"><rect width="100" height="6" rx="3" class="track"/>'
        . ($pct > 0 ? '<rect width="' . $pct . '" height="6" rx="3" class="fill"/>' : '') . '</svg>';
}

/**
 * Dollar kursi. Asosiy manba — Hamkorbank (bank saytidagi kurslar jadvali shu
 * manzildan o'qiydi), bo'lmasa Markaziy bank. 10 daqiqa keshda turadi; ikkalasi
 * ham javob bermasa — oxirgi olingan kurs (eskiligi ko'rsatiladi).
 *
 * Qaytaradi: ['buy', 'sell', 'cb', 'source', 'at', 'stale'] yoki null.
 * buy — bank dollarni shu kursda sotib oladi; kassaga dollar shu kursda hisoblanadi.
 */
function hs_ij_rate()
{
    $cached = hs_cache_get('ij_rate');
    if ($cached) {
        return $cached;
    }
    $rate = hs_ij_rate_hamkorbank();
    if (!$rate) {
        $rate = hs_ij_rate_cbu();
    }
    if ($rate) {
        $rate['stale'] = false;
        hs_cache_set('ij_rate', $rate, 600);
        hs_set_setting('ij_last_rate', json_encode($rate));
        return $rate;
    }
    $last = json_decode((string) hs_setting('ij_last_rate', ''), true);
    if (is_array($last)) {
        $last['stale'] = true;
        // Tarmoq ishlamayotganda har sahifada 2 ta so'rov kutib o'tirmaslik uchun.
        hs_cache_set('ij_rate', $last, 300);
        return $last;
    }
    return null;
}

function hs_ij_rate_hamkorbank()
{
    list($code, $body) = hs_http('GET', 'https://api-dbo.hamkorbank.uz/webflow/v1/exchanges', array('Accept: application/json'), null, 8);
    $j = $code === 200 ? json_decode($body, true) : null;
    if (!is_array($j) || !isset($j['data']) || !is_array($j['data'])) {
        return null;
    }
    // Bir nechta USD qatori bo'ladi: destination_code 2 — kassadagi (naqd) kurs,
    // begin_sum_i > 0 — katta summalar uchun alohida kurs. Oddiy, eng yangisini olamiz.
    $best = null;
    foreach ($j['data'] as $r) {
        if (!isset($r['currency_char']) || $r['currency_char'] !== 'USD') {
            continue;
        }
        if ((string) $r['destination_code'] !== '2' || (float) $r['begin_sum_i'] > 0) {
            continue;
        }
        if ($best === null || strcmp((string) $r['begin_date'], (string) $best['begin_date']) > 0) {
            $best = $r;
        }
    }
    if (!$best || (float) $best['buying_rate'] <= 0) {
        return null;
    }
    // API tiyinda beradi: 1176000 = 11 760 so'm.
    return array(
        'buy' => (float) $best['buying_rate'] / 100,
        'sell' => (float) $best['selling_rate'] / 100,
        'cb' => (float) $best['sb_course'] / 100,
        'source' => 'Hamkorbank',
        'at' => date('Y-m-d H:i', strtotime((string) $best['begin_date'])),
    );
}

function hs_ij_rate_cbu()
{
    list($code, $body) = hs_http('GET', 'https://cbu.uz/uz/arkhiv-kursov-valyut/json/USD/', array('Accept: application/json'), null, 8);
    $j = $code === 200 ? json_decode($body, true) : null;
    if (!is_array($j) || !isset($j[0]['Rate'])) {
        return null;
    }
    $cb = (float) $j[0]['Rate'];
    if ($cb <= 0) {
        return null;
    }
    return array('buy' => $cb, 'sell' => $cb, 'cb' => $cb, 'source' => 'Markaziy bank', 'at' => date('Y-m-d H:i'));
}

function hs_ij_object($id)
{
    $st = hs_db()->prepare('SELECT * FROM ij_objects WHERE id = ?');
    $st->execute(array((int) $id));
    $o = $st->fetch();
    return $o ?: null;
}

function hs_ij_tenant($id)
{
    $st = hs_db()->prepare('SELECT * FROM ij_tenants WHERE id = ?');
    $st->execute(array((int) $id));
    $t = $st->fetch();
    return $t ?: null;
}

/**
 * Obyekt qavatlari, har birida: band maydon, bo'sh maydon va kimlar egallagan.
 * Faqat faol ijarachilar hisoblanadi — ijarasi tugagan joy bo'sh bo'ladi.
 * $exceptTenant — tahrirlanayotgan ijarachining o'z joyi "bo'sh" deb olinadi.
 */
function hs_ij_floors($objectId, $exceptTenant = 0)
{
    $db = hs_db();
    // Ekranda yuqoridan pastga: Boshqalar, yuqori qavatlar … -1, Ko'cha — oxirida.
    $st = $db->prepare('SELECT * FROM ij_floors WHERE object_id = ? ORDER BY CASE WHEN level = ' . HS_IJ_KOCHA . ' THEN -1000 ELSE level END DESC');
    $st->execute(array((int) $objectId));
    $floors = array();
    foreach ($st->fetchAll() as $f) {
        $f['used'] = 0.0;
        $f['tenants'] = array();
        $floors[(int) $f['id']] = $f;
    }
    if (!$floors) {
        return array();
    }
    $st = $db->prepare('SELECT s.floor_id, s.area, t.id tid, t.name FROM ij_spaces s JOIN ij_tenants t ON t.id = s.tenant_id
        WHERE t.object_id = ? AND t.active = 1 AND t.id <> ? ORDER BY t.name');
    $st->execute(array((int) $objectId, (int) $exceptTenant));
    foreach ($st->fetchAll() as $s) {
        $fid = (int) $s['floor_id'];
        if (isset($floors[$fid])) {
            $floors[$fid]['used'] += (float) $s['area'];
            $floors[$fid]['tenants'][] = array('id' => (int) $s['tid'], 'name' => $s['name'], 'area' => (float) $s['area']);
        }
    }
    foreach ($floors as $fid => $f) {
        $floors[$fid]['free'] = max(0, (float) $f['area'] - $f['used']);
    }
    return $floors;
}

/** Jami, band va bo'sh maydon, faol ijarachilar soni. */
function hs_ij_object_stats($objectId)
{
    $total = 0.0;
    $used = 0.0;
    foreach (hs_ij_floors($objectId) as $f) {
        $total += (float) $f['area'];
        $used += min((float) $f['area'], $f['used']);
    }
    $st = hs_db()->prepare('SELECT COUNT(*) FROM ij_tenants WHERE object_id = ? AND active = 1');
    $st->execute(array((int) $objectId));
    return array('total' => $total, 'used' => $used, 'free' => max(0, $total - $used), 'tenants' => (int) $st->fetchColumn());
}

/** Ijarachi egallagan joylar: [floor_id => area]. */
function hs_ij_spaces($tenantId)
{
    $st = hs_db()->prepare('SELECT floor_id, SUM(area) a FROM ij_spaces WHERE tenant_id = ? GROUP BY floor_id');
    $st->execute(array((int) $tenantId));
    $out = array();
    foreach ($st->fetchAll() as $r) {
        $out[(int) $r['floor_id']] = (float) $r['a'];
    }
    return $out;
}

/**
 * Qarzdorlik. Ijara oyning boshida to'lanadi deb hisoblanadi: boshlanish
 * sanasidan bugungacha (yoki tugash sanasigacha) boshlangan har bir oy uchun
 * bir oylik ijara haqi. To'lovlar ijara valyutasiga o'sha kungi kurs bilan
 * o'giriladi.
 *
 * Qaytaradi: ['months', 'due', 'paid', 'debt'] — hammasi ijara valyutasida.
 */
function hs_ij_balance($t, $asOf = '')
{
    // $asOf — shu sanadagi holat (davr oxiri); bo'sh — bugun.
    $asOf = $asOf !== '' && $asOf < date('Y-m-d') ? $asOf : date('Y-m-d');
    $months = 0;
    if ($t['start_date'] !== '' && (float) $t['rent'] > 0) {
        $until = $asOf;
        if ($t['end_date'] !== '' && $t['end_date'] < $until) {
            $until = $t['end_date'];
        }
        $start = new DateTime($t['start_date']);
        $limit = new DateTime($until);
        while ($months < 1200) {
            $d = clone $start;
            $d->modify('+' . $months . ' month');
            if ($d > $limit) {
                break;
            }
            $months++;
        }
    }
    $st = hs_db()->prepare("SELECT amount, currency, rate, amount_uzs FROM ij_cash WHERE tenant_id = ? AND direction = 'in' AND purpose IN ('ijara', 'oldindan') AND paid_at <= ?");
    $st->execute(array((int) $t['id'], $asOf));
    $paid = 0.0;
    foreach ($st->fetchAll() as $p) {
        if ($p['currency'] === $t['rent_currency']) {
            $paid += (float) $p['amount'];
        } elseif ($t['rent_currency'] === 'UZS') {
            $paid += (float) $p['amount_uzs'];
        } else {
            $paid += (float) $p['rate'] > 0 ? (float) $p['amount'] / (float) $p['rate'] : 0;
        }
    }
    $due = $months * (float) $t['rent'];
    // Qaysi sanagacha to'langan: to'langan pulga necha to'liq oy sig'adi.
    $paidUntil = '';
    $covered = (float) $t['rent'] > 0 ? (int) floor($paid / (float) $t['rent'] + 0.0001) : 0;
    if ($covered > 0 && $t['start_date'] !== '') {
        $d = new DateTime($t['start_date']);
        $d->modify('+' . $covered . ' month');
        $d->modify('-1 day');
        $paidUntil = $d->format('Y-m-d');
    }
    return array('months' => $months, 'due' => $due, 'paid' => $paid, 'debt' => $due - $paid, 'covered' => $covered, 'paid_until' => $paidUntil);
}

/** Qarz holati — jadvalda qisqa belgi. */
function hs_ij_debt_pill($b, $currency)
{
    $eps = $currency === 'USD' ? 0.5 : 500;
    if ($b['debt'] > $eps) {
        return '<span class="pill pill-err">Qarz: ' . h(hs_ij_money($b['debt'], $currency)) . '</span>';
    }
    if ($b['debt'] < -$eps) {
        return '<span class="pill st-qongiroq">Oldindan: ' . h(hs_ij_money(-$b['debt'], $currency)) . '</span>';
    }
    return '<span class="pill pill-ok">To\'langan</span>';
}

/** Kurs kartochkasi — to'lov formasi va kassa sahifasida. */
function hs_ij_rate_card($rate)
{
    if (!$rate) {
        return '<p class="flash flash-warn">Dollar kursini olib bo\'lmadi (Hamkorbank va Markaziy bank javob bermadi). Dollar to\'lovida kursni qo\'lda yozing.</p>';
    }
    $html = '<dl class="kv">';
    $html .= '<dt>Bank sotib oladi</dt><dd><b>' . h(hs_ij_fmt($rate['buy'], 2)) . " so'm</b> <small class=\"muted\">— kassaga dollar shu kursda qo'shiladi</small></dd>";
    if ($rate['sell'] != $rate['buy']) {
        $html .= '<dt>Bank sotadi</dt><dd>' . h(hs_ij_fmt($rate['sell'], 2)) . " so'm</dd>";
    }
    $html .= '<dt>Markaziy bank</dt><dd>' . h(hs_ij_fmt($rate['cb'], 2)) . " so'm</dd>";
    $html .= '<dt>Manba</dt><dd>' . h($rate['source']) . ', ' . h(date('d.m.Y H:i', strtotime($rate['at']))) . '</dd>';
    $html .= '</dl>';
    if (!empty($rate['stale'])) {
        $html .= '<p class="flash flash-warn">Bank hozir javob bermadi — bu oxirgi olingan kurs.</p>';
    }
    return $html;
}

/**
 * Ijarachi egallaydigan joylar formadan: joy[floor_id] = kv.m.
 * Har bir qavatdagi bo'sh joydan oshmasligi tekshiriladi.
 * Qaytaradi: [[floor_id => area], xato yoki null].
 */
function hs_ij_read_spaces($objectId, $exceptTenant = 0)
{
    $floors = hs_ij_floors($objectId, $exceptTenant);
    $want = array();
    $raw = isset($_POST['joy']) && is_array($_POST['joy']) ? $_POST['joy'] : array();
    foreach ($raw as $fid => $v) {
        $fid = (int) $fid;
        $a = hs_ij_num(is_array($v) ? '' : $v);
        if ($a <= 0 || !isset($floors[$fid])) {
            continue;
        }
        if ($a > $floors[$fid]['free'] + 0.001) {
            return array(array(), hs_ij_level_name($floors[$fid]['level']) . ' da faqat ' . hs_ij_area($floors[$fid]['free']) . " bo'sh — " . hs_ij_area($a) . ' berib bo\'lmaydi.');
        }
        $want[$fid] = $a;
    }
    return array($want, null);
}

function hs_ij_save_spaces($tenantId, $spaces)
{
    $db = hs_db();
    $db->prepare('DELETE FROM ij_spaces WHERE tenant_id = ?')->execute(array((int) $tenantId));
    $st = $db->prepare('INSERT INTO ij_spaces(tenant_id, floor_id, area) VALUES(?, ?, ?)');
    foreach ($spaces as $fid => $a) {
        $st->execute(array((int) $tenantId, (int) $fid, $a));
    }
}

/** Ijarachi formasidagi asosiy maydonlar (yangi va tahrirlash uchun umumiy). */
function hs_ij_tenant_fields()
{
    $cur = hs_post('rent_currency') === 'USD' ? 'USD' : 'UZS';
    $start = hs_post('start_date');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
        $start = '';
    }
    $months = max(0, min(600, (int) hs_post('months')));
    return array(
        'name' => mb_substr(hs_post('name'), 0, 120),
        'phone' => mb_substr(hs_post('phone'), 0, 40),
        'activity' => mb_substr(hs_post('activity'), 0, 120),
        'rent' => hs_ij_num(hs_post('rent')),
        'rent_currency' => $cur,
        'months' => $months,
        'start_date' => $start,
        'end_date' => hs_ij_end_date($start, $months),
        'note' => mb_substr(hs_post('note'), 0, 1000),
    );
}

/** Tugash sanasi: boshlanish + N oy − 1 kun (1-yanvardan 12 oy → 31-dekabr). */
function hs_ij_end_date($start, $months)
{
    if ($start === '' || $months <= 0) {
        return '';
    }
    $d = new DateTime($start);
    $d->modify('+' . (int) $months . ' month');
    $d->modify('-1 day');
    return $d->format('Y-m-d');
}

/** Ijarachini saqlashdan oldin: kelishilgan summa va muddat albatta bo'lsin. */
function hs_ij_tenant_problem($f)
{
    if ($f['name'] === '') {
        return 'Ijarachi ismini yoki firma nomini yozing.';
    }
    if ($f['rent'] <= 0) {
        return "Oylik ijara haqini yozing — qancha kelishilgan bo'lsa.";
    }
    if ($f['months'] <= 0) {
        return "Necha oyga kelishilganini yozing.";
    }
    if ($f['start_date'] === '') {
        return 'Ijara boshlanish sanasini tanlang.';
    }
    return null;
}

/** Kelishuv muddati matni: "12 oy: 01.01.2026 — 31.12.2026, 4 oy qoldi". */
function hs_ij_term_text($t)
{
    if ((int) $t['months'] <= 0 || $t['start_date'] === '') {
        return '—';
    }
    $txt = (int) $t['months'] . ' oy: ' . date('d.m.Y', strtotime($t['start_date'])) . ' — ' . date('d.m.Y', strtotime($t['end_date']));
    $today = date('Y-m-d');
    if ($t['end_date'] < $today) {
        return $txt . ', muddati tugagan';
    }
    if ($t['start_date'] > $today) {
        return $txt . ', hali boshlanmagan';
    }
    $left = (new DateTime($today))->diff(new DateTime($t['end_date']));
    $m = $left->y * 12 + $left->m;
    return $txt . ', ' . ($m > 0 ? $m . ' oy' . ($left->d > 0 ? ' ' . $left->d . ' kun' : '') : ($left->d + 1) . ' kun') . ' qoldi';
}

/**
 * Kassaga yozuv (kirim yoki chiqim) — formadan. Kurs qo'lda o'zgartirilmasa,
 * bugungi bank kursi yoziladi. Qaytaradi: xato matni yoki null.
 */
function hs_ij_cash_save($user, $direction, $tenantId = 0)
{
    $db = hs_db();
    $purposes = $direction === 'out' ? hs_ij_out_purposes() : hs_ij_in_purposes();
    $methods = hs_ij_methods();
    $purpose = hs_post('purpose');
    $currency = hs_post('currency') === 'USD' ? 'USD' : 'UZS';
    $method = hs_post('method');
    $amount = hs_ij_num(hs_post('amount'));
    $date = hs_post('paid_at');
    if (!isset($purposes[$purpose])) {
        return 'Pul nima uchun ekanini tanlang.';
    }
    if (!isset($methods[$method])) {
        return "To'lov usulini tanlang.";
    }
    // "Necha oy uchun" yozilib summa bo'sh qolsa — summa kelishilgan oylikdan hisoblanadi.
    $oylar = $direction === 'in' ? max(0, min(120, (int) hs_post('oylar'))) : 0;
    $note = mb_substr(hs_post('note'), 0, 500);
    if ($oylar > 0 && (int) $tenantId > 0) {
        $tt = hs_ij_tenant($tenantId);
        if ($tt && $amount <= 0 && (float) $tt['rent'] > 0) {
            $amount = $oylar * (float) $tt['rent'];
            $currency = $tt['rent_currency'];
        }
        $note = trim($oylar . ' oy uchun. ' . $note);
    }
    if ($amount <= 0) {
        return 'Summani yozing.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
        return "Sana noto'g'ri (kelajakdagi sana bo'lmaydi).";
    }
    $objectId = null;
    $tenantId = (int) $tenantId;
    if ($tenantId > 0) {
        $t = hs_ij_tenant($tenantId);
        if (!$t) {
            return 'Ijarachi topilmadi.';
        }
        $objectId = (int) $t['object_id'];
    } elseif ($direction === 'in') {
        return 'Pul kimdan olinganini (ijarachini) tanlang.';
    } else {
        $tenantId = null;
        $oid = (int) hs_post('object_id');
        if ($oid <= 0 || !hs_ij_object($oid)) {
            return 'Chiqim qaysi binoning kassasidan ekanini tanlang.';
        }
        $objectId = $oid;
    }
    $auto = hs_ij_rate();
    $rate = hs_ij_num(hs_post('rate'));
    if ($rate <= 0 && $auto) {
        $rate = (float) $auto['buy'];
    }
    if ($rate <= 0) {
        return "Dollar kursini yozing — bank kursini olib bo'lmadi.";
    }
    $source = $auto && abs($rate - (float) $auto['buy']) < 0.005 ? $auto['source'] : "qo'lda";
    if ($direction === 'out') {
        // Bino kassasida yo'q pulni chiqarib bo'lmaydi.
        $bal = hs_ij_cash_balance($auto, $objectId);
        $w = hs_ij_wallet($currency, $method);
        if ($amount > $bal['wallets'][$w] + 0.001) {
            $names = hs_ij_wallet_names();
            return 'Bu bino kassasida: ' . mb_strtolower($names[$w]) . ' — faqat ' . hs_ij_money(max(0, $bal['wallets'][$w]), $currency) . ' bor.';
        }
    }
    $uzs = $currency === 'USD' ? $amount * $rate : $amount;
    $db->prepare('INSERT INTO ij_cash(created_at, paid_at, direction, tenant_id, object_id, purpose, amount, currency, method, rate, rate_source, amount_uzs, note, created_by) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute(array(hs_now(), $date, $direction, $tenantId, $objectId, $purpose, $amount, $currency, $method, $rate, $source, $uzs, $note, $user['login']));
    hs_audit($user['login'], 'ijara_kassa', ($direction === 'out' ? 'chiqim: ' : 'kirim: ') . hs_ij_money($amount, $currency) . ', ' . $purposes[$purpose] . ', ' . $methods[$method]);
    return null;
}

function hs_ij_cash_delete($user, $id)
{
    $st = hs_db()->prepare('SELECT * FROM ij_cash WHERE id = ?');
    $st->execute(array((int) $id));
    $r = $st->fetch();
    if (!$r) {
        return 'Yozuv topilmadi.';
    }
    hs_db()->prepare('DELETE FROM ij_cash WHERE id = ?')->execute(array((int) $id));
    hs_audit($user['login'], 'ijara_kassa', "o'chirildi: " . ($r['direction'] === 'out' ? 'chiqim ' : 'kirim ') . hs_ij_money($r['amount'], $r['currency']) . ' (' . $r['paid_at'] . ', ' . hs_ij_purpose_name($r['direction'], $r['purpose']) . ')');
    return null;
}

/** Kassa yozuvlari so'rovi: ijarachi va obyekt nomlari bilan. */
function hs_ij_cash_rows($where, $args, $limit = 50, $offset = 0)
{
    $st = hs_db()->prepare("SELECT c.*, t.name tenant_name, o.name object_name FROM ij_cash c
        LEFT JOIN ij_tenants t ON t.id = c.tenant_id LEFT JOIN ij_objects o ON o.id = c.object_id
        WHERE {$where} ORDER BY c.paid_at DESC, c.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset);
    $st->execute($args);
    return $st->fetchAll();
}

/**
 * Kirim/chiqim formasi. $hidden — [nom => qiymat]; $tenants — tanlash ro'yxati
 * (kassa sahifasida); $objects — chiqim qaysi obyektga tegishli.
 */
function hs_ij_cash_form($direction, $action, $hidden, $rate, $tenants = null, $objects = null, $tenant = null)
{
    $in = $direction === 'in';
    $purposes = $in ? hs_ij_in_purposes() : hs_ij_out_purposes();
    $p = $in ? 'k' : 'c';
    $html = '<form method="post" action="' . h($action) . '" class="ij-cash-form">' . hs_csrf_field();
    foreach ($hidden as $k => $v) {
        $html .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
    }
    if ($tenants !== null) {
        $html .= '<label for="' . $p . '-tenant">Kimdan</label><select id="' . $p . '-tenant" name="tenant_id" required><option value="">— ijarachini tanlang —</option>';
        foreach ($tenants as $t) {
            $html .= '<option value="' . (int) $t['id'] . '">' . h($t['name'] . ' — ' . $t['object_name']) . '</option>';
        }
        $html .= '</select>';
    }
    if ($objects !== null) {
        $html .= '<label for="' . $p . '-obj">Qaysi bino kassasidan</label><select id="' . $p . '-obj" name="object_id" required><option value="">— binoni tanlang —</option>';
        foreach ($objects as $o) {
            $html .= '<option value="' . (int) $o['id'] . '"' . (count($objects) === 1 ? ' selected' : '') . '>' . h($o['name']) . '</option>';
        }
        $html .= '</select>';
    }
    $html .= '<label for="' . $p . '-purpose">' . ($in ? 'Nima uchun' : 'Nimaga') . '</label><select id="' . $p . '-purpose" name="purpose" required>';
    foreach ($purposes as $k => $v) {
        $html .= '<option value="' . h($k) . '">' . h($v) . '</option>';
    }
    $html .= '</select>';
    if ($tenant !== null && (float) $tenant['rent'] > 0) {
        // Ijara yoki oldindan to'lov: necha oy uchunligi — summa bo'sh qolsa o'zi hisoblanadi.
        $html .= '<label for="' . $p . '-oylar">Necha oy uchun <small class="muted">(ixtiyoriy)</small></label><input id="' . $p . '-oylar" type="number" name="oylar" min="1" max="120" placeholder="Masalan: 3">';
        $html .= '<p class="hint">1 oy = ' . h(hs_ij_money($tenant['rent'], $tenant['rent_currency'])) . '. Oylar sonini yozib summani bo\'sh qoldirsangiz, summa o\'zi hisoblanadi.</p>';
    }
    $html .= '<div class="grid grid-2"><div><label for="' . $p . '-amount">Summa</label><div class="ij-money"><input id="' . $p . '-amount" type="text" name="amount" inputmode="decimal"' . ($tenant !== null ? '' : ' required') . ' placeholder="0">';
    $html .= '<select name="currency" aria-label="Valyuta"><option value="UZS">so\'m</option><option value="USD">dollar</option></select></div></div>';
    $html .= '<div><label for="' . $p . '-method">Qanday</label><select id="' . $p . '-method" name="method">';
    foreach (hs_ij_methods() as $k => $v) {
        $html .= '<option value="' . h($k) . '">' . h($v) . '</option>';
    }
    $html .= '</select></div></div>';
    $html .= '<div class="grid grid-2"><div><label for="' . $p . '-date">Sana</label><input id="' . $p . '-date" type="date" name="paid_at" required max="' . date('Y-m-d') . '" value="' . date('Y-m-d') . '"></div>';
    $html .= '<div><label for="' . $p . '-rate">Dollar kursi</label><input id="' . $p . '-rate" type="text" name="rate" inputmode="decimal" value="' . ($rate ? h(rtrim(rtrim(number_format((float) $rate['buy'], 2, '.', ''), '0'), '.')) : '') . '">';
    $html .= '<p class="hint">' . ($rate ? h($rate['source']) . ' sotib olish kursi' : "Bank kursi olinmadi — qo'lda yozing") . ". Dollarni so'mga shu kurs bilan hisoblaydi.</p></div></div>";
    $html .= '<label for="' . $p . '-note">Izoh</label><input id="' . $p . '-note" type="text" name="note" maxlength="500" placeholder="' . ($in ? 'Masalan: sentyabr oyi uchun' : 'Masalan: investor Aliyevga topshirildi') . '">';
    $html .= '<div class="actions"><button class="btn" type="submit">' . ($in ? 'Pulni qabul qilish' : 'Chiqimni yozish') . '</button></div></form>';
    return $html;
}

/** Kassa yozuvlari jadvali. $return — o'chirgandan keyin qaytiladigan sahifa. */
function hs_ij_cash_table($rows, $return, $showTenant = true, $canDelete = false)
{
    if (!$rows) {
        return '<p class="muted">Yozuv yo\'q.</p>';
    }
    $methods = hs_ij_methods();
    $html = '<div class="table-wrap"><table class="ij-cash"><thead><tr><th>Sana</th>' . ($showTenant ? '<th>Kimdan / kimga</th>' : '') . '<th>Nima uchun</th><th>Qanday</th><th class="right">Summa</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $out = $r['direction'] === 'out';
        $who = '';
        if ($showTenant) {
            if (!empty($r['tenant_name'])) {
                $who = '<a href="/ijara/ijarachi.php?id=' . (int) $r['tenant_id'] . '">' . h($r['tenant_name']) . '</a>';
            } else {
                $who = $out ? '<span class="muted">Chiqim</span>' : '—';
            }
            if (!empty($r['object_name'])) {
                $who .= '<br><small class="muted">' . h($r['object_name']) . '</small>';
            }
        }
        $sum = ($out ? '−' : '+') . hs_ij_money($r['amount'], $r['currency']);
        $sub = $r['currency'] === 'USD' ? '<br><small class="muted">× ' . h(hs_ij_fmt($r['rate'], 2)) . ' = ' . h(hs_ij_money($r['amount_uzs'], 'UZS')) . '</small>' : '';
        $html .= '<tr><td class="nowrap">' . h(date('d.m.Y', strtotime($r['paid_at']))) . '</td>' . ($showTenant ? '<td>' . $who . '</td>' : '');
        $html .= '<td>' . h(hs_ij_purpose_name($r['direction'], $r['purpose'])) . ($r['note'] !== '' ? '<br><small class="muted">' . h($r['note']) . '</small>' : '') . '</td>';
        $html .= '<td>' . h(isset($methods[$r['method']]) ? $methods[$r['method']] : $r['method']) . '</td>';
        $html .= '<td class="right nowrap"><b class="' . ($out ? 'ij-out' : 'ij-in') . '">' . h($sum) . '</b>' . $sub . '</td>';
        $html .= !$canDelete ? '<td></td></tr>' : '<td><form class="inline-form" method="post" action="' . h($return) . '" data-confirm="Bu yozuv o\'chirilsinmi? Kassa qoldig\'i ham o\'zgaradi.">' . hs_csrf_field() . '<input type="hidden" name="amal" value="yozuv_ochirish"><input type="hidden" name="yozuv" value="' . (int) $r['id'] . '"><button class="btn danger small" type="submit" title="O\'chirish" aria-label="O\'chirish">' . hs_icon('x') . '</button></form></td></tr>';
    }
    return $html . '</tbody></table></div>';
}

/** Kassa qoldig'i kartochkasi — dashboard va kassa sahifasida bir xil. */
function hs_ij_balance_card($bal, $rate, $title = 'Kassada hozir', $link = '/ijara/kassa.php')
{
    $w = $bal['wallets'];
    $html = '<section class="card ij-balance"><div class="card-head"><h2>' . h($title) . '</h2>' . ($link !== '' ? '<a class="btn outline small" href="' . h($link) . '">Kassa →</a>' : '') . '</div>';
    $html .= '<p class="ij-big">' . h(hs_ij_money($bal['total_uzs'], 'UZS')) . '</p>';
    $html .= "<p class=\"hint\">Jami, so'mda" . (abs($bal['usd']) > 0.001 && $rate ? ' — dollar bugungi kurs bilan (' . h(hs_ij_fmt($rate['buy'], 2)) . ')' : '') . '</p>';
    $html .= '<dl class="kv ij-wallets">';
    foreach (hs_ij_wallet_names() as $k => $label) {
        if ($k === 'usd_hisob' && abs($w[$k]) < 0.001) {
            continue;
        }
        $html .= '<dt>' . h($label) . '</dt><dd><b>' . h(hs_ij_money($w[$k], strpos($k, 'usd') === 0 ? 'USD' : 'UZS')) . '</b></dd>';
    }
    return $html . '</dl></section>';
}

/* ---------- Fayllar (shartnoma skani) ---------- */

/** Ruxsat etilgan turlar: MIME => kengaytma. Tur fayl mazmunidan aniqlanadi, nomidan emas. */
function hs_ij_file_types()
{
    return array(
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    );
}

/** Brauzerda ochiladigan turlar (qolgani — yuklab olinadi: Word, Excel, HEIC). */
function hs_ij_file_inline($mime)
{
    return in_array($mime, array('application/pdf', 'image/jpeg', 'image/png', 'image/webp'), true);
}

/** Ro'yxatdagi qisqa belgi: PDF, IMG, DOC, XLS. */
function hs_ij_file_badge($mime)
{
    $ext = hs_ij_file_types();
    $e = isset($ext[$mime]) ? $ext[$mime] : '';
    if ($e === 'pdf') {
        return 'PDF';
    }
    if ($e === 'doc' || $e === 'docx') {
        return 'DOC';
    }
    if ($e === 'xls' || $e === 'xlsx') {
        return 'XLS';
    }
    return 'IMG';
}

/**
 * Fayl turi mazmunidan (birinchi baytlaridan) aniqlanadi — nomidagi kengaytmaga
 * ishonilmaydi: .pdf deb nomlangan dastur fayli o'tib ketmasin.
 * Word/Excel: eski (.doc/.xls) — OLE konteyner, nomi bo'yicha ajratiladi;
 * yangi (.docx/.xlsx) — ZIP, ichidagi word/ yoki xl/ papkasi bo'yicha.
 */
function hs_ij_detect_type($path, $name)
{
    $head = (string) file_get_contents($path, false, null, 0, 16);
    $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
    if (strpos($head, '%PDF') === 0) {
        return 'application/pdf';
    }
    if (substr($head, 0, 3) === "\xFF\xD8\xFF") {
        return 'image/jpeg';
    }
    if (substr($head, 0, 8) === "\x89PNG\r\n\x1a\n") {
        return 'image/png';
    }
    if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    if (substr($head, 4, 4) === 'ftyp' && in_array(substr($head, 8, 4), array('heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1', 'heim', 'heis'), true)) {
        return 'image/heic';
    }
    if (substr($head, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
        return $ext === 'xls' ? 'application/vnd.ms-excel' : ($ext === 'doc' ? 'application/msword' : '');
    }
    if (substr($head, 0, 4) === "PK\x03\x04") {
        $word = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $xl = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        if (class_exists('ZipArchive')) {
            $z = new ZipArchive();
            if ($z->open($path) === true) {
                $isWord = $z->locateName('word/document.xml') !== false;
                $isXl = $z->locateName('xl/workbook.xml') !== false;
                $z->close();
                return $isWord ? $word : ($isXl ? $xl : '');
            }
            return '';
        }
        return $ext === 'docx' ? $word : ($ext === 'xlsx' ? $xl : '');
    }
    return '';
}

function hs_ij_files_dir()
{
    $dir = hs_data_dir() . '/ijara-fayllar';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

/** Qaytaradi: xato matni yoki null. */
function hs_ij_file_save($user, $tenantId, $file, $title)
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return 'Fayl topilmadi.';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'Fayl juda katta (hosting cheklovi).' : 'Fayl yuklanmadi (kod ' . (int) $file['error'] . ').';
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        return "Fayl 20 MB dan katta bo'lmasin.";
    }
    $mime = hs_ij_detect_type($file['tmp_name'], $file['name']);
    $types = hs_ij_file_types();
    if (!isset($types[$mime])) {
        return 'Bu turdagi fayl qabul qilinmaydi. Mumkin: PDF, rasm (JPG, PNG, WEBP, HEIC), Word (DOC, DOCX), Excel (XLS, XLSX).';
    }
    $stored = date('Ymd-His') . '-' . hs_random_hex(8) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], hs_ij_files_dir() . '/' . $stored)) {
        return 'Faylni saqlab bo\'lmadi.';
    }
    @chmod(hs_ij_files_dir() . '/' . $stored, 0600);
    $orig = mb_substr(preg_replace('/[\x00-\x1f\/\\\\]/u', '', basename((string) $file['name'])), 0, 150);
    hs_db()->prepare('INSERT INTO ij_files(tenant_id, title, orig_name, stored, mime, size, uploaded_at, uploaded_by) VALUES(?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute(array((int) $tenantId, mb_substr($title, 0, 120), $orig !== '' ? $orig : $stored, $stored, $mime, (int) $file['size'], hs_now(), $user['login']));
    hs_audit($user['login'], 'ijara_fayl', 'yuklandi: ' . $orig . ' (ijarachi #' . (int) $tenantId . ')');
    return null;
}

function hs_ij_files($tenantId)
{
    $st = hs_db()->prepare('SELECT * FROM ij_files WHERE tenant_id = ? ORDER BY id DESC');
    $st->execute(array((int) $tenantId));
    return $st->fetchAll();
}

function hs_ij_file_delete($user, $fileId, $tenantId)
{
    $st = hs_db()->prepare('SELECT * FROM ij_files WHERE id = ? AND tenant_id = ?');
    $st->execute(array((int) $fileId, (int) $tenantId));
    $f = $st->fetch();
    if (!$f) {
        return 'Fayl topilmadi.';
    }
    hs_db()->prepare('DELETE FROM ij_files WHERE id = ?')->execute(array((int) $fileId));
    @unlink(hs_ij_files_dir() . '/' . basename($f['stored']));
    hs_audit($user['login'], 'ijara_fayl', "o'chirildi: " . $f['orig_name'] . ' (ijarachi #' . (int) $tenantId . ')');
    return null;
}

function hs_ij_size($bytes)
{
    return $bytes >= 1048576 ? hs_ij_fmt($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
}

/* ---------- Ruxsatlar ---------- */

/** Boshliq (va egasi): joy, qavat, ijarachi, chiqim, o'chirish. Ishchi: pul qabul qilish va fayl yuklash. */
function hs_ij_can_manage($user)
{
    return hs_is_owner($user) || $user['role'] === 'ijara_boshliq';
}

/** Ijara sahifasining boshida. $manage — faqat boshliq ko'radigan sahifa. */
function hs_ij_login($manage = false)
{
    $user = hs_require_login();
    if ($manage && !hs_ij_can_manage($user)) {
        hs_ij_forbidden($user);
    }
    return $user;
}

/** POST amal boshliqniki bo'lsa — ishchini ogohlantirib, $back ga qaytaradi. */
function hs_ij_forbidden($user, $back = '/ijara/')
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        hs_flash('Bu amal faqat boshliq uchun.', 'err');
        hs_redirect($back);
    }
    http_response_code(403);
    hs_page_start('Ruxsat yo\'q', $user);
    echo '<div class="card"><p>Bu bo\'lim faqat boshliq uchun.</p></div>';
    hs_page_end();
    exit;
}

/* ---------- Davr va kassa hisoboti (kassa sahifasi va Excel uchun umumiy) ---------- */

/**
 * GET dan davr: davr=oy|otgan|yil|hammasi|boshqa (+ dan, gacha).
 * Qaytaradi: ['key', 'from', 'to', 'label'] — sanalar 'Y-m-d', hammasi uchun ''.
 */
function hs_ij_period()
{
    $key = hs_get('davr', 'oy');
    $today = date('Y-m-d');
    $from = '';
    $to = '';
    if ($key === 'otgan') {
        $from = date('Y-m-01', strtotime('first day of last month'));
        $to = date('Y-m-t', strtotime('first day of last month'));
    } elseif ($key === 'yil') {
        $from = date('Y-01-01');
        $to = $today;
    } elseif ($key === 'hammasi') {
        $from = '';
        $to = '';
    } elseif ($key === 'boshqa') {
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', hs_get('dan')) ? hs_get('dan') : date('Y-m-01');
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', hs_get('gacha')) ? hs_get('gacha') : $today;
        if ($from > $to) {
            list($from, $to) = array($to, $from);
        }
    } else {
        $key = 'oy';
        $from = date('Y-m-01');
        $to = $today;
    }
    $months = array(1 => 'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentyabr', 'oktyabr', 'noyabr', 'dekabr');
    if ($key === 'hammasi') {
        $label = 'Butun davr';
    } elseif ($key === 'oy' || $key === 'otgan') {
        $label = ucfirst($months[(int) date('n', strtotime($from))]) . ' ' . date('Y', strtotime($from));
    } else {
        $label = date('d.m.Y', strtotime($from)) . ' — ' . date('d.m.Y', strtotime($to));
    }
    return array('key' => $key, 'from' => $from, 'to' => $to, 'label' => $label);
}

/** SQL shart: davr va (ixtiyoriy) bino. */
function hs_ij_cash_where($period, $objectId)
{
    $w = array('1 = 1');
    $a = array();
    if ($period['from'] !== '') {
        $w[] = 'c.paid_at >= ?';
        $a[] = $period['from'];
    }
    if ($period['to'] !== '') {
        $w[] = 'c.paid_at <= ?';
        $a[] = $period['to'];
    }
    if ($objectId > 0) {
        $w[] = 'c.object_id = ?';
        $a[] = (int) $objectId;
    }
    return array(implode(' AND ', $w), $a);
}

/**
 * Davr xulosasi: kirim/chiqim jami (so'mda), kirim usul+valyuta bo'yicha,
 * maqsad bo'yicha, ijarachilar bo'yicha.
 */
function hs_ij_summary($period, $objectId)
{
    list($where, $args) = hs_ij_cash_where($period, $objectId);
    $db = hs_db();
    $out = array('in' => 0.0, 'out' => 0.0, 'by_method' => array(), 'by_purpose_in' => array(), 'by_purpose_out' => array(), 'tenants' => array(), 'count' => 0);
    $st = $db->prepare("SELECT direction, currency, method, purpose, COUNT(*) n, SUM(amount) a, SUM(amount_uzs) u FROM ij_cash c WHERE {$where} GROUP BY direction, currency, method, purpose");
    $st->execute($args);
    foreach ($st->fetchAll() as $r) {
        $u = (float) $r['u'];
        $out['count'] += (int) $r['n'];
        if ($r['direction'] === 'out') {
            $out['out'] += $u;
            $k = $r['purpose'];
            $out['by_purpose_out'][$k] = (isset($out['by_purpose_out'][$k]) ? $out['by_purpose_out'][$k] : 0) + $u;
            continue;
        }
        $out['in'] += $u;
        $k = $r['purpose'];
        $out['by_purpose_in'][$k] = (isset($out['by_purpose_in'][$k]) ? $out['by_purpose_in'][$k] : 0) + $u;
        $mk = $r['currency'] . ':' . $r['method'];
        if (!isset($out['by_method'][$mk])) {
            $out['by_method'][$mk] = array('currency' => $r['currency'], 'method' => $r['method'], 'amount' => 0.0, 'uzs' => 0.0);
        }
        $out['by_method'][$mk]['amount'] += (float) $r['a'];
        $out['by_method'][$mk]['uzs'] += $u;
    }
    uasort($out['by_method'], function ($a, $b) {
        return $b['uzs'] <=> $a['uzs'];
    });
    arsort($out['by_purpose_in']);
    arsort($out['by_purpose_out']);

    $st = $db->prepare("SELECT c.tenant_id, t.name, o.name oname, c.purpose, c.currency, SUM(c.amount) a, SUM(c.amount_uzs) u, COUNT(*) n
        FROM ij_cash c JOIN ij_tenants t ON t.id = c.tenant_id LEFT JOIN ij_objects o ON o.id = c.object_id
        WHERE {$where} AND c.direction = 'in' GROUP BY c.tenant_id, c.purpose, c.currency ORDER BY t.name");
    $st->execute($args);
    foreach ($st->fetchAll() as $r) {
        $tid = (int) $r['tenant_id'];
        if (!isset($out['tenants'][$tid])) {
            $out['tenants'][$tid] = array('name' => $r['name'], 'object' => (string) $r['oname'], 'purposes' => array(), 'usd' => 0.0, 'uzs' => 0.0, 'n' => 0);
        }
        $p = $r['purpose'];
        $out['tenants'][$tid]['purposes'][$p] = (isset($out['tenants'][$tid]['purposes'][$p]) ? $out['tenants'][$tid]['purposes'][$p] : 0) + (float) $r['u'];
        $out['tenants'][$tid]['uzs'] += (float) $r['u'];
        $out['tenants'][$tid]['n'] += (int) $r['n'];
        if ($r['currency'] === 'USD') {
            $out['tenants'][$tid]['usd'] += (float) $r['a'];
        }
    }
    uasort($out['tenants'], function ($a, $b) {
        return $b['uzs'] <=> $a['uzs'];
    });
    return $out;
}

/** Usul + valyuta nomi: "Naqd so'm", "Click", "Naqd dollar". */
function hs_ij_method_label($currency, $method)
{
    $m = hs_ij_methods();
    $name = isset($m[$method]) ? $m[$method] : $method;
    if ($currency === 'USD') {
        return $method === 'naqd' ? 'Naqd dollar' : $name . ' (dollar)';
    }
    return $method === 'naqd' ? "Naqd so'm" : $name;
}

/** Ulushli ro'yxat: [[nom, so'mda, izoh], ...]. */
function hs_ij_share($rows, $total)
{
    if (!$rows) {
        return '<p class="muted">Bu davrda yozuv yo\'q.</p>';
    }
    $html = '<ul class="share">';
    foreach ($rows as $r) {
        $pct = $total > 0 ? round($r[1] / $total * 100) : 0;
        $html .= '<li><span class="share-label">' . h($r[0]) . (isset($r[2]) && $r[2] !== '' ? ' <small class="muted">' . h($r[2]) . '</small>' : '') . '</span>';
        $html .= '<span class="share-val">' . h(hs_ij_money($r[1], 'UZS')) . ' <small>' . $pct . '%</small></span>' . hs_ij_bar($r[1], $total) . '</li>';
    }
    return $html . '</ul>';
}

/* ---------- Eslatmalar: muddati tugayotgan shartnomalar ---------- */

/** Necha kun qolganda eslatiladi. */
const HS_IJ_WARN_DAYS = 30;

/**
 * Faol ijarachilar: shartnomasi HS_IJ_WARN_DAYS kun ichida tugaydigan yoki
 * muddati o'tib ketgan (lekin ijara hali tugatilmagan). Eng shoshilinchi birinchi.
 * Har birida: 'days' — qolgan kun (manfiy bo'lsa, shuncha kun oldin tugagan).
 */
function hs_ij_expiring($objectId = 0)
{
    $limit = date('Y-m-d', strtotime('+' . HS_IJ_WARN_DAYS . ' day'));
    $sql = "SELECT t.*, o.name object_name FROM ij_tenants t JOIN ij_objects o ON o.id = t.object_id
        WHERE t.active = 1 AND t.end_date <> '' AND t.end_date <= ?" . ($objectId ? ' AND t.object_id = ?' : '') . ' ORDER BY t.end_date';
    $st = hs_db()->prepare($sql);
    $st->execute($objectId ? array($limit, (int) $objectId) : array($limit));
    $today = new DateTime(date('Y-m-d'));
    $out = array();
    foreach ($st->fetchAll() as $t) {
        $end = new DateTime($t['end_date']);
        $t['days'] = (int) $today->diff($end)->format('%r%a');
        $out[] = $t;
    }
    return $out;
}

function hs_ij_expiry_text($days)
{
    if ($days < 0) {
        return 'muddati ' . abs($days) . ' kun oldin tugagan';
    }
    if ($days === 0) {
        return 'muddati bugun tugaydi';
    }
    return $days . ' kun qoldi';
}

/** To'lov kuni necha kun oldin eslatiladi. */
const HS_IJ_PAY_WARN_DAYS = 5;

/**
 * To'lov eslatmalari (faol ijarachilar): ['debt' => qarzdorlar, 'soon' => to'lov kuni yaqin].
 * Keyingi to'lov kuni — to'langan oylardan keyingi birinchi kun (oldindan to'lov hisobga olinadi).
 */
function hs_ij_payment_reminders()
{
    $out = array('debt' => array(), 'soon' => array());
    $today = date('Y-m-d');
    $limit = date('Y-m-d', strtotime('+' . HS_IJ_PAY_WARN_DAYS . ' day'));
    $rows = hs_db()->query("SELECT t.*, o.name object_name FROM ij_tenants t JOIN ij_objects o ON o.id = t.object_id WHERE t.active = 1 AND t.rent > 0 AND t.start_date <> '' ORDER BY t.name")->fetchAll();
    foreach ($rows as $t) {
        $b = hs_ij_balance($t);
        $eps = $t['rent_currency'] === 'USD' ? 0.5 : 500;
        if ($b['debt'] > $eps) {
            $t['debt'] = $b['debt'];
            $out['debt'][] = $t;
            continue;
        }
        $next = $b['paid_until'] !== '' ? date('Y-m-d', strtotime($b['paid_until'] . ' +1 day')) : $t['start_date'];
        if ($next >= $today && $next <= $limit && ($t['end_date'] === '' || $next <= $t['end_date'])) {
            $t['next'] = $next;
            $t['days'] = (int) (new DateTime($today))->diff(new DateTime($next))->format('%r%a');
            $out['soon'][] = $t;
        }
    }
    usort($out['soon'], function ($x, $y) {
        return strcmp($x['next'], $y['next']);
    });
    return $out;
}

/** Menyudagi belgi uchun: hamma eslatmalar soni. */
function hs_ij_reminder_count()
{
    $p = hs_ij_payment_reminders();
    return count(hs_ij_expiring()) + count($p['debt']) + count($p['soon']);
}

/** Eslatmalar bloki (bosh sahifa). Hech narsa bo'lmasa — bo'sh. */
/**
 * Eslatmalar qo'ng'iroqchasi — har bir ijara sahifasining yuqori o'ng burchagida.
 * Bosilganda ro'yxat ochiladi (<details>, JavaScript'siz ishlaydi).
 */
function hs_ij_bell()
{
    $exp = hs_ij_expiring();
    $pay = hs_ij_payment_reminders();
    $total = count($exp) + count($pay['debt']) + count($pay['soon']);
    $html = '<details class="ij-bell" data-ij-bell data-count="' . $total . '"><summary class="btn outline small" aria-label="Eslatmalar: ' . $total . ' ta" title="Eslatmalar">' . hs_icon('bell') . ($total > 0 ? '<span class="ij-bell-count">' . $total . '</span>' : '') . '</summary>';
    $html .= '<div class="ij-bell-panel"><div class="ij-bell-head"><b>Eslatmalar</b><small class="muted">' . ($total > 0 ? $total . ' ta' : '') . '</small></div>';
    if ($total === 0) {
        return $html . '<p class="muted ij-bell-empty">Hozircha eslatma yo\'q: qarzdor, yaqin to\'lov va tugayotgan shartnoma yo\'q.</p></div></details>';
    }
    $item = function ($t, $cls, $text, $btn, $href) {
        return '<li class="' . $cls . '"><span class="ij-remind-dot" aria-hidden="true"></span><div>'
            . '<a href="/ijara/ijarachi.php?id=' . (int) $t['id'] . '"><b>' . h($t['name']) . '</b></a> <small class="muted">' . h($t['object_name']) . '</small><br><small>' . $text . '</small></div>'
            . '<a class="btn outline small" href="' . h($href) . '">' . h($btn) . '</a></li>';
    };
    if ($pay['debt']) {
        $html .= '<h3 class="ij-remind-h">Qarzdorlar — to\'lov kuni o\'tib ketgan</h3><ul class="ij-remind-list">';
        foreach ($pay['debt'] as $t) {
            $html .= $item($t, 'is-over', 'Qarzi: <b>' . h(hs_ij_money($t['debt'], $t['rent_currency'])) . '</b> (oylik ' . h(hs_ij_money($t['rent'], $t['rent_currency'])) . ')', 'Pul qabul qilish', '/ijara/ijarachi.php?id=' . (int) $t['id']);
        }
        $html .= '</ul>';
    }
    if ($pay['soon']) {
        $html .= '<h3 class="ij-remind-h">To\'lov kuni yaqin — ' . HS_IJ_PAY_WARN_DAYS . ' kun ichida</h3><ul class="ij-remind-list">';
        foreach ($pay['soon'] as $t) {
            $when = $t['days'] === 0 ? 'bugun' : ($t['days'] === 1 ? 'ertaga' : $t['days'] . ' kundan keyin');
            $html .= $item($t, $t['days'] <= 1 ? 'is-soon' : '', 'Keyingi to\'lov: <b>' . h(date('d.m.Y', strtotime($t['next']))) . '</b> (' . $when . ') — ' . h(hs_ij_money($t['rent'], $t['rent_currency'])), 'Pul qabul qilish', '/ijara/ijarachi.php?id=' . (int) $t['id']);
        }
        $html .= '</ul>';
    }
    if ($exp) {
        $html .= '<h3 class="ij-remind-h">Shartnoma muddati tugayapti — ' . HS_IJ_WARN_DAYS . ' kun ichida</h3><ul class="ij-remind-list">';
        foreach ($exp as $t) {
            $html .= $item($t, $t['days'] < 0 ? 'is-over' : ($t['days'] <= 7 ? 'is-soon' : ''), 'Shartnoma ' . h(date('d.m.Y', strtotime($t['end_date']))) . ' da tugaydi — <b>' . h(hs_ij_expiry_text($t['days'])) . '</b>', 'Uzaytirish', '/ijara/ijarachi.php?id=' . (int) $t['id'] . '#tahrir');
        }
        $html .= '</ul>';
    }
    return $html . '</div></details>';
}

/* ---------- Bino surati ---------- */

/** Surat yuklash: JPG/PNG/WEBP, katta bo'lsa 2000px gacha kichraytiriladi. Qaytaradi: xato yoki null. */
function hs_ij_photo_save($user, $objectId, $file)
{
    require_once __DIR__ . '/content.php';
    $o = hs_ij_object($objectId);
    if (!$o) {
        return 'Obyekt topilmadi.';
    }
    $up = hs_read_upload($file);
    if (!$up['ok']) {
        return $up['error'];
    }
    $name = 'bino-' . (int) $objectId . '-' . hs_random_hex(6) . '.' . $up['ext'];
    if (@file_put_contents(hs_ij_files_dir() . '/' . $name, $up['bytes']) === false) {
        return 'Suratni saqlab bo\'lmadi.';
    }
    @chmod(hs_ij_files_dir() . '/' . $name, 0600);
    if ($o['photo'] !== '') {
        @unlink(hs_ij_files_dir() . '/' . basename($o['photo']));
    }
    hs_db()->prepare('UPDATE ij_objects SET photo = ?, updated_at = ? WHERE id = ?')->execute(array($name, hs_now(), (int) $objectId));
    hs_audit($user['login'], 'ijara_obyekt', 'surat: ' . $o['name']);
    return null;
}

function hs_ij_photo_delete($user, $objectId)
{
    $o = hs_ij_object($objectId);
    if ($o && $o['photo'] !== '') {
        @unlink(hs_ij_files_dir() . '/' . basename($o['photo']));
        hs_db()->prepare("UPDATE ij_objects SET photo = '', updated_at = ? WHERE id = ?")->execute(array(hs_now(), (int) $objectId));
        hs_audit($user['login'], 'ijara_obyekt', "surat o'chirildi: " . $o['name']);
    }
}

/** Surat manzili (panel orqali beriladi) yoki ''. v= — surat almashsa brauzer keshi yangilanadi. */
function hs_ij_photo_url($o)
{
    return $o['photo'] !== '' ? '/ijara/rasm.php?id=' . (int) $o['id'] . '&v=' . substr(md5($o['photo']), 0, 8) : '';
}

/**
 * Davrda to'lanishi kerak bo'lgan ijara: davr ichida boshlanadigan to'lov oylari
 * (boshlanish sanasidan har oy) × oylik. Ijara valyutasida.
 */
function hs_ij_period_due($t, $from, $to)
{
    if ($t['start_date'] === '' || (float) $t['rent'] <= 0) {
        return array('months' => 0, 'due' => 0.0);
    }
    $to = $to !== '' && $to < date('Y-m-d') ? $to : date('Y-m-d');
    if ($t['end_date'] !== '' && $t['end_date'] < $to) {
        $to = $t['end_date'];
    }
    $n = 0;
    for ($k = 0; $k < 1200; $k++) {
        $d = new DateTime($t['start_date']);
        $d->modify('+' . $k . ' month');
        $day = $d->format('Y-m-d');
        if ($day > $to) {
            break;
        }
        if ($from === '' || $day >= $from) {
            $n++;
        }
    }
    return array('months' => $n, 'due' => $n * (float) $t['rent']);
}

/** Ko'cha / Boshqalar joylarini yoqish-o'chirish (qavat sonidan alohida). $on = [level => bool]. */
function hs_ij_sync_zones($objectId, $on)
{
    $db = hs_db();
    foreach (array(HS_IJ_KOCHA, HS_IJ_BOSHQA) as $lv) {
        if (!empty($on[$lv])) {
            $db->prepare('INSERT OR IGNORE INTO ij_floors(object_id, level, area) VALUES(?, ?, 0)')->execute(array((int) $objectId, $lv));
            continue;
        }
        $st = $db->prepare('SELECT COUNT(*) FROM ij_floors f JOIN ij_spaces s ON s.floor_id = f.id WHERE f.object_id = ? AND f.level = ?');
        $st->execute(array((int) $objectId, $lv));
        if ((int) $st->fetchColumn() > 0) {
            return hs_ij_level_name($lv) . ' da ijarachi bor — olib tashlab bo\'lmaydi.';
        }
        $db->prepare('DELETE FROM ij_floors WHERE object_id = ? AND level = ?')->execute(array((int) $objectId, $lv));
    }
    return null;
}
