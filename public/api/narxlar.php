<?php
/**
 * Raqobatchilar narxlari — vizual hisobot.
 *
 * Telegram'dagi narxlar xabari qisqa (turkum va narx oralig'i). Har bir
 * mahsulot alohida, havolasi bilan — shu sahifada: har do'kon o'z kartasida,
 * turkumlar bo'yicha, arzonidan qimmatiga. "PDF saqlash" tugmasi har do'konni
 * alohida varaqqa chiqaradi.
 *
 * Kirish — havoladagi kalit bilan (Raqobatchilar bo'limida yangilanadi):
 * sahifani panelga kirmagan boshliq ham telefonidan ochsin. Kalitsiz yoki
 * noto'g'ri kalit bilan — oddiy "topilmadi". Qidiruv tizimlari ko'rmaydi,
 * havola boshqa saytga o'tganda kalit sarlavhada ketmaydi (Referrer-Policy).
 */
require __DIR__ . '/../admin/_lib/bootstrap.php';
require_once __DIR__ . '/../admin/_lib/kuzatuv.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$kalit = (string) hs_setting('rq_hisobot_key', '');
$berilgan = isset($_GET['k']) && is_string($_GET['k']) ? $_GET['k'] : '';
if ($kalit === '' || strlen($berilgan) !== strlen($kalit) || !hash_equals($kalit, $berilgan)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Topilmadi';
    exit;
}

$kun = isset($_GET['kun']) ? (int) $_GET['kun'] : 7;
$kun = in_array($kun, array(1, 7, 30), true) ? $kun : 7;
$st = hs_db()->prepare('SELECT * FROM rq_posts WHERE analyzed = 1 AND (price > 0 OR price_total > 0) AND posted_at > ? ORDER BY posted_at DESC');
$st->execute(array(date('Y-m-d H:i:s', time() - $kun * 86400)));
$rows = $st->fetchAll();

// do'kon -> turkum -> mahsulotlar (arzonidan qimmatiga)
$dokonlar = array();
foreach ($rows as $p) {
    $dokonlar[$p['channel']][hs_rq_turkum($p)][] = $p;
}
uasort($dokonlar, function ($a, $b) {
    return array_sum(array_map('count', $b)) - array_sum(array_map('count', $a));
});
$narx = function ($p) {
    return (int) $p['price'] > 0 ? (int) $p['price'] : (int) $p['price_total'];
};
foreach ($dokonlar as $k => $turkumlar) {
    foreach ($turkumlar as $t => $ps) {
        usort($ps, function ($a, $b) use ($narx) {
            return $narx($a) - $narx($b);
        });
        $turkumlar[$t] = $ps;
    }
    uasort($turkumlar, function ($a, $b) {
        return count($b) - count($a);
    });
    $dokonlar[$k] = $turkumlar;
}
$som = function ($n) {
    return (int) $n > 0 ? number_format((int) $n, 0, '.', ' ') : '—';
};
$slug = function ($s) {
    return 'd-' . preg_replace('/[^a-z0-9_]/', '', strtolower($s));
};

// "PDF saqlash" tugmasi: bitta qator skript, CSP da hash bilan ruxsat etilgan.
$js = "document.getElementById('pdf').addEventListener('click',function(){window.print()});";
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'sha256-"
    . base64_encode(hash('sha256', $js, true)) . "'; img-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
