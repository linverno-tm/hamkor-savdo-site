<?php
/**
 * Arizalar uchun umumiy funksiyalar.
 * Operator filialga biriktirilgan bo'lsa, faqat o'sha filial arizalarini ko'radi.
 */

/** @return array{0:string,1:array} — " AND ..." SQL bo'lagi va parametrlari */
function hs_lead_scope($user)
{
    if ($user['role'] === 'operator' && $user['branch'] !== '') {
        return array(' AND branch = ?', array($user['branch']));
    }
    return array('', array());
}

function hs_can_see_lead($user, $lead)
{
    if ($user['role'] === 'operator' && $user['branch'] !== '') {
        return $lead['branch'] === $user['branch'];
    }
    return true;
}

function hs_source_label($src)
{
    $map = array(
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'google' => 'Google',
        'yandex' => 'Yandex',
        'facebook' => 'Facebook',
        'togridan' => "To'g'ridan-to'g'ri",
        '' => "Noma'lum",
    );
    return isset($map[$src]) ? $map[$src] : $src;
}

function hs_status_pill($status)
{
    $all = hs_lead_statuses();
    $label = isset($all[$status]) ? $all[$status] : $status;
    return '<span class="pill st-' . h($status) . '">' . h($label) . '</span>';
}

/**
 * @param bool $inline holatni ro'yxatning o'zidan o'zgartirish (Arizalar sahifasida)
 */
function hs_render_leads_table($rows, $inline = false)
{
    if (!$rows) {
        echo '<p class="empty">Hozircha ariza yo\'q. Saytdagi formadan ariza kelganda shu yerda paydo bo\'ladi.</p>';
        return;
    }
    $branches = hs_branch_names();
    $back = isset($_SERVER['REQUEST_URI']) ? hs_safe_return((string) $_SERVER['REQUEST_URI'], '/admin/arizalar.php') : '/admin/arizalar.php';
    echo '<div class="table-wrap leads-wrap"><table class="leads"><thead><tr><th>#</th><th>Vaqt</th><th>Mijoz</th><th>Telefon</th><th>Filial</th><th>Manba</th><th>Holat</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $branch = $r['branch'] !== '' ? (isset($branches[$r['branch']]) ? $branches[$r['branch']] : $r['branch']) : '—';
        $note = trim($r['note']);
        $preview = $note !== '' ? mb_substr($note, 0, 70) . (mb_strlen($note) > 70 ? '…' : '') : '';
        echo '<tr class="' . ($r['status'] === 'yangi' ? 'is-new' : '') . '">';
        echo '<td class="c-id"><a href="/admin/ariza.php?id=' . $id . '">#' . $id . '</a></td>';
        echo '<td class="nowrap c-time">' . h(date('d.m H:i', strtotime($r['created_at']))) . '</td>';
        echo '<td class="c-name"><a href="/admin/ariza.php?id=' . $id . '"><b>' . h($r['name']) . '</b></a>' . ((int) $r['special'] ? ' <span class="pill st-yangi">yo\'q mahsulot</span>' : '');
        if ($preview !== '') {
            echo '<small class="note-preview">' . h($preview) . '</small>';
        }
        echo '</td>';
        echo '<td class="nowrap" data-label="Telefon"><a href="tel:' . h($r['phone']) . '">' . h($r['phone']) . '</a></td>';
        echo '<td data-label="Filial">' . h($branch) . '</td>';
        echo '<td data-label="Manba">' . h(hs_source_label($r['source'])) . '</td>';
        if ($inline) {
            echo '<td class="c-status"><form method="post" action="/admin/ariza.php" class="status-form">' . hs_csrf_field()
                . '<input type="hidden" name="amal" value="holat"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="qayt" value="' . h($back) . '">'
                . '<select name="holat" data-autosubmit aria-label="Ariza #' . $id . ' holati" class="st-select st-' . h($r['status']) . '">';
            foreach (hs_lead_statuses() as $k => $v) {
                echo '<option value="' . h($k) . '"' . ($r['status'] === $k ? ' selected' : '') . '>' . h($v) . '</option>';
            }
            echo '</select><button type="submit" class="btn outline small js-hide">OK</button></form></td>';
        } else {
            echo '<td class="c-status">' . hs_status_pill($r['status']) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

/**
 * Filtrlardan SQL. Qaytaradi: [where, args, filters]
 */
function hs_lead_filters($user)
{
    list($scopeSql, $args) = hs_lead_scope($user);
    $where = 'WHERE 1=1' . $scopeSql;
    $f = array(
        'holat' => hs_get('holat'),
        'filial' => hs_get('filial'),
        'manba' => hs_get('manba'),
        'dan' => hs_get('dan'),
        'gacha' => hs_get('gacha'),
        'q' => mb_substr(hs_get('q'), 0, 60),
    );
    if ($f['holat'] !== '' && isset(hs_lead_statuses()[$f['holat']])) {
        $where .= ' AND status = ?';
        $args[] = $f['holat'];
    }
    if ($f['filial'] !== '' && preg_match('/^[a-z0-9-]+$/', $f['filial'])) {
        $where .= ' AND branch = ?';
        $args[] = $f['filial'];
    }
    if ($f['manba'] !== '' && preg_match('/^[a-z0-9._-]+$/', $f['manba'])) {
        $where .= ' AND source = ?';
        $args[] = $f['manba'];
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['dan'])) {
        $where .= ' AND created_at >= ?';
        $args[] = $f['dan'] . ' 00:00:00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['gacha'])) {
        $where .= ' AND created_at <= ?';
        $args[] = $f['gacha'] . ' 23:59:59';
    }
    if ($f['q'] !== '') {
        $where .= " AND (name LIKE ? ESCAPE '\\' OR phone LIKE ? ESCAPE '\\' OR note LIKE ? ESCAPE '\\')";
        $like = '%' . str_replace(array('%', '_'), array('\\%', '\\_'), $f['q']) . '%';
        array_push($args, $like, $like, $like);
    }
    return array($where, $args, $f);
}
