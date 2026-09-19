<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/leads.php';

$user = hs_require_login();
list($where, $args, $f) = hs_lead_filters($user);

$perPage = 50;
$page = max(1, (int) hs_get('sahifa', '1'));
$st = hs_db()->prepare("SELECT COUNT(*) FROM leads {$where}");
$st->execute($args);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$st = hs_db()->prepare("SELECT * FROM leads {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage));
$st->execute($args);
$rows = $st->fetchAll();

$sources = hs_db()->query("SELECT DISTINCT source FROM leads WHERE source != '' ORDER BY source")->fetchAll(PDO::FETCH_COLUMN);

hs_page_start('Arizalar', $user);

// Telefonda filtrlar yig'iq turadi (kompyuterda admin.js ochib qo'yadi). Faol filtr bo'lsa — doim ochiq.
$active = count(array_filter($f, 'strlen'));
echo '<details class="card filter-box"' . ($active ? ' open' : '') . '><summary>' . hs_icon('filter') . ' Filtrlar'
    . ($active ? ' <span class="pill st-qongiroq count">' . $active . '</span>' : '') . '</summary>';
echo '<form class="filters" method="get" action="/admin/arizalar.php">';
echo '<div><label for="q">Qidirish</label><input id="q" type="search" name="q" value="' . h($f['q']) . '" placeholder="Ism, telefon, izoh"></div>';
echo '<div><label for="holat">Holat</label><select id="holat" name="holat"><option value="">Hammasi</option>';
foreach (hs_lead_statuses() as $k => $v) {
    echo '<option value="' . h($k) . '"' . ($f['holat'] === $k ? ' selected' : '') . '>' . h($v) . '</option>';
}
echo '</select></div>';
if (!($user['role'] === 'operator' && $user['branch'] !== '')) {
    echo '<div><label for="filial">Filial</label><select id="filial" name="filial"><option value="">Hammasi</option>';
    foreach (hs_branch_names() as $k => $v) {
        echo '<option value="' . h($k) . '"' . ($f['filial'] === $k ? ' selected' : '') . '>' . h($v) . '</option>';
    }
    echo '</select></div>';
}
echo '<div><label for="manba">Manba</label><select id="manba" name="manba"><option value="">Hammasi</option>';
foreach ($sources as $s) {
    echo '<option value="' . h($s) . '"' . ($f['manba'] === $s ? ' selected' : '') . '>' . h(hs_source_label($s)) . '</option>';
}
echo '</select></div>';
echo '<div><label for="dan">Sanadan</label><input id="dan" type="date" name="dan" value="' . h($f['dan']) . '"></div>';
echo '<div><label for="gacha">Sanagacha</label><input id="gacha" type="date" name="gacha" value="' . h($f['gacha']) . '"></div>';
echo '<div class="actions"><button class="btn" type="submit">Ko\'rsatish</button><a class="btn outline" href="/admin/arizalar.php">Tozalash</a></div>';
echo '</form></details>';

$query = http_build_query(array_filter($f, 'strlen'));
echo '<section class="card"><div class="card-head"><h2>' . $total . ' ta ariza</h2>';
echo '<a class="btn outline small" href="/admin/eksport.php' . ($query ? '?' . h($query) : '') . '">Excel\'ga yuklab olish</a></div>';
hs_render_leads_table($rows, true);
echo hs_pager($page, $pages, function ($i) use ($f) {
    return '/admin/arizalar.php?' . http_build_query(array_merge(array_filter($f, 'strlen'), array('sahifa' => $i)));
});
echo '</section>';

hs_page_end();
