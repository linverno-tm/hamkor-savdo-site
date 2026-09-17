<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    $problem = "Noma'lum amal.";

    if ($action === 'matnlar') {
        $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\r?\n\s*\r?\n/", hs_post('aboutParagraphs'))), 'strlen'));
        $texts = array(
            'heroLead' => mb_substr(hs_post('heroLead'), 0, 400),
            'aboutParagraphs' => array_map(function ($p) {
                return mb_substr(preg_replace('/\s*\r?\n\s*/', ' ', $p), 0, 800);
            }, $paragraphs),
            'companyStory' => mb_substr(hs_post('companyStory'), 0, 1500),
            'specialOrderLead' => mb_substr(hs_post('specialOrderLead'), 0, 400),
            'finalCtaLead' => mb_substr(hs_post('finalCtaLead'), 0, 400),
        );
        $problem = hs_content_publish($user, 'sayt matnlari', function (&$c) use ($texts) {
            if (trim($texts['heroLead']) === '' || !$texts['aboutParagraphs']) {
                return "Bosh sahifa matni va \"Biz haqimizda\" bo'sh bo'lmasin.";
            }
            $c['texts'] = array_merge($c['texts'], $texts);
            return null;
        });
    } elseif ($action === 'faq') {
        $qs = isset($_POST['q']) && is_array($_POST['q']) ? $_POST['q'] : array();
        $as = isset($_POST['a']) && is_array($_POST['a']) ? $_POST['a'] : array();
        $order = isset($_POST['o']) && is_array($_POST['o']) ? $_POST['o'] : array();
        $items = array();
        foreach ($qs as $k => $q) {
            $q = trim((string) $q);
            $a = isset($as[$k]) ? trim((string) $as[$k]) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $items[] = array('o' => isset($order[$k]) ? (int) $order[$k] : 999, 'q' => mb_substr($q, 0, 200), 'a' => mb_substr($a, 0, 1500));
        }
        usort($items, function ($x, $y) {
            return $x['o'] - $y['o'];
        });
        $faq = array_map(function ($i) {
            return array('q' => $i['q'], 'a' => $i['a']);
        }, $items);
        $problem = hs_content_publish($user, 'savol-javob (' . count($faq) . ' ta)', function (&$c) use ($faq) {
            $c['faq'] = $faq;
            return null;
        });
    }
    hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    hs_redirect('/admin/matnlar.php');
}

$err = null;
$c = hs_content_load($err);
hs_page_start('Matnlar va FAQ', $user);
if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
    hs_page_end();
    exit;
}
$t = $c['texts'];

echo '<p class="flash flash-warn">Matn ichida ishlatish mumkin: <span class="code">{oy}</span> — muddatli to\'lov oyi, <span class="code">{filial}</span> — filiallar soni, <span class="code">{mahsulot}</span> — mahsulotlar soni, <span class="code">{bepul_hudud}</span> — bepul yetkazish hududi. <span class="code">**so\'z**</span> — qalin yozuv.</p>';

echo '<form class="card" method="post" action="/admin/matnlar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="matnlar">';
echo '<h2>Sayt matnlari</h2>';
echo '<label for="heroLead">Bosh sahifa — sarlavha ostidagi matn</label><textarea id="heroLead" name="heroLead" maxlength="400" required>' . h($t['heroLead']) . '</textarea>';
echo '<label for="aboutParagraphs">"Biz haqimizda" — xatboshilar</label><textarea id="aboutParagraphs" name="aboutParagraphs" rows="10" required>' . h(implode("\n\n", $t['aboutParagraphs'])) . '</textarea><p class="hint">Xatboshilar orasida bitta bo\'sh qator qoldiring.</p>';
echo '<label for="companyStory">Kompaniya tarixi (ixtiyoriy)</label><textarea id="companyStory" name="companyStory" maxlength="1500">' . h($t['companyStory']) . '</textarea><p class="hint">Bo\'sh bo\'lsa saytda ko\'rinmaydi.</p>';
echo '<label for="specialOrderLead">"Bizda yo\'qmi?" bo\'limi matni</label><textarea id="specialOrderLead" name="specialOrderLead" maxlength="400" required>' . h($t['specialOrderLead']) . '</textarea>';
echo '<label for="finalCtaLead">"Hamkor bo\'ling" bo\'limi matni</label><textarea id="finalCtaLead" name="finalCtaLead" maxlength="400" required>' . h($t['finalCtaLead']) . '</textarea>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button></div></form>';

function hs_faq_row($k, $o, $q, $a)
{
    return '<div class="repeat-row"><div class="grid grid-4"><div><label>Tartib</label><input type="number" name="o[' . $k . ']" value="' . h($o) . '" min="1" max="999"></div></div>'
        . '<label>Savol</label><input type="text" name="q[' . $k . ']" maxlength="200" value="' . h($q) . '">'
        . '<label>Javob</label><textarea name="a[' . $k . ']" maxlength="1500">' . h($a) . '</textarea>'
        . '<p class="hint">O\'chirish uchun savol va javobni bo\'shating.</p></div>';
}

echo '<form class="card" method="post" action="/admin/matnlar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="faq">';
echo '<h2>Savol-javob (FAQ)</h2><p class="muted">Bosh sahifada va Google qidiruvida chiqishi mumkin.</p><div id="faq-rows">';
foreach ($c['faq'] as $i => $f) {
    echo hs_faq_row($i, $i + 1, $f['q'], $f['a']);
}
$n = count($c['faq']);
for ($j = 0; $j < 2; $j++) {
    echo hs_faq_row($n + $j, $n + $j + 1, '', '');
}
echo '</div><template id="faq-tpl">' . hs_faq_row('__i__', 99, '', '') . '</template>';
echo '<div class="actions"><button class="btn outline" type="button" data-add-row="faq-tpl" data-target="faq-rows">+ Yana savol</button><button class="btn" type="submit">Saqlash va nashr qilish</button></div></form>';

hs_page_end();
