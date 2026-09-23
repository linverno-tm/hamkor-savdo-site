<?php
/**
 * Raqobatchilar — boshqa do'konlarning ochiq Telegram kanallarini kuzatish.
 * Mantiq _lib/kuzatuv.php da. Bu sahifa faqat egasiga ko'rinadi.
 */
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/kuzatuv.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');

    if ($action === 'token') {
        $t = hs_post('token');
        // BotFather tokeni: "123456789:AA...". Mijozlar botiniki bu yerga tushmasin.
        if (!preg_match('/^\d{6,12}:[A-Za-z0-9_\-]{30,}$/', $t)) {
            hs_flash("Token noto'g'ri ko'rinishda. BotFather bergan to'liq qatorni qo'ying: 123456789:AA…", 'err');
        } elseif ($t === (string) hs_config('token', '')) {
            hs_flash("Bu mijozlar botining tokeni. Kuzatuv uchun @BotFather da ALOHIDA bot yarating.", 'err');
        } else {
            hs_set_setting('rq_token', $t);
            hs_cache_set('rq:bot', null, 1);
            hs_audit($user['login'], 'kuzatuv: bot tokeni saqlandi', '…' . substr($t, -4));
            hs_flash('Token saqlandi. Endi botni kuzatuv guruhiga qo\'shing va "Guruhni aniqlash" ni bosing.');
        }
    } elseif ($action === 'token_ochir') {
        hs_set_setting('rq_token', '');
        hs_set_setting('rq_on', '0');
        hs_cache_set('rq:bot', null, 1);
        hs_audit($user['login'], "kuzatuv: bot tokeni o'chirildi");
        hs_flash("Token o'chirildi, kuzatuv to'xtatildi.");
    } elseif ($action === 'guruh_izlash') {
        list($chats, $err) = hs_rq_find_chats();
        if ($err !== '') {
            hs_flash('Telegram javob bermadi: ' . $err, 'err');
        } elseif (!$chats) {
            hs_flash("Bot hali hech qayerda ko'rinmadi. Uni guruhga qo'shing, guruhda bitta xabar yozing va qaytadan bosing.", 'err');
        } else {
            $_SESSION['rq_chats'] = $chats;
            hs_flash(count($chats) . ' ta chat topildi — pastdan tanlang.');
        }
    } elseif ($action === 'guruh') {
        $id = hs_post('chat_id');
        $title = hs_post('chat_title');
        if ($id === '') {
            hs_flash('Guruh tanlanmadi.', 'err');
        } else {
            hs_set_setting('rq_chat_id', $id);
            hs_set_setting('rq_chat_title', $title);
            unset($_SESSION['rq_chats']);
            hs_audit($user['login'], 'kuzatuv: guruh tanlandi', $title . ' (' . $id . ')');
            hs_flash("Xulosalar «{$title}» guruhiga boradi.");
        }
    } elseif ($action === 'sozlama') {
        hs_set_setting('rq_on', hs_post('on') === '1' ? '1' : '0');
        hs_set_setting('rq_alerts', hs_post('alerts') === '1' ? '1' : '0');
        $h = (int) hs_post('digest_hour');
        hs_set_setting('rq_digest_hour', (string) ($h >= 0 && $h <= 23 ? $h : 9));
        $d = (int) hs_post('alert_discount');
        hs_set_setting('rq_alert_discount', (string) ($d >= 5 && $d <= 90 ? $d : 30));
        hs_audit($user['login'], 'kuzatuv: sozlamalar');
        hs_flash('Saqlandi.');
    } elseif ($action === 'kanal_qosh') {
        list($ok, $msg) = hs_rq_channel_add(hs_post('username'));
        if ($ok) {
            hs_audit($user['login'], 'kuzatuv: kanal qo\'shildi', hs_post('username'));
        }
        hs_flash($msg, $ok ? 'ok' : 'err');
    } elseif ($action === 'kanal_holat') {
        $st = hs_db()->prepare('UPDATE rq_channels SET active = 1 - active WHERE id = ?');
        $st->execute(array((int) hs_post('id')));
        hs_flash("Kanal holati o'zgartirildi.");
    } elseif ($action === 'kanal_ochir') {
        $st = hs_db()->prepare('SELECT username FROM rq_channels WHERE id = ?');
        $st->execute(array((int) hs_post('id')));
        $u = (string) $st->fetchColumn();
        if ($u !== '') {
            hs_db()->prepare('DELETE FROM rq_channels WHERE id = ?')->execute(array((int) hs_post('id')));
            hs_db()->prepare('DELETE FROM rq_posts WHERE channel = ?')->execute(array($u));
            hs_audit($user['login'], "kuzatuv: kanal o'chirildi", $u);
            hs_flash('@' . $u . " ro'yxatdan chiqarildi.");
        }
    } elseif ($action === 'yigish') {
        list($n, $errs) = hs_rq_fetch_all();
        $t = hs_rq_analyze(10);
        hs_flash("Yangi e'lon: {$n} ta, tahlil qilindi: {$t} ta." . ($errs ? ' Xato: ' . implode('; ', $errs) : ''), $errs ? 'err' : 'ok');
    } elseif ($action === 'qayta') {
        // Kalit tugagan yoki kunlik limit bitgan bo'lsa, e'lonlar "tahlil qilinmadi"
        // bo'lib qoladi. Bu tugma ularni navbatga qaytaradi.
        $n = hs_db()->exec("UPDATE rq_posts SET ai_error = '' WHERE ai_error <> ''");
        $t = hs_rq_analyze(10);
        hs_flash("{$n} ta e'lon navbatga qaytarildi, {$t} tasi tahlil qilindi.");
    } elseif ($action === 'kalit_yangi') {
        hs_rq_ingest_key_new();
        hs_audit($user['login'], "kuzatuv: o'quvchi dastur kaliti yangilandi");
        hs_flash("Yangi kalit yaratildi. Uni kompyuterdagi sozlama.ini ga ko'chiring - eskisi endi ishlamaydi.");
    } elseif ($action === 'sinov') {
        list($ok, $err) = hs_rq_send("Sinov xabari — HAMKOR SAVDO kuzatuv boti ishlayapti.");
        hs_flash($ok ? 'Xabar yuborildi — guruhni tekshiring.' : 'Yuborilmadi: ' . $err, $ok ? 'ok' : 'err');
    } elseif ($action === 'xulosa') {
        $ok = hs_rq_digest(true);
        hs_flash($ok ? 'Xulosa yuborildi.' : "Yuborishga yangi e'lon yo'q (yoki bot sozlanmagan).", $ok ? 'ok' : 'err');
    }
    hs_redirect('/admin/kuzatuv.php');
}

