<?php
/**
 * Kuzatuv o'quvchisi uchun manzil.
 *
 * Do'kondagi kompyuterda ishlaydigan dastur (tools/kuzatuv-oquvchi) ochiq
 * guruhlarni o'qib, yangi xabarlarni shu yerga yuboradi. Telegram guruhni
 * bot orqali o'qishga yo'l bermaydi, shuning uchun bu yo'l tanlangan.
 *
 * Himoya: paneldagi kalit (Raqobatchilar bo'limida yaratiladi). Kalitsiz yoki
 * noto'g'ri kalit bilan kelgan so'rov hech narsa qilmaydi. Kalit sarlavhada
 * yuboriladi — manzil qatorida emas, aks holda u server jurnaliga tushardi.
 *
 * Ikki amal:
 *   {"amal":"royxat"}            -> qaysi guruhlarni o'qish kerak
 *   {"amal":"yuklash","postlar":[...]} -> yangi xabarlar
 */
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/kuzatuv.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

function hs_rq_javob($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_rq_javob(array('ok' => false, 'xato' => 'faqat POST'), 405);
}

$kalit = hs_rq_ingest_key();
$berilgan = isset($_SERVER['HTTP_X_HS_KEY']) ? (string) $_SERVER['HTTP_X_HS_KEY'] : '';
if ($kalit === '' || strlen($berilgan) !== strlen($kalit) || !hash_equals($kalit, $berilgan)) {
    // Kalit hali yaratilmagan bo'lsa ham xuddi shu javob — farqi bilinmasin.
    hs_rq_javob(array('ok' => false, 'xato' => 'kalit'), 403);
}

$xom = (string) file_get_contents('php://input');
if (strlen($xom) > 4000000) {
    hs_rq_javob(array('ok' => false, 'xato' => 'juda katta'), 413);
}
$in = json_decode($xom, true);
if (!is_array($in)) {
    hs_rq_javob(array('ok' => false, 'xato' => 'json'), 400);
}

$amal = isset($in['amal']) ? (string) $in['amal'] : '';
if ($amal === 'royxat') {
    hs_rq_javob(array('ok' => true, 'guruhlar' => hs_rq_group_list()));
}
if ($amal === 'yuklash') {
    $postlar = isset($in['postlar']) && is_array($in['postlar']) ? $in['postlar'] : array();
    if (count($postlar) > 200) {
        $postlar = array_slice($postlar, 0, 200);
    }
    list($n, $skip) = hs_rq_ingest($postlar);
    if (!$n) {
        hs_rq_javob(array('ok' => true, 'yozildi' => 0, 'otkazildi' => $skip));
    }
    /* Real vaqt: cron'ni (5 daqiqa) kutmasdan shu zahoti tahlil qilib guruhga
       yozamiz. O'quvchi AI ni kutib turmasin — javob oldin yopiladi. */
    $javob = json_encode(array('ok' => true, 'yozildi' => $n, 'otkazildi' => $skip), JSON_UNESCAPED_UNICODE);
    ignore_user_abort(true);
    header('Connection: close');
    header('Content-Length: ' . strlen($javob));
    echo $javob;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    hs_rq_darhol();
    exit;
}
hs_rq_javob(array('ok' => false, 'xato' => 'amal'), 400);
