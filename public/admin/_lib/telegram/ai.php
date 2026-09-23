<?php
/* Telegram sahifasining bir bo'limi — faqat admin/telegram.php dan chaqiriladi. */
if (!defined('HS_ADMIN')) {
    exit;
}

/* ---- AI kaliti ---- */
/* Bitta kalit ikki joyda ishlatiladi: mijozlar boti (guruhdagi savollar) va
   Raqobatchilar bo'limi (raqobatchi e'lonlarini tahlil qilish). Ilgari u
   mijozlar boti sozlamalari ichida yashiringan edi va topib bo'lmasdi. */
$mbKey = hs_mb_key();
$gemini = hs_mb_provider() === 'gemini';
$aiKey = $gemini ? hs_mb_gemini_key() : $mbKey;
$aiNom = $gemini ? 'Gemini' : 'Claude';
$aiModel = $gemini ? hs_mb_gemini_model() : hs_mb_setting('model');
echo '<section class="card"><div class="part-head"><span class="part-ico">' . hs_icon('settings') . '</span><div><h2>AI kaliti</h2>'
    . "<p class=\"muted\">Ikkala bot shu kalitdan foydalanadi: <a href=\"/admin/telegram.php?bolim=mijozlar\">Mijozlar boti</a> va <a href=\"/admin/kuzatuv.php\">Raqobatchilar</a></p></div></div>";
echo '<ul class="checklist">';
echo '<li>' . $okIco($aiKey !== '') . '<div><b>' . ($aiKey !== '' ? h($aiNom) . ' ulangan (…' . h(substr($aiKey, -4)) . ') · ' . h($aiModel) : h($aiNom) . ' kaliti kiritilmagan') . '</b><small>'
    . ($aiKey !== ''
        ? "Savollarni lotin/kirill, xato yozuvda ham tushunadi. Telefon raqamlari AI ga yuborilmaydi — yuborishdan oldin o'chiriladi."
        : 'Kalitsiz bot oddiy so\'z qidiruvi bilan ishlaydi va har bir savolni operatorga yuboradi. Kalit: ' . ($gemini ? 'aistudio.google.com → Get API key' : 'console.anthropic.com → API Keys') . '.') . '</small></div></li>';
echo '</ul>';

echo '<div class="grid grid-2"><div>';
echo '<form method="post" action="/admin/telegram.php" autocomplete="off">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_kalit">'
    . '<label for="mb_key">API kaliti' . ($aiKey !== '' ? ' (almashtirish)' : '') . '</label><input id="mb_key" type="password" name="mb_key" required maxlength="260" autocomplete="off" spellcheck="false" placeholder="sk-ant-… , AIza… yoki AQ.…">'
    . '<p class="hint">Qaysi xizmatniki ekani kalitning o\'zidan aniqlanadi: sk-ant-… — Claude, AIza… yoki AQ.… — Google AI Studio.</p>'
    . '<p class="hint">Faqat serverdagi bazada saqlanadi, sahifada qayta ko\'rsatilmaydi.</p><div class="actions"><button class="btn outline small" type="submit">Kalitni saqlash</button></div></form>';
if ($aiKey !== '') {
    echo '<form method="post" action="/admin/telegram.php" class="actions" data-confirm="' . h($aiNom) . ' kaliti o\'chirilsinmi?">' . hs_csrf_field() . '<input type="hidden" name="amal" value="mb_kalit_ochir"><button class="btn danger small" type="submit">Kalitni o\'chirish</button></form>';
}
echo '</div>';
echo '<form method="post" action="/admin/telegram.php">' . hs_csrf_field() . '<input type="hidden" name="amal" value="ai_sozlama">';
echo '<div><label for="mb_provider">AI xizmati</label><select id="mb_provider" name="mb_provider">'
    . '<option value="claude"' . (!$gemini ? ' selected' : '') . '>Claude (Anthropic) — pullik</option>'
    . '<option value="gemini"' . ($gemini ? ' selected' : '') . '>Gemini (Google AI Studio) — bepul tarifi bor</option>'
    . '</select><p class="hint">Har ikkalasining kaliti alohida saqlanadi, almashtirish bir bosishda.</p></div>';
echo '<div><label for="mb_gemini_model">Gemini modeli</label><select id="mb_gemini_model" name="mb_gemini_model">';
foreach (array('gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite — eng tez, eng arzon', 'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite', 'gemini-3.5-flash' => 'Gemini 3.5 Flash — aniqroq') as $gv => $gl) {
    echo '<option value="' . h($gv) . '"' . (hs_mb_gemini_model() === $gv ? ' selected' : '') . '>' . h($gl) . '</option>';
}
echo '</select><p class="hint">Faqat "AI xizmati: Gemini" bo\'lganda ishlatiladi.</p></div>';
echo '<div><label for="mb_model">Claude modeli</label><select id="mb_model" name="mb_model">';
foreach (array('claude-opus-5' => 'Claude Opus 5 — eng aniq (tavsiya)', 'claude-sonnet-5' => 'Claude Sonnet 5 — arzonroq', 'claude-haiku-4-5' => 'Claude Haiku 4.5 — eng arzon, tez') as $mv => $ml) {
    echo '<option value="' . h($mv) . '"' . (hs_mb_setting('model') === $mv ? ' selected' : '') . '>' . h($ml) . '</option>';
}
echo '</select><p class="hint">Bitta savol taxminan: Opus 5 — 2–7 sent, Sonnet 5 — 1–3 sent, Haiku — ~1 sent (katalog hajmiga bog\'liq). Aniq xarajat — console.anthropic.com da.</p></div>';
echo '<div class="actions"><button class="btn" type="submit">Saqlash</button></div></form>';
echo '</div>';
echo '</section>';
