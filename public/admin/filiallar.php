<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';
require_once __DIR__ . '/_lib/photos.php';

$user = hs_require_login(true);

/** Telefonni bir xil ko'rinishga keltiradi: 9 raqam -> +998..., ko'rinishi "+998 55 203 08 80". */
function hs_branch_phone($raw)
{
    $phone = preg_replace('/[^\d+]/', '', (string) $raw);
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 9) {
        $phone = '+998' . $digits;
    } elseif (strlen($digits) === 12) {
        $phone = '+' . $digits;
    }
    return array(
        $phone !== '' ? $phone : null,
        $phone !== '' ? preg_replace('/^\+998(\d{2})(\d{3})(\d{2})(\d{2})$/', '+998 $1 $2 $3 $4', $phone) : null,
    );
}

/**
 * Formadagi filial maydonlari. Faqat formada BOR maydonlar qaytadi —
 * filial sahifasidagi har bir karta o'z qismini alohida saqlaydi va
 * qolganiga tegmaydi.
 */
function hs_branch_fields_from_post()
{
    $has = function ($k) {
        return array_key_exists($k, $_POST);
    };
    $out = array();
    if ($has('phone')) {
        list($out['phone'], $out['phoneDisplay']) = hs_branch_phone(hs_post('phone'));
    }
    if ($has('hours')) {
        $out['hours'] = str_replace(array('-', '—'), '–', preg_replace('/\s+/', '', hs_post('hours')));
    }
    if ($has('instagram')) {
        $insta = ltrim(preg_replace('#^https?://(www\.)?instagram\.com/#i', '', trim(hs_post('instagram'), '/ ')), '@');
        $out['instagram'] = $insta !== '' ? mb_substr($insta, 0, 40) : null;
    }
    foreach (array('city' => 60, 'address' => 200, 'landmark' => 120, 'mapQuery' => 200) as $k => $max) {
        if ($has($k)) {
            $out[$k] = mb_substr(hs_post($k), 0, $max);
        }
    }
    foreach (array('lat', 'lng') as $k) {
        if ($has($k)) {
            $out[$k] = (float) str_replace(',', '.', hs_post($k));
        }
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $id = hs_post('id');
    $problem = 'Noma\'lum amal.';
    $back = '/admin/filiallar.php' . ($id !== '' && $action !== 'ochirish' ? '?id=' . rawurlencode($id) : '');

    if ($action === 'qism') {
        // Filial sahifasidagi bitta karta: faqat o'zidagi maydonlar yangilanadi.
        $patch = hs_branch_fields_from_post();
        $what = hs_post('nima', 'filial');
        $problem = hs_content_publish($user, "filial ({$what}): {$id}", function (&$c) use ($id, $patch) {
            foreach ($c['branches'] as $i => $b) {
                if ($b['id'] === $id) {
                    if (isset($patch['city']) && trim($patch['city']) === '') {
                        return 'Shahar nomi bo\'sh bo\'lmasin.';
                    }
                    if (isset($patch['hours']) && $patch['hours'] === '') {
                        return 'Ish vaqtini kiriting (masalan 9:00–18:00).';
                    }
                    $c['branches'][$i] = array_merge($b, $patch);
                    return null;
                }
            }
            return 'Filial topilmadi (boshqa kishi o\'chirgan bo\'lishi mumkin).';
        });
        if ($problem === null && hs_post('qayt') === 'royxat') {
            $back = '/admin/filiallar.php';
        }
    } elseif ($action === 'qoshish') {
        $newId = strtolower(trim(hs_post('newid')));
        $new = array_merge(array(
            'id' => $newId, 'city' => '', 'address' => '', 'landmark' => '', 'phone' => null, 'phoneDisplay' => null,
            'instagram' => null, 'hours' => '9:00–18:00', 'mapQuery' => '', 'lat' => 0, 'lng' => 0, 'closed' => false, 'closedNote' => '',
        ), hs_branch_fields_from_post());
        $problem = hs_content_publish($user, "yangi filial: {$new['city']}, {$new['landmark']}", function (&$c) use ($new) {
            if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $new['id'])) {
                return "Sahifa manzili faqat kichik lotin harf, raqam va chiziqchadan iborat bo'lsin.";
            }
            foreach ($c['branches'] as $b) {
                if ($b['id'] === $new['id']) {
                    return 'Bunday manzilli filial allaqachon bor.';
                }
            }
            $c['branches'][] = $new;
            return null;
        });
        $back = $problem === null ? '/admin/filiallar.php?id=' . rawurlencode($newId) : '/admin/filiallar.php?yangi=1';
    } elseif ($action === 'ochirish') {
        $problem = hs_content_publish($user, "filial o'chirildi: {$id}", function (&$c) use ($id) {
            $before = count($c['branches']);
            $c['branches'] = array_values(array_filter($c['branches'], function ($b) use ($id) {
                return $b['id'] !== $id;
            }));
            unset($c['photos'][$id]);
            return count($c['branches']) === $before ? 'Filial topilmadi.' : null;
        });
    } elseif ($action === 'holat') {
        $close = hs_post('yopiq') === '1';
        $note = mb_substr(hs_post('closedNote'), 0, 200);
        $problem = hs_content_publish($user, ($close ? 'filial vaqtincha yopildi: ' : 'filial qayta ochildi: ') . $id, function (&$c) use ($id, $close, $note) {
            foreach ($c['branches'] as $i => $b) {
                if ($b['id'] === $id) {
                    $c['branches'][$i]['closed'] = $close;
                    $c['branches'][$i]['closedNote'] = $close ? $note : '';
                    return null;
                }
            }
            return 'Filial topilmadi.';
        });
        if (hs_post('qayt') === 'royxat') {
            $back = '/admin/filiallar.php';
        }
    } elseif ($action === 'tartib') {
        $order = isset($_POST['tartib']) && is_array($_POST['tartib']) ? $_POST['tartib'] : array();
        $problem = hs_content_publish($user, 'filiallar tartibi', function (&$c) use ($order) {
            usort($c['branches'], function ($a, $b) use ($order) {
                $x = isset($order[$a['id']]) ? (int) $order[$a['id']] : 99;
                $y = isset($order[$b['id']]) ? (int) $order[$b['id']] : 99;
                return $x - $y;
            });
            return null;
        });
        $back = '/admin/filiallar.php';
    }

    hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    hs_redirect($back);
}

