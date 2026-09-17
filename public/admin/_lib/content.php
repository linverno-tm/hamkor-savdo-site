<?php
/**
 * Sayt kontenti (data/content.json) va nashr.
 *
 * Ikki holat:
 *  - github: secrets.php da `github_token` bor. Fayl GitHub'dan o'qiladi,
 *    o'zgarish bitta commit bo'lib yoziladi, GitHub Actions saytni yig'ib
 *    hostingga yuklaydi (2–3 daqiqa).
 *  - local: HS_LOCAL_REPO muhit o'zgaruvchisi (faqat kompyuterda sinov uchun).
 *
 * Saqlashda har doim ENG SO'NGGI fayl qayta o'qiladi va faqat tahrirlangan
 * bo'lim o'zgartiriladi — ikki kishi bir vaqtda boshqa-boshqa bo'limni
 * saqlasa, biri ikkinchisini o'chirib yubormaydi.
 */

const HS_CONTENT_PATH = 'data/content.json';

function hs_repo_mode()
{
    if ((string) hs_config('github_token', '') !== '') {
        return 'github';
    }
    $local = getenv('HS_LOCAL_REPO');
    if ($local && is_dir($local)) {
        return 'local';
    }
    return 'none';
}

function hs_github_repo()
{
    return (string) hs_config('github_repo', 'linverno-tm/hamkor-savdo-site');
}

function hs_github_branch()
{
    return (string) hs_config('github_branch', 'main');
}

function hs_github($method, $path, $payload = null, $accept = 'application/vnd.github+json')
{
    $headers = array(
        'Authorization: Bearer ' . hs_config('github_token', ''),
        'Accept: ' . $accept,
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: hamkor-savdo-admin',
    );
    $body = null;
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    list($code, $resp) = hs_http($method, 'https://api.github.com' . $path, $headers, $body, 60);
    return array($code, $resp);
}

/** Repodagi faylni o'qish. Yo'q bo'lsa null. */
function hs_repo_read($path)
{
    $mode = hs_repo_mode();
    if ($mode === 'local') {
        $full = rtrim(getenv('HS_LOCAL_REPO'), '/\\') . '/' . $path;
        return is_file($full) ? file_get_contents($full) : null;
    }
    if ($mode === 'github') {
        list($code, $resp) = hs_github(
            'GET',
            '/repos/' . hs_github_repo() . '/contents/' . str_replace('%2F', '/', rawurlencode($path)) . '?ref=' . rawurlencode(hs_github_branch()),
            null,
            'application/vnd.github.raw'
        );
        return $code === 200 ? $resp : null;
    }
    return null;
}

/**
 * Bir nechta faylni BITTA commit bilan yozish/o'chirish.
 * $changes = [['path' => 'a/b.json', 'content' => '...'], ['path' => 'x.webp', 'content' => null]]
 * @return array{ok:bool, sha:string, error:string}
 */
