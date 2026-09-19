<?php
/**
 * Yandex Metrika Reporting API. Token faqat serverda (secrets.php > metrika_token).
 * Natijalar 10 daqiqa keshlanadi — API limitlariga urilmaslik va panel tez ochilishi uchun.
 */

function hs_metrika_counter()
{
    return (int) hs_config('metrika_counter', 112743600);
}

/**
 * Token ikki joydan: secrets.php (ustun) yoki paneldagi "Metrika'ni ulash"
 * formasi (bazadagi settings). Baza veb orqali ochilmaydi.
 */
function hs_metrika_token()
{
    $t = (string) hs_config('metrika_token', '');
    return $t !== '' ? $t : (string) hs_setting('metrika_token', '');
}

function hs_metrika_ready()
{
    return hs_metrika_token() !== '';
}

/** Tokenni saqlashdan oldin tekshirish: hisoblagichni o'qiy oladimi. */
function hs_metrika_check_token($token)
{
    list($code, $body) = hs_http(
        'GET',
        'https://api-metrika.yandex.net/management/v1/counter/' . hs_metrika_counter(),
        array('Authorization: OAuth ' . $token, 'Accept: application/json'),
        null,
        20
    );
    if ($code === 200) {
        return null;
    }
    if ($code === 401 || $code === 403) {
        return "Token qabul qilinmadi: u noto'liq nusxalangan yoki boshqa Yandex akkauntdan olingan. Tokenni Metrika (hisoblagich " . hs_metrika_counter() . ") ochilgan akkaunt bilan qaytadan oling.";
    }
    return $code === 0 ? "Yandex'ga ulanib bo'lmadi (internet yoki serverda curl yo'q)." : "Yandex javob bermadi (HTTP {$code}).";
}

function hs_metrika_request($endpoint, $params, &$error = null)
{
    $url = 'https://api-metrika.yandex.net' . $endpoint . ($params ? '?' . http_build_query($params) : '');
    $key = 'ym:' . md5($url);
    $cached = hs_cache_get($key);
    if ($cached !== null) {
        return $cached;
    }
    list($code, $body) = hs_http('GET', $url, array('Authorization: OAuth ' . hs_metrika_token(), 'Accept: application/json'), null, 20);
    if ($code !== 200) {
        /* Qaysi so'rov yiqilgani va Yandex nima deganini xabarga qo'shamiz.
           Ilgari faqat "token yaroqsiz" deb yozilardi va shu bilan ish
           tugardi: token saqlashdan oldin tekshiruvdan o'tgan, lekin
           sahifadagi boshqa so'rov 403 bergan holatni ajratib bo'lmasdi.
           Endpoint har xil — biri /management, ikkinchisi /stat — ruxsat
           talablari ham har xil bo'lishi mumkin. Yandex javobining
           sababi `message` maydonida keladi. */
        $sabab = '';
        $j = json_decode($body, true);
        if (is_array($j)) {
            if (!empty($j['message'])) {
                $sabab = (string) $j['message'];
            } elseif (!empty($j['errors'][0]['message'])) {
                $sabab = (string) $j['errors'][0]['message'];
            }
        }
        $quyruq = $endpoint . ($sabab !== '' ? ' — ' . mb_substr($sabab, 0, 160) : '');
        $error = $code === 401 || $code === 403
            ? "Metrika tokeni yaroqsiz yoki ruxsati yetmaydi (HTTP {$code}): {$quyruq}"
            : "Metrika'dan ma'lumot olinmadi (HTTP {$code}): {$quyruq}";
        return null;
    }
    $data = json_decode($body, true);
    hs_cache_set($key, $data, 600);
    return $data;
}

/** Maqsad identifikatori (phone_click, lead_sent) -> Metrika ichki ID raqami. */
function hs_metrika_goal_ids(&$error = null)
{
    $data = hs_metrika_request('/management/v1/counter/' . hs_metrika_counter() . '/goals', array(), $error);
    $ids = array('phone_click' => null, 'telegram_click' => null, 'lead_sent' => null);
    if (!$data || empty($data['goals'])) {
        return $ids;
    }
    foreach ($data['goals'] as $g) {
        if (empty($g['conditions'])) {
            continue;
        }
        foreach ($g['conditions'] as $c) {
            if (isset($c['url']) && array_key_exists($c['url'], $ids)) {
                $ids[$c['url']] = (int) $g['id'];
            }
        }
    }
    return $ids;
}

