<?php
/* Telegram sahifasining bir bo'limi — faqat admin/telegram.php dan chaqiriladi. */
if (!defined('HS_ADMIN')) {
    exit;
}

/* ---- eslatma va zaxira ---- */
$cronAt = (int) hs_setting('tasks_last_cron', '0');
$cronOk = $cronAt > time() - 20 * 60;
$cronCmd = 'php ' . str_replace('\\', '/', realpath(__DIR__ . '/cron/vazifalar.php'));
$checked = function ($k) {
    return hs_task_setting($k) === '1' ? ' checked' : '';
};
$hourOpts = function ($name, $cur, $from, $to) {
    $h = '<select id="t-' . $name . '" name="' . $name . '">';
    for ($i = $from; $i <= $to; $i++) {
        $h .= '<option value="' . $i . '"' . ((int) $cur === $i ? ' selected' : '') . '>' . sprintf('%02d:00', $i % 24) . '</option>';
    }
    return $h . '</select>';
};
echo '<form class="card" method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="vazifalar">';
echo '<div class="part-head"><span class="part-ico">' . hs_icon('clock') . '</span><div><h2>Eslatma va zaxira</h2><p class="muted">Ariza javobsiz qolmasin, ma\'lumot yo\'qolmasin</p></div></div>';

echo '<label class="switch toggle-row"><input type="hidden" name="remind_on" value="0"><input type="checkbox" name="remind_on" value="1"' . $checked('remind_on') . '><span class="switch-ui" aria-hidden="true"></span><span>Javobsiz ariza eslatmasi</span></label>';
echo '<div class="grid grid-4">';
echo '<div><label for="t-remind_m1">1-eslatma (daqiqa)</label><input id="t-remind_m1" type="number" name="remind_m1" min="1" max="240" value="' . h(hs_task_setting('remind_m1')) . '"><p class="hint">Ariza kelgan chatlarga: "hali hech kim olmadi".</p></div>';
echo '<div><label for="t-remind_m2">2-eslatma (daqiqa)</label><input id="t-remind_m2" type="number" name="remind_m2" min="2" max="480" value="' . h(hs_task_setting('remind_m2')) . '"><p class="hint">Ariza turgan guruhlarga va boshqaruvchiga: "hali javob yo\'q" (kimdir olgan bo\'lsa ham).</p></div>';
echo '<div><label for="t-work_from">Ish boshlanishi</label>' . $hourOpts('work_from', hs_task_setting('work_from'), 0, 23) . '</div>';
echo '<div><label for="t-work_to">Ish tugashi</label>' . $hourOpts('work_to', hs_task_setting('work_to'), 1, 24) . '<p class="hint">Kechasi eslatma yuborilmaydi; tungi ariza ertalab hisoblanadi.</p></div>';
echo '</div>';

echo '<label class="switch toggle-row"><input type="hidden" name="backup_on" value="0"><input type="checkbox" name="backup_on" value="1"' . $checked('backup_on') . '><span class="switch-ui" aria-hidden="true"></span><span>Har kuni arizalar zaxirasini yuborish</span></label>';
echo '<div class="grid grid-4"><div><label for="t-backup_hour">Soat</label>' . $hourOpts('backup_hour', hs_task_setting('backup_hour'), 0, 23) . '</div>';
echo '<div class="span-3"><p class="hint">Boshqaruvchiga Telegram\'da Excel\'da ochiladigan fayl keladi — hamma arizalar, holati va izohlari bilan. Parol va kalitlar faylga kirmaydi.'
    . (hs_setting('backup_last_at', '') !== '' ? ' Oxirgi zaxira: ' . h(date('d.m.Y H:i', strtotime(hs_setting('backup_last_at')))) . '.' : '') . '</p></div></div>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';

echo '<div class="card cron-card' . ($cronOk ? ' ok' : '') . '"><div class="part-head"><span class="part-ico' . ($cronOk ? '' : ' warn') . '">' . hs_icon($cronOk ? 'check' : 'clock') . '</span><div>';
if ($cronOk) {
    echo '<h2>Vaqt bo\'yicha vazifalar ishlayapti</h2><p class="muted">Oxirgi tekshiruv: ' . h(date('H:i', $cronAt)) . '. Eslatma va zaxira vaqtida ketadi.</p>';
} else {
    echo '<h2>Hostingda Cron sozlanmagan</h2><p class="muted">Hozir eslatmalar faqat panel ochilganda yoki botga kimdir yozganda tekshiriladi — tunda va jim paytlarda kechikadi.</p>';
}
echo '</div></div>';
if (!$cronOk) {
    echo '<ol class="steps"><li>ahost panelida <b>Cron Jobs</b> (Cron vazifalari) bo\'limini oching.</li>'
        . '<li>Vaqt: <b>har 5 daqiqada</b> (<span class="code">*/5 * * * *</span>).</li>'
        . '<li>Buyruq (to\'liq nusxalang):<br><span class="code">' . h($cronCmd) . '</span></li>'
        . '<li>Saqlang. 5 daqiqadan keyin bu karta yashil bo\'ladi.</li></ol>';
}
echo '<form method="post" action="/admin/telegram.php" class="actions">' . hs_csrf_field() . '<input type="hidden" name="amal" value="zaxira"><button class="btn outline small" type="submit">💾 Zaxirani hozir yuborish</button></form></div>';