function hs_repo_commit($changes, $message)
{
    $mode = hs_repo_mode();
    if ($mode === 'none') {
        return array('ok' => false, 'sha' => '', 'error' => "Nashr sozlanmagan: secrets.php da github_token yo'q.");
    }
    foreach ($changes as $ch) {
        if (!hs_repo_path_allowed($ch['path'])) {
            return array('ok' => false, 'sha' => '', 'error' => 'Ruxsat etilmagan yo\'l: ' . $ch['path']);
        }
    }

    if ($mode === 'local') {
        $root = rtrim(getenv('HS_LOCAL_REPO'), '/\\');
        foreach ($changes as $ch) {
            $full = $root . '/' . $ch['path'];
            if ($ch['content'] === null) {
                if (is_file($full)) {
                    unlink($full);
                }
            } else {
                if (!is_dir(dirname($full))) {
                    mkdir(dirname($full), 0775, true);
                }
                file_put_contents($full, $ch['content']);
            }
        }
        return array('ok' => true, 'sha' => 'local', 'error' => '');
    }

    $repo = hs_github_repo();
    $branch = hs_github_branch();
    for ($try = 0; $try < 2; $try++) {
        list($c1, $r1) = hs_github('GET', "/repos/{$repo}/git/ref/heads/{$branch}");
        if ($c1 !== 200) {
            return array('ok' => false, 'sha' => '', 'error' => "GitHub'ga ulanib bo'lmadi (ref, HTTP {$c1}). Token muddati tugagan bo'lishi mumkin.");
        }
        $head = json_decode($r1, true);
        $headSha = $head['object']['sha'];
        list($c2, $r2) = hs_github('GET', "/repos/{$repo}/git/commits/{$headSha}");
        if ($c2 !== 200) {
            return array('ok' => false, 'sha' => '', 'error' => "GitHub commit o'qilmadi (HTTP {$c2}).");
        }
        $baseTree = json_decode($r2, true)['tree']['sha'];

        $tree = array();
        foreach ($changes as $ch) {
            if ($ch['content'] === null) {
                $tree[] = array('path' => $ch['path'], 'mode' => '100644', 'type' => 'blob', 'sha' => null);
                continue;
            }
            list($cb, $rb) = hs_github('POST', "/repos/{$repo}/git/blobs", array(
                'content' => base64_encode($ch['content']),
                'encoding' => 'base64',
            ));
            if ($cb !== 201) {
                return array('ok' => false, 'sha' => '', 'error' => "Fayl yuklanmadi: {$ch['path']} (HTTP {$cb}).");
            }
            $tree[] = array('path' => $ch['path'], 'mode' => '100644', 'type' => 'blob', 'sha' => json_decode($rb, true)['sha']);
        }

        list($ct, $rt) = hs_github('POST', "/repos/{$repo}/git/trees", array('base_tree' => $baseTree, 'tree' => $tree));
        if ($ct !== 201) {
            return array('ok' => false, 'sha' => '', 'error' => "GitHub tree yaratilmadi (HTTP {$ct}).");
        }
        list($cc, $rc) = hs_github('POST', "/repos/{$repo}/git/commits", array(
            'message' => $message,
            'tree' => json_decode($rt, true)['sha'],
            'parents' => array($headSha),
        ));
        if ($cc !== 201) {
            return array('ok' => false, 'sha' => '', 'error' => "Commit yaratilmadi (HTTP {$cc}).");
        }
        $newSha = json_decode($rc, true)['sha'];
        list($cu) = hs_github('PATCH', "/repos/{$repo}/git/refs/heads/{$branch}", array('sha' => $newSha, 'force' => false));
        if ($cu === 200) {
            return array('ok' => true, 'sha' => $newSha, 'error' => '');
        }
        // 422 — shu orada boshqa commit tushdi; qaytadan eng so'nggisidan.
        if ($cu !== 422) {
            return array('ok' => false, 'sha' => '', 'error' => "Branch yangilanmadi (HTTP {$cu}).");
        }
    }
    return array('ok' => false, 'sha' => '', 'error' => "Bir vaqtda boshqa o'zgarish bo'ldi. Qaytadan saqlang.");
}

/** Panel faqat shu joylarga yoza oladi — token o'g'irlansa ham kodga tegib bo'lmasin. */
function hs_repo_path_allowed($path)
{
    if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        return false;
    }
    return $path === HS_CONTENT_PATH
        || preg_match('#^rasmlar/(filiallar/[a-z0-9-]+|aksiyalar|katalog)/[a-z0-9-]+\.(jpg|jpeg|png|webp)$#', $path)
        || preg_match('#^public/filiallar/[a-z0-9-]+/[a-z0-9-]+-(480|960)\.webp$#', $path);
}

/* ---------------- kontent ---------------- */

/** Saytda hozir turgan (build qilingan) kontent — faqat o'qish uchun. */
function hs_content_published()
{
    static $c = false;
    if ($c !== false) {
        return $c;
    }
    $file = __DIR__ . '/../../api/sayt.json';
    $c = is_file($file) ? json_decode(file_get_contents($file), true) : null;
    return $c;
}

/** Tahrirlash uchun eng so'nggi kontent (repodan). */
function hs_content_load(&$error = null)
{
    $raw = hs_repo_read(HS_CONTENT_PATH);
    if ($raw === null) {
        $error = hs_repo_mode() === 'none'
            ? "Tahrirlash hali sozlanmagan: secrets.php ga github_token qo'shilishi kerak."
            : "content.json o'qilmadi (GitHub bilan aloqa yoki token muammosi).";
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $error = "content.json buzilgan.";
        return null;
    }
    return $data;
}

