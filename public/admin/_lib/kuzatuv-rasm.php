<?php
/**
 * Raqobatchilar narxlari — rasm va PDF.
 *
 * Egasi so'radi: guruhga havola emas, rasm kelsin — ochmasdan ko'rinsin.
 *   - Yangi narxlar: do'kon -> turkum -> mahsulot jadvali, bitta yoki bir
 *     nechta rasm (sendPhoto / sendMediaGroup).
 *   - Kun oxirida PDF: 1-sahifa — do'konlar kesimida (har turkumda har
 *     do'konning oylik to'lov oralig'i), keyin har do'kon alohida, hamma
 *     narxi bilan.
 *
 * Chizish — GD (hostingda bor), shrift — DejaVu Sans (lotin va kirill;
 * litsenziyasi shrift/DejaVu-LICENSE.txt). PDF — kutubxonasiz: har sahifa
 * bitta JPEG rasm.
 * GD yo'q bo'lsa, hs_rq_gd_bor() false — narxlar oldingidek matnda ketadi.
 */

/* Ranglar — narxlar.php hisobot sahifasi bilan bir xil. */
const HS_RQ_RANG = array(
    'ink' => array(0x1a, 0x11, 0x30),
    'ink2' => array(0x4a, 0x44, 0x58),
    'ink3' => array(0x73, 0x6d, 0x82),
    'line' => array(0xeb, 0xe7, 0xf1),
    'p' => array(0x5a, 0x30, 0x89),
    'p50' => array(0xf4, 0xef, 0xf9),
    'oq' => array(0xff, 0xff, 0xff),
    'fon' => array(0xfa, 0xf9, 0xfc),
);

/** PDF sahifasi: A4, 150 dpi. */
const HS_RQ_A4_W = 1240;
const HS_RQ_A4_H = 1754;
/** Telegram rasmi: eni 1080, bitta rasm bundan baland bo'lsa — keyingi rasmga. */
const HS_RQ_FOTO_W = 1080;
const HS_RQ_FOTO_MAX_H = 2400;

function hs_rq_shrift($qalin = false)
{
    return __DIR__ . '/shrift/DejaVuSans' . ($qalin ? '-Bold' : '') . '.ttf';
}

function hs_rq_gd_bor()
{
    return function_exists('imagecreatetruecolor') && function_exists('imagettftext')
        && function_exists('imagejpeg') && is_file(hs_rq_shrift()) && is_file(hs_rq_shrift(true));
}

/** Shrift chiza olmaydigan belgilar (emoji) olib tashlanadi — aks holda kvadrat chiqadi. */
function hs_rq_toza($s)
{
    $s = preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE00}-\x{FE0F}\x{200B}-\x{200D}]/u', '', (string) $s);
    return trim(preg_replace('/\s+/u', ' ', $s));
}

function hs_rq_eni($matn, $olcham, $qalin = false)
{
    $b = imagettfbbox($olcham, 0, hs_rq_shrift($qalin), $matn);
    return abs($b[2] - $b[0]);
}

/** So'z bo'yicha qatorlarga bo'lish; sig'maganining oxiri "…" bilan. */
function hs_rq_qatorla($matn, $olcham, $qalin, $kenglik, $eng_kop = 2)
{
    $sozlar = preg_split('/\s+/u', hs_rq_toza($matn), -1, PREG_SPLIT_NO_EMPTY);
    $qatorlar = array();
    $joriy = '';
    foreach ($sozlar as $soz) {
        $sinov = $joriy === '' ? $soz : $joriy . ' ' . $soz;
        if ($joriy !== '' && hs_rq_eni($sinov, $olcham, $qalin) > $kenglik) {
            $qatorlar[] = $joriy;
            $joriy = $soz;
        } else {
            $joriy = $sinov;
        }
    }
    if ($joriy !== '') {
        $qatorlar[] = $joriy;
    }
    if (count($qatorlar) > $eng_kop) {
        $qatorlar = array_slice($qatorlar, 0, $eng_kop);
        $qatorlar[$eng_kop - 1] .= '…';
    }
    foreach ($qatorlar as $i => $q) {
        while (mb_strlen($q) > 1 && hs_rq_eni($q, $olcham, $qalin) > $kenglik) {
            $q = mb_substr($q, 0, -2) . '…';
        }
        $qatorlar[$i] = $q;
    }
    return $qatorlar ?: array('');
}

