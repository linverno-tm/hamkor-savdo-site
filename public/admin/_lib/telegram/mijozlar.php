<?php
/* Telegram sahifasining bir bo'limi — faqat admin/telegram.php dan chaqiriladi. */
if (!defined('HS_ADMIN')) {
    exit;
}

/* ---- mijozlar guruhi yordamchisi ---- */
$mbGroup = null;
$mbChannel = null;
foreach ($chats as $c) {
    if ($c['role'] === 'mijozlar' && $c['status'] === 'member' && !$mbGroup) {
        $mbGroup = $c;
    } elseif ($c['role'] === 'katalog' && $c['status'] === 'member' && !$mbChannel) {
        $mbChannel = $c;
    }
}
$mbAdmin = null;
if ($mbGroup && $info['me']) {
    $mbAdmin = hs_cache_get('mb:botstatus:' . $mbGroup['chat_id']);
    if (!is_string($mbAdmin)) {
        list($okM, $mem) = hs_tg_api('getChatMember', array('chat_id' => $mbGroup['chat_id'], 'user_id' => $info['me']['id']));
        $mbAdmin = $okM && isset($mem['status']) ? (string) $mem['status'] : '';
        hs_cache_set('mb:botstatus:' . $mbGroup['chat_id'], $mbAdmin, 300);
    }
}
$mbKey = hs_mb_key();
$mbCount = hs_mb_catalog_count();
$mbStats = hs_db()->query("SELECT COUNT(*) AS jami, SUM(status = 'javob') AS javob, SUM(status = 'kuzatish') AS kuzatish, SUM(needs_operator = 1) AS operator, SUM(answered_by <> '') AS xodim, SUM(status = 'xodimda') AS xodimda, SUM(lead_id IS NOT NULL) AS ariza
    FROM mb_questions WHERE created_at > '" . date('Y-m-d H:i:s', time() - 7 * 86400) . "' AND status <> 'tashlandi'")->fetch();
echo '<section class="card"><div class="part-head"><span class="part-ico">' . hs_icon('users') . '</span><div><h2>Mijozlar guruhi yordamchisi</h2>'
    . '<p class="muted">Guruhdagi "X bormi? narxi?" savollariga bot kanal postlari bilan javob beradi, aniq javob kerak bo\'lsa — xodimlarga yuboradi</p></div></div>';
echo '<ul class="checklist">';
$groupAt = '@' . h(hs_mb_setting('group'));
echo '<li>' . $okIco($mbGroup && ($mbAdmin === 'administrator' || $mbAdmin === 'creator')) . '<div><b>'
    . ($mbGroup ? 'Guruh: ' . h($mbGroup['title']) . ($mbAdmin === 'administrator' || $mbAdmin === 'creator' ? ' — bot admin' : ' — bot admin EMAS') : 'Bot ' . $groupAt . ' guruhida hali yo\'q') . '</b><small>'
    . ($mbGroup ? ($mbAdmin === 'administrator' || $mbAdmin === 'creator' ? 'Bot barcha xabarlarni ko\'radi. Arizalar (mijozlar raqami) bu guruhga yuborilmaydi.' : 'Barcha xabarlarni ko\'rishi uchun botni guruhda admin qiling (qo\'shimcha huquq shart emas).')
        : 'Botni ' . $groupAt . ' guruhiga qo\'shing va admin qiling. Guruh o\'zi "mijozlar guruhi" deb belgilanadi — unga salom ham, ariza ham yuborilmaydi.') . '</small></div></li>';
$faol = hs_mb_setting('mode') === 'faol';
echo '<li>' . $okIco(true) . '<div><b>' . (hs_mb_setting('on') !== '1' ? "Rejim: o'chirilgan" : ($faol ? 'Rejim: ✅ Faol — bot guruhga javob yozadi' : "Rejim: 🧪 Kuzatish — bot guruhga yozmaydi")) . '</b><small>'
    . ($faol ? "Aniq javob kerak bo'lsa, savol xodimlarga ham keladi." : "Har bir savol va botning javob loyihasi xodimlar chatiga keladi. Javoblar to'g'ri ekaniga ishonch hosil qilgach, pastda \"Faol\" ni tanlang.") . '</small></div></li>';
echo '<li>' . $okIco($mbCount > 0) . '<div><b>Katalog: ' . $mbCount . ' ta post</b><small>'
    . 'Manba: @' . h(hs_mb_setting('channel')) . ($mbChannel ? ' (bot kanalda — yangi postlar o\'zi tushadi)' : ' (bot kanalda emas — postlar har 6 soatda kanalning ochiq sahifasidan yig\'iladi; darhol tushishi uchun botni kanalga admin qiling)')
    . ' va guruhdagi xodimlarning narxli postlari.' . (hs_setting('mb_backfill_at', '') !== '' ? ' Oxirgi yig\'ish: ' . h(date('d.m H:i', strtotime(hs_setting('mb_backfill_at')))) . '.' : '') . '</small></div></li>';
$gemini = hs_mb_provider() === 'gemini';
$aiKey = $gemini ? hs_mb_gemini_key() : $mbKey;
$aiNom = $gemini ? 'Gemini' : 'Claude';
$aiModel = $gemini ? hs_mb_gemini_model() : hs_mb_setting('model');
echo '<li>' . $okIco($aiKey !== '') . '<div><b>' . ($aiKey !== '' ? 'AI: ' . h($aiNom) . ' · ' . h($aiModel) : 'AI kaliti kiritilmagan') . '</b><small>'
    . ($aiKey !== '' ? 'Savollarni lotin/kirill, xato yozuvda ham tushunadi. ' : "Kalitsiz bot oddiy so'z qidiruvi bilan ishlaydi va har savolni operatorga yuboradi. ")
    . '<a href="/admin/telegram.php?bolim=ai">' . ($aiKey !== '' ? 'AI kalitini boshqarish' : 'Kalitni kiritish') . '</a></small></div></li>';
echo '<li>' . $okIco(true) . '<div><b>Oxirgi 7 kun: ' . (int) $mbStats['jami'] . ' ta savol</b><small>'
    . ((int) $mbStats['kuzatish'] ? 'Kuzatishda (guruhga yozilmadi): ' . (int) $mbStats['kuzatish'] . ' · ' : '')
    . 'Bot javob berdi: ' . (int) $mbStats['javob'] . ' · operator kerak: ' . (int) $mbStats['operator'] . ' · xodim javob berdi: ' . (int) $mbStats['xodim'] . ' · xodim bilan suhbatda (bot aralashmadi): ' . (int) $mbStats['xodimda'] . ' · raqam qoldirdi (ariza): ' . (int) $mbStats['ariza'] . '</small></div></li>';
echo '</ul>';

echo '<form method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_sozlama">';
echo '<label class="switch toggle-row"><input type="hidden" name="mb_on" value="0"><input type="checkbox" name="mb_on" value="1"' . (hs_mb_setting('on') === '1' ? ' checked' : '') . '><span class="switch-ui" aria-hidden="true"></span><span>Guruhdagi savollarga javob berish</span></label>';
echo '<div class="grid grid-4">';
echo '<div class="span-2"><label for="mb_mode">Rejim</label><select id="mb_mode" name="mb_mode"><option value="kuzatish"' . (hs_mb_setting('mode') !== 'faol' ? ' selected' : '') . '>🧪 Kuzatish — guruhga yozmaydi, javob loyihasini xodimlarga ko\'rsatadi</option><option value="faol"' . (hs_mb_setting('mode') === 'faol' ? ' selected' : '') . '>✅ Faol — guruhdagi savolga o\'zi javob beradi</option></select><p class="hint">Avval bir necha kun kuzating: bot javoblari xodimlar chatiga keladi. To\'g\'ri bo\'lsa — Faol.</p></div>';
echo '<div><label for="mb_group">Mijozlar guruhi</label><input id="mb_group" type="text" name="mb_group" maxlength="80" value="@' . h(hs_mb_setting('group')) . '"></div>';
echo '<div><label for="mb_channel">Mahsulot kanali</label><input id="mb_channel" type="text" name="mb_channel" maxlength="80" value="@' . h(hs_mb_setting('channel')) . '"></div>';
echo '<div><label for="mb_notify">Xodimlarga yuborish</label><select id="mb_notify" name="mb_notify"><option value="kerak"' . (hs_mb_setting('notify') === 'kerak' ? ' selected' : '') . '>Operator kerak bo\'lganda</option><option value="hammasi"' . (hs_mb_setting('notify') === 'hammasi' ? ' selected' : '') . '>Har bir savol</option></select><p class="hint">Arizalar keladigan "barcha filiallar" chatlariga.</p></div>';
echo '<div><label for="mb_remind_m">Javobsiz eslatma (daqiqa)</label><input id="mb_remind_m" type="number" name="mb_remind_m" min="5" max="240" value="' . h(hs_mb_setting('remind_m')) . '"></div>';
echo '<div><label for="mb_wait_s">Xodimga imkon (soniya)</label><input id="mb_wait_s" type="number" name="mb_wait_s" min="0" max="180" value="' . h(hs_mb_setting('wait_s')) . '">'
    . "<p class=\"hint\">Yangi savolga bot shuncha kutadi. Shu orada xodim javob bersa, bot yozmaydi. Mijoz xodim bilan gaplashayotgan bo'lsa, bot umuman aralashmaydi. 0 — darhol.</p></div>";
echo '</div><div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';

echo '<div class="grid grid-2">';
echo '<form method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_sinov">'
    . '<label for="savol">Sinab ko\'rish (guruhga hech narsa yuborilmaydi)</label><input id="savol" type="text" name="savol" maxlength="500" placeholder="masalan: Assalomu alaykum planshetlar ham bormi">'
    . '<div class="actions"><button class="btn outline small" type="submit"' . ($aiKey === '' ? ' disabled' : '') . '>Sinash</button></div></form>';
echo '</div>';
echo '<form method="post" action="/admin/telegram.php" class="actions">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_katalog"><button class="btn outline small" type="submit">🔄 Katalogni kanaldan yangilash</button></form>';

$recent = hs_db()->query("SELECT * FROM mb_questions WHERE status <> 'tashlandi' ORDER BY id DESC LIMIT 15")->fetchAll();
if ($recent) {
    echo '<h3>Oxirgi savollar</h3><div class="table-wrap"><table><thead><tr><th>Vaqt</th><th>Mijoz</th><th>Savol</th><th>Bot javobi</th><th>Holat</th></tr></thead><tbody>';
    foreach ($recent as $q) {
        if ($q['answered_by'] !== '') {
            $state = '<span class="pill st-sotildi">✅ ' . h($q['answered_by']) . '</span>';
        } elseif ((int) $q['lead_id'] > 0) {
            $state = '<span class="pill st-qongiroq">📞 ariza #' . (int) $q['lead_id'] . '</span>';
        } elseif ($q['status'] === 'xato') {
            $state = '<span class="pill pill-err">xato</span>';
        } elseif ($q['status'] === 'xodimda') {
            $state = '<span class="pill st-qongiroq">👤 xodim bilan suhbatda</span>';
        } elseif ($q['status'] === 'kuzatish') {
            $state = '<span class="pill st-yangi">🧪 kuzatishda</span>';
        } elseif ((int) $q['needs_operator']) {
            $state = '<span class="pill st-yangi">operator kerak</span>';
        } else {
            $state = '<span class="pill st-sotildi">bot javob berdi</span>';
        }
        echo '<tr><td>' . h(date('d.m H:i', strtotime($q['created_at']))) . '</td><td>' . h($q['user_name']) . '</td><td>' . h(mb_substr($q['text'], 0, 120)) . '</td><td><small>' . h(mb_substr($q['reply'], 0, 140)) . ($q['post_ids'] !== '' ? ' · ' . count(explode(',', $q['post_ids'])) . ' post' : '') . ($q['error'] !== '' ? '<br><span class="closed-note">' . h(mb_substr($q['error'], 0, 120)) . '</span>' : '') . '</small></td><td>' . $state . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';
