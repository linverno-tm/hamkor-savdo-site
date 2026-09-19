<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);
$CATS = array('texnika' => 'Texnika', 'tilla' => 'Tilla', 'mebel' => 'Mebel');

function hs_catalog_source($image)
{
    $manifest = __DIR__ . '/../rasm/manifest.json';
    if ($image === '' || !is_file($manifest)) {
        return null;
    }
    $m = json_decode(file_get_contents($manifest), true);
    return isset($m[$image]) ? $m[$image] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $id = hs_post('id');
    $problem = "Noma'lum amal.";

    if ($action === 'saqlash') {
        $isNew = $id === '';
        $id = $isNew ? 'm' . date('ymdHis') : $id;
        $extra = array();
        $image = hs_post('eski_rasm');
        $files = hs_files_list('rasm');
        if ($files) {
            $up = hs_read_upload($files[0]);
            if (!$up['ok']) {
                hs_flash($up['error'], 'err');
                hs_redirect('/admin/katalog.php' . ($isNew ? '' : '?id=' . rawurlencode($id)) . '#forma');
            }
            $name = hs_new_image_name('mahsulot');
            $extra[] = array('path' => "rasmlar/katalog/{$name}.{$up['ext']}", 'content' => $up['bytes']);
            $old = hs_catalog_source($image);
            if ($old) {
                $extra[] = array('path' => $old, 'content' => null);
            }
            $image = "katalog/{$name}";
        }
        $priceRaw = preg_replace('/\D/', '', hs_post('price'));
        $product = array(
            'id' => $id,
            'name' => mb_substr(hs_post('name'), 0, 120),
            'category' => hs_post('category'),
            'price' => $priceRaw === '' ? null : (int) $priceRaw,
            'image' => $image,
            'inStock' => hs_post('inStock') === '1',
            'note' => mb_substr(hs_post('note'), 0, 300),
        );
        $problem = hs_content_publish($user, ($isNew ? 'yangi mahsulot: ' : 'mahsulot tahrirlandi: ') . $product['name'], function (&$c) use ($product, $isNew) {
            if (trim($product['name']) === '') {
                return 'Mahsulot nomi bo\'sh bo\'lmasin.';
            }
            if ($isNew) {
                array_unshift($c['products'], $product);
                return null;
            }
            foreach ($c['products'] as $i => $p) {
                if ($p['id'] === $product['id']) {
                    $c['products'][$i] = $product;
                    return null;
                }
            }
            return 'Mahsulot topilmadi.';
        }, $extra);
    } elseif ($action === 'ochirish') {
        $old = hs_catalog_source(hs_post('rasm'));
        $extra = $old ? array(array('path' => $old, 'content' => null)) : array();
        $problem = hs_content_publish($user, "mahsulot o'chirildi: {$id}", function (&$c) use ($id) {
            foreach ($c['products'] as $i => $p) {
                if ($p['id'] === $id) {
                    array_splice($c['products'], $i, 1);
                    return null;
                }
            }
            return 'Mahsulot topilmadi.';
        }, $extra);
    }
    hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    hs_redirect('/admin/katalog.php');
}

$err = null;
$c = hs_content_load($err);
hs_page_start('Katalog', $user);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}

$editId = hs_get('id');
$edit = array('id' => '', 'name' => '', 'category' => 'texnika', 'price' => null, 'image' => '', 'inStock' => true, 'note' => '');
foreach ($c['products'] as $p) {
    if ($p['id'] === $editId) {
        $edit = $p;
    }
}

echo '<section class="card"><div class="card-head"><h2>Mahsulotlar (' . count($c['products']) . ')</h2><a class="btn small" href="/admin/katalog.php#forma">+ Yangi mahsulot</a></div>';
if (!$c['products']) {
    echo '<p class="muted">Mahsulot kiritilmagan — saytda katalog bo\'limi ko\'rinmaydi.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th></th><th>Nomi</th><th>Bo\'lim</th><th class="right">Narx</th><th>Mavjud</th><th></th></tr></thead><tbody>';
    foreach ($c['products'] as $p) {
        $thumb = $p['image'] !== '' ? '<img class="list-thumb" src="/rasm/' . h($p['image']) . '-480.webp" alt="" loading="lazy">' : '<span class="list-thumb"></span>';
        echo '<tr><td>' . $thumb . '</td><td>' . h($p['name']) . '</td><td>' . h(isset($CATS[$p['category']]) ? $CATS[$p['category']] : $p['category']) . '</td>';
        echo '<td class="right nowrap">' . ($p['price'] === null ? '—' : h(number_format($p['price'], 0, '', ' ')) . ' so\'m') . '</td>';
        echo '<td>' . ($p['inStock'] ? '<span class="pill pill-ok">ha</span>' : '<span class="pill st-rad">buyurtma</span>') . '</td>';
        echo '<td class="nowrap"><a class="btn outline small" href="/admin/katalog.php?id=' . h(rawurlencode($p['id'])) . '">Tahrirlash</a> ';
        echo '<form class="inline-form" method="post" action="/admin/katalog.php" data-confirm="Mahsulot o\'chirilsinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . h($p['id']) . '"><input type="hidden" name="rasm" value="' . h($p['image']) . '"><button class="btn danger small" type="submit">O\'chirish</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

echo '<form id="forma" class="card" method="post" action="/admin/katalog.php" enctype="multipart/form-data">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="id" value="' . h($edit['id']) . '"><input type="hidden" name="eski_rasm" value="' . h($edit['image']) . '">';
echo '<h2>' . ($edit['id'] === '' ? 'Yangi mahsulot' : 'Mahsulotni tahrirlash') . '</h2><div class="grid grid-2">';
echo '<div><label for="name">Nomi</label><input id="name" type="text" name="name" required maxlength="120" value="' . h($edit['name']) . '"></div>';
echo '<div><label for="category">Bo\'lim</label><select id="category" name="category">';
foreach ($CATS as $k => $v) {
    echo '<option value="' . $k . '"' . ($edit['category'] === $k ? ' selected' : '') . '>' . $v . '</option>';
}
echo '</select></div>';
echo '<div><label for="price">Narx (so\'m)</label><input id="price" type="text" inputmode="numeric" name="price" maxlength="15" value="' . h($edit['price'] === null ? '' : $edit['price']) . '"><p class="hint">Bo\'sh = "Narxini so\'rang".</p></div>';
echo '<div><label class="inline"><input type="checkbox" name="inStock" value="1"' . ($edit['inStock'] ? ' checked' : '') . '> Do\'konda mavjud</label></div>';
echo '</div><label for="note">Qisqa izoh</label><input id="note" type="text" name="note" maxlength="300" value="' . h($edit['note']) . '">';
if ($edit['image'] !== '') {
    echo '<p><img class="thumb" src="/rasm/' . h($edit['image']) . '-480.webp" alt="" width="240"></p>';
}
echo '<label for="rasm">Rasm</label><input id="rasm" type="file" name="rasm" accept="image/jpeg,image/png,image/webp">';
echo '<div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button>';
if ($edit['id'] !== '') {
    echo '<a class="btn outline" href="/admin/katalog.php">Yangi mahsulot</a>';
}
echo '</div></form>';

hs_page_end();
