<?php
/* Telegram sahifasining bir bo'limi — faqat admin/telegram.php dan chaqiriladi. */
if (!defined('HS_ADMIN')) {
    exit;
}

/* ---- bot holati ---- */
echo '<section class="card"><div class="card-head"><h2>Bot</h2>';
if ($botName !== '') {
    echo '<a class="btn outline small" href="https://t.me/' . h($botName) . '" target="_blank" rel="noopener noreferrer">' . hs_icon('send') . ' @' . h($botName) . '</a>';
}
echo '</div>';
if (!$info['me']) {
    echo '<p class="flash flash-err">Bot javob bermadi: ' . h($info['meError']) . '</p>';
} else {
    echo '<ul class="checklist">';
    echo '<li><span class="state ' . ($connected ? 'on' : 'off') . '">' . hs_icon($connected ? 'check' : 'clock') . '</span><div><b>'
        . ($connected ? 'Ulangan — yangi chatlar ro\'yxatga o\'zi tushadi' : ($otherHook ? 'Bot boshqa manzilga ulangan' : 'Hali ulanmagan')) . '</b><small>'
        . ($connected ? 'Bot guruhga qo\'shilishi yoki unga /start yozilishi bilan shu yerda paydo bo\'ladi.' : 'Ulanmaguncha yangi guruh va odamlar ro\'yxatga o\'zi tushmaydi (qo\'lda qo\'shish ishlaydi).')
        . '</small></div></li>';
    if ($wh && !empty($wh['last_error_message'])) {
        echo '<li><span class="state off">' . hs_icon('x') . '</span><div><b>Telegram oxirgi marta yetkaza olmadi</b><small>'
            . h($wh['last_error_message']) . (!empty($wh['last_error_date']) ? ' · ' . h(date('d.m H:i', (int) $wh['last_error_date'])) : '') . '</small></div></li>';
    }
    $adminNames = array();
    foreach ($chats as $c) {
        if ((int) $c['admin'] === 1 && $c['status'] === 'member') {
            $adminNames[] = $c['title'];
        }
    }
    echo '<li><span class="state ' . ($adminNames ? 'on' : 'off') . '">' . hs_icon($adminNames ? 'check' : 'clock') . '</span><div><b>'
        . ($adminNames ? 'Boshqaruvchi: ' . h(implode(', ', $adminNames)) : 'Boshqaruvchi tanlanmagan') . '</b><small>'
        . ($adminNames ? 'Yangi odam yoki guruh qo\'shilsa, Telegram\'da tugmali so\'rov keladi. Botga /royxat yozsangiz — hamma chatlarni o\'sha yerda yoqib-o\'chirasiz.' : 'So\'rovlar hozircha secrets.php dagi chatga boradi. Pastdagi ro\'yxatdan o\'zingizni "★ Boshqaruvchi" qiling.')
        . '</small></div></li>';
    echo '<li><span class="state ' . ($active ? 'on' : 'off') . '">' . hs_icon($active ? 'check' : 'clock') . '</span><div><b>Arizalar '
        . ($active ? $active . ' ta chatga boradi' : 'hech kimga bormayapti') . '</b><small>'
        . ($active ? 'Pastdagi ro\'yxatda yoqilganlar.' : 'Ariza baribir panelda saqlanadi, lekin Telegram\'ga xabar ketmaydi. Pastda kamida bitta chatni yoqing.') . '</small></div></li>';
    echo '</ul>';
    echo '<form method="post" action="/admin/telegram.php" class="actions"' . ($otherHook ? ' data-confirm="Bot hozir boshqa manzilga ulangan (' . h($wh['url']) . '). Shu saytga o\'tkazilsinmi?"' : '') . '>'
        . hs_csrf_field() . '<input type="hidden" name="amal" value="ulash">'
        . '<button class="btn' . ($connected ? ' outline small' : '') . '" type="submit">' . ($connected ? 'Qayta ulash' : 'Botni ulash') . '</button></form>';
}
echo '</section>';