$token = hs_rq_token();
$botName = hs_rq_bot_username();
$chatId = hs_rq_chat();
$chatTitle = hs_rq_setting('chat_title');
$channels = hs_rq_channels();
$faol = hs_rq_channels(true);
$jami = (int) hs_db()->query('SELECT COUNT(*) FROM rq_posts')->fetchColumn();
$yangi = (int) hs_db()->query("SELECT COUNT(*) FROM rq_posts WHERE posted_at > '" . date('Y-m-d H:i:s', time() - 7 * 86400) . "'")->fetchColumn();
$xato = hs_db()->query("SELECT ai_error FROM rq_posts WHERE ai_error <> '' ORDER BY posted_at DESC LIMIT 1")->fetchColumn();
$lastFetch = (int) hs_setting('rq_last_fetch', '0');

hs_page_start('Raqobatchilar', $user);

$okIco = function ($ok) {
    return '<span class="state ' . ($ok ? 'on' : 'off') . '">' . hs_icon($ok ? 'check' : 'clock') . '</span>';
};

/* ---------------------------- holat ---------------------------- */
echo '<section class="card"><div class="part-head"><span class="part-ico">' . hs_icon('eye') . '</span><div><h2>Raqobatchilar kuzatuvi</h2>'
    . '<p class="muted">Boshqa do\'konlarning ochiq Telegram kanallaridagi e\'lonlar yig\'iladi, tahlil qilinadi va alohida guruhga yuboriladi</p></div></div>';
