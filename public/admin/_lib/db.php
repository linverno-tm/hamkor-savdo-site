<?php
/**
 * SQLite — alohida server kerak emas, bitta fayl. `hs_data_dir()` da turadi,
 * veb orqali ochilmaydi. Jadval tuzilmasi shu yerda, versiyalari bilan.
 */

function hs_db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $path = hs_data_dir() . '/hamkor.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');
    @chmod($path, 0600);
    hs_db_migrate($pdo);
    return $pdo;
}

function hs_db_migrate(PDO $pdo)
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    $steps = array(
        1 => array(
            "CREATE TABLE leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                name TEXT NOT NULL,
                phone TEXT NOT NULL,
                branch TEXT NOT NULL DEFAULT '',
                note TEXT NOT NULL DEFAULT '',
                special INTEGER NOT NULL DEFAULT 0,
                page TEXT NOT NULL DEFAULT '',
                source TEXT NOT NULL DEFAULT '',
                telegram_sent INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'yangi',
                operator_note TEXT NOT NULL DEFAULT '',
                updated_at TEXT,
                updated_by TEXT
            )",
            "CREATE INDEX leads_created ON leads(created_at)",
            "CREATE INDEX leads_status ON leads(status)",
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                login TEXT NOT NULL UNIQUE COLLATE NOCASE,
                name TEXT NOT NULL DEFAULT '',
                password_hash TEXT NOT NULL,
                branch TEXT NOT NULL DEFAULT '',
                active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL
            )",
            "CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                login TEXT NOT NULL DEFAULT '',
                ip TEXT NOT NULL,
                device TEXT NOT NULL,
                user_agent TEXT NOT NULL DEFAULT '',
                result TEXT NOT NULL,
                cleared INTEGER NOT NULL DEFAULT 0
            )",
            "CREATE INDEX attempts_device ON login_attempts(device)",
            "CREATE INDEX attempts_ip ON login_attempts(ip)",
            "CREATE TABLE blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                ip TEXT NOT NULL,
                device TEXT NOT NULL,
                user_agent TEXT NOT NULL DEFAULT '',
                last_login TEXT NOT NULL DEFAULT '',
                token_hash TEXT NOT NULL,
                unblocked_at TEXT,
                unblocked_by TEXT
            )",
            "CREATE TABLE audit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                actor TEXT NOT NULL,
                action TEXT NOT NULL,
                details TEXT NOT NULL DEFAULT ''
            )",
            "CREATE TABLE settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )",
            "CREATE TABLE cache (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                expires_at INTEGER NOT NULL
            )",
            "CREATE TABLE publishes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                actor TEXT NOT NULL,
                summary TEXT NOT NULL,
                mode TEXT NOT NULL,
                commit_sha TEXT NOT NULL DEFAULT '',
                ok INTEGER NOT NULL DEFAULT 1,
                error TEXT NOT NULL DEFAULT ''
            )",
        ),
        // Bot turgan chatlar: kimga/qaysi guruhga ariza borishini panel hal qiladi.
        2 => array(
            "CREATE TABLE tg_chats (
                chat_id TEXT PRIMARY KEY,
                type TEXT NOT NULL,
                title TEXT NOT NULL DEFAULT '',
                username TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'member',
                leads INTEGER NOT NULL DEFAULT 0,
                branch TEXT NOT NULL DEFAULT '',
                added_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                last_sent_at TEXT,
                last_error TEXT NOT NULL DEFAULT ''
            )",
        ),
        // Boshqaruvchi: botdan kelgan ruxsat so'rovlarini tasdiqlay oladigan odam.
        3 => array(
            "ALTER TABLE tg_chats ADD COLUMN admin INTEGER NOT NULL DEFAULT 0",
        ),
        // Arizaning batafsil manbasi va Metrika ClientID; har bir chatga ketgan
        // xabar — holat o'zgarsa hamma nusxadagi tugmalar yangilanishi uchun.
        4 => array(
            "ALTER TABLE leads ADD COLUMN source_detail TEXT NOT NULL DEFAULT ''",
            "ALTER TABLE leads ADD COLUMN ym_client TEXT NOT NULL DEFAULT ''",
            "CREATE TABLE tg_lead_msgs (
                lead_id INTEGER NOT NULL,
                chat_id TEXT NOT NULL,
                message_id INTEGER NOT NULL,
                PRIMARY KEY (lead_id, chat_id)
            )",
        ),
        // "Men oldim" va javobsiz ariza eslatmasi.
        5 => array(
            "ALTER TABLE leads ADD COLUMN claimed_by TEXT NOT NULL DEFAULT ''",
            "ALTER TABLE leads ADD COLUMN claimed_at TEXT",
            "ALTER TABLE leads ADD COLUMN remind_level INTEGER NOT NULL DEFAULT 0",
            // Mavjud arizalar uchun eslatma yubormaymiz — aks holda yangilangan
            // zahoti eski "Yangi" arizalar bo'yicha xabarlar yog'ilib ketadi.
            "UPDATE leads SET remind_level = 2",
        ),
        // Arizani olgan odamning Telegram ID'si — eslatmada uni belgilash (username'i bo'lmasa ham).
        6 => array(
            "ALTER TABLE leads ADD COLUMN claimed_uid TEXT NOT NULL DEFAULT ''",
            // Eski arizalar bo'yicha yangi, takroriy eslatmalar boshlanib ketmasin.
            "UPDATE leads SET remind_level = 9 WHERE remind_level >= 2",
        ),
        // Mijozlar guruhi yordamchisi (_lib/mijozbot.php): chat roli, kanal/guruhdagi
        // mahsulot postlari (katalog) va guruhdagi savollar.
        7 => array(
            // '' — oddiy chat (arizalar yoqilishi mumkin); 'mijozlar' — mijozlar guruhi,
            // 'katalog' — mahsulot kanali. Bu ikkisiga ariza HECH QACHON yuborilmaydi.
            "ALTER TABLE tg_chats ADD COLUMN role TEXT NOT NULL DEFAULT ''",
            "CREATE TABLE mb_posts (
                source TEXT NOT NULL,
                post_id INTEGER NOT NULL,
                url TEXT NOT NULL DEFAULT '',
                text TEXT NOT NULL,
                photo INTEGER NOT NULL DEFAULT 0,
                posted_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                PRIMARY KEY (source, post_id)
            )",
            "CREATE INDEX mb_posts_posted ON mb_posts(posted_at)",
            "CREATE TABLE mb_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                chat_id TEXT NOT NULL,
                message_id INTEGER NOT NULL,
                user_id TEXT NOT NULL DEFAULT '',
                user_name TEXT NOT NULL DEFAULT '',
                text TEXT NOT NULL,
                context TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'yangi',
                kind TEXT NOT NULL DEFAULT '',
                reply TEXT NOT NULL DEFAULT '',
                post_ids TEXT NOT NULL DEFAULT '',
                bot_message_id INTEGER,
                needs_operator INTEGER NOT NULL DEFAULT 0,
                answered_by TEXT NOT NULL DEFAULT '',
                answered_at TEXT,
                lead_id INTEGER,
                reminded INTEGER NOT NULL DEFAULT 0,
                error TEXT NOT NULL DEFAULT ''
            )",
            "CREATE INDEX mb_q_created ON mb_questions(created_at)",
            "CREATE INDEX mb_q_msg ON mb_questions(chat_id, message_id)",
            "CREATE TABLE mb_q_msgs (
                q_id INTEGER NOT NULL,
                chat_id TEXT NOT NULL,
                message_id INTEGER NOT NULL,
                PRIMARY KEY (q_id, chat_id)
            )",
        ),
        // Ijara (_lib/ijara.php): obyektlar, qavatlar, ijarachilar, ular egallagan
        // joylar va kassa. Savdo arizalari bilan hech qanday bog'liqligi yo'q.
        8 => array(
            // Foydalanuvchi turi: operator (savdo) / ijara_boshliq / ijara_ishchi — _lib/auth.php.
            "ALTER TABLE users ADD COLUMN kind TEXT NOT NULL DEFAULT 'operator'",
            "CREATE TABLE ij_objects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                address TEXT NOT NULL DEFAULT '',
                land_area REAL NOT NULL DEFAULT 0,
                floors_below INTEGER NOT NULL DEFAULT 0,
                floors_above INTEGER NOT NULL DEFAULT 1,
                note TEXT NOT NULL DEFAULT '',
                -- Bino surati: fayl nomi data papkasidagi ijara-fayllar/ ichida (bo'sh — surat yo'q).
                photo TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )",
            // level: -2, -1 — yerto'la; 1, 2, 3 … — yer usti. 0 bo'lmaydi.
            "CREATE TABLE ij_floors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                object_id INTEGER NOT NULL REFERENCES ij_objects(id) ON DELETE CASCADE,
                level INTEGER NOT NULL,
                area REAL NOT NULL DEFAULT 0,
                UNIQUE (object_id, level)
            )",
            "CREATE TABLE ij_tenants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                object_id INTEGER NOT NULL REFERENCES ij_objects(id),
                name TEXT NOT NULL,
                phone TEXT NOT NULL DEFAULT '',
                activity TEXT NOT NULL DEFAULT '',
                rent REAL NOT NULL DEFAULT 0,
                rent_currency TEXT NOT NULL DEFAULT 'UZS',
                -- Necha oyga kelishilgan; end_date shundan hisoblanadi.
                months INTEGER NOT NULL DEFAULT 0,
                start_date TEXT NOT NULL DEFAULT '',
                end_date TEXT NOT NULL DEFAULT '',
                active INTEGER NOT NULL DEFAULT 1,
                note TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )",
            "CREATE INDEX ij_tenants_object ON ij_tenants(object_id)",
            // Ijarachi bir nechta qavatda joy olishi mumkin — har biri alohida qator.
            "CREATE TABLE ij_spaces (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL REFERENCES ij_tenants(id) ON DELETE CASCADE,
                floor_id INTEGER NOT NULL REFERENCES ij_floors(id),
                area REAL NOT NULL
            )",
            "CREATE INDEX ij_spaces_floor ON ij_spaces(floor_id)",
            // Kassa daftari: kirim (ijarachidan) va chiqim (investorga topshirildi, xarajat).
            // purpose — nima uchun (ijara, elektr …); method — naqd, click …
            // rate — o'sha kungi dollar kursi (so'm); amount_uzs — so'mdagi qiymati.
            "CREATE TABLE ij_cash (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                paid_at TEXT NOT NULL,
                direction TEXT NOT NULL,
                tenant_id INTEGER REFERENCES ij_tenants(id),
                object_id INTEGER REFERENCES ij_objects(id),
                purpose TEXT NOT NULL,
                amount REAL NOT NULL,
                currency TEXT NOT NULL,
                method TEXT NOT NULL,
                rate REAL NOT NULL,
                rate_source TEXT NOT NULL DEFAULT '',
                amount_uzs REAL NOT NULL,
                note TEXT NOT NULL DEFAULT '',
                created_by TEXT NOT NULL DEFAULT ''
            )",
            "CREATE INDEX ij_cash_paid ON ij_cash(paid_at)",
            "CREATE INDEX ij_cash_tenant ON ij_cash(tenant_id)",
            // Ijarachiga biriktirilgan fayllar (shartnoma skani). Fayl o'zi data papkasida,
            // veb orqali to'g'ridan-to'g'ri ochilmaydi — faqat panel orqali (ijara-fayl.php).
            "CREATE TABLE ij_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL REFERENCES ij_tenants(id),
                title TEXT NOT NULL DEFAULT '',
                orig_name TEXT NOT NULL,
                stored TEXT NOT NULL,
                mime TEXT NOT NULL,
                size INTEGER NOT NULL,
                uploaded_at TEXT NOT NULL,
                uploaded_by TEXT NOT NULL DEFAULT ''
            )",
            "CREATE INDEX ij_files_tenant ON ij_files(tenant_id)",
        ),
    );
    foreach ($steps as $v => $sqls) {
        if ($version >= $v) {
            continue;
        }
        $pdo->beginTransaction();
        foreach ($sqls as $sql) {
            $pdo->exec($sql);
        }
        $pdo->exec('PRAGMA user_version = ' . (int) $v);
        $pdo->commit();
    }
}