$err = null;
$c = hs_content_load($err);
$editId = hs_get('id');
$isNew = hs_get('yangi') === '1';
$edit = null;
if ($c) {
    foreach ($c['branches'] as $b) {
        if ($b['id'] === $editId) {
            $edit = $b;
        }
    }
}

hs_page_start($edit ? $edit['city'] : ($isNew ? 'Yangi filial' : 'Filiallar'), $user, $edit ? 'Shu filialning saytdagi hamma ma\'lumoti bir joyda' : null);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}

/** Oddiy matn maydoni. */
function hs_branch_input($name, $label, $value, $hint = '', $attrs = '', $type = 'text')
{
    $id = 'f-' . $name;
    echo '<div><label for="' . $id . '">' . h($label) . '</label><input id="' . $id . '" type="' . $type . '" name="' . $name . '" value="' . h($value === null ? '' : $value) . '" ' . $attrs . '>';
    if ($hint !== '') {
        echo '<p class="hint">' . $hint . '</p>';
    }
    echo '</div>';
}

/** Filial sahifasidagi bitta saqlanadigan karta boshi. */
function hs_part_start($b, $what, $title, $icon, $sub)
{
    echo '<form class="card part" method="post" action="/admin/filiallar.php">' . hs_csrf_field()
        . '<input type="hidden" name="amal" value="qism"><input type="hidden" name="nima" value="' . h($what) . '"><input type="hidden" name="id" value="' . h($b['id']) . '">';
    echo '<div class="part-head"><span class="part-ico">' . hs_icon($icon) . '</span><div><h2>' . h($title) . '</h2><p class="muted">' . h($sub) . '</p></div></div>';
}

function hs_part_end($label = 'Saqlash')
{
    echo '<div class="actions"><button class="btn" type="submit">' . h($label) . '</button></div></form>';
}

