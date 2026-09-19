<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/leads.php';

$user = hs_require_login();

/** Paneldagi holat o'zgarishi Telegram'dagi ariza xabarlarida ham ko'rinsin. Telegram ishlamasa — jim. */
function hs_lead_tg_sync($id)
{
    try {
        require_once __DIR__ . '/_lib/tgchats.php';
        hs_tg_sync_lead($id);
    } catch (Throwable $e) {
        error_log('HAMKOR SAVDO: Telegram xabari yangilanmadi: ' . $e->getMessage());
    }
}
$id = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? hs_post('id') : hs_get('id'));

$st = hs_db()->prepare('SELECT * FROM leads WHERE id = ?');
$st->execute(array($id));
$lead = $st->fetch();
if (!$lead || !hs_can_see_lead($user, $lead)) {
    http_response_code(404);
    hs_page_start('Ariza topilmadi', $user);
    echo '<div class="card"><p>Bunday ariza yo\'q.</p><a class="btn outline" href="/admin/arizalar.php">Arizalar</a></div>';
    hs_page_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');
    if ($action === 'holat') {
        // Ro'yxatdan tezkor o'zgartirish — izohga tegilmaydi.
        $status = hs_post('holat');
        if (isset(hs_lead_statuses()[$status]) && $status !== $lead['status']) {
            $up = hs_db()->prepare('UPDATE leads SET status = ?, updated_at = ?, updated_by = ? WHERE id = ?');
            $up->execute(array($status, hs_now(), $user['login'], $id));
            hs_audit($user['login'], 'ariza holati', "#{$id}: {$lead['status']} -> {$status}");
            hs_flash("Ariza #{$id}: " . hs_lead_statuses()[$status]);
            hs_lead_tg_sync($id);
        }
        hs_redirect(hs_safe_return(hs_post('qayt'), '/admin/arizalar.php'));
    }
    if ($action === 'saqlash') {
        $status = hs_post('holat');
        $note = mb_substr(hs_post('izoh'), 0, 2000);
        if (!isset(hs_lead_statuses()[$status])) {
            hs_flash("Holat noto'g'ri.", 'err');
        } else {
            $up = hs_db()->prepare('UPDATE leads SET status = ?, operator_note = ?, updated_at = ?, updated_by = ? WHERE id = ?');
            $up->execute(array($status, $note, hs_now(), $user['login'], $id));
            hs_audit($user['login'], 'ariza holati', "#{$id}: {$lead['status']} -> {$status}");
            hs_flash('Saqlandi.');
            if ($status !== $lead['status']) {
                hs_lead_tg_sync($id);
            }
        }
    } elseif ($action === 'telegram' && hs_is_owner($user)) {
        // Arizani qayta yuborish: hamma yoqilgan chatlarga yoki tanlangan bittasiga.
        require_once __DIR__ . '/_lib/tgchats.php';
        $to = hs_post('chat');
        if ($to === '') {
            $n = hs_tg_deliver_lead($id, 'qayta yuborildi');
        } else {
            $n = hs_tg_deliver_to($lead, $to, 'qayta yuborildi') ? 1 : 0;
        }
        if ($n > 0) {
            hs_db()->prepare('UPDATE leads SET telegram_sent = 1 WHERE id = ?')->execute(array($id));
            hs_audit($user['login'], "ariza Telegram'ga qayta yuborildi", "#{$id} -> " . ($to === '' ? "{$n} ta chat" : $to));
        }
        hs_flash($n > 0 ? "Ariza #{$id} Telegram'ga yuborildi ({$n} ta chat)." : "Yuborilmadi. Telegram bo'limida kamida bitta chat yoqilganini tekshiring.", $n > 0 ? 'ok' : 'err');
    } elseif ($action === 'ochirish' && hs_is_owner($user)) {
        hs_db()->prepare('DELETE FROM leads WHERE id = ?')->execute(array($id));
        hs_audit($user['login'], "ariza o'chirildi", "#{$id} ({$lead['phone']})");
        hs_flash("Ariza #{$id} butunlay o'chirildi.");
        hs_redirect('/admin/arizalar.php');
    }
    hs_redirect('/admin/ariza.php?id=' . $id);
}

$branches = hs_branch_names();
hs_page_start('Ariza #' . $id, $user);

