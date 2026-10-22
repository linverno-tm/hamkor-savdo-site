<?php
/**
 * Saytdagi arizalarni Hamkor CRM'ga (Call center > Лидлар) yuborish.
 *
 * Shartnoma (CRM tomoni: hamkor-crm, src/app/api/webhooks/sayt-ariza):
 *   POST <crm_url>   Authorization: Bearer <kalit>   JSON:
 *   { site_id, name, phone, branch, note, special, page, source, source_detail, created_at }
 *   200 {"ok":true,"lead_id":N,"duplicate":bool} | 401 kalit | 422 telefon
 *
 * Filial bo'yicha taqsimlashni CRM o'zi qiladi: `branch` — saytdagi filial
 * kodi (CRM'dagi kod bilan bir xil); "" yoki "boshqa-viloyat" umumiy
 * ro'yxatga tushadi.
 *
 * Kalitni egasi CRM'ning superadmin sozlamalaridan olib, panelning
 * Sozlamalar sahifasiga o'zi qo'yadi. Kalit hech qachon logga yoki
 * sahifaga to'liq chiqarilmaydi.
 *
 * CRM ishlamay qolsa ariza yo'qolmaydi: u baribir saytning bazasida va
 * Telegram'da; yuborilmaganlari har 5 daqiqalik vazifada (tasks.php)
 * qayta yuboriladi — 7 kun ichida. CRM o'chiq yoki kalit xato bo'lsa urinishlar
 * soni cheklanmaydi (har safar faqat bitta ariza bilan sinab ko'riladi), boshqa
 * xatolarda — ko'pi bilan 12 marta.
 */

const HS_CRM_DEFAULT_URL = 'https://hamkorcrm.uz/api/webhooks/sayt-ariza';
const HS_CRM_MAX_TRIES = 12;

function hs_crm_url()
{
    $u = trim((string) hs_setting('crm_url', ''));
    return $u !== '' ? $u : HS_CRM_DEFAULT_URL;
}

function hs_crm_key()
{
    return trim((string) hs_setting('crm_key', ''));
}

function hs_crm_enabled()
{
    return hs_crm_key() !== '' && hs_setting('crm_enabled', '1') === '1';
}

/**
 * Bitta HTTP so'rov. Qaytaradi: [kod, javob tanasi yoki '' , xato matni].
 */
function hs_crm_post($payload)
{
    $url = hs_crm_url();
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer ' . hs_crm_key(),
        'User-Agent: HamkorSavdo-sayt/1.0',
    );
    if (getenv('HS_DRY_RUN') === '1') {
        return array(200, '{"ok":true,"lead_id":0,"duplicate":false}', '');
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = $res === false ? curl_error($ch) : '';
        curl_close($ch);
        return array($code, $res === false ? '' : (string) $res, $err);
    }
    $ctx = stream_context_create(array('http' => array(
        'method' => 'POST',
        'header' => implode("\r\n", $headers) . "\r\n",
        'content' => $body,
        'timeout' => 10,
        'ignore_errors' => true,
    )));
    $res = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    return array($code, $res === false ? '' : (string) $res, $res === false ? 'ulanib bo\'lmadi' : '');
}

/** Ariza qatoridan CRM'ga yuboriladigan ma'lumot. */
function hs_crm_payload($lead)
{
    return array(
        'site_id' => (int) $lead['id'],
        'name' => (string) $lead['name'],
        'phone' => (string) $lead['phone'],
        'branch' => (string) $lead['branch'],
        'note' => (string) $lead['note'],
        'special' => !empty($lead['special']),
        'page' => (string) $lead['page'],
        'source' => (string) $lead['source'],
        'source_detail' => (string) $lead['source_detail'],
        'created_at' => (string) $lead['created_at'],
    );
}

/**
 * Bitta arizani yuboradi va natijani bazaga yozadi.
 * Qaytaradi: true — CRM qabul qildi (yoki avval qabul qilgan).
 */
