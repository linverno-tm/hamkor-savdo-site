<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);
$KINDS = array('tashqi' => "Do'kon tashqarisi", 'zal' => 'Savdo zali', 'jamoa' => 'Jamoa', 'mijoz' => 'Mijozlar bilan');

/**
 * Saytda HOZIR turgan suratlar (build qilingan papkadan).
 * @return array<int, array{base:string, kind:string, thumb:string, paths:array}>
 */
function hs_branch_photo_files($slug)
{
    $root = realpath(__DIR__ . '/..');
    $manifest = array();
    if (is_file($root . '/rasm/manifest.json')) {
        $manifest = json_decode(file_get_contents($root . '/rasm/manifest.json'), true) ?: array();
    }
    $out = array();
    foreach (glob($root . '/filiallar/' . $slug . '/*-960.webp') ?: array() as $f) {
        $base = basename($f, '-960.webp');
        $out[$base] = array(
            'base' => $base,
            'kind' => explode('-', $base)[0],
            'thumb' => "/filiallar/{$slug}/{$base}-480.webp",
            'paths' => array("public/filiallar/{$slug}/{$base}-480.webp", "public/filiallar/{$slug}/{$base}-960.webp"),
        );
    }
    foreach (glob($root . '/rasm/filiallar/' . $slug . '/*-960.webp') ?: array() as $f) {
        $base = basename($f, '-960.webp');
        $key = "filiallar/{$slug}/{$base}";
        $out[$base] = array(
            'base' => $base,
            'kind' => explode('-', $base)[0],
            'thumb' => "/rasm/filiallar/{$slug}/{$base}-480.webp",
            'paths' => isset($manifest[$key]) ? array($manifest[$key]) : array(),
        );
    }
    return array_values($out);
}

