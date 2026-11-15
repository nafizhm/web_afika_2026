-- Only for standalone hosting. Laravel installations use php artisan migrate.
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dedup_key CHAR(64) NOT NULL UNIQUE,
    message_id VARCHAR(191) NOT NULL,
    account VARCHAR(30) NOT NULL,
    chat_jid VARCHAR(191) NOT NULL,
    sender_jid VARCHAR(191) NOT NULL,
    sender_phone VARCHAR(30) NULL,
    sender_name VARCHAR(255) NULL,
    message_type VARCHAR(80) NOT NULL,
    body LONGTEXT NULL,
    raw_payload JSON NOT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX whatsapp_messages_chat_jid_index (chat_jid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

