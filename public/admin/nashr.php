<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);
$mode = hs_repo_mode();

$runs = array();
$runsError = '';
if ($mode === 'github') {
    $cached = hs_cache_get('gh:runs');
    if ($cached === null) {
        list($code, $body) = hs_github('GET', '/repos/' . hs_github_repo() . '/actions/runs?per_page=10&branch=' . rawurlencode(hs_github_branch()));
        if ($code === 200) {
            $cached = json_decode($body, true);
            hs_cache_set('gh:runs', $cached, 30);
        } else {
            $runsError = "GitHub Actions holati o'qilmadi (HTTP {$code}). Tokenga \"Actions: Read\" ruxsati kerak.";
        }
    }
    if ($cached && !empty($cached['workflow_runs'])) {
        $runs = $cached['workflow_runs'];
    }
}

$publishes = hs_db()->query('SELECT * FROM publishes ORDER BY id DESC LIMIT 30')->fetchAll();

hs_page_start('Nashr holati', $user);

$modeLabel = array('github' => 'GitHub orqali avtomatik nashr', 'local' => 'Mahalliy sinov rejimi', 'none' => 'Sozlanmagan');
echo '<section class="card"><h2>Rejim: ' . h($modeLabel[$mode]) . '</h2>';
if ($mode === 'none') {
    echo '<p>Saytni paneldan tahrirlash uchun hostingdagi <span class="code">api/secrets.php</span> ga <span class="code">github_token</span> qo\'shilishi kerak (faqat shu repo, "Contents: Read and write" va "Actions: Read" ruxsatlari bilan).</p>';
}
$published = hs_content_published();
echo '<p class="muted">Saytda hozir: ' . ($published ? count($published['branches']) . ' ta filial, ' . count($published['promotions']) . ' ta aksiya, ' . count($published['products']) . ' ta mahsulot.' : "ma'lumot topilmadi (api/sayt.json).") . '</p></section>';

if ($mode === 'github') {
    echo '<section class="card"><h2>Oxirgi yig\'ish va yuklashlar</h2>';
    if ($runsError !== '') {
        echo '<p class="flash flash-err">' . h($runsError) . '</p>';
    }
    if ($runs) {
        $state = function ($r) {
            if ($r['status'] !== 'completed') {
                return '<span class="pill st-qongiroq">jarayonda</span>';
            }
            return $r['conclusion'] === 'success' ? '<span class="pill pill-ok">saytda</span>' : '<span class="pill pill-err">xato: ' . h($r['conclusion']) . '</span>';
        };
        echo '<div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>O\'zgarish</th><th>Holat</th></tr></thead><tbody>';
        foreach ($runs as $r) {
            $msg = isset($r['head_commit']['message']) ? strtok($r['head_commit']['message'], "\n") : $r['display_title'];
            echo '<tr><td class="nowrap">' . h(date('d.m H:i', strtotime($r['created_at']))) . '</td><td>' . h($msg) . '</td><td>' . $state($r) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</section>';
}

echo '<section class="card"><h2>Paneldan qilingan nashrlar</h2><div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>Kim</th><th>Nima</th><th>Natija</th></tr></thead><tbody>';
foreach ($publishes as $p) {
    echo '<tr><td class="nowrap">' . h(date('d.m.Y H:i', strtotime($p['created_at']))) . '</td><td>' . h($p['actor']) . '</td><td>' . h($p['summary']) . '</td><td>' . ((int) $p['ok'] ? '<span class="pill pill-ok">yuborildi</span>' : '<span class="pill pill-err">' . h($p['error']) . '</span>') . '</td></tr>';
}
if (!$publishes) {
    echo '<tr><td colspan="4" class="muted">Hozircha yo\'q.</td></tr>';
}
echo '</tbody></table></div></section>';

hs_page_end();
