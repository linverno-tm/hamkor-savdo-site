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
 * Token ikki joydan: paneldagi "Metrika'ni ulash" formasi (bazadagi
 * settings, ustun) yoki secrets.php. Baza veb orqali ochilmaydi.
 *
 * Ilgari secrets.php ustun edi. Natijada paneldan kiritilgan yangi token
 * tekshiruvdan o'tib saqlanardi, lekin so'rovlar baribir secrets.php dagi
 * eski (bekor qilingan) token bilan ketib, 403 qaytarardi. secrets.php
 * deploy'da yozilmaydi, ya'ni serverdagi eski qiymat o'zicha yo'qolmaydi.
 * Paneldan kiritish — egasining oxirgi aniq harakati, shuning uchun u yutadi.
 */
function hs_metrika_token()
{
    $t = (string) hs_setting('metrika_token', '');
    return $t !== '' ? $t : (string) hs_config('metrika_token', '');
}

/** Xato xabarida qaysi token ishlatilganini aytish uchun. */
function hs_metrika_token_source()
{
    return (string) hs_setting('metrika_token', '') !== '' ? 'panel' : 'secrets.php';
}

function hs_metrika_ready()
{
    return hs_metrika_token() !== '';
}

/** Tokenni saqlashdan oldin tekshirish: hisoblagichni o'qiy oladimi. */
function hs_metrika_check_token($token)
{
    /* Sahifa ishlatadigan uchala so'rovni ham sinaymiz. Ilgari faqat
       birinchisi tekshirilardi: u 200 berib token saqlanardi, sahifadagi
       /goals yoki /stat esa 403 qaytarardi — sabab ko'rinmasdi. */
    $id = hs_metrika_counter();
    $checks = array(
        '/management/v1/counter/' . $id,
        '/management/v1/counter/' . $id . '/goals',
        '/stat/v1/data?' . http_build_query(array('ids' => $id, 'metrics' => 'ym:s:visits', 'date1' => 'today', 'date2' => 'today')),
    );
    foreach ($checks as $i => $path) {
        list($code, $body) = hs_http(
            'GET',
            'https://api-metrika.yandex.net' . $path,
            array('Authorization: OAuth ' . $token, 'Accept: application/json'),
            null,
            20
        );
        if ($code === 200) {
            continue;
        }
        if ($code === 0) {
            return "Yandex'ga ulanib bo'lmadi (internet yoki serverda curl yo'q).";
        }
        $sabab = hs_metrika_error_message($body);
        $nom = strtok($path, '?') . ($sabab !== '' ? ' — ' . $sabab : '');
        if ($i === 0 && ($code === 401 || $code === 403)) {
            return "Token qabul qilinmadi: u noto'liq nusxalangan yoki boshqa Yandex akkauntdan olingan. Tokenni Metrika (hisoblagich {$id}) ochilgan akkaunt bilan qaytadan oling. (HTTP {$code}: {$nom})";
        }
        return "Token hisoblagichni ko'radi, lekin statistikani o'qiy olmayapti (HTTP {$code}): {$nom}";
    }
    return null;
}

/** Yandex xato javobidagi sabab matni. */
function hs_metrika_error_message($body)
{
    $j = json_decode((string) $body, true);
    if (!is_array($j)) {
        return '';
    }
    if (!empty($j['message'])) {
        return mb_substr((string) $j['message'], 0, 160);
    }
    if (!empty($j['errors'][0]['message'])) {
        return mb_substr((string) $j['errors'][0]['message'], 0, 160);
    }
    return '';
}

function hs_metrika_url($endpoint, $params)
{
    return 'https://api-metrika.yandex.net' . $endpoint . ($params ? '?' . http_build_query($params) : '');
}

/**
 * Bir nechta so'rovni BIR VAQTDA yuborib, javoblarni keshga yozadi.
 * Statistika sahifasi 8 ta so'rov qiladi; ketma-ket bo'lsa 5–10 soniya
 * qotib turardi. Endi hammasi parallel ketadi, keyin sahifa ularni
 * keshdan oladi. Xato javob keshlanmaydi — oddiy so'rov uni qayta urinib,
 * xato matnini o'zi ko'rsatadi. curl_multi bo'lmasa — jimgina hech narsa qilmaydi.
 */
