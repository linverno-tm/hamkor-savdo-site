<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);

/** Formadagi filial maydonlari -> content.json yozuvi. */
function hs_branch_from_post($id)
{
    $phone = preg_replace('/[^\d+]/', '', hs_post('phone'));
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 9) {
        $phone = '+998' . $digits;
    } elseif (strlen($digits) === 12) {
        $phone = '+' . $digits;
    }
    $display = $phone !== '' ? preg_replace('/^\+998(\d{2})(\d{3})(\d{2})(\d{2})$/', '+998 $1 $2 $3 $4', $phone) : null;
    $hours = str_replace(array('-', '—'), '–', preg_replace('/\s+/', '', hs_post('hours')));
    $insta = ltrim(hs_post('instagram'), '@');
    return array(
        'id' => $id,
        'city' => mb_substr(hs_post('city'), 0, 60),
        'address' => mb_substr(hs_post('address'), 0, 200),
        'landmark' => mb_substr(hs_post('landmark'), 0, 120),
        'phone' => $phone !== '' ? $phone : null,
        'phoneDisplay' => $display,
        'instagram' => $insta !== '' ? mb_substr($insta, 0, 40) : null,
        'hours' => $hours,
        'mapQuery' => mb_substr(hs_post('mapQuery'), 0, 200),
        'lat' => (float) str_replace(',', '.', hs_post('lat')),
        'lng' => (float) str_replace(',', '.', hs_post('lng')),
        'closed' => hs_post('closed') === '1',
        'closedNote' => mb_substr(hs_post('closedNote'), 0, 200),
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $id = hs_post('id');
    $problem = 'Noma\'lum amal.';

    if ($action === 'saqlash') {
        $new = hs_branch_from_post($id);
        $problem = hs_content_publish($user, 'filial tahrirlandi: ' . $new['city'] . ', ' . $new['landmark'], function (&$c) use ($id, $new) {
            foreach ($c['branches'] as $i => $b) {
                if ($b['id'] === $id) {
                    $c['branches'][$i] = $new;
                    return null;
                }
            }
            return 'Filial topilmadi (boshqa kishi o\'chirgan bo\'lishi mumkin).';
        });
    } elseif ($action === 'qoshish') {
        $newId = strtolower(trim(hs_post('newid')));
        $new = hs_branch_from_post($newId);
        $problem = hs_content_publish($user, "yangi filial: {$new['city']}, {$new['landmark']}", function (&$c) use ($new) {
            $c['branches'][] = $new;
            return null;
        });
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
    }

    if ($problem === null) {
        hs_flash(hs_publish_note());
    } else {
        hs_flash($problem, 'err');
    }
    hs_redirect('/admin/filiallar.php');
}

$err = null;
$c = hs_content_load($err);
hs_page_start('Filiallar', $user);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}

function hs_branch_fields($b, $prefix)
{
    $v = function ($k) use ($b) {
        return isset($b[$k]) && $b[$k] !== null ? $b[$k] : '';
    };
    echo '<div class="grid grid-2">';
    echo '<div><label for="' . $prefix . 'city">Shahar</label><input id="' . $prefix . 'city" type="text" name="city" required maxlength="60" value="' . h($v('city')) . '"></div>';
    echo '<div><label for="' . $prefix . 'landmark">Mo\'ljal (kartochka sarlavhasi)</label><input id="' . $prefix . 'landmark" type="text" name="landmark" required maxlength="120" value="' . h($v('landmark')) . '"></div>';
    echo '<div><label for="' . $prefix . 'address">To\'liq manzil</label><input id="' . $prefix . 'address" type="text" name="address" required maxlength="200" value="' . h($v('address')) . '"></div>';
    echo '<div><label for="' . $prefix . 'phone">Telefon</label><input id="' . $prefix . 'phone" type="tel" name="phone" maxlength="20" placeholder="+998 33 342 08 80" value="' . h($v('phone')) . '"><p class="hint">Bo\'sh qoldirilsa, umumiy raqam ko\'rsatiladi.</p></div>';
    echo '<div><label for="' . $prefix . 'hours">Ish vaqti</label><input id="' . $prefix . 'hours" type="text" name="hours" required maxlength="20" placeholder="9:00–18:00" value="' . h($v('hours')) . '"></div>';
    echo '<div><label for="' . $prefix . 'instagram">Filialning o\'z Instagram sahifasi</label><input id="' . $prefix . 'instagram" type="text" name="instagram" maxlength="40" placeholder="bo\'lmasa bo\'sh" value="' . h($v('instagram')) . '"></div>';
    echo '<div><label for="' . $prefix . 'lat">Xarita: kenglik (lat)</label><input id="' . $prefix . 'lat" type="text" name="lat" required inputmode="decimal" value="' . h($v('lat')) . '"></div>';
    echo '<div><label for="' . $prefix . 'lng">Xarita: uzunlik (lng)</label><input id="' . $prefix . 'lng" type="text" name="lng" required inputmode="decimal" value="' . h($v('lng')) . '"><p class="hint">Google Maps\'da joyni bosib turing — koordinata chiqadi.</p></div>';
    echo '</div>';
    echo '<label for="' . $prefix . 'mapQuery">Xarita qidiruv matni</label><input id="' . $prefix . 'mapQuery" type="text" name="mapQuery" maxlength="200" value="' . h($v('mapQuery')) . '">';
    echo '<label class="inline"><input type="checkbox" name="closed" value="1"' . (!empty($b['closed']) ? ' checked' : '') . '> Filial vaqtincha yopiq</label>';
    echo '<label for="' . $prefix . 'closedNote">Yopiqlik izohi (masalan: "25-sentabrgacha ta\'mirlanmoqda")</label><input id="' . $prefix . 'closedNote" type="text" name="closedNote" maxlength="200" value="' . h($v('closedNote')) . '">';
}

