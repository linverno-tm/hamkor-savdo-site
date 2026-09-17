<?php
require __DIR__ . '/_lib/bootstrap.php';

$user = hs_require_login(true);

$perPage = 100;
$page = max(1, (int) hs_get('sahifa', '1'));
$total = (int) hs_db()->query('SELECT COUNT(*) FROM audit')->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$st = hs_db()->prepare('SELECT * FROM audit ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ?');
$st->execute(array(($min = min($page, $pages)) * $perPage - $perPage));
$rows = $st->fetchAll();

hs_page_start("O'zgarishlar tarixi", $user);
echo '<section class="card"><div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>Kim</th><th>Amal</th><th>Tafsilot</th></tr></thead><tbody>';
foreach ($rows as $r) {
    echo '<tr><td class="nowrap">' . h(date('d.m.Y H:i', strtotime($r['created_at']))) . '</td><td>' . h($r['actor']) . '</td><td>' . h($r['action']) . '</td><td>' . h($r['details']) . '</td></tr>';
}
if (!$rows) {
    echo '<tr><td colspan="4" class="muted">Hozircha yozuv yo\'q.</td></tr>';
}
echo '</tbody></table></div>';
if ($pages > 1) {
    echo '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        echo $i === $min ? '<strong>' . $i . '</strong>' : '<a href="/admin/tarix.php?sahifa=' . $i . '">' . $i . '</a>';
    }
    echo '</div>';
}
echo '</section>';
hs_page_end();