function hs_metrika_prefetch($requests)
{
    if (!function_exists('curl_multi_init') || !hs_metrika_ready()) {
        return;
    }
    $mh = curl_multi_init();
    $handles = array();
    foreach ($requests as $r) {
        $url = hs_metrika_url($r[0], $r[1]);
        $key = 'ym:' . md5($url);
        if (isset($handles[$key]) || hs_cache_get($key) !== null) {
            continue;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => array('Authorization: OAuth ' . hs_metrika_token(), 'Accept: application/json'),
        ));
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    if (!$handles) {
        curl_multi_close($mh);
        return;
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);
    foreach ($handles as $key => $ch) {
        if ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $data = json_decode((string) curl_multi_getcontent($ch), true);
            if (is_array($data)) {
                hs_cache_set($key, $data, 600);
            }
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

/** Statistika sahifasi uchun hamma so'rovlar oldindan, parallel. */
function hs_metrika_prefetch_page($date1, $date2)
{
    $goalsEndpoint = '/management/v1/counter/' . hs_metrika_counter() . '/goals';
    // 1-to'lqin: maqsadlar ro'yxati va unga bog'liq bo'lmagan hamma narsa.
    $reqs = array(
        array($goalsEndpoint, array()),
        array('/stat/v1/data', hs_metrika_daily_params()),
        array('/stat/v1/data', hs_metrika_top_pages_params($date1, $date2)),
    );
    foreach (array(array('ym:s:lastTrafficSource', 10), array('ym:s:lastSocialNetwork', 8), array('ym:s:regionCity', 10), array('ym:s:deviceCategory', 5)) as $b) {
        $reqs[] = array('/stat/v1/data', hs_metrika_breakdown_params($b[0], $date1, $date2, $b[1]));
    }
    hs_metrika_prefetch($reqs);
    // 2-to'lqin: asosiy raqamlar maqsad ID laridan tuziladi.
    $err = null;
    hs_metrika_prefetch(array(array('/stat/v1/data', hs_metrika_overview_params(hs_metrika_goal_ids($err), $date1, $date2))));
}

function hs_metrika_request($endpoint, $params, &$error = null)
{
    $url = hs_metrika_url($endpoint, $params);
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
        $sabab = hs_metrika_error_message($body);
        $quyruq = $endpoint . ($sabab !== '' ? ' — ' . $sabab : '') . ' [token: ' . hs_metrika_token_source() . ']';
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

function hs_metrika_stat_params($params)
{
    return array_merge(array('ids' => hs_metrika_counter(), 'accuracy' => 'full', 'lang' => 'en'), $params);
}

function hs_metrika_stat($params, &$error = null)
{
    return hs_metrika_request('/stat/v1/data', hs_metrika_stat_params($params), $error);
}

/* So'rov parametrlari alohida: sahifa ham, oldindan yuklash ham aynan bir
   xil manzilni yasashi kerak — aks holda kesh kaliti mos kelmaydi. */
function hs_metrika_overview_params($goals, $date1, $date2)
{
    $metrics = array('ym:s:visits', 'ym:s:users');
    foreach ($goals as $gid) {
        if ($gid) {
            $metrics[] = 'ym:s:goal' . $gid . 'reaches';
        }
    }
    return hs_metrika_stat_params(array('metrics' => implode(',', $metrics), 'date1' => $date1, 'date2' => $date2));
}

function hs_metrika_daily_params()
{
    return hs_metrika_stat_params(array('metrics' => 'ym:s:visits', 'dimensions' => 'ym:s:date', 'date1' => '6daysAgo', 'date2' => 'today', 'sort' => 'ym:s:date', 'limit' => 7));
}

function hs_metrika_breakdown_params($dimension, $date1, $date2, $limit)
{
    return hs_metrika_stat_params(array(
        'metrics' => 'ym:s:visits',
        'dimensions' => $dimension,
        'date1' => $date1,
        'date2' => $date2,
        'sort' => '-ym:s:visits',
        'limit' => $limit,
    ));
}

function hs_metrika_top_pages_params($date1, $date2)
{
    return hs_metrika_stat_params(array(
        'metrics' => 'ym:pv:pageviews',
        'dimensions' => 'ym:pv:URLPath',
        'date1' => $date1,
        'date2' => $date2,
        'sort' => '-ym:pv:pageviews',
        'limit' => 10,
    ));
}

/**
 * Asosiy raqamlar: tashriflar, odamlar, maqsadlar va 7 kunlik grafik.
 */
function hs_metrika_overview($date1, $date2, &$error = null)
{
    $goals = hs_metrika_goal_ids($error);
    $d = hs_metrika_request('/stat/v1/data', hs_metrika_overview_params($goals, $date1, $date2), $error);
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
    $daily = hs_metrika_request('/stat/v1/data', hs_metrika_daily_params(), $error);
    /* Metrika faqat tashrif bo'lgan kunlarni qaytaradi. Bo'sh kunlarni 0
       bilan to'ldiramiz, aks holda bitta kunlik ma'lumot butun grafikni
       egallab oladi va "7 kun" grafigi bir ustunga aylanadi. */
    if ($daily !== null) {
        $byDate = array();
        if (!empty($daily['data'])) {
            foreach ($daily['data'] as $row) {
                $byDate[$row['dimensions'][0]['name']] = (int) $row['metrics'][0];
            }
        }
        for ($k = 6; $k >= 0; $k--) {
            $day = date('Y-m-d', strtotime("-{$k} day"));
            $out['daily7'][] = array(date('d.m', strtotime($day)), isset($byDate[$day]) ? $byDate[$day] : 0);
        }
    }
    return $out;
}

function hs_metrika_breakdown($dimension, $date1, $date2, $limit = 10, &$error = null)
{
    $d = hs_metrika_request('/stat/v1/data', hs_metrika_breakdown_params($dimension, $date1, $date2, $limit), $error);
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
    $d = hs_metrika_request('/stat/v1/data', hs_metrika_top_pages_params($date1, $date2), $error);
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