$editId = hs_get('id');
$isNew = hs_get('yangi') === '1';
$edit = null;
foreach ($c['branches'] as $b) {
    if ($b['id'] === $editId) {
        $edit = $b;
    }
}

if ($edit) {
    echo '<p><a href="/admin/filiallar.php">← Barcha filiallar</a></p>';
    echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="id" value="' . h($edit['id']) . '">';
    echo '<div class="card-head"><h2>' . h($edit['city'] . ' — ' . $edit['landmark']) . '</h2><a class="btn outline small" href="' . h(hs_site_url()) . '/filiallar/' . h($edit['id']) . '/" target="_blank" rel="noopener noreferrer">' . hs_icon('external') . ' Saytda ko\'rish</a></div>';
    hs_branch_fields($edit, 'e-');
    echo '<div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button><a class="btn outline" href="/admin/filiallar.php">Bekor qilish</a></div></form>';
    echo '<form class="card" method="post" action="/admin/filiallar.php" data-confirm="Filial saytdan butunlay olib tashlanadi. Davom etasizmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . h($edit['id']) . '">';
    echo '<h2>Filialni o\'chirish</h2><p class="muted">Filial sahifasi va bosh sahifadagi kartochkasi saytdan olib tashlanadi. Arizalar tarixi panelda qoladi.</p>';
    echo '<button class="btn danger" type="submit">Filialni o\'chirish</button></form>';
    hs_page_end();
    exit;
}

if ($isNew) {
    echo '<p><a href="/admin/filiallar.php">← Barcha filiallar</a></p>';
    echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
    echo '<h2>Yangi filial</h2>';
    echo '<label for="newid">Sahifa manzili</label><input id="newid" type="text" name="newid" required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="40" placeholder="masalan: namangan-markaz"><p class="hint">Saytda <span class="code">hamkorsavdo.uz/filiallar/<b>shu-nom</b>/</span> bo\'ladi. Faqat kichik lotin harf, raqam va chiziqcha; keyin o\'zgartirib bo\'lmaydi.</p>';
    hs_branch_fields(array('closed' => false), 'n-');
    echo '<div class="actions"><button class="btn" type="submit">Filialni qo\'shish</button><a class="btn outline" href="/admin/filiallar.php">Bekor qilish</a></div></form>';
    hs_page_end();
    exit;
}

echo '<div class="card"><div class="card-head"><h2>' . count($c['branches']) . ' ta filial</h2><a class="btn small" href="/admin/filiallar.php?yangi=1">+ Yangi filial</a></div>';
echo '<div class="branch-list">';
foreach ($c['branches'] as $b) {
    $phone = $b['phoneDisplay'] ?: 'umumiy raqam';
    echo '<div class="branch-row">';
    echo '<div class="branch-main"><b>' . h($b['city']) . '</b> <span class="muted">— ' . h($b['landmark']) . '</span>';
    echo '<small>' . hs_icon('phone') . ' ' . h($phone) . ' &nbsp; ' . hs_icon('clock') . ' ' . h($b['hours']) . '</small>';
    if (!empty($b['closed']) && $b['closedNote'] !== '') {
        echo '<small class="closed-note">' . h($b['closedNote']) . '</small>';
    }
    echo '</div>';
    echo '<div>' . (!empty($b['closed']) ? '<span class="pill pill-err">Vaqtincha yopiq</span>' : '<span class="pill pill-ok">Ishlayapti</span>') . '</div>';
    echo '<div class="branch-actions">';
    // "Tahrirlash" birinchi: telefonda popover ochilganda tugmalar joyidan sakramaydi.
    echo '<a class="btn outline small" href="/admin/filiallar.php?id=' . h(rawurlencode($b['id'])) . '">Tahrirlash</a>';
    if (empty($b['closed'])) {
        echo '<details class="inline-details"><summary class="btn outline small">Vaqtincha yopish</summary><form method="post" action="/admin/filiallar.php" class="popover">' . hs_csrf_field()
            . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . h($b['id']) . '"><input type="hidden" name="yopiq" value="1">'
            . '<label for="yopiq-' . h($b['id']) . '">Saytda ko\'rinadigan izoh (ixtiyoriy)</label><input id="yopiq-' . h($b['id']) . '" type="text" name="closedNote" maxlength="200" placeholder="masalan: 25-sentabrgacha ta\'mirlanmoqda">'
            . '<div class="actions"><button class="btn danger small" type="submit">Yopish</button></div></form></details>';
    } else {
        echo '<form class="inline-form" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . h($b['id']) . '"><input type="hidden" name="yopiq" value="0"><button class="btn small" type="submit">Qayta ochish</button></form>';
    }
    echo '</div></div>';
}
echo '</div></div>';

echo '<details class="card"><summary class="summary-head">Saytdagi tartibni o\'zgartirish</summary>';
echo '<form method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="tartib"><div class="grid grid-4">';
foreach ($c['branches'] as $i => $b) {
    echo '<div><label for="t-' . h($b['id']) . '">' . h($b['city'] . ', ' . $b['landmark']) . '</label><input id="t-' . h($b['id']) . '" type="number" min="1" max="99" name="tartib[' . h($b['id']) . ']" value="' . ($i + 1) . '"></div>';
}
echo '</div><div class="actions"><button class="btn outline" type="submit">Tartibni saqlash</button></div></form></details>';

hs_page_end();