/* ---- chatlar ---- */
echo '<section class="card"><div class="card-head"><h2>Arizalar kimga boradi</h2><span class="muted">' . count($chats) . ' ta chat</span></div>';
if (!$chats) {
    echo '<p class="empty">Ro\'yxat bo\'sh. Pastdagi yo\'riqnoma bo\'yicha odam yoki guruh qo\'shing.</p>';
} else {
    echo '<div class="chat-list">';
    foreach ($chats as $c) {
        $cid = h($c['chat_id']);
        $on = (int) $c['leads'] === 1;
        $left = $c['status'] !== 'member';
        echo '<div class="chat-row' . ($on && !$left ? ' is-on' : '') . ($left ? ' is-left' : '') . '">';
        echo '<div class="chat-main"><div class="chat-title"><b>' . h($c['title'] !== '' ? $c['title'] : $c['chat_id']) . '</b> <span class="pill ' . ($c['type'] === 'private' ? 'st-qongiroq' : 'st-rad') . '">' . h(hs_tg_type_label($c['type'])) . '</span>';
        if ($left) {
            echo ' <span class="pill pill-err">bot chiqarilgan</span>';
        }
        if ((int) $c['admin'] === 1) {
            echo ' <span class="pill admin-pill">★ Boshqaruvchi</span>';
        }
        if ($c['role'] !== '') {
            echo ' <span class="pill st-yangi">' . ($c['role'] === 'mijozlar' ? '👥 Mijozlar guruhi' : '📣 Mahsulot kanali') . '</span>';
        }
        echo '</div><small>' . ($c['username'] !== '' ? '@' . h($c['username']) . ' · ' : '') . 'ID ' . $cid
            . ($c['last_sent_at'] ? ' · oxirgi xabar ' . h(date('d.m H:i', strtotime($c['last_sent_at']))) : '') . '</small>';
        if ($c['last_error'] !== '') {
            echo '<small class="closed-note">Xato: ' . h($c['last_error']) . '</small>';
        }
        echo '</div>';

        echo '<div class="chat-controls">';
        // Yoqish tugmasi: yashirin 0 + checkbox 1 — belgilanmasa 0 yuboriladi.
        echo '<form method="post" action="/admin/telegram.php" class="switch-form">' . hs_csrf_field()
            . '<input type="hidden" name="amal" value="arizalar"><input type="hidden" name="id" value="' . $cid . '"><input type="hidden" name="leads" value="0">'
            . '<label class="switch"' . ($c['role'] !== '' ? ' title="Mijozlar guruhi / mahsulot kanaliga arizalar yuborilmaydi"' : '') . '><input type="checkbox" name="leads" value="1" data-autosubmit' . ($on ? ' checked' : '') . ($left || $c['role'] !== '' ? ' disabled' : '') . '><span class="switch-ui" aria-hidden="true"></span><span>Arizalar</span></label>'
            . '<button class="btn outline small js-hide" type="submit">OK</button></form>';

        if ($c['type'] !== 'private') {
            echo '<form method="post" action="/admin/telegram.php" class="branch-form">' . hs_csrf_field()
                . '<input type="hidden" name="amal" value="rol"><input type="hidden" name="id" value="' . $cid . '">'
                . '<select name="rol" data-autosubmit aria-label="' . h($c['title']) . ' — roli">';
            foreach (array('' => 'Xodimlar chati (arizalar mumkin)', 'mijozlar' => '👥 Mijozlar guruhi — bot javob beradi', 'katalog' => '📣 Mahsulot kanali — katalog') as $rv => $rl) {
                echo '<option value="' . h($rv) . '"' . ($c['role'] === $rv ? ' selected' : '') . '>' . h($rl) . '</option>';
            }
            echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';
        }

        // Mijozlar guruhi / mahsulot kanaliga ariza bormaydi — filial tanlovi ham kerak emas.
        if ($c['role'] === '') {
            echo '<form method="post" action="/admin/telegram.php" class="branch-form">' . hs_csrf_field()
                . '<input type="hidden" name="amal" value="filial"><input type="hidden" name="id" value="' . $cid . '">'
                . '<select name="filial" data-autosubmit aria-label="' . h($c['title']) . ' — qaysi filial arizalari"><option value="">Barcha filiallar</option>';
            foreach ($branches as $k => $v) {
                echo '<option value="' . h($k) . '"' . ((string) $c['branch'] === (string) $k ? ' selected' : '') . '>📍 Filial rahbari: ' . h($v) . '</option>';
            }
            echo '</select><button class="btn outline small js-hide" type="submit">OK</button></form>';
        }

        echo '<div class="actions tight">';
        if ($c['type'] === 'private' && !$left) {
            $isAdm = (int) $c['admin'] === 1;
            echo '<form class="inline-form" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="admin"><input type="hidden" name="id" value="' . $cid . '"><input type="hidden" name="admin" value="' . ($isAdm ? '0' : '1') . '">'
                . '<button class="btn outline small" type="submit" title="' . ($isAdm ? 'Ruxsat so\'rovlari endi unga kelmaydi' : 'Ruxsat so\'rovlari Telegram\'da shu odamga keladi') . '">' . ($isAdm ? '☆ Boshqaruvchilikdan olish' : '★ Boshqaruvchi') . '</button></form>';
        }
        // Mijozlar guruhi va mahsulot kanalini yuzlab mijoz o'qiydi — u yerga
        // "Sinov xabari — admin paneli, arizalar o'chirilgan" chiqmasligi kerak.
        if ($c['role'] === '') {
            echo '<form class="inline-form" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sinov"><input type="hidden" name="id" value="' . $cid . '"><button class="btn outline small" type="submit"' . ($left ? ' disabled' : '') . '>Sinov xabari</button></form>';
        }
        echo '<form class="inline-form" method="post" action="/admin/telegram.php" data-confirm="«' . h($c['title']) . '» ro\'yxatdan olib tashlansinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ochirish"><input type="hidden" name="id" value="' . $cid . '"><button class="btn danger small" type="submit">O\'chirish</button></form>';
        echo '</div></div></div>';
    }
    echo '</div>';
}
echo '</section>';