echo '<ul class="checklist">';
echo '<li>' . $okIco($token !== '') . '<div><b>' . ($token !== ''
        ? 'Kuzatuv boti ulangan' . ($botName !== '' ? ' — @' . h($botName) : '')
        : 'Kuzatuv boti ulanmagan') . '</b><small>'
    . ($token !== '' ? 'Bu bot faqat xulosa yozadi. Mijozlar botidan butunlay alohida — mijozlar guruhlariga kira olmaydi.'
        : '@BotFather da yangi bot yarating (/newbot) va tokenini pastga qo\'ying. Mijozlar botining tokeni bu yerga yaramaydi.') . '</small></div></li>';
echo '<li>' . $okIco($chatId !== '') . '<div><b>' . ($chatId !== '' ? 'Guruh: ' . h($chatTitle !== '' ? $chatTitle : $chatId) : 'Guruh tanlanmagan') . '</b><small>'
    . ($chatId !== '' ? 'Kunlik xulosa va ogohlantirishlar shu yerga boradi.'
        : 'Yopiq guruh oching, botni a\'zo qilib qo\'shing, guruhda bitta xabar yozing — keyin "Guruhni aniqlash".') . '</small></div></li>';
echo '<li>' . $okIco(count($faol) > 0) . '<div><b>' . count($faol) . ' ta kanal kuzatuvda</b><small>'
    . ($lastFetch ? 'Oxirgi yig\'ish: ' . h(date('d.m H:i', $lastFetch)) . '. ' : '')
    . 'Jami ' . $jami . ' ta e\'lon saqlangan, oxirgi 7 kunda ' . $yangi . ' ta.</small></div></li>';
$aiKey = hs_mb_provider() === 'gemini' ? hs_mb_gemini_key() : hs_mb_key();
echo '<li>' . $okIco($aiKey !== '') . '<div><b>' . ($aiKey !== '' ? 'Tahlil: ' . (hs_mb_provider() === 'gemini' ? 'Gemini' : 'Claude') : 'AI kaliti yo\'q') . '</b><small>'
    . ($aiKey !== '' ? 'Telegram bo\'limidagi o\'sha kalit ishlatiladi — alohida kalit kerak emas. <a href="/admin/telegram.php#mb_key">Kalitni ochish</a>.' . ($xato ? ' Oxirgi xato: ' . h($xato) : '')
        : 'Kalitsiz e\'lonlar yig\'iladi, lekin tahlil qilinmaydi. Kalit: <a href="/admin/telegram.php#mb_key">Telegram bo\'limida</a>.') . '</small></div></li>';
echo '</ul>';

if (hs_rq_on()) {
    echo '<div class="actions">';
    echo '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="yigish"><button class="btn outline small" type="submit">Hozir yig\'ish</button></form>';
    echo '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="xulosa"><button class="btn outline small" type="submit">Xulosani hozir yuborish</button></form>';
    echo '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sinov"><button class="btn outline small" type="submit">Sinov xabari</button></form>';
    if ($xato) {
        echo '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qayta"><button class="btn outline small" type="submit">Tahlilni qayta urinish</button></form>';
    }
    echo '</div>';
}
echo '</section>';

/* ---------------------------- bot va guruh ---------------------------- */
echo '<section class="card"><div class="card-head"><h2>Bot va guruh</h2></div>';
echo '<p class="hint">Bu bo\'lim uchun <b>alohida</b> bot kerak. Telegram\'da @BotFather ga <code>/newbot</code> yozing, nom va username bering — u sizga token beradi.</p>';
echo '<div class="grid grid-2">';
echo '<form method="post" autocomplete="off">' . hs_csrf_field() . '<input type="hidden" name="amal" value="token">'
    . '<label for="token">Bot tokeni' . ($token !== '' ? ' (almashtirish)' : '') . '</label>'
    . '<input id="token" type="password" name="token" required maxlength="120" autocomplete="off" spellcheck="false" placeholder="123456789:AA…">'
    . '<p class="hint">Faqat serverdagi bazada saqlanadi, sahifada qayta ko\'rsatilmaydi.</p>'
    . '<div class="actions"><button class="btn outline small" type="submit">Tokenni saqlash</button></div></form>';
