<?php
/**
 * Nusxa oling va shu papkaga "secrets.php" nomi bilan saqlang.
 * Bu faylni hech qachon git ga qo'shmang. Veb orqali ochilmaydi (.htaccess).
 *
 * token   — @BotFather dan olingan bot kaliti
 * chat_id — arizalar tushadigan chat. Bilish uchun: botga bir marta yozing,
 *           so'ng brauzerda oching:
 *           https://api.telegram.org/bot<TOKEN>/getUpdates
 *           va javobdagi "chat":{"id":...} raqamini oling.
 *
 * admin_login / admin_hash — admin panel egasi. Qo'lda yozmang:
 *           php tools/admin-parol.php
 *
 * github_token — paneldan saytni tahrirlash uchun. GitHub > Settings >
 *           Developer settings > Fine-grained tokens: faqat shu repo,
 *           "Contents: Read and write" va "Actions: Read".
 *
 * metrika_token — paneldagi statistika uchun (oauth.yandex.ru, faqat
 *           "Yandex.Metrika: получение статистики" ruxsati).
 */
return array(
    'token'   => '',
    'chat_id' => '',

    'admin_login' => '',
    'admin_hash' => '',
    // 'admin_chat_id' => '',  // blok/kirish xabarlari boshqa chatga borsin desangiz

    'github_token' => '',
    'github_repo' => 'linverno-tm/hamkor-savdo-site',
    'github_branch' => 'main',

    'metrika_token' => '',
    'metrika_counter' => 112743600,

    'site_url' => 'https://hamkorsavdo.uz',
    // 'data_dir' => '/home/FOYDALANUVCHI/hamkor-data',  // tavsiya: public_html dan tashqarida
);