header('Content-Type: text/html; charset=utf-8');
$havola = function ($d) use ($berilgan) {
    return '/api/narxlar.php?k=' . rawurlencode($berilgan) . '&kun=' . $d;
};
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Raqobatchilar narxlari — HAMKOR SAVDO</title>
<style>
  :root { --ink:#1a1130; --ink2:#4a4458; --ink3:#736d82; --line:#ebe7f1; --bg:#f5f3f8; --card:#fff; --p:#5a3089; --p50:#f4eff9; }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--bg); color:var(--ink); font:15px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
  .wrap { max-width:980px; margin:0 auto; padding:18px 16px 40px; }
  header h1 { margin:4px 0 2px; font-size:24px; letter-spacing:-.01em; }
  .muted { color:var(--ink3); font-size:13px; }
  .bar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:14px 0 10px; }
  .chip { display:inline-block; padding:6px 12px; border-radius:999px; background:var(--card); border:1px solid var(--line); color:var(--ink2); text-decoration:none; font-size:13px; font-weight:600; }
  .chip.on { background:var(--p); border-color:var(--p); color:#fff; }
  button#pdf { margin-left:auto; padding:8px 14px; border-radius:10px; border:0; background:var(--p); color:#fff; font:600 14px system-ui,sans-serif; cursor:pointer; }
  nav.dokonlar { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  nav.dokonlar a { font-size:13px; color:var(--p); text-decoration:none; padding:4px 10px; background:var(--p50); border-radius:8px; }
  section.dokon { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:16px 16px 6px; margin-bottom:16px; }
  section.dokon h2 { margin:0; font-size:19px; }
  .turkum { margin:16px 0 4px; display:flex; flex-wrap:wrap; gap:4px 10px; align-items:baseline; border-bottom:2px solid var(--p50); padding-bottom:4px; }
  .turkum h3 { margin:0; font-size:15px; color:var(--p); }
  .row { display:grid; grid-template-columns: 1fr 120px 110px 70px; gap:10px; padding:8px 0; border-bottom:1px solid var(--line); align-items:baseline; }
  .row.head { color:var(--ink3); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.03em; border-bottom:0; padding-bottom:0; }
  .row a { color:var(--ink); text-decoration:none; font-weight:600; }
  .row a:hover { color:var(--p); text-decoration:underline; }
  .num { text-align:right; font-variant-numeric: tabular-nums; white-space:nowrap; }
  .oy { color:var(--ink3); font-size:12px; }
  .bosh { text-align:center; padding:40px 16px; color:var(--ink3); }
  footer { margin-top:20px; font-size:12px; color:var(--ink3); }
  @media (max-width: 640px) {
    .row { grid-template-columns: 1fr auto; gap:2px 10px; }
    .row.head { display:none; }
    .row .toliq, .row .sana { grid-column: 1 / -1; text-align:left; font-size:12px; color:var(--ink3); }
    .row .toliq:empty, .row .sana:empty { display:none; }
  }
  @media print {
    body { background:#fff; font-size:12px; }
    .bar, nav.dokonlar, footer .chop-yoq { display:none !important; }
    section.dokon { border:0; padding:0; break-before:page; }
    section.dokon:first-of-type { break-before:auto; }
    .row { break-inside:avoid; }
    .row a { color:#000; }
  }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="muted">HAMKOR SAVDO · ichki hisobot</div>
    <h1>Raqobatchilar narxlari</h1>
    <div class="muted">
      <?= $kun === 1 ? 'Oxirgi 1 kun' : "Oxirgi {$kun} kun" ?> · <?= count($rows) ?> ta mahsulot · <?= count($dokonlar) ?> ta do'kon · <?= h(date('d.m.Y H:i')) ?>
    </div>
  </header>

  <div class="bar">
    <?php foreach (array(1 => '1 kun', 7 => '7 kun', 30 => '30 kun') as $d => $nom) : ?>
      <a class="chip<?= $d === $kun ? ' on' : '' ?>" href="<?= h($havola($d)) ?>"><?= h($nom) ?></a>
    <?php endforeach; ?>
    <?php if ($rows) : ?><button id="pdf" type="button">PDF saqlash</button><?php endif; ?>
  </div>

  <?php if (!$rows) : ?>
    <p class="bosh">Bu davrda narxi o'qilgan e'lon yo'q.</p>
  <?php else : ?>
    <nav class="dokonlar" aria-label="Do'konlar">
      <?php foreach ($dokonlar as $kanal => $turkumlar) : ?>
        <a href="#<?= h($slug($kanal)) ?>"><?= h(hs_rq_channel_title($kanal)) ?> · <?= array_sum(array_map('count', $turkumlar)) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php foreach ($dokonlar as $kanal => $turkumlar) : ?>
      <section class="dokon" id="<?= h($slug($kanal)) ?>">
        <h2><?= h(hs_rq_channel_title($kanal)) ?></h2>
        <div class="muted"><?= array_sum(array_map('count', $turkumlar)) ?> ta mahsulot · <?= count($turkumlar) ?> ta turkum</div>
        <?php foreach ($turkumlar as $turkum => $ps) :
            $oyliklar = array_filter(array_map(function ($p) { return (int) $p['price']; }, $ps));
            ?>
          <div class="turkum">
            <h3><?= h(hs_rq_bosh_harf($turkum)) ?></h3>
            <span class="muted"><?= count($ps) ?> ta<?= $oyliklar ? ' · oyiga ' . h(hs_rq_oraliq($oyliklar)) : '' ?></span>
          </div>
          <div class="row head"><span>Mahsulot</span><span class="num">Oyiga, so'm</span><span class="num">To'liq narx</span><span class="num">Sana</span></div>
          <?php foreach ($ps as $p) : ?>
            <div class="row">
              <span><a href="<?= h($p['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h(hs_rq_sarlavha($p)) ?></a></span>
              <span class="num"><?= $som($p['price']) ?><?= (int) $p['months'] > 0 ? ' <span class="oy">× ' . (int) $p['months'] . ' oy</span>' : '' ?></span>
              <span class="num toliq"><?= (int) $p['price_total'] > 0 ? 'narxi ' . $som($p['price_total']) : '' ?></span>
              <span class="num sana"><?= h(date('d.m', strtotime($p['posted_at']))) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer>
    Narxlar raqobatchilarning Telegram e'lonlaridan (matni va rasmidan) avtomatik o'qilgan. Xato bo'lishi mumkin —
    har birining asl e'loni nomiga bosilganda ochiladi.
    <span class="chop-yoq">PDF: «PDF saqlash» → «Saqlash PDF sifatida» (har do'kon alohida varaqda).</span>
  </footer>
</div>
<?php if ($rows) : ?><script><?= $js ?></script><?php endif; ?>
</body>
</html>
