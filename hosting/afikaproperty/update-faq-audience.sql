-- Import into the existing Afika database using phpMyAdmin. Backup first.
SET NAMES utf8mb4;
SET @afika_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='faqs' AND COLUMN_NAME='is_ai')=0,'ALTER TABLE `faqs` ADD COLUMN `is_ai` TINYINT(1) NOT NULL DEFAULT 1','SELECT 1');
PREPARE afika_stmt FROM @afika_sql;
EXECUTE afika_stmt;
DEALLOCATE PREPARE afika_stmt;
SET @afika_batch=(SELECT COALESCE(MAX(batch),0)+1 FROM migrations);
INSERT INTO migrations(migration,batch) SELECT '2026_11_15_000003_add_faq_ai_audience',@afika_batch WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_11_15_000003_add_faq_ai_audience');