/** content.json ni saytdagi formatda yozadi: 2 bo'shliq, bo'sh obyektlar {}. */
function hs_content_encode($data)
{
    foreach (array('photos') as $objKey) {
        if (empty($data[$objKey])) {
            $data[$objKey] = new stdClass();
        }
    }
    if (!empty($data['photos']) && is_array($data['photos'])) {
        foreach ($data['photos'] as $slug => $p) {
            foreach (array('labels', 'alts') as $k) {
                if (isset($p[$k]) && empty($p[$k])) {
                    $data['photos'][$slug][$k] = new stdClass();
                }
            }
        }
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $json = preg_replace_callback('/^( +)/m', function ($m) {
        return str_repeat(' ', (int) (strlen($m[1]) / 2));
    }, $json);
    return $json . "\n";
}

/**
 * Kontentni o'zgartirib nashr qilish.
 * $mutate — function(array &$content): string|null (xato matni yoki null)
 * $extra — qo'shimcha fayl o'zgarishlari (rasmlar)
 */
function hs_content_publish($user, $summary, $mutate, $extra = array())
{
    $err = null;
    $content = hs_content_load($err);
    if ($content === null) {
        return $err;
    }
    $problem = $mutate($content);
    if (is_string($problem) && $problem !== '') {
        return $problem;
    }
    $problem = hs_content_validate($content);
    if ($problem !== null) {
        return $problem;
    }
    $changes = $extra;
    $changes[] = array('path' => HS_CONTENT_PATH, 'content' => hs_content_encode($content));
    $res = hs_repo_commit($changes, 'Admin: ' . $summary . ' (' . $user['login'] . ')');
    $st = hs_db()->prepare('INSERT INTO publishes(created_at, actor, summary, mode, commit_sha, ok, error) VALUES(?, ?, ?, ?, ?, ?, ?)');
    $st->execute(array(hs_now(), $user['login'], $summary, hs_repo_mode(), $res['sha'], $res['ok'] ? 1 : 0, $res['error']));
    if (!$res['ok']) {
        return $res['error'];
    }
    hs_audit($user['login'], 'nashr', $summary);
    return null;
}

/** Faqat fayllarni (content.json siz) commit qilish — masalan surat o'chirish. */
function hs_files_publish($user, $summary, $changes)
{
    $res = hs_repo_commit($changes, 'Admin: ' . $summary . ' (' . $user['login'] . ')');
    $st = hs_db()->prepare('INSERT INTO publishes(created_at, actor, summary, mode, commit_sha, ok, error) VALUES(?, ?, ?, ?, ?, ?, ?)');
    $st->execute(array(hs_now(), $user['login'], $summary, hs_repo_mode(), $res['sha'], $res['ok'] ? 1 : 0, $res['error']));
    if (!$res['ok']) {
        return $res['error'];
    }
    hs_audit($user['login'], 'nashr', $summary);
    return null;
}

/** Sayt build'ini buzadigan xatolarni GitHub'ga yozishdan oldin ushlaydi. */
function hs_content_validate($c)
{
    $s = isset($c['settings']) ? $c['settings'] : null;
    if (!is_array($s)) {
        return 'Sozlamalar topilmadi.';
    }
    if (!preg_match('/^\+998\d{9}$/', (string) $s['phone'])) {
        return "Asosiy telefon +998XXXXXXXXX ko'rinishida bo'lishi kerak.";
    }
    $m = $s['installmentMonthsMax'];
    if (!is_int($m) || $m < 1 || $m > 60) {
        return "Muddatli to'lov oyi 1 dan 60 gacha butun son bo'lishi kerak.";
    }
    foreach (array('instagram', 'telegram', 'telegramCustomers', 'telegramBot') as $k) {
        if (!preg_match('/^[A-Za-z0-9_.]{3,40}$/', (string) $s[$k])) {
            return "Havola nomi noto'g'ri: {$k}. @ belgisisiz, faqat lotin harf, raqam, _ va nuqta.";
        }
    }
    if (empty($c['branches']) || !is_array($c['branches'])) {
        return "Kamida bitta filial bo'lishi kerak.";
    }
    $ids = array();
    foreach ($c['branches'] as $b) {
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', (string) $b['id'])) {
            return "Filial manzili (id) faqat kichik lotin harf, raqam va chiziqchadan iborat bo'lsin: " . $b['id'];
        }
        if (isset($ids[$b['id']])) {
            return 'Bir xil id li ikki filial: ' . $b['id'];
        }
        $ids[$b['id']] = true;
        if (trim((string) $b['city']) === '' || trim((string) $b['landmark']) === '' || trim((string) $b['address']) === '') {
            return "Filialda shahar, mo'ljal va manzil bo'sh bo'lmasin.";
        }
        if ($b['phone'] !== null && !preg_match('/^\+998\d{9}$/', (string) $b['phone'])) {
            return 'Filial telefoni +998XXXXXXXXX ko\'rinishida bo\'lsin: ' . $b['city'];
        }
        if (!preg_match('/^\d{1,2}:\d{2}–\d{1,2}:\d{2}$/u', (string) $b['hours'])) {
            return "Ish vaqti 9:00–18:00 ko'rinishida bo'lsin: " . $b['city'];
        }
        if (!is_float($b['lat']) && !is_int($b['lat']) || !is_float($b['lng']) && !is_int($b['lng'])) {
            return "Xarita koordinatasi son bo'lishi kerak: " . $b['city'];
        }
    }
    foreach ($c['promotions'] as $p) {
        foreach (array('startsAt', 'endsAt') as $k) {
            if ($p[$k] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $p[$k])) {
                return "Aksiya sanasi noto'g'ri: " . $p['title'];
            }
        }
        if ($p['startsAt'] !== '' && $p['endsAt'] !== '' && $p['startsAt'] > $p['endsAt']) {
            return "Aksiya tugash sanasi boshlanishidan oldin bo'lmasin: " . $p['title'];
        }
    }
    foreach ($c['products'] as $p) {
        if (!in_array($p['category'], array('tilla', 'texnika', 'mebel'), true)) {
            return "Mahsulot bo'limi noto'g'ri: " . $p['name'];
        }
        if ($p['price'] !== null && (!is_int($p['price']) || $p['price'] < 0)) {
            return "Narx butun musbat son bo'lsin: " . $p['name'];
        }
    }
    return null;
}