$err = null;
$c = hs_content_load($err);
$slug = hs_get('filial') !== '' ? hs_get('filial') : hs_post('filial');
$branch = null;
if ($c) {
    foreach ($c['branches'] as $b) {
        if ($b['id'] === $slug) {
            $branch = $b;
        }
    }
    if (!$branch) {
        $branch = $c['branches'][0];
        $slug = $branch['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $branch) {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $problem = "Noma'lum amal.";

    if ($action === 'yuklash') {
        $kind = hs_post('tur');
        $files = hs_files_list('suratlar');
        if (!isset($KINDS[$kind])) {
            $problem = 'Surat turini tanlang.';
        } elseif (!$files) {
            $problem = 'Surat tanlanmadi.';
        } elseif (count($files) > 10) {
            $problem = 'Bir martada 10 tadan ko\'p surat yuklamang.';
        } else {
            $changes = array();
            $problem = null;
            foreach ($files as $i => $file) {
                $up = hs_read_upload($file);
                if (!$up['ok']) {
                    $problem = $file['name'] . ': ' . $up['error'];
                    break;
                }
                $name = hs_new_image_name($kind . '-u') . $i;
                $changes[] = array('path' => "rasmlar/filiallar/{$slug}/{$name}.{$up['ext']}", 'content' => $up['bytes']);
            }
            if ($problem === null) {
                $problem = hs_files_publish($user, count($changes) . " ta surat yuklandi: {$branch['city']}, {$branch['landmark']}", $changes);
            }
        }
    } elseif ($action === 'saqlash') {
        $files = hs_branch_photo_files($slug);
        $delete = isset($_POST['ochir']) && is_array($_POST['ochir']) ? $_POST['ochir'] : array();
        $orders = isset($_POST['tartib']) && is_array($_POST['tartib']) ? $_POST['tartib'] : array();
        $labels = isset($_POST['yorliq']) && is_array($_POST['yorliq']) ? $_POST['yorliq'] : array();
        $alts = isset($_POST['alt']) && is_array($_POST['alt']) ? $_POST['alt'] : array();
        $cover = hs_post('muqova');

        $extra = array();
        $keep = array();
        foreach ($files as $f) {
            if (isset($delete[$f['base']])) {
                foreach ($f['paths'] as $p) {
                    $extra[] = array('path' => $p, 'content' => null);
                }
            } else {
                $keep[] = $f['base'];
            }
        }
        usort($keep, function ($a, $b) use ($orders) {
            $x = isset($orders[$a]) ? (int) $orders[$a] : 999;
            $y = isset($orders[$b]) ? (int) $orders[$b] : 999;
            return $x - $y;
        });
        $prefs = array('cover' => in_array($cover, $keep, true) ? $cover : '', 'order' => $keep, 'labels' => array(), 'alts' => array());
        foreach ($keep as $base) {
            if (!empty($labels[$base]) && trim($labels[$base]) !== '') {
                $prefs['labels'][$base] = mb_substr(trim($labels[$base]), 0, 60);
            }
            if (!empty($alts[$base]) && trim($alts[$base]) !== '') {
                $prefs['alts'][$base] = mb_substr(trim($alts[$base]), 0, 200);
            }
        }
        if ($prefs['cover'] === '') {
            unset($prefs['cover']);
        }
        $problem = hs_content_publish($user, "suratlar: {$branch['city']}, {$branch['landmark']}" . ($extra ? ' (' . count($extra) . " fayl o'chirildi)" : ''), function (&$c) use ($slug, $prefs) {
            $c['photos'][$slug] = $prefs;
            return null;
        }, $extra);
    }
    hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    hs_redirect('/admin/suratlar.php?filial=' . rawurlencode($slug));
}

hs_page_start('Suratlar', $user);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}

echo '<div class="actions">';
foreach ($c['branches'] as $b) {
    $label = $b['city'] . ', ' . $b['landmark'];
    echo $b['id'] === $slug ? '<span class="btn small">' . h($label) . '</span>' : '<a class="btn outline small" href="/admin/suratlar.php?filial=' . h(rawurlencode($b['id'])) . '">' . h($label) . '</a>';
}
echo '</div><br>';

$prefs = isset($c['photos'][$slug]) ? $c['photos'][$slug] : array();
$files = hs_branch_photo_files($slug);
$order = isset($prefs['order']) ? $prefs['order'] : array();
usort($files, function ($a, $b) use ($order) {
    $x = array_search($a['base'], $order, true);
    $y = array_search($b['base'], $order, true);
    $x = $x === false ? 999 : $x;
    $y = $y === false ? 999 : $y;
    return $x - $y ?: strnatcmp($a['base'], $b['base']);
});

echo '<form class="card" method="post" action="/admin/suratlar.php" enctype="multipart/form-data">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="yuklash"><input type="hidden" name="filial" value="' . h($slug) . '">';
echo '<h2>Yangi surat yuklash</h2><div class="grid grid-2"><div><label for="tur">Surat turi</label><select id="tur" name="tur">';
foreach ($KINDS as $k => $v) {
    echo '<option value="' . $k . '">' . h($v) . '</option>';
}
echo '</select></div><div><label for="suratlar">Suratlar (10 tagacha)</label><input id="suratlar" type="file" name="suratlar[]" accept="image/jpeg,image/png,image/webp" multiple required></div></div>';
echo '<p class="hint">Sayt uchun avtomatik 4:3 o\'lchamga kesiladi va siqiladi. Telefonda "Rasm" dan tanlang.</p>';
echo '<div class="actions"><button class="btn" type="submit">Yuklash</button></div></form>';

echo '<form class="card" method="post" action="/admin/suratlar.php">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="filial" value="' . h($slug) . '">';
echo '<h2>Saytdagi suratlar (' . count($files) . ')</h2>';
if (!$files) {
    echo '<p class="muted">Bu filialda hali surat yo\'q. Yangi yuklangan suratlar nashrdan keyin (2–3 daqiqa) shu yerda paydo bo\'ladi.</p>';
} else {
    echo '<p class="muted">Tartib raqami kichigi birinchi chiqadi. Muqova — bosh sahifadagi filial kartochkasida.</p><div class="photos">';
    foreach ($files as $i => $f) {
        $b = h($f['base']);
        $isCover = isset($prefs['cover']) ? $prefs['cover'] === $f['base'] : false;
        echo '<div class="photo-item"><img src="' . h($f['thumb']) . '" alt="" loading="lazy"><div class="body">';
        echo '<small>' . h(isset($KINDS[$f['kind']]) ? $KINDS[$f['kind']] : $f['kind']) . ' · ' . $b . '</small>';
        echo '<label>Tartib</label><input type="number" name="tartib[' . $b . ']" min="1" max="999" value="' . ($i + 1) . '">';
        echo '<label>Rasm ostidagi yozuv</label><input type="text" name="yorliq[' . $b . ']" maxlength="60" value="' . h(isset($prefs['labels'][$f['base']]) ? $prefs['labels'][$f['base']] : '') . '" placeholder="avtomatik">';
        echo '<label>Tavsif (ko\'zi ojizlar va Google uchun)</label><input type="text" name="alt[' . $b . ']" maxlength="200" value="' . h(isset($prefs['alts'][$f['base']]) ? $prefs['alts'][$f['base']] : '') . '" placeholder="avtomatik">';
        echo '<label class="inline"><input type="radio" name="muqova" value="' . $b . '"' . ($isCover ? ' checked' : '') . '> Muqova</label>';
        echo '<label class="inline"><input type="checkbox" name="ochir[' . $b . ']" value="1"> O\'chirish</label>';
        echo '</div></div>';
    }
    echo '</div><div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button></div>';
}
echo '</form>';

hs_page_end();
