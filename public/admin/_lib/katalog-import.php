<?php
/**
 * Katalogni @hamkorsavdouz kanalidan to'ldirish.
 *
 * Sayt katalogi bo'sh edi: mahsulotlarni qo'lda kiritish uchun minglab post
 * bor, hech kim ulgurmaydi. Kanaldagi postlar esa allaqachon bazada —
 * mijozlar boti ularni `mb_posts` ga yig'ib turadi.
 *
 * Zanjir:
 *   1. `mb_posts` dagi hali ko'rilmagan postlar olinadi.
 *   2. AI har birini o'qiydi — MATNI VA RASMI bilan: mahsulot nomi,
 *      yo'nalishi, oylik to'lovi. Narx ko'pincha faqat rasmda bo'ladi.
 *   3. Natija `kat_import` ga yoziladi va panelda ro'yxat bo'lib ko'rinadi.
 *   4. EGASI ko'rib chiqadi va tanlaganini saytga chiqaradi.
 *
 * Nega 4-qadam qo'lda: narx ochiq saytga chiqadi va mijoz uni haq deb biladi.
 * AI rasmdagi raqamni xato o'qisa (1 793 000 -> 793 000), do'kon javobgar
 * bo'lib qoladi. Bir marta bosish — arzon narx, xato narx esa qimmat.
 * Ro'yxatdagi hammasini bitta tugma bilan tasdiqlash mumkin, ya'ni qo'lda
 * yozish baribir yo'q.
 */

require_once __DIR__ . '/mijozbot.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/kuzatuv.php';

/** Saytdagi yo'nalishlar. Skuterlar — texnika ichida (egasi shunday deydi). */
function hs_ki_kategoriyalar()
{
    return array('texnika' => 'Texnika', 'tilla' => 'Tilla', 'mebel' => 'Mebel');
}

function hs_ki_schema()
{
    return array(
        'type' => 'object',
        'properties' => array(
            'mahsulotmi' => array('type' => 'boolean'),
            'name' => array('type' => 'string'),
            'category' => array('type' => 'string', 'enum' => array('texnika', 'tilla', 'mebel')),
            'price' => array('type' => 'integer'),
            'months' => array('type' => 'integer'),
            'note' => array('type' => 'string'),
        ),
        'required' => array('mahsulotmi', 'name', 'category', 'price', 'months', 'note'),
        'additionalProperties' => false,
    );
}

function hs_ki_prompt()
{
    $oy = 12;
    $c = hs_content_published();
    if (is_array($c) && isset($c['settings']['installmentMonthsMax'])) {
        $oy = (int) $c['settings']['installmentMonthsMax'];
    }
    return "Sen HAMKOR SAVDO do'koni uchun katalog tuzayapsan. Senga do'konning o'z Telegram posti beriladi: "
        . "matni va, bo'lsa, rasmi. Undan BITTA mahsulot kartochkasini tayyorla.\n\n"
        . "Mahsulot nomi va narxi ko'pincha RASMGA yozilgan bo'ladi — rasmdagi yozuvlarni diqqat bilan o'qi.\n\n"
        . "Qoidalar:\n"
        . "- mahsulotmi: false — agar bu aksiya e'loni, tabrik, bayram xabari, ish e'loni, "
        . "mijoz fikri yoki bir nechta har xil mahsulot ro'yxati bo'lsa. Bunda qolgan maydonlar bo'sh.\n"
        . "- name: brend va model bilan, qisqa: \"Artel muzlatgich HD-395\", \"Samsung kir yuvish mashinasi 7 kg\". "
        . "BOSH HARFLAR bilan yozilgan bo'lsa, oddiy yozuvga o'gir. Reklama so'zlarini (ZO'R, ARZON, YANGI) olib tashla.\n"
        . "- category: texnika (maishiy texnika, telefon, skuter, velosiped), tilla (zargarlik), mebel (uy jihozlari).\n"
        . "- price: OYIGA to'lanadigan summa, so'mda, faqat raqam. \"12 OYGA 209 000 so'mdan\" -> 209000. "
        . "Faqat to'liq narx ko'rsatilgan bo'lsa ham shu yerga yoz. Ko'rinmasa 0 — o'ylab topma.\n"
        . "- months: necha oyga. Ko'rinmasa {$oy}.\n"
        . "- note: bitta qisqa jumla — hajmi, rangi, quvvati kabi aniq belgi. Matnda bo'lmasa bo'sh qoldir. "
        . "Reklama gapini ko'chirma.";
}