function hs_rq_rang($img, $nom)
{
    $r = HS_RQ_RANG[$nom];
    return imagecolorallocate($img, $r[0], $r[1], $r[2]);
}

/** Matn: $y — qatorning yuqori cheti; $o'ng — o'ng chetga tekislash. */
function hs_rq_yoz($img, $x, $y, $matn, $olcham, $rang, $qalin = false, $ong = false)
{
    $matn = hs_rq_toza($matn);
    if ($ong) {
        $x -= hs_rq_eni($matn, $olcham, $qalin);
    }
    imagettftext($img, $olcham, 0, (int) $x, (int) ($y + $olcham * 1.33), hs_rq_rang($img, $rang), hs_rq_shrift($qalin), $matn);
}

function hs_rq_raqam($n)
{
    return number_format((int) $n, 0, '.', ' ');
}

/* ============================ ma'lumotni tayyorlash ============================ */

/**
 * Postlar -> [kanal => [turkum => [qator, ...]]], takrorlarsiz.
 * Bitta mahsulot bir necha marta tushadi (albomda, uchta filial guruhida,
 * ertasi kuni qayta) — nomi va narxi bir xil bo'lsa, bittasi qoladi.
 */
function hs_rq_jadval_malumot($rows)
{
    $korilgan = array();
    $dokonlar = array();
    foreach ($rows as $p) {
        if ((int) $p['price'] <= 0 && (int) $p['price_total'] <= 0) {
            continue;
        }
        $nom = hs_rq_sarlavha($p);
        $kalit = $p['channel'] . '|' . mb_strtolower($nom) . '|' . (int) $p['price'] . '|' . (int) $p['price_total'];
        if (isset($korilgan[$kalit])) {
            continue;
        }
        $korilgan[$kalit] = true;
        $dokonlar[$p['channel']][hs_rq_turkum($p)][] = array(
            'nom' => $nom,
            'oylik' => (int) $p['price'],
            'oy' => (int) $p['months'],
            'toliq' => (int) $p['price_total'],
        );
    }
    $narx = function ($q) {
        return $q['oylik'] > 0 ? $q['oylik'] : $q['toliq'];
    };
    foreach ($dokonlar as $k => $turkumlar) {
        foreach ($turkumlar as $t => $qs) {
            usort($qs, function ($a, $b) use ($narx) {
                return $narx($a) - $narx($b);
            });
            $turkumlar[$t] = $qs;
        }
        uasort($turkumlar, function ($a, $b) {
            return count($b) - count($a);
        });
        $dokonlar[$k] = $turkumlar;
    }
    uasort($dokonlar, function ($a, $b) {
        return array_sum(array_map('count', $b)) - array_sum(array_map('count', $a));
    });
    return $dokonlar;
}

function hs_rq_jadval_soni($dokonlar)
{
    $n = 0;
    foreach ($dokonlar as $turkumlar) {
        $n += array_sum(array_map('count', $turkumlar));
    }
    return $n;
}

/* ================================ jadval rasmi ================================ */

/**
 * Jadvalni sahifalarga chizish. Qaytadi: GD rasmlar ro'yxati.
 * $balandlik — PDF uchun qat'iy (A4); null — rasm mazmunicha, HS_RQ_FOTO_MAX_H gacha.
 */