echo '<div class="grid grid-2"><section class="card"><h2>Mijoz</h2><dl class="kv">';
echo '<dt>Ism</dt><dd>' . h($lead['name']) . '</dd>';
$digits = preg_replace('/\D/', '', $lead['phone']);
echo '<dt>Telefon</dt><dd><span class="nowrap">' . h($lead['phone']) . '</span><div class="actions tight"><a class="btn small" href="tel:' . h($lead['phone']) . '">' . hs_icon('phone') . ' Qo\'ng\'iroq</a>';
if (strlen($digits) >= 12) {
    echo '<a class="btn outline small" href="https://t.me/+' . h($digits) . '" target="_blank" rel="noopener noreferrer">' . hs_icon('send') . ' Telegram</a>';
}
echo '</div></dd>';
echo '<dt>Filial</dt><dd>' . h($lead['branch'] !== '' ? (isset($branches[$lead['branch']]) ? $branches[$lead['branch']] : $lead['branch']) : 'tanlanmagan') . '</dd>';
echo '<dt>So\'rovi</dt><dd>' . ($lead['note'] !== '' ? nl2br(h($lead['note'])) : '—') . '</dd>';
if ((int) $lead['special']) {
    echo '<dt>Turi</dt><dd><span class="pill st-yangi">Do\'konda yo\'q mahsulot</span></dd>';
}
echo '<dt>Vaqt</dt><dd>' . h(date('d.m.Y H:i', strtotime($lead['created_at']))) . '</dd>';
require_once __DIR__ . '/_lib/tgchats.php';
echo '<dt>Qayerdan</dt><dd>' . h(hs_tg_source_text($lead['source'], $lead['source_detail'])) . '</dd>';
if ($lead['ym_client'] !== '') {
    echo '<dt>Metrika ID</dt><dd><span class="code">' . h($lead['ym_client']) . '</span><p class="hint">Metrika\'da Vebvizor yoki "Посетители" hisobotida shu ID bo\'yicha qidirsangiz, mijoz saytda nima qilganini ko\'rasiz.</p></dd>';
}
echo '<dt>Sahifa</dt><dd>' . h($lead['page'] !== '' ? $lead['page'] : '—') . '</dd>';
echo '<dt>Telegram</dt><dd>' . ((int) $lead['telegram_sent'] ? '<span class="pill pill-ok">yuborilgan</span>' : '<span class="pill pill-err">yetib bormagan</span>') . '</dd>';
echo '</dl></section>';

echo '<form class="card" method="post" action="/admin/ariza.php">' . hs_csrf_field();
echo '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="amal" value="saqlash">';
echo '<h2>Ish jarayoni</h2><label for="holat">Holat</label><select id="holat" name="holat">';
foreach (hs_lead_statuses() as $k => $v) {
    echo '<option value="' . h($k) . '"' . ($lead['status'] === $k ? ' selected' : '') . '>' . h($v) . '</option>';
}
echo '</select><label for="izoh">Operator izohi</label><textarea id="izoh" name="izoh" maxlength="2000">' . h($lead['operator_note']) . '</textarea>';
if ($lead['updated_at']) {
    echo '<p class="hint">Oxirgi o\'zgarish: ' . h($lead['updated_by']) . ', ' . h(date('d.m.Y H:i', strtotime($lead['updated_at']))) . '</p>';
}
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button><a class="btn outline" href="/admin/arizalar.php">Ro\'yxatga qaytish</a></div></form></div>';

if (hs_is_owner($user)) {
    require_once __DIR__ . '/_lib/tgchats.php';
    $tgChats = hs_db()->query("SELECT chat_id, title, type FROM tg_chats WHERE status = 'member' ORDER BY leads DESC, title")->fetchAll();
    echo '<form class="card" method="post" action="/admin/ariza.php">' . hs_csrf_field();
    echo '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="amal" value="telegram">';
    echo '<div class="card-head"><h2>Telegram\'ga yuborish</h2><a class="btn outline small" href="/admin/telegram.php">Qabul qiluvchilar</a></div>';
    echo '<p class="muted">Ariza kimgadir yetib bormagan bo\'lsa yoki boshqa guruhga ham kerak bo\'lsa.</p>';
    echo '<div class="grid grid-2"><div><label for="tg-chat">Kimga</label><select id="tg-chat" name="chat"><option value="">Arizalar yoqilgan hamma chatlarga</option>';
    foreach ($tgChats as $tc) {
        echo '<option value="' . h($tc['chat_id']) . '">' . h($tc['title']) . ' (' . h(hs_tg_type_label($tc['type'])) . ')</option>';
    }
    echo '</select></div><div class="actions"><button class="btn outline" type="submit">' . hs_icon('send') . ' Yuborish</button></div></div></form>';

    echo '<form class="card" method="post" action="/admin/ariza.php" data-confirm="Ariza butunlay o\'chiriladi. Davom etasizmi?">' . hs_csrf_field();
    echo '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="amal" value="ochirish">';
    echo '<h2>Mijoz so\'rovi bilan o\'chirish</h2><p class="muted">Mijoz ma\'lumotlarini o\'chirishni so\'rasa (maxfiylik siyosati bo\'yicha).</p>';
    echo '<button class="btn danger" type="submit">Arizani o\'chirish</button></form>';
}

hs_page_end();
