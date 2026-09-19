<?php
/**
 * Panel qolipi. Inline <script> va style="" yo'q — CSP ularni taqiqlaydi.
 *
 * Kompyuterda chap tomonda doimiy menyu (<aside>). Telefonda yuqorida
 * "Menyu" tugmasi — <details>, JavaScript'siz ochiladi. Ikkalasi alohida:
 * yopiq <details> ichidagi narsani brauzer umuman chizmaydi, shuning uchun
 * kompyuter menyusini unga tiqib bo'lmaydi.
 */

function hs_icon($name, $class = '')
{
    $p = array(
        'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'inbox' => '<path d="M4 13h4l2 3h4l2-3h4"/><path d="M5 5h14l2 8v6H3v-6z"/>',
        'chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
        'store' => '<path d="M4 9h16l-1-5H5z"/><path d="M5 9v11h14V9"/><path d="M10 20v-6h4v6"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-9 8"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
        'box' => '<path d="m3 7 9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'text' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'upload' => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 16v4h16v-4"/>',
        'logout' => '<path d="M15 4h4v16h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12"/><circle cx="12" cy="12" r="3"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'send' => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4z"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M18 14v6H4V6h6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
        'filter' => '<path d="M3 5h18l-7 8.5V20l-4-2v-4.5z"/>',
    );
    $body = isset($p[$name]) ? $p[$name] : '';
    return '<svg class="ico' . ($class !== '' ? ' ' . $class : '') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/**
 * Bo'limlar: [yo'l, nom, belgi, faqat egasi, qisqa izoh].
 * Bosh sahifadagi plitkalar ham shu ro'yxatdan chiziladi.
 */
function hs_nav_groups()
{
    return array(
        'Asosiy' => array(
            array('/admin/', 'Bosh sahifa', 'home', false, "Bugungi holat bir qarashda"),
            array('/admin/arizalar.php', 'Arizalar', 'inbox', false, "Holat, izoh, filtrlar, Excel'ga yuklab olish"),
            array('/admin/statistika.php', 'Statistika', 'chart', true, 'Tashriflar, manbalar, shaharlar, konversiya'),
        ),
        'Sayt' => array(
            array('/admin/filiallar.php', 'Filiallar', 'store', true, 'Telefon, ish vaqti, manzil, "vaqtincha yopiq"'),
            array('/admin/suratlar.php', 'Suratlar', 'image', true, 'Yuklash, tartib, muqova, o\'chirish'),
            array('/admin/aksiyalar.php', 'Aksiyalar', 'tag', true, 'Muddati tugagach o\'zi yashirinadi'),
            array('/admin/katalog.php', 'Katalog', 'box', true, 'Mahsulot, narx, bo\'lim, mavjudligi'),
            array('/admin/matnlar.php', 'Matnlar va FAQ', 'text', true, 'Bosh sahifa matnlari, savol-javob'),
            array('/admin/sozlamalar.php', 'Sozlamalar', 'settings', true, 'Asosiy telefon, muddat, havolalar, hisobot'),
        ),
        'Boshqaruv' => array(
            array('/admin/foydalanuvchilar.php', 'Foydalanuvchilar', 'users', true, 'Filial operatorlari va ruxsatlar'),
            array('/admin/telegram.php', 'Telegram', 'send', true, 'Arizalar qaysi odam va guruhlarga boradi'),
            array('/admin/bloklar.php', 'Bloklar', 'shield', true, 'Bloklangan qurilmalar va kirishlar'),
            array('/admin/tarix.php', "O'zgarishlar tarixi", 'history', true, 'Kim, qachon, nimani o\'zgartirdi'),
            array('/admin/nashr.php', 'Nashr holati', 'upload', true, 'O\'zgarishlar saytga chiqdimi'),
        ),
    );
}

function hs_new_leads_badge($user)
{
    try {
        if ($user['role'] === 'operator' && $user['branch'] !== '') {
            $st = hs_db()->prepare("SELECT COUNT(*) FROM leads WHERE status = 'yangi' AND branch = ?");
            $st->execute(array($user['branch']));
        } else {
            $st = hs_db()->query("SELECT COUNT(*) FROM leads WHERE status = 'yangi'");
        }
        return (int) $st->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function hs_render_nav($user, $current)
{
    $badge = hs_new_leads_badge($user);
    $html = '';
    foreach (hs_nav_groups() as $group => $items) {
        $visible = array();
        foreach ($items as $it) {
            if (!$it[3] || hs_is_owner($user)) {
                $visible[] = $it;
            }
        }
        if (!$visible) {
            continue;
        }
        $html .= '<p class="nav-group">' . h($group) . '</p>';
        foreach ($visible as $it) {
            // Bitta ariza sahifasi ham "Arizalar" bo'limiga tegishli.
            $active = $current === $it[0] || ($current === '/admin/ariza.php' && $it[0] === '/admin/arizalar.php') ? ' active' : '';
            $count = ($it[0] === '/admin/arizalar.php' && $badge > 0) ? '<span class="badge">' . $badge . '</span>' : '';
            $html .= '<a class="nav-link' . $active . '" href="' . h($it[0]) . '">' . hs_icon($it[2]) . '<span>' . h($it[1]) . '</span>' . $count . '</a>';
        }
    }
    $html .= '<form class="nav-logout" method="post" action="/admin/logout.php">' . hs_csrf_field()
        . '<button type="submit" class="nav-link">' . hs_icon('logout') . '<span>Chiqish</span></button></form>';
    return $html;
}

function hs_current_path()
{
    $path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    return preg_replace('#/index\.php$#', '/', $path);
}

/**
 * Statik fayl manzili, o'zgarish vaqti bilan. Fayl yangilansa manzil ham
 * o'zgaradi — brauzer eski CSS/JS ni keshdan ko'rsatib qolmaydi.
 */
function hs_asset($file)
{
    $full = __DIR__ . '/../assets/' . $file;
    return '/admin/assets/' . $file . '?v=' . (is_file($full) ? filemtime($full) : '1');
}

/** Hamma admin sahifalari uchun umumiy <head>. */
function hs_head($title, $noindex = 'noindex, nofollow')
{
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="robots" content="' . h($noindex) . '">';
    echo '<meta name="color-scheme" content="light dark">';
    echo '<meta name="theme-color" content="#ffffff">';
    echo '<title>' . h($title) . '</title>';
    // theme.js defer'siz: mavzu sahifa chizilishidan oldin qo'yilishi kerak.
    echo '<script src="' . hs_asset('theme.js') . '"></script>';
    echo '<link rel="stylesheet" href="' . hs_asset('admin.css') . '">';
    echo '<script src="' . hs_asset('admin.js') . '" defer></script>';
    echo '</head>';
}

function hs_theme_toggle()
{
    return '<button type="button" class="theme-toggle" data-theme-toggle aria-label="Mavzuni almashtirish" title="Mavzuni almashtirish">' . hs_icon('moon', 'i-moon') . hs_icon('sun', 'i-sun') . '</button>';
}

function hs_page_start($title, $user = null, $subtitle = null)
{
    $current = hs_current_path();
    if ($subtitle === null) {
        foreach (hs_nav_groups() as $items) {
            foreach ($items as $it) {
                if ($it[0] === $current) {
                    $subtitle = $it[4];
                }
            }
        }
    }
    hs_head($title . ' — HAMKOR SAVDO admin');
    echo '<body>';

    if (!$user) {
        echo '<main class="wrap">';
        return;
    }

    $brand = '<a class="brand" href="/admin/"><span class="brand-mark">H</span><span class="brand-text">HAMKOR SAVDO<small>boshqaruv paneli</small></span></a>';
    $who = '<div class="who"><span class="avatar">' . h(mb_strtoupper(mb_substr($user['login'], 0, 1))) . '</span><span><b>' . h($user['name']) . '</b><small>' . ($user['role'] === 'owner' ? 'Egasi' : 'Operator') . '</small></span>' . hs_theme_toggle() . '</div>';

    echo '<div class="shell">';
    echo '<aside class="side">' . $brand . '<nav class="side-nav" aria-label="Bo\'limlar">' . hs_render_nav($user, $current) . '</nav>' . $who . '</aside>';
    echo '<div class="main-col">';
    echo '<header class="mobile-top">' . $brand . hs_theme_toggle() . '<details class="mobile-menu"><summary>' . hs_icon('text') . ' Menyu</summary><nav class="mobile-nav" aria-label="Bo\'limlar">' . hs_render_nav($user, $current) . '</nav></details></header>';
    echo '<main class="wrap">';
    echo '<div class="page-head"><div><h1>' . h($title) . '</h1>' . ($subtitle ? '<p class="page-sub">' . h($subtitle) . '</p>' : '') . '</div>';
    echo '<a class="btn outline small" href="' . h(hs_site_url()) . '/" target="_blank" rel="noopener noreferrer" title="Saytni ochish" aria-label="Saytni ochish">' . hs_icon('external') . '<span class="label-long">Saytni ochish</span></a></div>';
    if (session_status() === PHP_SESSION_ACTIVE) {
        foreach (hs_flash() as $f) {
            echo '<p class="flash flash-' . h($f[0]) . '">' . h($f[1]) . '</p>';
        }
    }
}

function hs_page_end()
{
    echo '</main>';
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user'])) {
        echo '</div></div>';
    }
    echo '</body></html>';
}

function hs_render_blocked()
{
    http_response_code(403);
    hs_head('Kirish yopiq', 'noindex');
    echo '<body class="center"><div class="card narrow auth-card"><div class="auth-icon danger">' . hs_icon('shield') . '</div><h1>Kirish yopiq</h1>';
    echo "<p>Bu qurilmadan juda ko'p noto'g'ri urinish bo'ldi, shuning uchun admin panelga kirish bloklandi.</p>";
    echo '</div></body></html>';
    exit;
}

/**
 * Ustunli grafik. $rows = [[yorliq, qiymat], ...]
 *
 * Yorliq va raqamlar oddiy HTML matn: ilgari hammasi bitta SVG ichida edi
 * va telefonda grafik bilan birga kichrayib, o'qib bo'lmas darajaga tushardi.
 * Ustun balandligi SVG'ning height atributida (foizda) — CSP style="" ni
 * taqiqlaydi, atribut esa ruxsat etilgan. $todayLabel — ajratib ko'rsatiladigan kun.
 */
function hs_bar_chart($rows, $label = '', $todayLabel = null)
{
    if (!$rows) {
        return '<p class="empty">Ma\'lumot yo\'q.</p>';
    }
    $max = 0;
    foreach ($rows as $r) {
        $max = max($max, (float) $r[1]);
    }
    $max = $max > 0 ? $max : 1;
    if ($todayLabel === null) {
        $todayLabel = date('d.m');
    }
    $html = '<div class="bars" role="img" aria-label="' . h($label) . '">';
    foreach ($rows as $r) {
        $v = (float) $r[1];
        // Eng balandi 88% — tepasidagi raqamga joy qoladi.
        $pct = $v > 0 ? max(round($v / $max * 88, 1), 3) : 0;
        $html .= '<div class="bar-col' . ((string) $r[0] === $todayLabel ? ' today' : '') . '" title="' . h($r[0] . ': ' . $r[1]) . '">';
        $html .= '<div class="bar-plot"><div class="bar-stack">';
        $html .= '<span class="bar-val">' . h($r[1]) . '</span>';
        $html .= '<svg class="bar-svg" width="100%" height="' . ($v > 0 ? $pct . '%' : '3') . '" aria-hidden="true"><rect class="bar' . ($v > 0 ? '' : ' zero') . '" width="100%" height="100%" rx="' . ($v > 0 ? 7 : 1.5) . '"/></svg>';
        $html .= '</div></div><span class="bar-lbl">' . h($r[0]) . '</span></div>';
    }
    return $html . '</div>';
}

/**
 * Sahifalash: joriy sahifa atrofidagilar, birinchi va oxirgisi.
 * Ilgari hamma raqam chiqardi — 1000 ta arizada 20 ta tugma.
 * $url — sahifa raqamidan havola yasaydigan funksiya.
 */
function hs_pager($page, $pages, $url)
{
    if ($pages <= 1) {
        return '';
    }
    $show = array(1, $pages);
    for ($i = $page - 2; $i <= $page + 2; $i++) {
        if ($i >= 1 && $i <= $pages) {
            $show[] = $i;
        }
    }
    $show = array_values(array_unique($show));
    sort($show);
    $html = '<nav class="pager" aria-label="Sahifalar">';
    if ($page > 1) {
        $html .= '<a href="' . h($url($page - 1)) . '" aria-label="Oldingi sahifa">‹</a>';
    }
    $prev = 0;
    foreach ($show as $i) {
        if ($i - $prev > 1) {
            $html .= '<span>…</span>';
        }
        $html .= $i === $page ? '<strong aria-current="page">' . $i . '</strong>' : '<a href="' . h($url($i)) . '">' . $i . '</a>';
        $prev = $i;
    }
    if ($page < $pages) {
        $html .= '<a href="' . h($url($page + 1)) . '" aria-label="Keyingi sahifa">›</a>';
    }
    return $html . '</nav>';
}
/** Gorizontal ro'yxat: yorliq, qiymat va ulushi (SVG chiziqcha). */
function hs_share_list($rows)
{
    if (!$rows) {
        return '<p class="empty">Ma\'lumot yo\'q.</p>';
    }
    $total = 0;
    foreach ($rows as $r) {
        $total += (float) $r[1];
    }
    $total = $total > 0 ? $total : 1;
    $html = '<ul class="share">';
    foreach ($rows as $r) {
        $pct = round(((float) $r[1] / $total) * 100);
        $html .= '<li><span class="share-label">' . h($r[0]) . '</span><span class="share-val">' . h($r[1]) . ' <small>' . $pct . '%</small></span>';
        $html .= '<svg class="share-bar" viewBox="0 0 100 6" preserveAspectRatio="none"><rect width="100" height="6" rx="3" class="track"/><rect width="' . max($pct, 1) . '" height="6" rx="3" class="fill"/></svg></li>';
    }
    return $html . '</ul>';
}

/** Statistika kartasi. */
function hs_kpi($icon, $value, $label, $hint = '', $tone = '')
{
    return '<div class="kpi' . ($tone ? ' kpi-' . $tone : '') . '"><span class="kpi-ico">' . hs_icon($icon) . '</span><div><b>' . h($value) . '</b><span>' . h($label) . '</span>' . ($hint !== '' ? '<small>' . $hint . '</small>' : '') . '</div></div>';
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