function hs_rq_jadval_rasmlar($dokonlar, $sarlavha, $izoh, $en, $balandlik = null)
{
    $chet = 40;
    $ustunOylik = $en - $chet - 200;   // oylik to'lov ustunining o'ng cheti
    $ustunToliq = $en - $chet;         // to'liq narx ustunining o'ng cheti
    $nomEni = $ustunOylik - 230 - $chet;

    // 1) Bloklar: [tur, balandlik, ma'lumot]
    $bloklar = array();
    foreach ($dokonlar as $kanal => $turkumlar) {
        $dokonNom = hs_rq_channel_title($kanal);
        $bloklar[] = array('dokon', 74, array('nom' => $dokonNom, 'soni' => array_sum(array_map('count', $turkumlar))));
        foreach ($turkumlar as $turkum => $qs) {
            $oyliklar = array_filter(array_map(function ($q) { return $q['oylik']; }, $qs));
            $bloklar[] = array('turkum', 54, array('nom' => hs_rq_bosh_harf($turkum), 'soni' => count($qs),
                'oraliq' => $oyliklar ? 'oyiga ' . hs_rq_oraliq($oyliklar) : ''));
            foreach ($qs as $i => $q) {
                $q['qatorlar'] = hs_rq_qatorla($q['nom'], 15, false, $nomEni, 2);
                $q['juft'] = $i % 2 === 1;
                // kamida 64: oylik ostidagi "× 12 oy" sig'sin
                $bloklar[] = array('qator', max(64, 26 + 28 * count($q['qatorlar'])), $q);
            }
        }
    }

    // 2) Sahifalarga bo'lish. Sarlavha bloki keyingisisiz sahifa oxirida qolmaydi;
    //    do'kon sahifadan oshsa, yangi sahifa "(davomi)" bilan boshlanadi.
    $bosh = 196;          // sahifa boshi: sarlavha + ustun nomlari
    $oxir = $balandlik ? 70 : 30;
    $sigim = ($balandlik ?: HS_RQ_FOTO_MAX_H) - $bosh - $oxir;
    $sahifalar = array();
    $joriy = array();
    $band = 0;
    $dokon = null;
    $n = count($bloklar);
    for ($i = 0; $i < $n; $i++) {
        $b = $bloklar[$i];
        $kerak = $b[1];
        if ($b[0] !== 'qator' && $i + 1 < $n) {
            $kerak += $bloklar[$i + 1][1]; // sarlavha o'zidan keyingisi bilan birga
        }
        if ($joriy && $band + $kerak > $sigim) {
            $sahifalar[] = $joriy;
            $joriy = array();
            $band = 0;
            if ($b[0] !== 'dokon' && $dokon) {
                $davomi = $dokon;
                $davomi[2]['nom'] .= ' (davomi)';
                $joriy[] = $davomi;
                $band += $davomi[1];
            }
        }
        if ($b[0] === 'dokon') {
            $dokon = $b;
        }
        $joriy[] = $b;
        $band += $b[1];
    }
    if ($joriy) {
        $sahifalar[] = $joriy;
    }

    // 3) Chizish.
    $rasmlar = array();
    $jami = count($sahifalar);
    foreach ($sahifalar as $s => $bl) {
        $h = $balandlik ?: $bosh + array_sum(array_map(function ($b) { return $b[1]; }, $bl)) + $oxir;
        $img = imagecreatetruecolor($en, $h);
        imagefilledrectangle($img, 0, 0, $en, $h, hs_rq_rang($img, 'oq'));
        // sarlavha
        imagefilledrectangle($img, 0, 0, $en, 132, hs_rq_rang($img, 'p'));
        hs_rq_yoz($img, $chet, 26, $sarlavha, 25, 'oq', true);
        hs_rq_yoz($img, $chet, 80, $izoh, 14, 'oq');
        hs_rq_yoz($img, $en - $chet, 30, 'HAMKOR SAVDO', 13, 'oq', true, true);
        // ustun nomlari
        hs_rq_yoz($img, $chet, 152, 'MAHSULOT', 11, 'ink3', true);
        hs_rq_yoz($img, $ustunOylik, 152, "OYIGA, SO'M", 11, 'ink3', true, true);
        hs_rq_yoz($img, $ustunToliq, 152, "TO'LIQ NARX", 11, 'ink3', true, true);
        imageline($img, $chet, 186, $en - $chet, 186, hs_rq_rang($img, 'line'));
        $y = $bosh;
        foreach ($bl as $b) {
            list($tur, $bal, $d) = $b;
            if ($tur === 'dokon') {
                imagefilledrectangle($img, $chet - 12, $y + 10, $en - $chet + 12, $y + $bal - 8, hs_rq_rang($img, 'p50'));
                imagefilledrectangle($img, $chet - 12, $y + 10, $chet - 6, $y + $bal - 8, hs_rq_rang($img, 'p'));
                hs_rq_yoz($img, $chet + 8, $y + 22, $d['nom'], 19, 'ink', true);
                hs_rq_yoz($img, $en - $chet, $y + 27, $d['soni'] . ' ta mahsulot', 13, 'ink3', false, true);
            } elseif ($tur === 'turkum') {
                hs_rq_yoz($img, $chet, $y + 16, $d['nom'], 16, 'p', true);
                $x = $chet + hs_rq_eni(hs_rq_toza($d['nom']), 16, true) + 14;
                hs_rq_yoz($img, $x, $y + 20, $d['soni'] . ' ta' . ($d['oraliq'] !== '' ? ' · ' . $d['oraliq'] : ''), 12, 'ink3');
                imagefilledrectangle($img, $chet, $y + $bal - 4, $en - $chet, $y + $bal - 2, hs_rq_rang($img, 'p50'));
            } else {
                if ($d['juft']) {
                    imagefilledrectangle($img, $chet - 12, $y, $en - $chet + 12, $y + $bal - 1, hs_rq_rang($img, 'fon'));
                }
                foreach ($d['qatorlar'] as $k => $q) {
                    hs_rq_yoz($img, $chet, $y + 11 + 28 * $k, $q, 15, 'ink');
                }
                if ($d['oylik'] > 0) {
                    hs_rq_yoz($img, $ustunOylik, $y + 9, hs_rq_raqam($d['oylik']), 17, 'ink', true, true);
                    if ($d['oy'] > 0) {
                        hs_rq_yoz($img, $ustunOylik, $y + 38, '× ' . $d['oy'] . ' oy', 11, 'ink3', false, true);
                    }
                } else {
                    hs_rq_yoz($img, $ustunOylik, $y + 11, '—', 15, 'ink3', false, true);
                }
                hs_rq_yoz($img, $ustunToliq, $y + 11, $d['toliq'] > 0 ? hs_rq_raqam($d['toliq']) : '—', 15, $d['toliq'] > 0 ? 'ink2' : 'ink3', false, true);
                imageline($img, $chet, $y + $bal - 1, $en - $chet, $y + $bal - 1, hs_rq_rang($img, 'line'));
            }
            $y += $bal;
        }
        if ($jami > 1 || $balandlik) {
            hs_rq_yoz($img, $en - $chet, $h - ($balandlik ? 50 : 28), ($s + 1) . ' / ' . $jami, 11, 'ink3', false, true);
        }
        $rasmlar[] = $img;
    }
    return $rasmlar;
}

