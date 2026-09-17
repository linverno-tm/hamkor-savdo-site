<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);

/** Yuklangan rasmning repodagi asl fayl yo'li (manifest orqali). */
function hs_uploaded_source($image)
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
        $id = $isNew ? 'a' . date('ymdHis') : $id;
        $extra = array();
        $image = hs_post('eski_rasm');
        $files = hs_files_list('rasm');
        if ($files) {
            $up = hs_read_upload($files[0]);
            if (!$up['ok']) {
                hs_flash($up['error'], 'err');
                hs_redirect('/admin/aksiyalar.php' . ($isNew ? '' : '?id=' . rawurlencode($id)));
            }
            $name = hs_new_image_name('aksiya');
            $extra[] = array('path' => "rasmlar/aksiyalar/{$name}.{$up['ext']}", 'content' => $up['bytes']);
            $old = hs_uploaded_source($image);
            if ($old) {
                $extra[] = array('path' => $old, 'content' => null);
            }
            $image = "aksiyalar/{$name}";
        }
        $promo = array(
            'id' => $id,
            'title' => mb_substr(hs_post('title'), 0, 120),
            'body' => mb_substr(hs_post('body'), 0, 1000),
            'image' => $image,
            'startsAt' => hs_post('startsAt'),
            'endsAt' => hs_post('endsAt'),
        );
        $problem = hs_content_publish($user, ($isNew ? 'yangi aksiya: ' : 'aksiya tahrirlandi: ') . $promo['title'], function (&$c) use ($promo, $isNew) {
            if (trim($promo['title']) === '') {
                return 'Aksiya sarlavhasi bo\'sh bo\'lmasin.';
            }
            if ($isNew) {
                array_unshift($c['promotions'], $promo);
                return null;
            }
            foreach ($c['promotions'] as $i => $p) {
                if ($p['id'] === $promo['id']) {
                    $c['promotions'][$i] = $promo;
                    return null;
                }
            }
            return 'Aksiya topilmadi.';
        }, $extra);
    } elseif ($action === 'ochirish') {
        $old = hs_uploaded_source(hs_post('rasm'));
        $extra = $old ? array(array('path' => $old, 'content' => null)) : array();
        $problem = hs_content_publish($user, "aksiya o'chirildi: {$id}", function (&$c) use ($id) {
            foreach ($c['promotions'] as $i => $p) {
                if ($p['id'] === $id) {
                    array_splice($c['promotions'], $i, 1);
                    return null;
                }
            }
            return 'Aksiya topilmadi.';
        }, $extra);
    }
    hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    hs_redirect('/admin/aksiyalar.php');
}

$err = null;
$c = hs_content_load($err);
hs_page_start('Aksiyalar', $user);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}

$editId = hs_get('id');
$edit = array('id' => '', 'title' => '', 'body' => '', 'image' => '', 'startsAt' => '', 'endsAt' => '');
foreach ($c['promotions'] as $p) {
    if ($p['id'] === $editId) {
        $edit = $p;
    }
}

$today = date('Y-m-d');
echo '<section class="card"><h2>Barcha aksiyalar</h2>';
if (!$c['promotions']) {
    echo '<p class="muted">Aksiya yo\'q — saytda bu bo\'lim ko\'rinmaydi.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Sarlavha</th><th>Muddat</th><th>Holat</th><th></th></tr></thead><tbody>';
    foreach ($c['promotions'] as $p) {
        if ($p['endsAt'] !== '' && $p['endsAt'] < $today) {
            $state = '<span class="pill st-rad">Tugagan</span>';
        } elseif ($p['startsAt'] !== '' && $p['startsAt'] > $today) {
            $state = '<span class="pill st-qongiroq">Boshlanmagan</span>';
        } else {
            $state = '<span class="pill pill-ok">Saytda</span>';
        }
        echo '<tr><td>' . h($p['title']) . '</td><td class="nowrap">' . h(($p['startsAt'] ?: '…') . ' — ' . ($p['endsAt'] ?: '…')) . '</td><td>' . $state . '</td>';
        echo '<td class="nowrap"><a class="btn outline small" href="/admin/aksiyalar.php?id=' . h(rawurlencode($p['id'])) . '">Tahrirlash</a> ';
        echo '<form class="inline-form" method="post" action="/admin/aksiyalar.php" data-confirm="Aksiya o\'chirilsinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . h($p['id']) . '"><input type="hidden" name="rasm" value="' . h($p['image']) . '"><button class="btn danger small" type="submit">O\'chirish</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

echo '<form class="card" method="post" action="/admin/aksiyalar.php" enctype="multipart/form-data">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="id" value="' . h($edit['id']) . '"><input type="hidden" name="eski_rasm" value="' . h($edit['image']) . '">';
echo '<h2>' . ($edit['id'] === '' ? 'Yangi aksiya' : 'Aksiyani tahrirlash') . '</h2>';
echo '<label for="title">Sarlavha</label><input id="title" type="text" name="title" required maxlength="120" value="' . h($edit['title']) . '">';
echo '<label for="body">Matn</label><textarea id="body" name="body" maxlength="1000">' . h($edit['body']) . '</textarea>';
echo '<div class="grid grid-2"><div><label for="startsAt">Boshlanish sanasi</label><input id="startsAt" type="date" name="startsAt" value="' . h($edit['startsAt']) . '"></div>';
echo '<div><label for="endsAt">Tugash sanasi</label><input id="endsAt" type="date" name="endsAt" value="' . h($edit['endsAt']) . '"><p class="hint">Shu kun oxirida saytdan o\'zi yo\'qoladi. Bo\'sh = muddatsiz.</p></div></div>';
if ($edit['image'] !== '') {
    echo '<p><img class="thumb" src="/rasm/' . h($edit['image']) . '-480.webp" alt="" width="240"></p>';
}
echo '<label for="rasm">Rasm (JPG, PNG, WEBP — ixtiyoriy)</label><input id="rasm" type="file" name="rasm" accept="image/jpeg,image/png,image/webp">';
echo '<div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button>';
if ($edit['id'] !== '') {
    echo '<a class="btn outline" href="/admin/aksiyalar.php">Yangi aksiya</a>';
}
echo '</div></form>';

hs_page_end();