/* ---- qanday qo'shiladi ---- */
$botLink = $botName !== '' ? '<a href="https://t.me/' . h($botName) . '" target="_blank" rel="noopener noreferrer">@' . h($botName) . '</a>' : 'botni';
echo '<div class="grid grid-2">';
echo '<section class="card"><h2>Qanday qo\'shiladi</h2><ol class="steps">';
echo '<li><b>Odam (operator, menejer).</b> U Telegram\'da ' . $botLink . ' ni ochib <b>Start</b> ni bossin. Sizga Telegram\'da tugmali so\'rov keladi — "✅ Ruxsat" ni bossangiz bo\'ldi, saytga kirish shart emas.</li>';
echo '<li><b>Guruh (yopiq bo\'lsa ham).</b> Botni guruhga a\'zo qilib qo\'shing. Guruh shu yerda o\'zi chiqadi, sizga Telegram\'da xabar ham keladi.</li>';
echo '<li><b>Bot avvaldan turgan guruh.</b> Ro\'yxatda ko\'rinmasa, guruhga <span class="code">/start' . ($botName !== '' ? '@' . h($botName) : '') . '</span> deb yozing.</li>';
echo '<li><b>Filial guruhi.</b> "Barcha filiallar" o\'rniga filialni tanlang — guruhga faqat o\'sha filial arizalari boradi.</li>';
echo '<li><b>Botning o\'zidan boshqarish.</b> Botga <span class="code">/royxat</span> deb yozing — hamma chatlar tugma bo\'lib chiqadi, bosib yoqasiz yoki o\'chirasiz. Bu faqat boshqaruvchilarda ishlaydi.</li>';
echo '</ol><p class="hint">Yangi chat avtomatik yoqilmaydi: botni kimdir begona guruhga qo\'shsa ham, mijozlar raqami u yerga ketmaydi.</p></section>';

echo '<form class="card" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="qoshish">';
echo '<h2>Qo\'lda qo\'shish</h2><p class="muted">Chat ID ni bilsangiz yoki guruh/kanal ochiq bo\'lsa.</p>';
echo '<label for="chat">Chat ID yoki @nom</label><input id="chat" type="text" name="chat" required maxlength="80" placeholder="-1001234567890 yoki @guruh_nomi" autocomplete="off">';
echo '<div class="actions"><button class="btn" type="submit">Qo\'shish</button></div></form>';
echo '</div>';