/* ====================== do'konlar kesimida (PDF 1-sahifa) ====================== */

/**
 * Har turkum — qator, har do'kon — ustun, katakda oylik to'lov oralig'i.
 * Bir qarashda: qaysi do'konda nima qancha. Do'kon ko'p bo'lsa — 4 tadan sahifaga.
 */
function hs_rq_kesim_rasmlar($dokonlar, $sarlavha, $izoh)
{
    $en = HS_RQ_A4_W;
    $h = HS_RQ_A4_H;
    $chet = 40;
    $turkumlar = array();
    foreach ($dokonlar as $kanal => $ts) {
        foreach ($ts as $t => $qs) {
            $turkumlar[$t] = (isset($turkumlar[$t]) ? $turkumlar[$t] : 0) + count($qs);
        }
    }
    arsort($turkumlar);
    $turkumlar = array_keys($turkumlar);
    $guruhlar = array_chunk(array_keys($dokonlar), 4);
    $qatorH = 64;
    $boshH = 132 + 40 + 96;
    $sigim = (int) floor(($h - $boshH - 70) / $qatorH);
    $rasmlar = array();
    foreach ($guruhlar as $kanallar) {
        foreach (array_chunk($turkumlar, max(1, $sigim)) as $tqism) {
            $img = imagecreatetruecolor($en, $h);
            imagefilledrectangle($img, 0, 0, $en, $h, hs_rq_rang($img, 'oq'));
            imagefilledrectangle($img, 0, 0, $en, 132, hs_rq_rang($img, 'p'));
            hs_rq_yoz($img, $chet, 26, $sarlavha, 25, 'oq', true);
            hs_rq_yoz($img, $chet, 80, $izoh, 14, 'oq');
            hs_rq_yoz($img, $en - $chet, 30, 'HAMKOR SAVDO', 13, 'oq', true, true);
            hs_rq_yoz($img, $chet, 150, "Do'konlar kesimida — oylik to'lov oralig'i (so'm)", 16, 'ink', true);
            $birinchi = 230;
            $ustunEn = (int) floor(($en - 2 * $chet - $birinchi) / count($kanallar));
            $y = 132 + 40 + 40;
            imagefilledrectangle($img, $chet - 12, $y, $en - $chet + 12, $y + 56, hs_rq_rang($img, 'p50'));
            hs_rq_yoz($img, $chet, $y + 18, 'TURKUM', 11, 'ink3', true);
            foreach ($kanallar as $i => $kanal) {
                $x = $chet + $birinchi + $i * $ustunEn;
                $q = hs_rq_qatorla(hs_rq_channel_title($kanal), 11, true, $ustunEn - 16, 2);
                foreach ($q as $k => $qq) {
                    hs_rq_yoz($img, $x + 8, $y + 8 + 19 * $k + (count($q) === 1 ? 10 : 0), $qq, 11, 'ink', true);
                }
            }
            $y += 56;
            foreach ($tqism as $j => $t) {
                if ($j % 2 === 1) {
                    imagefilledrectangle($img, $chet - 12, $y, $en - $chet + 12, $y + $qatorH - 1, hs_rq_rang($img, 'fon'));
                }
                hs_rq_yoz($img, $chet, $y + 20, hs_rq_qatorla(hs_rq_bosh_harf($t), 14, true, $birinchi - 16, 1)[0], 14, 'p', true);
                foreach ($kanallar as $i => $kanal) {
                    $x = $chet + $birinchi + $i * $ustunEn + 8;
                    $qs = isset($dokonlar[$kanal][$t]) ? $dokonlar[$kanal][$t] : array();
                    if (!$qs) {
                        hs_rq_yoz($img, $x, $y + 20, '—', 14, 'line');
                        continue;
                    }
                    $oylik = array_filter(array_map(function ($q) { return $q['oylik']; }, $qs));
                    $toliq = array_filter(array_map(function ($q) { return $q['toliq']; }, $qs));
                    $asosiy = $oylik ? hs_rq_oraliq($oylik) : ($toliq ? 'narxi ' . hs_rq_oraliq($toliq) : '—');
                    hs_rq_yoz($img, $x, $y + 12, hs_rq_qatorla($asosiy, 14, true, $ustunEn - 16, 1)[0], 14, 'ink', true);
                    hs_rq_yoz($img, $x, $y + 38, count($qs) . ' ta mahsulot', 10, 'ink3');
                }
                imageline($img, $chet, $y + $qatorH - 1, $en - $chet, $y + $qatorH - 1, hs_rq_rang($img, 'line'));
                $y += $qatorH;
            }
            hs_rq_yoz($img, $chet, $h - 50, "Narxlar raqobatchilarning Telegram e'lonlaridan (matni va rasmidan) avtomatik o'qilgan — xato bo'lishi mumkin.", 10, 'ink3');
            $rasmlar[] = $img;
        }
    }
    return $rasmlar;
}

