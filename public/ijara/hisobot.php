<?php
/**
 * Ijara hisoboti — Excel (.xlsx) egasining shabloni bo'yicha: «Xulosa» (hamma binolar)
 * va har bir bino alohida varaqda. Filtrlar kassa sahifasidagi bilan bir xil
 * (obyekt, davr, dan, gacha).
 */
define('HS_AREA', 'ijara');
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/ijara.php';
require_once __DIR__ . '/../admin/_lib/xlsx.php';

$user = hs_ij_login();
hs_session_release();
$db = hs_db();

$objectId = (int) hs_get('obyekt');
$object = $objectId > 0 ? hs_ij_object($objectId) : null;
if (!$object) {
    $objectId = 0;
}
$objects = $object ? array($object) : $db->query('SELECT * FROM ij_objects ORDER BY name')->fetchAll();
$period = hs_ij_period();
$rate = hs_ij_rate();
$sum = hs_ij_summary($period, $objectId);
$methods = hs_ij_methods();
$scope = $object ? $object['name'] : 'Hamma binolar';
$stamp = 'Tuzildi: ' . date('d.m.Y H:i') . ' · ' . $user['name'];

$x = new HsXlsx();


/*
 * Egasi bergan shablon bo'yicha (Telegram'dagi "Лист Microsoft Excel"):
 *   Qavat (tik) | № | Ijarachi | Ijara to'lovi | Oy boshiga qoldiq | Elektr | Jami to'lashi kerak |
 *   To'landi so'mda | Qoldiq | Jami (qavat bo'yicha) | Oylar | Izoh
 * Bloklar: Ko'cha, -1, 1, 2 …, Boshqalar; har biridan keyin "Jami …", oxirida "HAMMASI".
 * Qoldiq ishorasi: minus — ijarachi qarzdor (biz haqdormiz), plus — oldindan to'lagan (u haqdor).
 */