if ($token !== '') {
    echo '<form method="post" class="actions" data-confirm="Kuzatuv botining tokeni o\'chirilsinmi?">' . hs_csrf_field()
        . '<input type="hidden" name="amal" value="token_ochir"><button class="btn danger small" type="submit">Tokenni o\'chirish</button></form>';
}
echo '</div>';

if ($token !== '') {
    echo '<form method="post" class="actions">' . hs_csrf_field() . '<input type="hidden" name="amal" value="guruh_izlash">'
        . '<button class="btn outline small" type="submit">Guruhni aniqlash</button></form>';
    $topilgan = isset($_SESSION['rq_chats']) ? $_SESSION['rq_chats'] : array();
    if ($topilgan) {
        echo '<div class="table-wrap"><table><thead><tr><th>Chat</th><th>Turi</th><th></th></tr></thead><tbody>';
        foreach ($topilgan as $c) {
            echo '<tr><td>' . h($c['title']) . '</td><td>' . h($c['type']) . '</td><td>'
                . '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="guruh">'
                . '<input type="hidden" name="chat_id" value="' . h($c['id']) . '"><input type="hidden" name="chat_title" value="' . h($c['title']) . '">'
                . '<button class="btn outline small" type="submit">Tanlash</button></form></td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
echo '</section>';

/* -------------------- kompyuterdagi o'quvchi dastur -------------------- */
$guruhSoni = 0;
foreach ($channels as $c) {
    if ($c["kind"] === "guruh") {
        $guruhSoni++;
    }
}
$ingestKey = hs_rq_ingest_key();
$ingestAt = hs_setting("rq_ingest_at", "");
echo '<section class="card"><div class="card-head"><h2>Guruhlarni o&rsquo;qish</h2></div>';
echo "<p class=\"hint\">Telegram boti faqat O'ZI a'zo bo'lgan chatni ko'ra oladi - bu Telegram qoidasi. "
    . "Shuning uchun raqobatchilarning do'kon guruhlarini (model va oylik to'lov aynan o'sha yerda yoziladi) bot o'qiy olmaydi. "
    . "Ularni do'kondagi kompyuterda ishlaydigan kichik dastur o'qiydi va shu yerga yuboradi. "
    . "Ochiq kanallar uchun bu dastur kerak emas - ularni panelning o'zi oladi.</p>";
echo '<ul class="checklist">';
echo "<li>" . $okIco($guruhSoni > 0) . "<div><b>{$guruhSoni} ta guruh ro'yxatda</b><small>"
    . "Guruhni pastdagi maydonga oddiy nom bilan qo'shasiz (masalan shahrixon_imkon) - panel uni guruh deb tanib oladi.</small></div></li>";
echo "<li>" . $okIco($ingestAt !== "") . "<div><b>" . ($ingestAt !== "" ? "Oxirgi qabul: " . h(date("d.m H:i", strtotime($ingestAt))) : "Dasturdan hali xabar kelmagan") . "</b><small>"
    . "Dastur kompyuter yoqilganda o'tkazib yuborilgan xabarlarni ham olib keladi.</small></div></li>";
echo "</ul>";
if ($ingestKey === "") {
    echo "<p class=\"muted\">Kalit hali yaratilmagan. Dastur ishlashi uchun kalit kerak.</p>";
} else {
    echo "<label for=\"ikey\">O'quvchi dastur kaliti</label>"
        . "<input id=\"ikey\" type=\"text\" value=\"" . h($ingestKey) . "\" readonly onclick=\"this.select()\">"
        . "<p class=\"hint\">Shuni kompyuterdagi <code>sozlama.ini</code> faylidagi <code>kalit</code> qatoriga qo'ying. "
        . "Kalitni hech kimga bermang - u bilan saytga e'lon yuborish mumkin.</p>";
}
echo '<form method="post" class="actions"' . ($ingestKey !== "" ? ' data-confirm="Yangi kalit yaratilsinmi? Eski kalit ishlamay qoladi va dasturni qayta sozlash kerak."' : '') . '>'
    . hs_csrf_field() . '<input type="hidden" name="amal" value="kalit_yangi">'
    . '<button class="btn outline small" type="submit">' . ($ingestKey === "" ? "Kalit yaratish" : "Kalitni yangilash") . '</button></form>';
echo "<p class=\"hint\">O'rnatish tartibi: <code>tools/kuzatuv-oquvchi/README.md</code>.</p>";
echo '</section>';

/* ---------------------------- sozlamalar ---------------------------- */
echo '<section class="card"><div class="card-head"><h2>Sozlamalar</h2></div>';
echo '<form method="post">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sozlama"><div class="grid grid-2">';
echo '<div><label for="on">Kuzatuv</label><select id="on" name="on">'
    . '<option value="0"' . (hs_rq_setting('on') !== '1' ? ' selected' : '') . ">O'chirilgan</option>"
    . '<option value="1"' . (hs_rq_setting('on') === '1' ? ' selected' : '') . '>Yoqilgan</option>'
    . '</select><p class="hint">Yoqilganda har yarim soatda yangi e\'lonlar yig\'iladi.</p></div>';
echo '<div><label for="digest_hour">Kunlik xulosa soati</label><input id="digest_hour" type="number" name="digest_hour" min="0" max="23" value="' . h(hs_rq_setting('digest_hour')) . '">'
    . '<p class="hint">Shu soatdan keyin kuniga bir marta bitta umumiy xabar keladi.</p></div>';
echo '<div><label for="alerts">Darhol ogohlantirish</label><select id="alerts" name="alerts">'
    . '<option value="1"' . (hs_rq_setting('alerts') === '1' ? ' selected' : '') . '>Yoqilgan</option>'
    . '<option value="0"' . (hs_rq_setting('alerts') !== '1' ? ' selected' : '') . ">O'chirilgan</option>"
    . '</select><p class="hint">Faqat jiddiy e\'lonlar: katta chegirma, muddatli to\'lov sharti, yangi do\'kon.</p></div>';
echo '<div><label for="alert_discount">Qaysi chegirmadan boshlab jiddiy</label><input id="alert_discount" type="number" name="alert_discount" min="5" max="90" value="' . h(hs_rq_setting('alert_discount')) . '">'
    . '<p class="hint">Foizda. Bundan pastlari faqat kunlik xulosaga tushadi.</p></div>';
echo '</div><div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';
echo '</section>';

/* ---------------------------- kanallar ---------------------------- */
echo '<section class="card"><div class="card-head"><h2>Kuzatilayotgan kanallar</h2></div>';
echo '<p class="hint">Faqat <b>ochiq</b> kanallar. Yopiq guruh yoki kanalni kuzatib bo\'lmaydi — u yerga kirish uchun odamning akkaunti kerak bo\'lardi, bu yo\'l ataylab qo\'shilmagan.</p>';
if ($channels) {
    echo '<div class="table-wrap"><table><thead><tr><th>Manba</th><th>Turi</th><th>E\'lonlar</th><th>Oxirgi yig\'ish</th><th>Holat</th><th></th></tr></thead><tbody>';
    foreach ($channels as $c) {
        $st = hs_db()->prepare('SELECT COUNT(*) FROM rq_posts WHERE channel = ?');
        $st->execute(array($c['username']));
        echo '<tr><td><a href="https://t.me/' . h($c['username']) . '" target="_blank" rel="noopener noreferrer">'
            . h($c['title'] !== '' ? $c['title'] : '@' . $c['username']) . '</a><small class="muted"> @' . h($c['username']) . '</small></td>'
            . "<td>" . ($c["kind"] === "guruh" ? "Guruh - dastur o'qiydi" : "Kanal - avtomatik") . "</td>"
            . '<td>' . (int) $st->fetchColumn() . '</td>'
            . '<td>' . h($c['last_fetch'] !== '' ? date('d.m H:i', strtotime($c['last_fetch'])) : '—')
            . ($c['last_error'] !== '' ? '<br><small class="muted">' . h($c['last_error']) . '</small>' : '') . '</td>'
            . '<td>' . ((int) $c['active'] ? 'Faol' : "To'xtatilgan") . '</td><td>'
            . '<form method="post" class="inline-form">' . hs_csrf_field() . '<input type="hidden" name="amal" value="kanal_holat"><input type="hidden" name="id" value="' . (int) $c['id'] . '">'
            . '<button class="btn outline small" type="submit">' . ((int) $c['active'] ? "To'xtatish" : 'Yoqish') . '</button></form> '
            . '<form method="post" class="inline-form" data-confirm="' . h($c['username']) . ' va uning e\'lonlari o\'chirilsinmi?">' . hs_csrf_field()
            . '<input type="hidden" name="amal" value="kanal_ochir"><input type="hidden" name="id" value="' . (int) $c['id'] . '">'
            . '<button class="btn danger small" type="submit">O\'chirish</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
} else {
    echo '<p class="muted">Hali kanal qo\'shilmagan. Masalan: <code>ishonch</code>, <code>imkon</code>, <code>elmakon</code>.</p>';
}
echo '<form method="post" class="grid grid-2">' . hs_csrf_field() . '<input type="hidden" name="amal" value="kanal_qosh">';
echo '<div><label for="username">Kanal</label><input id="username" type="text" name="username" required maxlength="80" placeholder="ishonch yoki t.me/ishonch" autocomplete="off">'
    . '<p class="hint">Qo\'shishdan oldin kanal ochiqligi tekshiriladi.</p>'
    . '<div class="actions"><button class="btn" type="submit">Qo\'shish</button></div></div></form>';
echo '</section>';

/* ---------------------------- oxirgi e'lonlar ---------------------------- */
$rows = hs_db()->query('SELECT * FROM rq_posts ORDER BY posted_at DESC LIMIT 40')->fetchAll();
echo '<section class="card"><div class="card-head"><h2>Oxirgi e\'lonlar</h2></div>';
if (!$rows) {
    echo '<p class="muted">Hali e\'lon yig\'ilmagan. Kanal qo\'shib, "Hozir yig\'ish" ni bosing.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Sana</th><th>Kanal</th><th>Xulosa</th><th>Chegirma</th><th>Muddatli to\'lov</th><th></th></tr></thead><tbody>';
    foreach ($rows as $p) {
        $sarlavha = $p['summary'] !== '' ? $p['summary'] : mb_substr(preg_split('/\R/u', trim($p['text']))[0], 0, 90);
        echo '<tr><td>' . h(date('d.m H:i', strtotime($p['posted_at']))) . '</td>'
            . '<td>' . h(hs_rq_channel_title($p['channel'])) . '</td>'
            . '<td>' . ((int) $p['important'] ? '⚡ ' : '') . h($sarlavha)
            . ($p['ai_error'] !== '' ? '<br><small class="muted">tahlil qilinmadi: ' . h($p['ai_error']) . '</small>'
                : (!(int) $p['analyzed'] ? '<br><small class="muted">tahlil navbatda</small>' : ''))
            . ($p['media'] !== '' ? '<br><small class="muted">' . h($p['media']) . ' — narx faqat shu yerda bo\'lishi mumkin</small>' : '') . '</td>'
            . '<td>' . ((int) $p['discount'] > 0 ? (int) $p['discount'] . '%' : '—') . '</td>'
            . '<td>' . h($p['instalment'] !== '' ? $p['instalment'] : '—') . '</td>'
            . '<td><a class="btn outline small" href="' . h($p['url']) . '" target="_blank" rel="noopener noreferrer">Ochish</a></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

hs_page_end();