/* ================================ JPEG va PDF ================================ */

function hs_rq_jpeg($img, $sifat = 90)
{
    ob_start();
    imagejpeg($img, null, $sifat);
    $b = ob_get_clean();
    imagedestroy($img);
    return $b;
}

/** Kutubxonasiz PDF: har sahifa — bitta JPEG, A4 ga to'liq cho'zilgan. */
function hs_rq_pdf($jpeglar)
{
    $pw = 595.28;
    $ph = 841.89;
    $obj = array();
    $sahifaIdlar = array();
    $n = 3; // 1 — katalog, 2 — sahifalar
    foreach ($jpeglar as $j) {
        $info = getimagesizefromstring($j);
        $rasmId = $n++;
        $mazmunId = $n++;
        $sahifaId = $n++;
        $obj[$rasmId] = '<< /Type /XObject /Subtype /Image /Width ' . $info[0] . ' /Height ' . $info[1]
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($j) . " >>\nstream\n" . $j . "\nendstream";
        $mazmun = sprintf('q %.2F 0 0 %.2F 0 0 cm /Im Do Q', $pw, $ph);
        $obj[$mazmunId] = '<< /Length ' . strlen($mazmun) . " >>\nstream\n" . $mazmun . "\nendstream";
        $obj[$sahifaId] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /XObject << /Im %d 0 R >> >> /Contents %d 0 R >>',
            $pw, $ph, $rasmId, $mazmunId);
        $sahifaIdlar[] = $sahifaId . ' 0 R';
    }
    $obj[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $sahifaIdlar) . '] /Count ' . count($sahifaIdlar) . ' >>';
    ksort($obj);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $joy = array();
    foreach ($obj as $id => $o) {
        $joy[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $o . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($obj) + 1) . "\n0000000000 65535 f \n";
    foreach ($joy as $o) {
        $pdf .= sprintf("%010d 00000 n \n", $o);
    }
    $pdf .= "trailer\n<< /Size " . (count($obj) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    return $pdf;
}

/* ============================== Telegram'ga fayl ============================== */

/**
 * Kuzatuv boti orqali fayl bilan so'rov (multipart). $fayllar: [maydon => [bayt, fayl nomi, mime]].
 * Qaytadi: [ok, xato].
 */
function hs_rq_tg_fayl($method, $params, $fayllar)
{
    $token = hs_rq_token();
    $params['chat_id'] = hs_rq_chat();
    if ($token === '' || $params['chat_id'] === '') {
        return array(false, 'Bot yoki guruh sozlanmagan.');
    }
    $vaqtinchalik = array();
    foreach ($fayllar as $maydon => $f) {
        $yol = hs_rq_rasm_dir() . '/yubor-' . bin2hex(random_bytes(6)) . '-' . $f[1];
        file_put_contents($yol, $f[0]);
        $vaqtinchalik[] = $yol;
        $params[$maydon] = new CURLFile($yol, $f[2], $f[1]);
    }
    if (getenv('HS_DRY_RUN') === '1') {
        foreach ($fayllar as $f) {
            @file_put_contents(hs_data_dir() . '/kuzatuv-' . $f[1], $f[0]);
        }
        $p = $params;
        foreach ($fayllar as $maydon => $f) {
            $p[$maydon] = '[fayl ' . $f[1] . ', ' . strlen($f[0]) . ' bayt]';
        }
        @file_put_contents(hs_data_dir() . '/kuzatuv.log', '[' . hs_now() . "] {$method}\n" . print_r($p, true) . "\n", FILE_APPEND);
        array_map('unlink', $vaqtinchalik);
        return array(true, '');
    }
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_POSTFIELDS => $params,
    ));
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    array_map('unlink', $vaqtinchalik);
    $j = json_decode((string) $body, true);
    if ($code !== 200 || empty($j['ok'])) {
        $msg = is_array($j) && isset($j['description']) ? (string) $j['description'] : 'javob yo\'q';
        return array(false, "Telegram HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    return array(true, '');
}

/** Rasmlarni guruhga: bitta — sendPhoto, ko'p — 10 tadan albom. Izoh birinchisida. */
function hs_rq_rasmlarni_yubor($jpeglar, $izoh)
{
    $izoh = mb_substr($izoh, 0, 1000);
    if (count($jpeglar) === 1) {
        return hs_rq_tg_fayl('sendPhoto', array('caption' => $izoh), array('photo' => array($jpeglar[0], 'narxlar.jpg', 'image/jpeg')));
    }
    foreach (array_chunk($jpeglar, 10) as $g => $qism) {
        $media = array();
        $fayllar = array();
        foreach ($qism as $i => $j) {
            $m = array('type' => 'photo', 'media' => 'attach://r' . $i);
            if ($g === 0 && $i === 0) {
                $m['caption'] = $izoh;
            }
            $media[] = $m;
            $fayllar['r' . $i] = array($j, 'narxlar-' . ($g * 10 + $i + 1) . '.jpg', 'image/jpeg');
        }
        if (count($qism) === 1) {
            list($ok, $err) = hs_rq_tg_fayl('sendPhoto', $g === 0 ? array('caption' => $izoh) : array(),
                array('photo' => $fayllar['r0']));
        } else {
            list($ok, $err) = hs_rq_tg_fayl('sendMediaGroup', array('media' => json_encode($media, JSON_UNESCAPED_UNICODE)), $fayllar);
        }
        if (!$ok) {
            return array(false, $err);
        }
    }
    return array(true, '');
}

/** Rasm izohi: kim qancha — "🏪 ISHONCH - Andijon — 5 ta". */
function hs_rq_izoh($bosh, $dokonlar)
{
    $s = $bosh;
    foreach ($dokonlar as $kanal => $turkumlar) {
        $s .= "\n🏪 " . hs_rq_channel_title($kanal) . ' — ' . array_sum(array_map('count', $turkumlar)) . ' ta';
    }
    return $s;
}

/** Yangi narxlar — rasm(lar) bilan. Qaytadi: [ok, xato]. */
function hs_rq_narx_rasm_yubor($rows)
{
    $dokonlar = hs_rq_jadval_malumot($rows);
    if (!$dokonlar) {
        return array(true, ''); // hammasi takror — yuboradigan narsa yo'q
    }
    $soni = hs_rq_jadval_soni($dokonlar);
    $izoh = hs_rq_dokonlar_soni_matn($dokonlar, $soni);
    $jpeglar = array_map('hs_rq_jpeg', hs_rq_jadval_rasmlar($dokonlar, 'Yangi narxlar', $izoh, HS_RQ_FOTO_W));
    return hs_rq_rasmlarni_yubor($jpeglar, hs_rq_izoh('💰 Yangi narxlar · ' . $soni . ' ta', $dokonlar));
}

function hs_rq_dokonlar_soni_matn($dokonlar, $soni)
{
    return date('d.m.Y H:i') . ' · ' . $soni . ' ta mahsulot · ' . count($dokonlar) . " ta do'kon";
}

/* ================================ kunlik PDF ================================ */

/**
 * Kun oxirida PDF: bugungi hamma narx, do'konlar kesimida va har do'kon alohida.
 * Sozlama: rq_pdf_on (1), rq_pdf_hour (21). Qaytadi: yuborildimi.
 */
function hs_rq_kunlik_pdf($majburiy = false)
{
    if (!hs_rq_on() || !hs_rq_gd_bor()) {
        return false;
    }
    if (!$majburiy) {
        if (hs_rq_setting('pdf_on') !== '1' || (int) date('G') < (int) hs_rq_setting('pdf_hour')
            || hs_setting('rq_pdf_last', '') === date('Y-m-d')) {
            return false;
        }
        hs_set_setting('rq_pdf_last', date('Y-m-d'));
    }
    $st = hs_db()->prepare('SELECT * FROM rq_posts WHERE analyzed = 1 AND (price > 0 OR price_total > 0) AND posted_at >= ? ORDER BY posted_at DESC');
    $st->execute(array(date('Y-m-d 00:00:00')));
    $dokonlar = hs_rq_jadval_malumot($st->fetchAll());
    if (!$dokonlar) {
        return false;
    }
    @set_time_limit(0);
    $soni = hs_rq_jadval_soni($dokonlar);
    $sarlavha = 'Raqobatchilar narxlari — ' . date('d.m.Y');
    $izoh = hs_rq_dokonlar_soni_matn($dokonlar, $soni);
    $jpeglar = array_map('hs_rq_jpeg', hs_rq_kesim_rasmlar($dokonlar, $sarlavha, $izoh));
    foreach ($dokonlar as $kanal => $turkumlar) {
        $bitta = array($kanal => $turkumlar);
        foreach (hs_rq_jadval_rasmlar($bitta, hs_rq_channel_title($kanal), $sarlavha . ' · ' . hs_rq_jadval_soni($bitta) . ' ta mahsulot', HS_RQ_A4_W, HS_RQ_A4_H) as $img) {
            $jpeglar[] = hs_rq_jpeg($img, 85);
        }
    }
    list($ok) = hs_rq_tg_fayl('sendDocument',
        array('caption' => mb_substr(hs_rq_izoh('📄 ' . $sarlavha . ' · ' . $soni . ' ta mahsulot', $dokonlar), 0, 1000)),
        array('document' => array(hs_rq_pdf($jpeglar), 'raqobatchilar-narxlari-' . date('Y-m-d') . '.pdf', 'application/pdf')));
    return $ok;
}
