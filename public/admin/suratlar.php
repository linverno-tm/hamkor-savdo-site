<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';
require_once __DIR__ . '/_lib/photos.php';

$user = hs_require_login(true);
$KINDS = hs_photo_kinds();

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
    if (!$branch && $c['branches']) {
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
            $problem = 'Avval rasm nima haqida ekanini tanlang (1-qadam).';
        } elseif (!$files) {
            $problem = 'Rasm tanlanmadi (2-qadam).';
        } elseif (count($files) > 10) {
            $problem = 'Bir martada 10 tadan ko\'p rasm yuklab bo\'lmaydi.';
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
        if ($problem === null) {
            hs_flash(count($files) . " ta rasm yuborildi. Saytda va shu ro'yxatda 2–3 daqiqadan keyin chiqadi.");
            hs_redirect('/admin/suratlar.php?filial=' . rawurlencode($slug));
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

hs_page_start('Suratlar', $user, $branch ? 'Qaysi filial rasmlari saytda qanday chiqishini shu yerda o\'zgartirasiz' : null);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}
if (!$branch) {
    echo '<div class="card"><p class="muted">Hali filial yo\'q. Avval <a href="/admin/filiallar.php?yangi=1">filial qo\'shing</a>.</p></div>';
    hs_page_end();
    exit;
}

echo '<div class="seg" role="tablist" aria-label="Filial">';
foreach ($c['branches'] as $b) {
    $label = $b['city'] . ' · ' . $b['landmark'];
    echo $b['id'] === $slug ? '<span role="tab" aria-selected="true">' . h($label) . '</span>' : '<a role="tab" href="/admin/suratlar.php?filial=' . h(rawurlencode($b['id'])) . '">' . h($label) . '</a>';
}
echo '</div>';

$prefs = isset($c['photos'][$slug]) ? $c['photos'][$slug] : array();
$files = hs_branch_photos_sorted($slug, $prefs);
$coverFile = hs_branch_cover($slug, $prefs);

/* ---------- yangi rasm qo'shish: uch oddiy qadam ---------- */
echo '<form class="card upload" method="post" action="/admin/suratlar.php" enctype="multipart/form-data">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="yuklash"><input type="hidden" name="filial" value="' . h($slug) . '">';
echo '<div class="part-head"><span class="part-ico">' . hs_icon('upload') . '</span><div><h2>Yangi rasm qo\'shish</h2><p class="muted">Telefondagi rasmni to\'g\'ridan-to\'g\'ri tanlasangiz bo\'ladi — o\'lchamini sayt o\'zi to\'g\'rilaydi.</p></div></div>';

echo '<fieldset class="step"><legend><span class="step-no">1</span> Rasmda nima bor?</legend><div class="kind-choices">';
$first = true;
foreach ($KINDS as $k => $v) {
    echo '<label class="kind-choice"><input type="radio" name="tur" value="' . h($k) . '"' . ($first ? ' checked' : '') . '><span><b>' . h($v[0]) . '</b><small>' . h($v[1]) . '</small></span></label>';
    $first = false;
}
echo '</div></fieldset>';

echo '<fieldset class="step"><legend><span class="step-no">2</span> Rasmlarni tanlang</legend>';
echo '<label class="dropzone" for="suratlar">' . hs_icon('image') . '<b>Rasm tanlash uchun bosing</b><small>Bir yo\'la 10 tagacha · JPG, PNG yoki WEBP</small></label>';
echo '<input id="suratlar" class="visually-hidden" type="file" name="suratlar[]" accept="image/jpeg,image/png,image/webp" multiple required data-preview="upload-preview">';
echo '<div id="upload-preview" class="upload-preview" aria-live="polite"></div></fieldset>';

echo '<fieldset class="step"><legend><span class="step-no">3</span> Saytga yuborish</legend>';
echo '<div class="actions"><button class="btn" type="submit">' . hs_icon('upload') . ' Yuklash</button></div>';
echo '<p class="hint">Yuklangan rasm 2–3 daqiqada saytda va pastdagi ro\'yxatda paydo bo\'ladi.</p></fieldset></form>';

/* ---------- saytdagi rasmlar ---------- */
echo '<form class="card" method="post" action="/admin/suratlar.php">' . hs_csrf_field();
echo '<input type="hidden" name="amal" value="saqlash"><input type="hidden" name="filial" value="' . h($slug) . '">';
echo '<div class="part-head"><span class="part-ico">' . hs_icon('image') . '</span><div><h2>Saytdagi rasmlar (' . count($files) . ')</h2><p class="muted">Saytda aynan shu tartibda chiqadi.</p></div></div>';
if (!$files) {
    echo '<p class="empty">Bu filialda hali rasm yo\'q. Yuqoridan qo\'shing.</p>';
} else {
    echo '<ul class="howto"><li><b>★ Asosiy rasm</b> — bosh sahifadagi filial kartochkasida shu rasm chiqadi.</li>'
        . '<li><b>← →</b> — rasmning joyini almashtiradi.</li>'
        . '<li><b>Olib tashlash</b> — belgilangan rasm saqlaganingizdan keyin saytdan o\'chadi.</li></ul>';
    echo '<div class="photos" data-sortable>';
    foreach ($files as $i => $f) {
        $b = h($f['base']);
        $fid = 'p' . $i;
        $isCover = $coverFile && $coverFile['base'] === $f['base'];
        $kind = isset($KINDS[$f['kind']]) ? $KINDS[$f['kind']][0] : 'Rasm';
        echo '<div class="photo-item" data-photo>';
        echo '<div class="photo-img"><img src="' . h($f['thumb']) . '" alt="" loading="lazy" decoding="async"><span class="photo-kind">' . h($kind) . '</span><span class="photo-no" data-photo-no>' . ($i + 1) . '</span></div>';
        echo '<div class="body">';
        echo '<div class="photo-tools">';
        echo '<label class="cover-pick" title="Asosiy rasm qilish"><input type="radio" name="muqova" value="' . $b . '"' . ($isCover ? ' checked' : '') . '><span>★ Asosiy rasm</span></label>';
        echo '<span class="move js-only"><button type="button" class="icon-btn" data-move="-1" aria-label="Oldinga surish">←</button><button type="button" class="icon-btn" data-move="1" aria-label="Orqaga surish">→</button></span>';
        echo '</div>';
        echo '<div class="js-hide"><label for="' . $fid . '-t">Tartib raqami</label><input id="' . $fid . '-t" type="number" name="tartib[' . $b . ']" min="1" max="999" value="' . ($i + 1) . '" data-order></div>';
        echo '<label for="' . $fid . '-y">Rasm ostidagi yozuv</label><input id="' . $fid . '-y" type="text" name="yorliq[' . $b . ']" maxlength="60" value="' . h(isset($prefs['labels'][$f['base']]) ? $prefs['labels'][$f['base']] : '') . '" placeholder="Bo\'sh qolsa: «' . h($kind) . '»">';
        echo '<details class="more"><summary>Qo\'shimcha</summary><label for="' . $fid . '-a">Rasm tavsifi</label><input id="' . $fid . '-a" type="text" name="alt[' . $b . ']" maxlength="200" value="' . h(isset($prefs['alts'][$f['base']]) ? $prefs['alts'][$f['base']] : '') . '" placeholder="Masalan: Asaka filiali, kirish eshigi"><p class="hint">Google va ko\'zi ojiz mijozlar uchun. Bo\'sh qolsa, o\'zi yoziladi.</p></details>';
        echo '<label class="remove-pick"><input type="checkbox" name="ochir[' . $b . ']" value="1" data-remove> Olib tashlash</label>';
        echo '</div></div>';
    }
    echo '</div>';
    echo '<div class="save-bar"><span class="muted">O\'zgarishlar faqat shu tugma bosilganda saytga chiqadi.</span><button class="btn" type="submit">O\'zgarishlarni saqlash</button></div>';
}
echo '</form>';

hs_page_end();
