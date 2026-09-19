<?php
/**
 * Filial suratlari: saytda hozir turgan fayllar va ularning tartibi.
 * Filial sahifasi ham, Suratlar sahifasi ham shu yerdan o'qiydi.
 */

/** Surat turlari — oddiy tilda. */
function hs_photo_kinds()
{
    return array(
        'tashqi' => array("Do'kon tashqarisi", 'Bino, kirish eshigi, peshtaxta'),
        'zal' => array('Savdo zali', 'Ichkarida: tovarlar, vitrinalar'),
        'jamoa' => array('Jamoa', 'Sotuvchilar, xodimlar'),
        'mijoz' => array('Mijozlar bilan', 'Xarid qilgan mijozlar, topshirish'),
    );
}

/**
 * Saytda HOZIR turgan suratlar (build qilingan papkadan).
 * @return array<int, array{base:string, kind:string, thumb:string, paths:array}>
 */
function hs_branch_photo_files($slug)
{
    $root = realpath(__DIR__ . '/../..');
    $manifest = array();
    if (is_file($root . '/rasm/manifest.json')) {
        $manifest = json_decode(file_get_contents($root . '/rasm/manifest.json'), true) ?: array();
    }
    $out = array();
    foreach (glob($root . '/filiallar/' . $slug . '/*-960.webp') ?: array() as $f) {
        $base = basename($f, '-960.webp');
        $out[$base] = array(
            'base' => $base,
            'kind' => explode('-', $base)[0],
            'thumb' => "/filiallar/{$slug}/{$base}-480.webp",
            'paths' => array("public/filiallar/{$slug}/{$base}-480.webp", "public/filiallar/{$slug}/{$base}-960.webp"),
        );
    }
    foreach (glob($root . '/rasm/filiallar/' . $slug . '/*-960.webp') ?: array() as $f) {
        $base = basename($f, '-960.webp');
        $key = "filiallar/{$slug}/{$base}";
        $out[$base] = array(
            'base' => $base,
            'kind' => explode('-', $base)[0],
            'thumb' => "/rasm/filiallar/{$slug}/{$base}-480.webp",
            'paths' => isset($manifest[$key]) ? array($manifest[$key]) : array(),
        );
    }
    return array_values($out);
}

/** Saytdagi tartib bilan bir xil (lib/photos.ts): avval qo'lda berilgani, keyin tashqi, zal, jamoa, mijoz. */
function hs_branch_photos_sorted($slug, $prefs)
{
    $files = hs_branch_photo_files($slug);
    $order = isset($prefs['order']) ? $prefs['order'] : array();
    $kindRank = array_flip(array_keys(hs_photo_kinds()));
    usort($files, function ($a, $b) use ($order, $kindRank) {
        $x = array_search($a['base'], $order, true);
        $y = array_search($b['base'], $order, true);
        $x = $x === false ? 999 : $x;
        $y = $y === false ? 999 : $y;
        $ka = isset($kindRank[$a['kind']]) ? $kindRank[$a['kind']] : 9;
        $kb = isset($kindRank[$b['kind']]) ? $kindRank[$b['kind']] : 9;
        return ($x - $y) ?: (($ka - $kb) ?: strnatcmp($a['base'], $b['base']));
    });
    return $files;
}

/** Filialning asosiy rasmi (bosh sahifadagi kartochka): tanlangan, bo'lmasa birinchisi. */
function hs_branch_cover($slug, $prefs)
{
    $files = hs_branch_photos_sorted($slug, $prefs);
    if (!$files) {
        return null;
    }
    if (!empty($prefs['cover'])) {
        foreach ($files as $f) {
            if ($f['base'] === $prefs['cover']) {
                return $f;
            }
        }
    }
    return $files[0];
}