/* =================== BITTA FILIAL =================== */
if ($edit) {
    $b = $edit;
    $prefs = isset($c['photos'][$b['id']]) ? $c['photos'][$b['id']] : array();
    $photos = hs_branch_photos_sorted($b['id'], $prefs);
    $cover = hs_branch_cover($b['id'], $prefs);
    $closed = !empty($b['closed']);
    $siteUrl = hs_site_url() . '/filiallar/' . rawurlencode($b['id']) . '/';

    echo '<p class="crumbs"><a href="/admin/filiallar.php">← Barcha filiallar</a></p>';

    // Bosh qism: rasm, nom, holat, asosiy tugmalar.
    echo '<section class="card branch-hero' . ($closed ? ' is-closed' : '') . '">';
    echo '<div class="hero-img">' . ($cover ? '<img src="' . h($cover['thumb']) . '" alt="" decoding="async">' : '<span>' . hs_icon('image') . '<br>Rasm yo\'q</span>') . '</div>';
    echo '<div class="hero-body"><div class="hero-status">' . ($closed ? '<span class="pill pill-err">Vaqtincha yopiq</span>' : '<span class="pill pill-ok">Ishlayapti</span>') . '</div>';
    echo '<h2 class="hero-title">' . h($b['city']) . ' <span>' . h($b['landmark']) . '</span></h2>';
    echo '<p class="hero-meta">' . hs_icon('phone') . ' ' . h($b['phoneDisplay'] ?: 'Umumiy raqam') . '<span class="dot">·</span>' . hs_icon('clock') . ' ' . h($b['hours']) . '</p>';
    if ($closed && $b['closedNote'] !== '') {
        echo '<p class="closed-note">' . h($b['closedNote']) . '</p>';
    }
    echo '<div class="actions">';
    echo '<a class="btn outline small" href="' . h($siteUrl) . '" target="_blank" rel="noopener noreferrer">' . hs_icon('external') . ' Saytda ko\'rish</a>';
    if ($closed) {
        echo '<form class="inline-form" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . h($b['id']) . '"><input type="hidden" name="yopiq" value="0"><button class="btn small" type="submit">' . hs_icon('check') . ' Qayta ochish</button></form>';
    }
    echo '</div></div></section>';

    echo '<div class="grid grid-2">';

    // 1. Aloqa — eng ko'p o'zgaradigan narsa, birinchi turadi.
    hs_part_start($b, 'aloqa', 'Telefon va Instagram', 'phone', 'Mijoz saytda shu raqamga qo\'ng\'iroq qiladi');
    hs_branch_input('phone', 'Filial telefoni', $b['phoneDisplay'] ?: $b['phone'], 'Istalgan ko\'rinishda yozing: <span class="code">55 203 08 80</span> ham bo\'ladi. Bo\'sh qoldirsangiz — umumiy raqam chiqadi.', 'maxlength="20" inputmode="tel" placeholder="+998 55 203 08 80" autocomplete="off"', 'tel');
    hs_branch_input('instagram', 'Filialning o\'z Instagram sahifasi', $b['instagram'], 'Bo\'lmasa bo\'sh qoldiring.', 'maxlength="60" placeholder="hamkorsavdo.asaka" autocomplete="off"');
    hs_part_end();

    // 2. Ish vaqti.
    hs_part_start($b, 'ish vaqti', 'Ish vaqti', 'clock', 'Saytda "Hozir ochiq / yopiq" shu bo\'yicha yoziladi');
    hs_branch_input('hours', 'Ochilish — yopilish', $b['hours'], '', 'required maxlength="20" placeholder="9:00–18:00" data-hours-input');
    echo '<div class="chips" data-hours-chips><span class="muted">Tez tanlash:</span>';
    foreach (array('8:00–18:00', '9:00–18:00', '9:00–19:00', '9:00–20:00', '8:00–20:00') as $hh) {
        echo '<button type="button" class="chip' . ($hh === $b['hours'] ? ' on' : '') . '" data-hours="' . h($hh) . '">' . h($hh) . '</button>';
    }
    echo '</div>';
    hs_part_end();

    // 3. Suratlar — ko'rinishi shu yerda, boshqarish alohida sahifada.
    echo '<section class="card part"><div class="part-head"><span class="part-ico">' . hs_icon('image') . '</span><div><h2>Suratlar</h2><p class="muted">' . count($photos) . ' ta rasm saytda turibdi</p></div></div>';
    if ($photos) {
        echo '<div class="mini-photos">';
        foreach (array_slice($photos, 0, 6) as $i => $f) {
            $isCover = $cover && $cover['base'] === $f['base'];
            echo '<div class="mini-photo' . ($isCover ? ' is-cover' : '') . '"><img src="' . h($f['thumb']) . '" alt="" loading="lazy" decoding="async">' . ($isCover ? '<span>Asosiy</span>' : '') . '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="empty">Hali rasm yo\'q. Rasmli filial mijozga ishonchliroq ko\'rinadi.</p>';
    }
    echo '<div class="actions"><a class="btn" href="/admin/suratlar.php?filial=' . h(rawurlencode($b['id'])) . '">' . hs_icon('image') . ' Rasmlarni o\'zgartirish</a></div></section>';

    // 4. Vaqtincha yopish.
    if (!$closed) {
        echo '<form class="card part" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . h($b['id']) . '"><input type="hidden" name="yopiq" value="1">';
        echo '<div class="part-head"><span class="part-ico warn">' . hs_icon('clock') . '</span><div><h2>Vaqtincha yopish</h2><p class="muted">Ta\'mir, bayram yoki inventarizatsiya bo\'lsa</p></div></div>';
        echo '<label for="f-closedNote">Saytda nima deb yozilsin? (ixtiyoriy)</label><input id="f-closedNote" type="text" name="closedNote" maxlength="200" placeholder="Masalan: 25-sentabrgacha ta\'mirlanmoqda">';
        echo '<p class="hint">Filial saytdan yo\'qolmaydi — kartochkasida "Vaqtincha yopiq" yozuvi chiqadi. Istalgan payt qayta ochasiz.</p>';
        echo '<div class="actions"><button class="btn danger" type="submit">Vaqtincha yopish</button></div></form>';
    } else {
        echo '<section class="card part"><div class="part-head"><span class="part-ico warn">' . hs_icon('clock') . '</span><div><h2>Filial yopiq</h2><p class="muted">Saytda "Vaqtincha yopiq" deb ko\'rinib turibdi</p></div></div>';
        echo '<form method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . h($b['id']) . '"><input type="hidden" name="yopiq" value="0"><div class="actions"><button class="btn" type="submit">' . hs_icon('check') . ' Qayta ochish</button></div></form></section>';
    }
    echo '</div>';

    // 5. Manzil va xarita — kamdan-kam o'zgaradi, pastda.
    hs_part_start($b, 'manzil', 'Manzil va xarita', 'store', 'Mijoz filialni qanday topadi');
    echo '<div class="grid grid-2">';
    hs_branch_input('city', 'Shahar', $b['city'], '', 'required maxlength="60"');
    hs_branch_input('landmark', 'Mo\'ljal (kartochkada katta yoziladi)', $b['landmark'], 'Masalan: "Makro supermarketi, 2-qavat"', 'required maxlength="120"');
    echo '</div>';
    hs_branch_input('address', 'To\'liq manzil', $b['address'], '', 'required maxlength="200"');
    echo '<details class="more"><summary>Xaritadagi joy (koordinata)</summary><div class="grid grid-2">';
    hs_branch_input('lat', 'Kenglik', $b['lat'], '', 'required inputmode="decimal"');
    hs_branch_input('lng', 'Uzunlik', $b['lng'], '', 'required inputmode="decimal"');
    echo '</div>';
    hs_branch_input('mapQuery', 'Xaritada qidiruv matni', $b['mapQuery'], '', 'maxlength="200"');
    echo '<p class="hint">Qanday topiladi: Google Maps\'da filial joyini barmoq bilan bosib turing — tepada ikkita raqam chiqadi (masalan <span class="code">40.7137, 72.0566</span>). Birinchisi kenglik, ikkinchisi uzunlik. '
        . '<a href="https://www.google.com/maps?q=' . h($b['lat'] . ',' . $b['lng']) . '" target="_blank" rel="noopener noreferrer">Hozirgi joyni xaritada ochish</a></p>';
    echo '</details>';
    hs_part_end();

    echo '<form class="card danger-zone" method="post" action="/admin/filiallar.php" data-confirm="«' . h($b['city'] . ' — ' . $b['landmark']) . '» saytdan butunlay olib tashlanadi. Davom etasizmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . h($b['id']) . '">';
    echo '<div><h2>Filialni butunlay o\'chirish</h2><p class="muted">Filial yopilgan bo\'lsa. Sahifasi va kartochkasi saytdan olib tashlanadi, arizalar tarixi panelda qoladi.</p></div>';
    echo '<button class="btn danger" type="submit">O\'chirish</button></form>';
    hs_page_end();
    exit;
}

/* =================== YANGI FILIAL =================== */
if ($isNew) {
    echo '<p class="crumbs"><a href="/admin/filiallar.php">← Barcha filiallar</a></p>';
    echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
    echo '<h2>Yangi filial</h2><p class="muted">Asosiysini to\'ldiring — rasm va qolganini keyin filial sahifasida qo\'shasiz.</p>';
    echo '<div class="grid grid-2">';
    hs_branch_input('city', 'Shahar', '', '', 'required maxlength="60" placeholder="Namangan"');
    hs_branch_input('landmark', 'Mo\'ljal', '', 'Kartochkada katta yoziladi.', 'required maxlength="120" placeholder="Markaziy bozor yonida"');
    hs_branch_input('address', 'To\'liq manzil', '', '', 'required maxlength="200"');
    hs_branch_input('phone', 'Telefon', '', 'Bo\'sh bo\'lsa — umumiy raqam.', 'maxlength="20" inputmode="tel" placeholder="+998 55 203 08 80"', 'tel');
    hs_branch_input('hours', 'Ish vaqti', '9:00–18:00', '', 'required maxlength="20"');
    hs_branch_input('newid', 'Sahifa manzili', '', 'Saytda <span class="code">hamkorsavdo.uz/filiallar/<b>shu-nom</b>/</span> bo\'ladi. Kichik lotin harf va chiziqcha, keyin o\'zgarmaydi.', 'required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="40" placeholder="namangan-bozor"');
    hs_branch_input('lat', 'Xarita: kenglik', '', 'Google Maps\'da joyni bosib turing — birinchi raqam.', 'required inputmode="decimal" placeholder="40.7137"');
    hs_branch_input('lng', 'Xarita: uzunlik', '', 'Ikkinchi raqam.', 'required inputmode="decimal" placeholder="72.0566"');
    echo '</div><input type="hidden" name="mapQuery" value="">';
    echo '<div class="actions"><button class="btn" type="submit">Filialni qo\'shish</button><a class="btn outline" href="/admin/filiallar.php">Bekor qilish</a></div></form>';
    hs_page_end();
    exit;
}

/* =================== RO'YXAT =================== */
echo '<div class="row-between list-head"><p class="muted">' . count($c['branches']) . ' ta filial. Birini bosing — hammasini o\'sha yerda o\'zgartirasiz.</p><a class="btn" href="/admin/filiallar.php?yangi=1">+ Yangi filial</a></div>';
echo '<div class="branch-cards">';
foreach ($c['branches'] as $b) {
    $prefs = isset($c['photos'][$b['id']]) ? $c['photos'][$b['id']] : array();
    $cover = hs_branch_cover($b['id'], $prefs);
    $closed = !empty($b['closed']);
    $url = '/admin/filiallar.php?id=' . rawurlencode($b['id']);
    echo '<article class="branch-card' . ($closed ? ' is-closed' : '') . '">';
    echo '<a class="bc-img" href="' . h($url) . '" tabindex="-1" aria-hidden="true">' . ($cover ? '<img src="' . h($cover['thumb']) . '" alt="" loading="lazy" decoding="async">' : '<span>' . hs_icon('image') . '</span>')
        . ($closed ? '<em class="pill pill-err">Vaqtincha yopiq</em>' : '') . '</a>';
    echo '<div class="bc-body"><a class="bc-title" href="' . h($url) . '"><b>' . h($b['city']) . '</b><span>' . h($b['landmark']) . '</span></a>';
    echo '<p class="bc-meta">' . hs_icon('clock') . ' ' . h($b['hours']) . '</p>';

    // Telefonni ro'yxatning o'zidan tez o'zgartirish.
    echo '<form class="quick-phone" method="post" action="/admin/filiallar.php">' . hs_csrf_field()
        . '<input type="hidden" name="amal" value="qism"><input type="hidden" name="nima" value="telefon"><input type="hidden" name="qayt" value="royxat"><input type="hidden" name="id" value="' . h($b['id']) . '">'
        . '<label for="qp-' . h($b['id']) . '">' . hs_icon('phone') . ' Telefon</label><div class="qp-row"><input id="qp-' . h($b['id']) . '" type="tel" name="phone" inputmode="tel" maxlength="20" value="' . h($b['phoneDisplay'] ?: '') . '" placeholder="umumiy raqam" data-dirty-watch>'
        . '<button class="btn small" type="submit" data-dirty-show>Saqlash</button></div></form>';

    echo '<a class="btn outline small bc-open" href="' . h($url) . '">Boshqarish →</a>';
    echo '</div></article>';
}
echo '</div>';

echo '<details class="card"><summary class="summary-head">Saytdagi tartibni o\'zgartirish</summary>';
echo '<form method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="tartib"><p class="muted">Kichik raqam saytda birinchi chiqadi.</p><div class="grid grid-4">';
foreach ($c['branches'] as $i => $b) {
    echo '<div><label for="t-' . h($b['id']) . '">' . h($b['city'] . ', ' . $b['landmark']) . '</label><input id="t-' . h($b['id']) . '" type="number" min="1" max="99" name="tartib[' . h($b['id']) . ']" value="' . ($i + 1) . '"></div>';
}
echo '</div><div class="actions"><button class="btn outline" type="submit">Tartibni saqlash</button></div></form></details>';

hs_page_end();
