<?php
require __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/content.php';
require_once __DIR__ . '/_lib/metrika.php';
require_once __DIR__ . '/_lib/leads.php';
require_once __DIR__ . '/_lib/report.php';

$user = hs_require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hs_require_post_csrf();
    $action = hs_post('amal');

    if ($action === 'sayt') {
        $phone = preg_replace('/[^\d+]/', '', hs_post('phone'));
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 9) {
            $phone = '+998' . $digits;
        } elseif (strlen($digits) === 12) {
            $phone = '+' . $digits;
        }
        $clean = function ($k) {
            return ltrim(preg_replace('#^https?://(www\.)?(instagram\.com|t\.me)/#i', '', trim(hs_post($k), '/ ')), '@');
        };
        $vals = array(
            'phone' => $phone,
            'phoneDisplay' => preg_replace('/^\+998(\d{2})(\d{3})(\d{2})(\d{2})$/', '+998 $1 $2 $3 $4', $phone),
            'instagram' => $clean('instagram'),
            'telegram' => $clean('telegram'),
            'telegramCustomers' => $clean('telegramCustomers'),
            'telegramBot' => $clean('telegramBot'),
            'productCount' => mb_substr(hs_post('productCount'), 0, 20),
            'installmentMonthsMax' => (int) hs_post('installmentMonthsMax'),
            'instagramFollowers' => mb_substr(hs_post('instagramFollowers'), 0, 20),
            'freeDeliveryArea' => mb_substr(hs_post('freeDeliveryArea'), 0, 60),
            'deliveryArea' => mb_substr(hs_post('deliveryArea'), 0, 60),
            'warrantyMonths' => hs_post('warrantyMonths') === '' ? null : (int) hs_post('warrantyMonths'),
            'yearFounded' => hs_post('yearFounded') === '' ? null : (int) hs_post('yearFounded'),
        );
        $problem = hs_content_publish($user, 'umumiy sozlamalar', function (&$c) use ($vals) {
            $c['settings'] = array_merge($c['settings'], $vals);
            return null;
        });
        hs_flash($problem === null ? hs_publish_note() : $problem, $problem === null ? 'ok' : 'err');
    } elseif ($action === 'hisobot') {
        $hour = max(0, min(23, (int) hs_post('report_hour')));
        $parts = isset($_POST['parts']) && is_array($_POST['parts']) ? array_intersect(array_keys(hs_report_parts()), $_POST['parts']) : array();
        hs_set_setting('report_enabled', hs_post('report_enabled') === '1' ? '1' : '0');
        hs_set_setting('report_hour', (string) $hour);
        hs_set_setting('report_parts', implode(',', $parts));
        hs_audit($user['login'], 'hisobot sozlamasi', "soat {$hour}, " . implode(',', $parts));
        hs_flash('Hisobot sozlamalari saqlandi.');
    } elseif ($action === 'parol') {
        $current = isset($_POST['joriy']) ? (string) $_POST['joriy'] : '';
        $new = isset($_POST['yangi']) ? (string) $_POST['yangi'] : '';
        $again = isset($_POST['yangi2']) ? (string) $_POST['yangi2'] : '';
        if (!password_verify($current, hs_owner_hash())) {
            sleep(1);
            hs_flash("Joriy parol noto'g'ri.", 'err');
        } elseif ($new !== $again) {
            hs_flash('Yangi parollar bir xil emas.', 'err');
        } elseif (($pp = hs_password_problem($new)) !== null) {
            hs_flash($pp, 'err');
        } elseif (password_verify($new, hs_owner_hash())) {
            hs_flash("Yangi parol eskisi bilan bir xil bo'lmasin.", 'err');
        } else {
            hs_set_setting('owner_hash', password_hash($new, PASSWORD_DEFAULT));
            hs_set_setting('owner_hash_set_at', (string) time());
            session_regenerate_id(true);
            hs_audit($user['login'], "egasining paroli o'zgartirildi", 'IP ' . hs_ip());
            hs_telegram_send("🔑 Admin panel paroli o'zgartirildi\nIP: " . hs_ip() . "\nQurilma: " . hs_describe_agent(hs_user_agent()) . "\nVaqt: " . date('d.m.Y H:i') . "\n\nSiz bo'lmasangiz — darhol kompyuterda php tools/admin-parol.php bilan yangi parol qo'ying.");
            hs_flash("Parol o'zgartirildi. Keyingi kirishda yangi parolni ishlating.");
        }
    } elseif ($action === 'sinov') {
        $ok = hs_telegram_send("🧪 Sinov hisoboti\n\n" . hs_build_daily_report(date('Y-m-d')));
        hs_flash($ok ? "Sinov hisoboti Telegram'ga yuborildi." : "Yuborilmadi — bot tokeni va chat_id ni tekshiring.", $ok ? 'ok' : 'err');
    }
    hs_redirect('/admin/sozlamalar.php');
}

$err = null;
$c = hs_content_load($err);
hs_page_start('Sozlamalar', $user);

