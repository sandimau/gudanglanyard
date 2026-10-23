-- Impor sekali melalui tab Import di phpMyAdmin pada database aplikasi.
-- Setara dengan migrasi 2026_10_22_000001 dan 2026_10_22_000002.
-- Tabel chats dan kolom chats.project_mp_id dari migrasi sebelumnya harus sudah ada.

CREATE TABLE IF NOT EXISTS `konten_tabs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `grup` varchar(255) NOT NULL,
  `warna` varchar(7) NOT NULL DEFAULT '#0d6efd',
  `urutan` int unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kontens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `topik` varchar(255) NOT NULL,
  `platform` varchar(255) NOT NULL,
  `waktu_posting` time NOT NULL,
  `tujuan` text NOT NULL,
  `konten_tab_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kontens_konten_tab_id_foreign` (`konten_tab_id`),
  KEY `kontens_tanggal_waktu_posting_index` (`tanggal`, `waktu_posting`),
  CONSTRAINT `kontens_konten_tab_id_foreign`
    FOREIGN KEY (`konten_tab_id`) REFERENCES `konten_tabs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MySQL tidak mendukung ADD COLUMN IF NOT EXISTS di semua versi.
-- PREPARE membuat impor aman diulang jika kolom atau foreign key sudah ada.
SET @konten_column_sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chats' AND COLUMN_NAME = 'konten_id') = 0,
  'ALTER TABLE `chats` ADD COLUMN `konten_id` bigint unsigned NULL AFTER `project_mp_id`',
  'SELECT 1'
);
PREPARE konten_column_stmt FROM @konten_column_sql;
EXECUTE konten_column_stmt;
DEALLOCATE PREPARE konten_column_stmt;

SET @konten_foreign_sql := IF(
  (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chats'
     AND COLUMN_NAME = 'konten_id' AND REFERENCED_TABLE_NAME = 'kontens'
     AND REFERENCED_COLUMN_NAME = 'id') = 0,
  'ALTER TABLE `chats` ADD CONSTRAINT `chats_konten_id_foreign` FOREIGN KEY (`konten_id`) REFERENCES `kontens` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE konten_foreign_stmt FROM @konten_foreign_sql;
EXECUTE konten_foreign_stmt;
DEALLOCATE PREPARE konten_foreign_stmt;

-- Catat migrasi agar php artisan migrate tidak mencoba menjalankannya lagi.
SET @konten_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_22_000001_create_konten_tables', @konten_migration_batch
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_22_000001_create_konten_tables'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_22_000002_add_konten_id_to_chats_table', @konten_migration_batch
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_22_000002_add_konten_id_to_chats_table'
);