function hs_metrika_stat($params, &$error = null)
{
    $params = array_merge(array('ids' => hs_metrika_counter(), 'accuracy' => 'full', 'lang' => 'en'), $params);
    return hs_metrika_request('/stat/v1/data', $params, $error);
}

/**
 * Asosiy raqamlar: tashriflar, odamlar, maqsadlar va 7 kunlik grafik.
 */
function hs_metrika_overview($date1, $date2, &$error = null)
{
    $goals = hs_metrika_goal_ids($error);
    $metrics = array('ym:s:visits', 'ym:s:users');
    foreach ($goals as $gid) {
        if ($gid) {
            $metrics[] = 'ym:s:goal' . $gid . 'reaches';
        }
    }
    $d = hs_metrika_stat(array('metrics' => implode(',', $metrics), 'date1' => $date1, 'date2' => $date2), $error);
    if (!$d) {
        return null;
    }
    $t = isset($d['totals']) ? $d['totals'] : array();
    $out = array(
        'visits' => isset($t[0]) ? (int) $t[0] : 0,
        'users' => isset($t[1]) ? (int) $t[1] : 0,
        'goals' => array('phone_click' => null, 'telegram_click' => null, 'lead_sent' => null),
        'daily7' => array(),
    );
    $i = 2;
    foreach ($goals as $name => $gid) {
        if ($gid) {
            $out['goals'][$name] = isset($t[$i]) ? (int) $t[$i] : 0;
            $i++;
        }
    }
    $daily = hs_metrika_stat(array('metrics' => 'ym:s:visits', 'dimensions' => 'ym:s:date', 'date1' => '6daysAgo', 'date2' => 'today', 'sort' => 'ym:s:date', 'limit' => 7), $error);
    if ($daily && !empty($daily['data'])) {
        foreach ($daily['data'] as $row) {
            $out['daily7'][] = array(date('d.m', strtotime($row['dimensions'][0]['name'])), (int) $row['metrics'][0]);
        }
    }
    return $out;
}

function hs_metrika_breakdown($dimension, $date1, $date2, $limit = 10, &$error = null)
{
    $d = hs_metrika_stat(array(
        'metrics' => 'ym:s:visits',
        'dimensions' => $dimension,
        'date1' => $date1,
        'date2' => $date2,
        'sort' => '-ym:s:visits',
        'limit' => $limit,
    ), $error);
    $rows = array();
    if ($d && !empty($d['data'])) {
        foreach ($d['data'] as $r) {
            $rows[] = array(
                'id' => isset($r['dimensions'][0]['id']) ? (string) $r['dimensions'][0]['id'] : '',
                'name' => (string) $r['dimensions'][0]['name'],
                'value' => (int) $r['metrics'][0],
            );
        }
    }
    return $rows;
}

function hs_metrika_top_pages($date1, $date2, &$error = null)
{
    $d = hs_metrika_stat(array(
        'metrics' => 'ym:pv:pageviews',
        'dimensions' => 'ym:pv:URLPath',
        'date1' => $date1,
        'date2' => $date2,
        'sort' => '-ym:pv:pageviews',
        'limit' => 10,
    ), $error);
    $rows = array();
    if ($d && !empty($d['data'])) {
        foreach ($d['data'] as $r) {
            $rows[] = array((string) $r['dimensions'][0]['name'], (int) $r['metrics'][0]);
        }
    }
    return $rows;
}

function hs_traffic_source_label($id, $name)
{
    $map = array(
        'organic' => 'Qidiruv tizimlari (Google, Yandex)',
        'direct' => "To'g'ridan-to'g'ri",
        'social' => 'Ijtimoiy tarmoqlar',
        'messenger' => 'Messenjerlar (Telegram)',
        'referral' => 'Boshqa saytlardagi havolalar',
        'internal' => 'Sayt ichidan',
        'ad' => 'Reklama',
        'email' => 'Email',
        'recommend' => 'Tavsiya tizimlari',
        'saved' => 'Saqlangan sahifalar',
        'qrcode' => 'QR-kod',
        'undefined' => "Aniqlanmagan",
    );
    return isset($map[$id]) ? $map[$id] : $name;
}

function hs_device_label($id, $name)
{
    $map = array('desktop' => 'Kompyuter', 'mobile' => 'Telefon', 'tablet' => 'Planshet', 'tv' => 'Televizor');
    return isset($map[$id]) ? $map[$id] : $name;
}