$monthly = $period['key'] === 'oy' || $period['key'] === 'otgan';
$startLbl = ($monthly ? 'Oy boshiga' : 'Davr boshiga') . " qoldiq\n(+ avans / − qarz)";
$endLbl = "Qoldiq\n(+ avans / − qarz)";
$before = $period['from'] !== '' ? date('Y-m-d', strtotime($period['from'] . ' -1 day')) : '';
$pFrom = $period['from'];
$pTo = $period['to'] !== '' ? $period['to'] : date('Y-m-d');
$monthNames = array(1 => 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr');
$widths = array(8, 5, 38, 16, 17, 15, 17, 17, 17, 18, 16, 36);
$headers = array('Qavat', '№', 'Ijarachi', "Ijara to'lovi\n(oylik)", $startLbl, "Elektr\n(to'landi)", "Jami to'lashi\nkerak", "To'landi\nso'mda", $endLbl, "Jami\n(qavat bo'yicha)", 'Oylar', 'Izoh');
$noteSt = $db->prepare("SELECT note FROM ij_cash WHERE tenant_id = ? AND direction = 'in' AND note <> '' AND paid_at <= ?" . ($pFrom !== '' ? ' AND paid_at >= ?' : '') . ' ORDER BY paid_at');

/** Qoldiq katagi: + avans (yashil), − qarz (qizil), ijara valyutasida. */
$signed = function ($v, $cur) {
    $eps = $cur === 'USD' ? 0.5 : 500;
    $usd = $cur === 'USD';
    if ($v < -$eps) {
        return array($v, $usd ? 'usd_out' : 'out');
    }
    if ($v > $eps) {
        return array($v, $usd ? 'usd_in' : 'in');
    }
    return array(0, $usd ? 'usd' : 'money');
};

/**
 * Jami qatori uchun: so'm + dollar aralash bo'lsa, dollar bugungi bank kursi bilan
 * so'mga o'giriladi — bitta son. Hammasi dollarda bo'lsa — dollarda qoladi.
 */
$buy = $rate ? (float) $rate['buy'] : 0;
$curTotal = function ($byCur) use ($buy) {
    $uzs = isset($byCur['UZS']) ? $byCur['UZS'] : 0.0;
    $usd = isset($byCur['USD']) ? $byCur['USD'] : 0.0;
    if (abs($usd) < 0.001) {
        return array($uzs, 'money');
    }
    if (abs($uzs) < 0.001) {
        return array($usd, 'usd');
    }
    if ($buy > 0) {
        return array($uzs + $usd * $buy, 'money');
    }
    return array(hs_ij_money($uzs, 'UZS') . ' + ' . hs_ij_money($usd, 'USD'), 'text');
};

/**
 * Bitta binoning qavat bloklari. $seen — bir necha qavatdagi ijarachi puli bir marta.
 * Qaytaradi: bino jami (so'm ustunlari va valyutadagi ustunlar).
 */
$renderObject = function ($s, $o) use ($pFrom, $pTo, $before, $sum, $monthNames, $noteSt, $signed, $curTotal) {
    // Tik yozuv sig'ishi uchun blok balandligi: ~7 pt har bir harfga.
    $floors = hs_ij_floors($o['id']);
    uasort($floors, function ($a, $b) {
        return hs_ij_level_order($a['level']) <=> hs_ij_level_order($b['level']);
    });
    $seen = array();
    $objTot = array('F' => 0.0, 'H' => 0.0, 'J' => 0.0, 'D' => array(), 'E' => array(), 'G' => array(), 'I' => array());
    foreach ($floors as $f) {
        $isZone = hs_ij_is_zone($f['level']);
        $label = mb_strtoupper($isZone ? hs_ij_level_name($f['level']) : ((int) $f['level'] < 0 ? $f['level'] . ' qavat' : $f['level'] . '-qavat'));
        $rows = array();
        $tot = array('F' => 0.0, 'H' => 0.0, 'J' => 0.0, 'D' => array(), 'E' => array(), 'G' => array(), 'I' => array());
        $n = 0;
        foreach ($f['tenants'] as $ft) {
            $n++;
            $tid = (int) $ft['id'];
            $t = hs_ij_tenant($tid);
            $cur = $t['rent_currency'];
            $name = $t['name'] . ' (' . hs_ij_money($t['rent'], $cur) . ')';
            $area = (float) $ft['area'] > 0 ? hs_ij_area($ft['area']) : '';
            if (isset($seen[$tid])) {
                $rows[] = array('', array($n, 'center'), array($name . ($area !== '' ? "\n" . $area : ''), 'bold'), '', '', '', '', '', '', '', '', array('Puli ' . $seen[$tid] . ' qatorida hisoblangan', 'muted'));
                continue;
            }
            $seen[$tid] = mb_strtolower(hs_ij_level_name($f['level']));
            // Oy boshidagi va oxiridagi qoldiq: + avans, − qarz (hs_ij_balance: debt = qarz).
            $open = ($before !== '' && $before >= $t['start_date']) ? -hs_ij_balance($t, $before)['debt'] : 0.0;
            $end = hs_ij_balance($t, $pTo);
            $close = -$end['debt'];
            $due = hs_ij_period_due($t, $pFrom, $pTo);
            $mustPay = max(0, $due['due'] - $open);
            $p = isset($sum['tenants'][$tid]) ? $sum['tenants'][$tid]['purposes'] : array();
            $rentPaid = (isset($p['ijara']) ? $p['ijara'] : 0) + (isset($p['oldindan']) ? $p['oldindan'] : 0);
            $el = isset($p['elektr']) ? $p['elektr'] : 0;
            $allPaid = isset($sum['tenants'][$tid]) ? $sum['tenants'][$tid]['uzs'] : 0;
            $usdPaid = isset($sum['tenants'][$tid]) ? $sum['tenants'][$tid]['usd'] : 0;
            // Oylar: qaysi oygacha to'langan.
            $oylar = '—';
            if ($end['paid_until'] !== '') {
                $ts = strtotime($end['paid_until']);
                $oylar = $monthNames[(int) date('n', $ts)] . ((int) date('Y', $ts) !== (int) date('Y') ? ' ' . date('Y', $ts) : '');
                $oylar .= $end['paid_until'] > $pTo ? 'gacha' : '';
            }
            $args = $pFrom !== '' ? array($tid, $pTo, $pFrom) : array($tid, $pTo);
            $noteSt->execute($args);
            $notes = array_values(array_unique(array_filter(array_map('trim', $noteSt->fetchAll(PDO::FETCH_COLUMN)))));
            if ($usdPaid > 0) {
                array_unshift($notes, 'shundan $' . hs_ij_fmt($usdPaid, 0) . ' dollarda');
            }
            if ($t['end_date'] !== '' && $t['end_date'] <= date('Y-m-d', strtotime($pTo . ' +' . HS_IJ_WARN_DAYS . ' day'))) {
                $notes[] = 'Shartnoma ' . date('d.m.Y', strtotime($t['end_date'])) . ' da tugaydi';
            }
            $rows[] = array(
                '',
                array($n, 'center'),
                array($name . ($area !== '' ? "\n" . $area : ''), 'bold'),
                array((float) $t['rent'], $cur === 'USD' ? 'usd' : 'money'),
                $signed($open, $cur),
                array($el, 'money'),
                array($mustPay, $cur === 'USD' ? 'usd' : 'money'),
                array($rentPaid, 'money'),
                $signed($close, $cur),
                array($allPaid, 'bold_money'),
                array($oylar, 'center'),
                array(implode('; ', $notes), 'muted'),
            );
            $tot['F'] += $el;
            $tot['H'] += $rentPaid;
            $tot['J'] += $allPaid;
            foreach (array('D' => (float) $t['rent'], 'E' => $open, 'G' => $mustPay, 'I' => $close) as $k => $v) {
                $tot[$k][$cur] = (isset($tot[$k][$cur]) ? $tot[$k][$cur] : 0) + $v;
            }
        }
        if ($f['free'] > 0.001 || !$f['tenants']) {
            $rows[] = array('', array('—', 'center'), array("Bo'sh joy" . ((float) $f['area'] > 0 ? ' (' . hs_ij_area($f['free']) . ')' : ''), 'muted'), '', '', '', '', '', '', '', array("Bo'sh joy", 'center'), '');
        }
        $first = $s->nextRow();
        $need = mb_strlen($label) * 7.5 + 16 - 20;
        $h = max(24, (int) ceil($need / count($rows)));
        foreach ($rows as $cells) {
            $cells[0] = array('', 'floor');
            $s->rowH($cells, $h);
        }
        $s->total(array(array('', 'floor'), '', 'Jami ' . mb_strtolower($isZone ? hs_ij_level_name($f['level']) : hs_ij_level_name($f['level'])), $curTotal($tot['D']), $curTotal($tot['E']), array($tot['F'], 'money'), $curTotal($tot['G']), array($tot['H'], 'money'), $curTotal($tot['I']), array($tot['J'], 'money'), '', ''));
        // Tik qavat nomi — blokning birinchi katagiga, blok bo'ylab birlashtiriladi.
        $s->rows[$first - 1]['c'][0] = array($label, 'floor');
        $s->merge('A' . $first . ':A' . ($s->nextRow() - 1));
        $s->blank();
        foreach (array('F', 'H', 'J') as $k) {
            $objTot[$k] += $tot[$k];
        }
        foreach (array('D', 'E', 'G', 'I') as $k) {
            foreach ($tot[$k] as $cur => $v) {
                $objTot[$k][$cur] = (isset($objTot[$k][$cur]) ? $objTot[$k][$cur] : 0) + $v;
            }
        }
    }
    return $objTot;
};

$totalRow = function ($s, $label, $t) use ($curTotal) {
    $s->total(array($label, '', '', $curTotal($t['D']), $curTotal($t['E']), array($t['F'], 'money'), $curTotal($t['G']), array($t['H'], 'money'), $curTotal($t['I']), array($t['J'], 'money'), '', ''));
    $s->merge('A' . ($s->nextRow() - 1) . ':C' . ($s->nextRow() - 1));
};
$legend = "Qoldiq: minus — ijarachi qarzdor (biz haqdormiz), plus — oldindan to'lagan (ijarachi haqdor). Ijara to'lovi va qoldiq — kelishilgan valyutada; to'lovlar so'mda (dollar — o'sha kungi kurs bilan)." . ($buy > 0 ? ' Jami qatorlarida dollar bugungi ' . $rate['source'] . ' kursi (' . hs_ij_fmt($buy, 2) . " so'm) bilan so'mga o'girilgan." : '');

/* ---------- 1. Xulosa — hamma binolar bitta varaqda ---------- */
$s = $x->sheet('Xulosa', $widths);
$s->bigTitle(mb_strtoupper('Ijara to\'lovlari ro\'yxati — ' . $scope), 'Davr: ' . $period['label'] . ' · ' . $stamp);
$s->header($headers, 48);
$grand = array('F' => 0.0, 'H' => 0.0, 'J' => 0.0, 'D' => array(), 'E' => array(), 'G' => array(), 'I' => array());
foreach ($objects as $o) {
    $r = $s->nextRow();
    $s->rowH(array(array(mb_strtoupper($o['name']) . ($o['address'] !== '' ? ' · ' . $o['address'] : ''), 'objbar')), 24);
    $s->merge('A' . $r . ':L' . $r);
    $t = $renderObject($s, $o);
    if (count($objects) > 1) {
        $totalRow($s, 'Jami: ' . $o['name'], $t);
        $s->blank();
    }
    foreach (array('F', 'H', 'J') as $k) {
        $grand[$k] += $t[$k];
    }
    foreach (array('D', 'E', 'G', 'I') as $k) {
        foreach ($t[$k] as $cur => $v) {
            $grand[$k][$cur] = (isset($grand[$k][$cur]) ? $grand[$k][$cur] : 0) + $v;
        }
    }
}
$totalRow($s, 'HAMMASI', $grand);
$s->row(array(array($legend, 'sub')));

/* ---------- 2. Har bir bino — alohida varaq, xuddi shu shablon ---------- */
if (count($objects) > 1) {
    foreach ($objects as $o) {
        $s = $x->sheet($o['name'], $widths);
        $s->bigTitle(mb_strtoupper($o['name']), ($o['address'] !== '' ? $o['address'] . ' · ' : '') . 'Davr: ' . $period['label'] . ' · ' . $stamp);
        $s->header($headers, 48);
        $t = $renderObject($s, $o);
        $totalRow($s, 'HAMMASI', $t);
        $s->row(array(array($legend, 'sub')));
    }
}

hs_audit($user['login'], 'ijara_hisobot', $scope . ', ' . $period['label']);
$slug = $object ? preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $object['name']) ?: 'bino')) : 'hammasi';
$file = 'ijara-' . trim($slug, '-') . '-' . date('Y-m-d') . '.xlsx';
if (!$x->send($file)) {
    hs_page_start('Hisobot', $user);
    echo '<div class="card"><p>Serverda ZipArchive yo\'q — Excel fayl yasab bo\'lmadi. Hostingda PHP «zip» kengaytmasini yoqing.</p></div>';
    hs_page_end();
}