function hs_crm_send_lead($leadId)
{
    if (!hs_crm_enabled()) {
        return false;
    }
    $st = hs_db()->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute(array((int) $leadId));
    $lead = $st->fetch();
    if (!$lead) {
        return false;
    }
    if ((int) $lead['crm_id'] > 0) {
        return true;
    }
    list($code, $res, $err) = hs_crm_post(hs_crm_payload($lead));
    $json = json_decode($res, true);
    $ok = $code === 200 && is_array($json) && !empty($json['ok']);
    if ($ok) {
        hs_db()->prepare("UPDATE leads SET crm_id = ?, crm_sent_at = ?, crm_error = '', crm_tries = crm_tries + 1 WHERE id = ?")
            ->execute(array(max(1, (int) (isset($json['lead_id']) ? $json['lead_id'] : 0)), date('Y-m-d H:i:s'), (int) $leadId));
        return true;
    }
    if ($code === 401) {
        $why = "CRM kalitni qabul qilmadi — Sozlamalarda kalitni tekshiring";
    } elseif ($code === 422) {
        $why = "CRM telefon raqamini qabul qilmadi";
    } elseif ($code === 0) {
        $why = 'CRM bilan ulanib bo\'lmadi' . ($err !== '' ? ': ' . substr($err, 0, 120) : '');
    } else {
        $why = "CRM javobi: HTTP {$code}";
    }
    $GLOBALS['hs_crm_last_code'] = $code;
    hs_db()->prepare('UPDATE leads SET crm_error = ?, crm_tries = crm_tries + 1 WHERE id = ?')
        ->execute(array($why, (int) $leadId));
    // Kalit logga tushmaydi — faqat ariza raqami va sabab.
    error_log("HAMKOR SAVDO: ariza #{$leadId} CRM'ga yuborilmadi — {$why}");
    return false;
}

/**
 * Yuborilmay qolgan arizalarni qayta yuborish (tasks.php, har 5 daqiqada).
 * 422 (telefon) qayta yuborilmaydi — natija o'zgarmaydi.
 * Qaytaradi: yuborilganlar soni.
 */
function hs_crm_retry($limit = 20)
{
    if (!hs_crm_enabled()) {
        return 0;
    }
    /* Faqat ulanish yoqilgandan keyingi arizalar: eskilari Telegram orqali
       allaqachon ko'rilgan, CRM'da takror chiqmasin (crm_since — kalit
       birinchi qo'yilgan vaqt, sozlamalar.php). */
    $since = max(date('Y-m-d H:i:s', time() - 7 * 86400), (string) hs_setting('crm_since', date('Y-m-d H:i:s')));
    /* CRM o'chiq (ulanib bo'lmadi / HTTP 5xx) yoki kalit xato bo'lgan arizalar
       urinishlar soniga qaramay 7 kun qayta yuboriladi: aks holda bir soatdan
       uzun uzilishdagi arizalar CRM'ga hech qachon yetmasdi. */
    $st = hs_db()->prepare("SELECT id FROM leads WHERE crm_id = 0 AND created_at >= ?
        AND crm_error NOT LIKE '%telefon%'
        AND (crm_tries < ? OR crm_error LIKE 'CRM bilan ulanib%' OR crm_error LIKE 'CRM kalitni%' OR crm_error LIKE 'CRM javobi: HTTP 5%')
        ORDER BY id LIMIT ?");
    $st->execute(array($since, HS_CRM_MAX_TRIES, (int) $limit));
    $n = 0;
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        if (hs_crm_send_lead((int) $id)) {
            $n++;
            continue;
        }
        $code = isset($GLOBALS['hs_crm_last_code']) ? (int) $GLOBALS['hs_crm_last_code'] : -1;
        if ($code === 0 || $code === 401 || $code >= 500) {
            // CRM yetib bo'lmaydi yoki kalit xato — qolganlarini keyingi safar.
            break;
        }
    }
    return $n;
}

/** Sozlamalar sahifasidagi "Sinash": kalit va manzilni tekshiradi (ariza yaratmaydi). */
function hs_crm_ping()
{
    if (hs_crm_key() === '') {
        return array(false, 'Kalit kiritilmagan.');
    }
    list($code, $res, $err) = hs_crm_post(array('ping' => true));
    if ($code === 200) {
        return array(true, "CRM bilan aloqa bor, kalit to'g'ri.");
    }
    if ($code === 401) {
        return array(false, "CRM kalitni qabul qilmadi.");
    }
    if ($code === 0) {
        return array(false, "CRM bilan ulanib bo'lmadi" . ($err !== '' ? ': ' . $err : '.'));
    }
    return array(false, "CRM javobi: HTTP {$code}");
}
