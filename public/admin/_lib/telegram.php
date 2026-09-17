<?php
/**
 * Telegram xabari. Token faqat serverda — brauzerga hech qachon chiqmaydi.
 * parse_mode berilmaydi: foydalanuvchi matni belgilash sifatida o'qilmasin.
 *
 * HS_DRY_RUN=1 (faqat mahalliy sinov) — xabar yuborilmaydi, _data/telegram.log ga yoziladi.
 */

function hs_http($method, $url, $headers = array(), $body = null, $timeout = 20)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($code, $resp === false ? '' : $resp);
    }
    $ctx = stream_context_create(array('http' => array(
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $body === null ? '' : $body,
        'timeout' => $timeout,
        'ignore_errors' => true,
    )));
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    return array($code, $resp === false ? '' : $resp);
}

function hs_telegram_send($text, $chatId = null)
{
    $token = (string) hs_config('token', '');
    $chatId = $chatId !== null ? $chatId : (string) hs_config('admin_chat_id', hs_config('chat_id', ''));
    if (getenv('HS_DRY_RUN') === '1') {
        @file_put_contents(hs_data_dir() . '/telegram.log', '[' . hs_now() . "] to {$chatId}\n{$text}\n\n", FILE_APPEND);
        return true;
    }
    if ($token === '' || $chatId === '') {
        return false;
    }
    list($code, $body) = hs_http(
        'POST',
        'https://api.telegram.org/bot' . $token . '/sendMessage',
        array('Content-Type: application/x-www-form-urlencoded'),
        http_build_query(array(
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => 'true',
        )),
        15
    );
    if ($code !== 200) {
        // Tokenni logga yozmaymiz.
        error_log('HAMKOR SAVDO admin: Telegram xabari yuborilmadi (HTTP ' . $code . ')');
        return false;
    }
    return true;
}

/** Qurilma haqida odam o'qiy oladigan qisqa yozuv: "Chrome · Android". */
function hs_describe_agent($ua)
{
    $browser = 'Brauzer';
    foreach (array('Edg/' => 'Edge', 'OPR/' => 'Opera', 'YaBrowser' => 'Yandex', 'Firefox/' => 'Firefox', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari') as $needle => $label) {
        if (strpos($ua, $needle) !== false) {
            $browser = $label;
            break;
        }
    }
    $os = 'noma\'lum tizim';
    foreach (array('Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Windows' => 'Windows', 'Mac OS' => 'macOS', 'Linux' => 'Linux') as $needle => $label) {
        if (strpos($ua, $needle) !== false) {
            $os = $label;
            break;
        }
    }
    return $browser . ' · ' . $os;
}
