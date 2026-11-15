-- Afika Property: import into the existing website/webhook database.
-- Select the correct database in phpMyAdmin and back it up first.
-- No existing FAQ, conversation or AI job is deleted.
SET NAMES utf8mb4;

SET @afika_sql = IF(
 (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='faqs' AND COLUMN_NAME='faq_type')=0,
 'ALTER TABLE `faqs` ADD COLUMN `faq_type` VARCHAR(20) NOT NULL DEFAULT ''general''',
 'SELECT ''faq_type already exists'' AS result'
);
PREPARE afika_stmt FROM @afika_sql;
EXECUTE afika_stmt;
DEALLOCATE PREPARE afika_stmt;

SET @afika_sql = IF(
 (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='faqs' AND COLUMN_NAME='is_public')=0,
 'ALTER TABLE `faqs` ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 1',
 'SELECT ''is_public already exists'' AS result'
);
PREPARE afika_stmt FROM @afika_sql;
EXECUTE afika_stmt;
DEALLOCATE PREPARE afika_stmt;

-- Older standalone webhook schemas did not auto-generate FAQ IDs.
ALTER TABLE `faqs` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `whatsapp_ai_control` (
 `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 `enabled` TINYINT(1) NOT NULL DEFAULT 1,
 `updated_at` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `whatsapp_ai_control` (`id`,`enabled`,`updated_at`) VALUES (1,1,UTC_TIMESTAMP());

START TRANSACTION;
INSERT INTO `faqs` (`question`,`answer`,`sort_order`,`is_active`,`is_public`,`faq_type`,`created_at`,`updated_at`)
SELECT 'Nama asisten','Fika',0,1,0,'identity',UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `faq_type`='identity');

INSERT INTO `faqs` (`question`,`answer`,`sort_order`,`is_active`,`is_public`,`faq_type`,`created_at`,`updated_at`)
SELECT 'Jika informasi belum tersedia atau perlu pengecekan admin',
'Untuk informasi tersebut, saya belum bisa memastikan, Kak. Admin kami akan mengambil alih obrolan ini pada jam kerja. Kakak cukup melanjutkan percakapan di sini.',
0,1,0,'handoff',UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `faq_type`='handoff');
COMMIT;

-- Register these two schema changes so a future migrate does not add them again.
CREATE TABLE IF NOT EXISTS `migrations` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `migration` VARCHAR(255) NOT NULL,
 `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @afika_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_15_000001_ensure_shared_ai_control',@afika_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_15_000001_ensure_shared_ai_control');
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_15_000002_add_faq_visibility',@afika_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_15_000002_add_faq_visibility');

SELECT 'FAQ schema update completed' AS result;
