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
    echo '<label class="inline"><input type="checkbox" name="closed" value="1"' . (!empty($b['closed']) ? ' checked' : '') . '> Vaqtincha yopiq</label>';
    echo '<label for="' . $prefix . 'closedNote">Yopiqlik izohi (masalan: "25-sentabrgacha ta\'mirlanmoqda")</label><input id="' . $prefix . 'closedNote" type="text" name="closedNote" maxlength="200" value="' . h($v('closedNote')) . '">';
}

echo '<p class="muted">Har bir filial alohida saqlanadi. O\'zgarish saytda 2–3 daqiqada ko\'rinadi.</p>';

echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="tartib"><h2>Saytdagi tartib</h2><div class="grid grid-4">';
foreach ($c['branches'] as $i => $b) {
    echo '<div><label for="t-' . h($b['id']) . '">' . h($b['city'] . ', ' . $b['landmark']) . '</label><input id="t-' . h($b['id']) . '" type="number" min="1" max="99" name="tartib[' . h($b['id']) . ']" value="' . ($i + 1) . '"></div>';
}
echo '</div><div class="actions"><button class="btn outline" type="submit">Tartibni saqlash</button></div></form>';

foreach ($c['branches'] as $b) {
    echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="id" value="' . h($b['id']) . '">';
    echo '<h2>' . h($b['city'] . ' — ' . $b['landmark']) . ' <small class="code">/filiallar/' . h($b['id']) . '/</small></h2>';
    hs_branch_fields($b, h($b['id']) . '-');
    echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';
    echo '<form method="post" action="/admin/filiallar.php" data-confirm="Filial saytdan butunlay olib tashlanadi. Davom etasizmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . h($b['id']) . '">';
    echo '<button class="btn danger small" type="submit">' . h($b['city'] . ', ' . $b['landmark']) . ' — filialni o\'chirish</button></form><br>';
}

echo '<form class="card" method="post" action="/admin/filiallar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
echo '<h2>Yangi filial qo\'shish</h2>';
echo '<label for="newid">Sahifa manzili</label><input id="newid" type="text" name="newid" required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="40" placeholder="masalan: namangan-markaz"><p class="hint">Faqat kichik lotin harf, raqam va chiziqcha. Keyin o\'zgartirib bo\'lmaydi.</p>';
hs_branch_fields(array('closed' => false), 'new-');
echo '<div class="actions"><button class="btn" type="submit">Filialni qo\'shish</button></div></form>';

hs_page_end();