/* ---------------- rasm yuklash ---------------- */

/**
 * Yuklangan faylni tekshiradi va (GD bo'lsa) 2000px gacha kichraytiradi.
 * @return array{ok:bool, error:string, bytes:string, ext:string}
 */
function hs_read_upload($file)
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return array('ok' => false, 'error' => 'Fayl topilmadi.', 'bytes' => '', 'ext' => '');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'Fayl juda katta.' : 'Fayl yuklanmadi (kod ' . (int) $file['error'] . ').';
        return array('ok' => false, 'error' => $msg, 'bytes' => '', 'ext' => '');
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        return array('ok' => false, 'error' => "Fayl 15 MB dan katta bo'lmasin.", 'bytes' => '', 'ext' => '');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
    if (defined('IMAGETYPE_WEBP')) {
        $types[IMAGETYPE_WEBP] = 'webp';
    }
    if (!$info || !isset($types[$info[2]])) {
        return array('ok' => false, 'error' => 'Faqat JPG, PNG yoki WEBP rasm yuklash mumkin.', 'bytes' => '', 'ext' => '');
    }
    $ext = $types[$info[2]];
    $bytes = file_get_contents($file['tmp_name']);

    // Juda katta suratni repoga sig'dirish uchun kichraytiramiz (sifat saytda baribir 960px).
    $max = 2000;
    if (function_exists('imagecreatefromstring') && ($info[0] > $max || $info[1] > $max)) {
        $img = @imagecreatefromstring($bytes);
        if ($img) {
            if ($ext === 'jpg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file['tmp_name']);
                $o = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
                if ($o === 3) {
                    $img = imagerotate($img, 180, 0);
                } elseif ($o === 6) {
                    $img = imagerotate($img, -90, 0);
                } elseif ($o === 8) {
                    $img = imagerotate($img, 90, 0);
                }
            }
            $w = imagesx($img);
            $hh = imagesy($img);
            $scale = $max / max($w, $hh);
            if ($scale < 1) {
                $img = imagescale($img, (int) round($w * $scale), (int) round($hh * $scale), IMG_BICUBIC);
            }
            ob_start();
            imagejpeg($img, null, 90);
            $bytes = ob_get_clean();
            $ext = 'jpg';
            imagedestroy($img);
        }
    }
    return array('ok' => true, 'error' => '', 'bytes' => $bytes, 'ext' => $ext);
}

/** $_FILES['x'] (multiple) -> oddiy ro'yxat. */
function hs_files_list($field)
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return empty($_FILES[$field]) ? array() : array($_FILES[$field]);
    }
    $out = array();
    foreach ($_FILES[$field]['name'] as $i => $name) {
        if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = array(
            'name' => $name,
            'type' => $_FILES[$field]['type'][$i],
            'tmp_name' => $_FILES[$field]['tmp_name'][$i],
            'error' => $_FILES[$field]['error'][$i],
            'size' => $_FILES[$field]['size'][$i],
        );
    }
    return $out;
}

function hs_new_image_name($prefix)
{
    return $prefix . '-' . date('ymdHis') . substr(hs_random_hex(2), 0, 3);
}

/** Saqlangandan keyin foydalanuvchiga ko'rsatiladigan xabar. */
function hs_publish_note()
{
    return hs_repo_mode() === 'local'
        ? "Saqlandi (mahalliy sinov rejimi — natijani ko'rish uchun npm run build)."
        : "Saqlandi. Saytda 2–3 daqiqada ko'rinadi.";
}
