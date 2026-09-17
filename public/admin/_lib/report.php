<?php
/**
 * Kunlik hisobot matni. Sozlamalarda qaysi bloklar kiritilishi tanlanadi.
 */

function hs_report_parts()
{
    return array(
        'tashrif' => 'Tashriflar va odamlar',
        'manba' => 'Qayerdan kelishdi',
        'shahar' => 'Shaharlar',
        'maqsad' => "Qo'ng'iroq va Telegram bosishlar",
        'ariza' => 'Arizalar (filial bo\'yicha)',
        'javobsiz' => 'Javob kutayotgan arizalar',
    );
}

function hs_report_enabled_parts()
{
    $raw = hs_setting('report_parts', implode(',', array_keys(hs_report_parts())));
    return array_filter(explode(',', $raw));
}

function hs_build_daily_report($day)
{
    $parts = hs_report_enabled_parts();
    $lines = array('📊 HAMKOR SAVDO — ' . date('d.m.Y', strtotime($day)), '');
    $err = '';

    $ov = null;
    if (hs_metrika_ready() && (in_array('tashrif', $parts, true) || in_array('maqsad', $parts, true))) {
        $ov = hs_metrika_overview($day, $day, $err);
    }
    if ($ov && in_array('tashrif', $parts, true)) {
        $lines[] = '👥 Tashriflar: ' . $ov['visits'] . ' · odamlar: ' . $ov['users'];
    }
    if ($ov && in_array('maqsad', $parts, true) && $ov['goals']['phone_click'] !== null) {
        $lines[] = "📞 \"Qo'ng'iroq\" bosildi: " . $ov['goals']['phone_click'];
    }
    if ($ov && in_array('maqsad', $parts, true) && $ov['goals']['telegram_click'] !== null) {
        $lines[] = "✈️ Telegram'ga o'tishdi: " . $ov['goals']['telegram_click'];
    }
    if (hs_metrika_ready() && in_array('manba', $parts, true)) {
        $src = array();
        foreach (hs_metrika_breakdown('ym:s:lastTrafficSource', $day, $day, 5, $err) as $r) {
            $label = hs_traffic_source_label($r['id'], $r['name']);
            $label = preg_replace('/\s*\(.*\)$/', '', $label);
            $src[] = $label . ' ' . $r['value'];
        }
        if ($src) {
            $lines[] = '📍 ' . implode(' · ', $src);
        }
    }
    if (hs_metrika_ready() && in_array('shahar', $parts, true)) {
        $c = array();
        foreach (hs_metrika_breakdown('ym:s:regionCity', $day, $day, 5, $err) as $r) {
            $c[] = ($r['name'] !== '' ? $r['name'] : '?') . ' ' . $r['value'];
        }
        if ($c) {
            $lines[] = '🏙 ' . implode(' · ', $c);
        }
    }

    if (in_array('ariza', $parts, true)) {
        $st = hs_db()->prepare('SELECT branch, COUNT(*) c FROM leads WHERE substr(created_at,1,10) = ? GROUP BY branch ORDER BY c DESC');
        $st->execute(array($day));
        $rows = $st->fetchAll();
        $total = 0;
        $names = hs_branch_names();
        $by = array();
        foreach ($rows as $r) {
            $total += (int) $r['c'];
            $n = $r['branch'] === '' ? 'tanlanmagan' : (isset($names[$r['branch']]) ? $names[$r['branch']] : $r['branch']);
            $by[] = $n . ': ' . $r['c'];
        }
        $lines[] = '📝 Arizalar: ' . $total;
        foreach ($by as $b) {
            $lines[] = '   • ' . $b;
        }
    }
    if (in_array('javobsiz', $parts, true)) {
        $n = (int) hs_db()->query("SELECT COUNT(*) FROM leads WHERE status = 'yangi'")->fetchColumn();
        $lines[] = '⏳ Javob kutayotgan arizalar: ' . $n;
    }
    if ($err !== '') {
        $lines[] = '';
        $lines[] = '⚠️ ' . $err;
    }
    $lines[] = '';
    $lines[] = hs_site_url() . '/admin/';
    return implode("\n", $lines);
}
