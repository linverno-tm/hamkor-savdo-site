<?php
/**
 * Panel qolipi. Inline <script> va style="" yo'q — CSP ularni taqiqlaydi.
 * Menyu mobil telefonda <details> orqali ochiladi (JavaScript'siz).
 */

function hs_nav_items($user)
{
    $items = array(
        array('/admin/', 'Bosh sahifa', false),
        array('/admin/arizalar.php', 'Arizalar', false),
        array('/admin/statistika.php', 'Statistika', true),
        array('/admin/filiallar.php', 'Filiallar', true),
        array('/admin/suratlar.php', 'Suratlar', true),
        array('/admin/aksiyalar.php', 'Aksiyalar', true),
        array('/admin/katalog.php', 'Katalog', true),
        array('/admin/matnlar.php', 'Matnlar va FAQ', true),
        array('/admin/sozlamalar.php', 'Sozlamalar', true),
        array('/admin/foydalanuvchilar.php', 'Foydalanuvchilar', true),
        array('/admin/bloklar.php', 'Bloklar', true),
        array('/admin/tarix.php', "O'zgarishlar tarixi", true),
        array('/admin/nashr.php', 'Nashr holati', true),
    );
    $out = array();
    foreach ($items as $it) {
        if ($it[2] && !hs_is_owner($user)) {
            continue;
        }
        $out[] = $it;
    }
    return $out;
}

function hs_page_start($title, $user = null)
{
    $path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $path = preg_replace('#/index\.php$#', '/', $path);
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . h($title) . ' — HAMKOR SAVDO admin</title>';
    echo '<link rel="stylesheet" href="/admin/assets/admin.css?v=1">';
    echo '<script src="/admin/assets/admin.js?v=1" defer></script>';
    echo '</head><body>';
    if ($user) {
        echo '<header class="top"><a class="brand" href="/admin/">HAMKOR SAVDO <span>admin</span></a>';
        echo '<details class="menu"><summary aria-label="Menyu">Menyu</summary><nav>';
        foreach (hs_nav_items($user) as $it) {
            $active = ($path === $it[0]) ? ' class="active"' : '';
            echo '<a href="' . h($it[0]) . '"' . $active . '>' . h($it[1]) . '</a>';
        }
        echo '<form method="post" action="/admin/logout.php">' . hs_csrf_field() . '<button type="submit" class="linklike">Chiqish (' . h($user['login']) . ')</button></form>';
        echo '</nav></details></header>';
    }
    echo '<main class="wrap"><h1>' . h($title) . '</h1>';
    if (session_status() === PHP_SESSION_ACTIVE) {
        foreach (hs_flash() as $f) {
            echo '<p class="flash flash-' . h($f[0]) . '">' . h($f[1]) . '</p>';
        }
    }
}

function hs_page_end()
{
    echo '</main></body></html>';
}

function hs_render_blocked()
{
    http_response_code(403);
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex"><title>Kirish yopiq</title><link rel="stylesheet" href="/admin/assets/admin.css?v=1"></head>';
    echo '<body class="center"><div class="card narrow"><h1>Kirish yopiq</h1>';
    echo "<p>Bu qurilmadan juda ko'p noto'g'ri urinish bo'ldi, shuning uchun admin panelga kirish bloklandi.</p>";
    echo '</div></body></html>';
    exit;
}

/**
 * Oddiy ustunli grafik, SVG. $rows = [[yorliq, qiymat], ...]
 */
function hs_bar_chart($rows, $label = '')
{
    $n = count($rows);
    if ($n === 0) {
        return '<p class="muted">Ma\'lumot yo\'q.</p>';
    }
    $max = 0;
    foreach ($rows as $r) {
        $max = max($max, (float) $r[1]);
    }
    $max = $max > 0 ? $max : 1;
    $w = 640;
    $hgt = 180;
    $gap = 6;
    $bw = ($w - $gap * ($n - 1)) / $n;
    $svg = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . ($hgt + 34) . '" role="img" aria-label="' . h($label) . '">';
    foreach ($rows as $i => $r) {
        $bh = round(($r[1] / $max) * $hgt, 1);
        $x = round($i * ($bw + $gap), 1);
        $y = $hgt - $bh;
        $svg .= '<rect class="bar" x="' . $x . '" y="' . $y . '" width="' . round($bw, 1) . '" height="' . max($bh, 1) . '" rx="4"><title>' . h($r[0] . ': ' . $r[1]) . '</title></rect>';
        $svg .= '<text class="val" x="' . round($x + $bw / 2, 1) . '" y="' . max($y - 4, 10) . '" text-anchor="middle">' . h($r[1]) . '</text>';
        $svg .= '<text class="lbl" x="' . round($x + $bw / 2, 1) . '" y="' . ($hgt + 20) . '" text-anchor="middle">' . h($r[0]) . '</text>';
    }
    return $svg . '</svg>';
}

/** Gorizontal ro'yxat: yorliq, qiymat va ulushi (SVG chiziqcha). */
function hs_share_list($rows)
{
    if (!$rows) {
        return '<p class="muted">Ma\'lumot yo\'q.</p>';
    }
    $total = 0;
    foreach ($rows as $r) {
        $total += (float) $r[1];
    }
    $total = $total > 0 ? $total : 1;
    $html = '<ul class="share">';
    foreach ($rows as $r) {
        $pct = round(((float) $r[1] / $total) * 100);
        $html .= '<li><span class="share-label">' . h($r[0]) . '</span><span class="share-val">' . h($r[1]) . ' <small>(' . $pct . '%)</small></span>';
        $html .= '<svg class="share-bar" viewBox="0 0 100 4" preserveAspectRatio="none"><rect width="100" height="4" class="track"/><rect width="' . $pct . '" height="4" class="fill"/></svg></li>';
    }
    return $html . '</ul>';
}

function hs_branch_names()
{
    require_once __DIR__ . '/content.php';
    $names = array();
    $c = hs_content_published();
    if ($c) {
        foreach ($c['branches'] as $b) {
            $names[$b['id']] = $b['city'] . ' — ' . $b['landmark'];
        }
    }
    $names['boshqa-viloyat'] = 'Boshqa viloyat';
    return $names;
}