function hs_setting($key, $default = null)
{
    $st = hs_db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute(array($key));
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function hs_set_setting($key, $value)
{
    // INSERT OR REPLACE — eski hostinglardagi SQLite (3.24 dan past) UPSERT'ni bilmaydi.
    $st = hs_db()->prepare('INSERT OR REPLACE INTO settings(key, value) VALUES(?, ?)');
    $st->execute(array($key, (string) $value));
}

function hs_audit($actor, $action, $details = '')
{
    $st = hs_db()->prepare('INSERT INTO audit(created_at, actor, action, details) VALUES(?, ?, ?, ?)');
    $st->execute(array(hs_now(), (string) $actor, (string) $action, mb_substr((string) $details, 0, 2000)));
}

function hs_cache_get($key)
{
    $st = hs_db()->prepare('SELECT value FROM cache WHERE key = ? AND expires_at > ?');
    $st->execute(array($key, time()));
    $v = $st->fetchColumn();
    return $v === false ? null : json_decode($v, true);
}

function hs_cache_set($key, $value, $ttl)
{
    $st = hs_db()->prepare('INSERT OR REPLACE INTO cache(key, value, expires_at) VALUES(?, ?, ?)');
    $st->execute(array($key, json_encode($value, JSON_UNESCAPED_UNICODE), time() + (int) $ttl));
}

/** Ariza holatlari — tartibi panelda shunday ko'rinadi. */
function hs_lead_statuses()
{
    return array(
        'yangi' => 'Yangi',
        'qongiroq' => "Qo'ng'iroq qilindi",
        'sotildi' => 'Sotildi',
        'rad' => 'Rad etildi',
    );
}