/** Bitta postni AI ga yuborish. Qaytadi: [massiv yoki null, xato]. */
function hs_ki_ai($post)
{
    $matn = "Post matni:\n\"\"\"" . mb_substr(hs_mb_scrub($post['text']), 0, 1500) . "\"\"\"";
    $img = null;
    if ((string) $post['rasm'] !== '') {
        list($code, $b) = hs_http('GET', (string) $post['rasm'], array('User-Agent: Mozilla/5.0 (HamkorSavdo katalog)'), null, 20);
        if ($code === 200 && strlen((string) $b) < 3000000) {
            $mime = hs_rq_mime((string) $b);
            if ($mime !== '') {
                $img = array($mime, base64_encode($b));
                $matn .= "\n\nRasm ilova qilingan — nom va narxni undan o'qi.";
            }
        }
    }
    if (getenv('HS_MB_FAKE_AI')) {
        $fake = json_decode((string) @file_get_contents(getenv('HS_MB_FAKE_AI')), true);
        return array(is_array($fake) ? $fake : null, is_array($fake) ? '' : "fake yo'q");
    }
    return hs_mb_provider() === 'gemini'
        ? hs_ki_gemini($matn, $img)
        : hs_ki_claude($matn, $img);
}

function hs_ki_gemini($matn, $img)
{
    $key = hs_mb_gemini_key();
    if ($key === '') {
        return array(null, 'Gemini kaliti kiritilmagan.');
    }
    $schema = hs_ki_schema();
    unset($schema['additionalProperties']);
    $schema['type'] = 'OBJECT';
    $schema['propertyOrdering'] = array_keys($schema['properties']);
    foreach ($schema['properties'] as $k => $v) {
        $schema['properties'][$k]['type'] = strtoupper($v['type']);
    }
    $body = array(
        'system_instruction' => array('parts' => array(array('text' => hs_ki_prompt()))),
        'contents' => array(array('role' => 'user', 'parts' => $img
            ? array(array('inline_data' => array('mime_type' => $img[0], 'data' => $img[1])), array('text' => $matn))
            : array(array('text' => $matn)))),
        'generationConfig' => array(
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
            'temperature' => 0.1,
            'maxOutputTokens' => 900,
        ),
    );
    $url = HS_MB_GEMINI_API . rawurlencode(hs_mb_gemini_model()) . ':generateContent';
    list($code, $resp) = hs_http('POST', $url, array('Content-Type: application/json', 'x-goog-api-key: ' . $key), json_encode($body, JSON_UNESCAPED_UNICODE), 60);
    $j = json_decode((string) $resp, true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? (string) $j['error']['message'] : "javob yo'q";
        return array(null, "Gemini HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    $out = '';
    foreach (isset($j['candidates'][0]['content']['parts']) ? $j['candidates'][0]['content']['parts'] : array() as $part) {
        if (isset($part['text'])) {
            $out .= $part['text'];
        }
    }
    $d = json_decode($out, true);
    return is_array($d) && isset($d['mahsulotmi']) ? array($d, '') : array(null, 'Gemini javobi tushunarsiz.');
}

function hs_ki_claude($matn, $img)
{
    $key = hs_mb_key();
    if ($key === '') {
        return array(null, 'Claude kaliti kiritilmagan.');
    }
    $model = hs_mb_setting('model');
    $body = array(
        'model' => $model,
        'max_tokens' => 900,
        'system' => array(array('type' => 'text', 'text' => hs_ki_prompt())),
        'messages' => array(array('role' => 'user', 'content' => $img
            ? array(array('type' => 'image', 'source' => array('type' => 'base64', 'media_type' => $img[0], 'data' => $img[1])), array('type' => 'text', 'text' => $matn))
            : $matn)),
        'output_config' => array('effort' => 'low', 'format' => array('type' => 'json_schema', 'schema' => hs_ki_schema())),
    );
    $headers = array('Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01');
    if ($model === 'claude-opus-5') {
        $body['fallbacks'] = 'default';
        $headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
    }
    list($code, $resp) = hs_http('POST', HS_MB_API, $headers, json_encode($body, JSON_UNESCAPED_UNICODE), 60);
    $j = json_decode((string) $resp, true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? (string) $j['error']['message'] : "javob yo'q";
        return array(null, "Claude HTTP {$code}: " . mb_substr($msg, 0, 160));
    }
    $out = '';
    foreach (isset($j['content']) ? $j['content'] : array() as $block) {
        if (isset($block['type']) && $block['type'] === 'text') {
            $out .= $block['text'];
        }
    }
    $d = json_decode($out, true);
    return is_array($d) && isset($d['mahsulotmi']) ? array($d, '') : array(null, 'Claude javobi tushunarsiz.');
}

/**
 * Ko'rilmagan postlarni o'qib, kat_import ga yozish.
 * Faqat rasmi bor va yetarlicha yangi postlar: rasmsiz postdan kartochka
 * chiqmaydi, juda eski postning narxi esa allaqachon o'zgargan.
 * Qaytadi: [tahlil qilingan, mahsulot topilgan, xato matni].
 */
function hs_ki_scan($limit = 20, $kunlar = 180)
{
    $st = hs_db()->prepare("SELECT p.* FROM mb_posts p
        LEFT JOIN kat_import k ON k.source = p.source AND k.post_id = p.post_id
        WHERE k.post_id IS NULL AND p.rasm <> '' AND p.posted_at > ?
        ORDER BY p.posted_at DESC LIMIT ?");
    $st->execute(array(date('Y-m-d H:i:s', time() - $kunlar * 86400), (int) $limit));
    $rows = $st->fetchAll();
    $ins = hs_db()->prepare('INSERT OR REPLACE INTO kat_import
        (source, post_id, url, name, category, price, months, note, rasm, holat, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $n = 0;
    $topildi = 0;
    foreach ($rows as $p) {
        list($d, $err) = hs_ki_ai($p);
        if ($d === null) {
            return array($n, $topildi, $err);
        }
        $n++;
        $mahsulot = !empty($d['mahsulotmi']) && trim((string) $d['name']) !== '';
        $topildi += $mahsulot ? 1 : 0;
        $ins->execute(array(
            $p['source'], (int) $p['post_id'], $p['url'],
            $mahsulot ? mb_substr(trim((string) $d['name']), 0, 120) : '',
            isset(hs_ki_kategoriyalar()[$d['category']]) ? $d['category'] : 'texnika',
            $mahsulot && (int) $d['price'] > 0 ? (int) $d['price'] : null,
            (int) $d['months'],
            mb_substr(trim((string) $d['note']), 0, 300),
            $p['rasm'],
            $mahsulot ? 'yangi' : 'mahsulot emas',
            hs_now(),
        ));
    }
    return array($n, $topildi, '');
}

function hs_ki_kutayotganlar($limit = 200)
{
    $st = hs_db()->prepare("SELECT * FROM kat_import WHERE holat = 'yangi' ORDER BY created_at DESC, post_id DESC LIMIT ?");
    $st->execute(array((int) $limit));
    return $st->fetchAll();
}

function hs_ki_sanoq()
{
    $r = hs_db()->query("SELECT
        (SELECT COUNT(*) FROM kat_import WHERE holat = 'yangi') AS kutmoqda,
        (SELECT COUNT(*) FROM kat_import WHERE holat = 'qabul') AS qabul,
        (SELECT COUNT(*) FROM kat_import WHERE holat = 'rad') AS rad,
        (SELECT COUNT(*) FROM kat_import WHERE holat = 'mahsulot emas') AS emas,
        (SELECT COUNT(*) FROM mb_posts WHERE rasm <> '') AS rasmli,
        (SELECT COUNT(*) FROM kat_import) AS korilgan")->fetch();
    return $r ?: array('kutmoqda' => 0, 'qabul' => 0, 'rad' => 0, 'emas' => 0, 'rasmli' => 0, 'korilgan' => 0);
}

/**
 * Tanlanganlarni saytga chiqarish: rasmlar va content.json BITTA commit'da.
 * Bitta-bitta commit qilinsa, har biri build'ni qayta ishga tushirardi.
 * Qaytadi: [qo'shilgan soni, xato matni].
 */
function hs_ki_publish($user, $kalitlar)
{
    $qatorlar = array();
    foreach ($kalitlar as $k) {
        list($src, $pid) = array_pad(explode('|', (string) $k, 2), 2, '');
        $st = hs_db()->prepare("SELECT * FROM kat_import WHERE source = ? AND post_id = ? AND holat = 'yangi'");
        $st->execute(array($src, (int) $pid));
        $r = $st->fetch();
        if ($r) {
            $qatorlar[] = $r;
        }
    }
    if (!$qatorlar) {
        return array(0, 'Hech narsa tanlanmadi.');
    }
    $extra = array();
    $yangilar = array();
    foreach ($qatorlar as $r) {
        $rasm = '';
        list($code, $b) = hs_http('GET', (string) $r['rasm'], array('User-Agent: Mozilla/5.0 (HamkorSavdo katalog)'), null, 25);
        if ($code === 200 && hs_rq_mime((string) $b) !== '') {
            $kengaytma = hs_rq_mime($b) === 'image/png' ? 'png' : (hs_rq_mime($b) === 'image/webp' ? 'webp' : 'jpg');
            $nom = hs_new_image_name('mahsulot');
            $extra[] = array('path' => "rasmlar/katalog/{$nom}.{$kengaytma}", 'content' => $b);
            $rasm = "katalog/{$nom}";
        }
        $yangilar[] = array(
            'id' => 'k' . preg_replace('/[^0-9]/', '', $r['source'] . $r['post_id']),
            'name' => (string) $r['name'],
            'category' => (string) $r['category'],
            'price' => $r['price'] === null ? null : (int) $r['price'],
            'image' => $rasm,
            'inStock' => true,
            'note' => (string) $r['note'],
        );
    }
    $xato = hs_content_publish($user, count($yangilar) . " ta mahsulot kanaldan qo'shildi", function (&$c) use ($yangilar) {
        $bor = array();
        foreach ($c['products'] as $p) {
            $bor[$p['id']] = true;
        }
        foreach ($yangilar as $p) {
            if (!isset($bor[$p['id']])) {
                array_unshift($c['products'], $p);
            }
        }
    }, $extra);
    if ($xato !== null) {
        return array(0, $xato);
    }
    $u = hs_db()->prepare("UPDATE kat_import SET holat = 'qabul', product_id = ? WHERE source = ? AND post_id = ?");
    foreach ($qatorlar as $i => $r) {
        $u->execute(array($yangilar[$i]['id'], $r['source'], $r['post_id']));
    }
    return array(count($yangilar), '');
}

/** Rad etilgan post qayta taklif qilinmaydi. */
function hs_ki_rad($kalitlar)
{
    $u = hs_db()->prepare("UPDATE kat_import SET holat = 'rad' WHERE source = ? AND post_id = ? AND holat = 'yangi'");
    $n = 0;
    foreach ($kalitlar as $k) {
        list($src, $pid) = array_pad(explode('|', (string) $k, 2), 2, '');
        $u->execute(array($src, (int) $pid));
        $n += $u->rowCount();
    }
    return $n;
}