if (!$c) {
    echo '<p class="flash flash-err">' . h($err) . '</p>';
} else {
    $s = $c['settings'];
    $f = function ($name, $label, $hint = '', $type = 'text', $attrs = '') use ($s) {
        $val = isset($s[$name]) && $s[$name] !== null ? $s[$name] : '';
        echo '<div><label for="s-' . $name . '">' . h($label) . '</label><input id="s-' . $name . '" type="' . $type . '" name="' . $name . '" value="' . h($val) . '" ' . $attrs . '>';
        if ($hint !== '') {
            echo '<p class="hint">' . h($hint) . '</p>';
        }
        echo '</div>';
    };
    echo '<form class="card" method="post" action="/admin/sozlamalar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sayt">';
    echo '<h2>Saytdagi umumiy ma\'lumotlar</h2><div class="grid grid-2">';
    $f('phone', 'Asosiy telefon', 'Sarlavhada, pastki tugmada va hamma joyda ko\'rinadi.', 'tel', 'required maxlength="20"');
    $f('installmentMonthsMax', 'Muddatli to\'lov — necha oygacha', 'Butun sayt bo\'ylab shu raqam ishlatiladi.', 'number', 'required min="1" max="60"');
    $f('productCount', 'Mahsulotlar soni', 'Masalan: 7000+', 'text', 'required maxlength="20"');
    $f('instagramFollowers', 'Instagram obunachilar', 'Masalan: 41 000+', 'text', 'required maxlength="20"');
    $f('instagram', 'Instagram sahifa nomi', '@ belgisisiz', 'text', 'required maxlength="40"');
    $f('telegram', 'Rasmiy Telegram kanal', '@ belgisisiz', 'text', 'required maxlength="40"');
    $f('telegramCustomers', 'Mijozlar Telegram kanali', '@ belgisisiz', 'text', 'required maxlength="40"');
    $f('telegramBot', 'Taklif boti', '@ belgisisiz', 'text', 'required maxlength="40"');
    $f('freeDeliveryArea', 'Bepul yetkazish hududi', 'Masalan: Andijon viloyati', 'text', 'required maxlength="60"');
    $f('deliveryArea', 'Umumiy yetkazish hududi', "Masalan: O'zbekiston bo'ylab", 'text', 'required maxlength="60"');
    $f('warrantyMonths', 'Kafolat (oy)', 'Hozircha saytda ko\'rsatilmaydi, bo\'sh qoldirish mumkin.', 'number', 'min="0" max="120"');
    $f('yearFounded', 'Tashkil topgan yil', 'Bo\'sh qoldirish mumkin.', 'number', 'min="1990" max="2100"');
    echo '</div><div class="actions"><button class="btn" type="submit">Saqlash va nashr qilish</button></div></form>';
}

$enabled = hs_setting('report_enabled', '1') === '1';
$hour = (int) hs_setting('report_hour', '21');
$parts = hs_report_enabled_parts();
echo '<form class="card" method="post" action="/admin/sozlamalar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="hisobot">';
echo '<h2>Kunlik Telegram hisoboti</h2>';
echo '<label class="inline"><input type="checkbox" name="report_enabled" value="1"' . ($enabled ? ' checked' : '') . '> Har kuni yuborilsin</label>';
echo '<label for="report_hour">Soat (Toshkent vaqti)</label><select id="report_hour" name="report_hour">';
for ($i = 0; $i < 24; $i++) {
    echo '<option value="' . $i . '"' . ($i === $hour ? ' selected' : '') . '>' . sprintf('%02d:00', $i) . '</option>';
}
echo '</select><fieldset><legend class="label">Hisobotda nimalar bo\'lsin</legend>';
foreach (hs_report_parts() as $k => $v) {
    echo '<label class="inline"><input type="checkbox" name="parts[]" value="' . h($k) . '"' . (in_array($k, $parts, true) ? ' checked' : '') . '> ' . h($v) . '</label>';
}
echo '</fieldset><p class="hint">Hostingda Cron har soatda <span class="code">php …/public_html/admin/cron/hisobot.php</span> ni ishga tushirishi kerak.</p>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';

echo '<form id="parol" class="card" method="post" action="/admin/sozlamalar.php" autocomplete="off">' . hs_csrf_field() . '<input type="hidden" name="amal" value="parol">';
echo '<div class="card-head"><h2>Mening parolim</h2><span class="muted">' . h($user['login']) . '</span></div>';
echo '<div class="grid grid-3">';
echo '<div><label for="joriy">Joriy parol</label><input id="joriy" type="password" name="joriy" required autocomplete="current-password"></div>';
echo '<div><label for="yangi">Yangi parol</label><input id="yangi" type="password" name="yangi" required minlength="10" autocomplete="new-password"></div>';
echo '<div><label for="yangi2">Yangi parolni takrorlang</label><input id="yangi2" type="password" name="yangi2" required minlength="10" autocomplete="new-password"></div>';
echo '</div><p class="hint">Kamida 10 belgi, harf va raqam. O\'zgarganda Telegram\'ga xabar keladi. Parolni unutsangiz — kompyuterda <span class="code">php tools/admin-parol.php</span>.</p>';
echo '<div class="actions"><button class="btn" type="submit">Parolni o\'zgartirish</button></div></form>';

echo '<form class="card" method="post" action="/admin/sozlamalar.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="sinov">';
echo '<h2>Sinab ko\'rish</h2><p class="muted">Bugungi hisobotni hozir Telegram\'ga yuboradi.</p><button class="btn outline" type="submit">Sinov hisobotini yuborish</button></form>';

hs_page_end();
