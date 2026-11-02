-- =====================================================================
-- FITUR DESAIN - SQL UNTUK PHPMYADMIN
-- Database: database aplikasi gudanglanyard
--
-- Cara pakai:
--   1. Backup database terlebih dahulu.
--   2. Buka phpMyAdmin -> pilih database aplikasi -> tab "SQL".
--   3. Paste seluruh isi file ini, lalu klik "Go".
--   4. Setelah itu jalankan: php artisan permission:cache-reset
--      (atau bersihkan cache aplikasi) agar permission baru terbaca.
--
-- Script ini aman dijalankan ulang:
--   - Tabel memakai CREATE TABLE IF NOT EXISTS.
--   - Kategori hasil migrasi data memakai INSERT IGNORE (tidak dobel).
--   - Permission memakai INSERT IGNORE (tidak dobel).
--   - Grant role memakai INSERT IGNORE (tidak dobel).
--
-- Catatan: versi ini menambahkan tabel `desain_groups` untuk menyimpan
-- urutan kategori dan kolom `tipe` (offline/online) pada tabel `desains`.
-- Jika sebelumnya sudah menjalankan SQL versi lama, cukup jalankan ulang
-- seluruh file ini.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tabel tab/sub kategori proses desain
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `desain_tabs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grup` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warna` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0d6efd',
  `urutan` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Tabel kategori desain
--    Kategori dipakai untuk mengatur urutan grup di halaman Setup Desain
--    dan urutan grup pada halaman Proses Desain.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `desain_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `desain_groups_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Salin kategori dari tab yang sudah ada ke tabel desain_groups.
-- Data yang sudah ada tidak akan terduplikasi karena INSERT IGNORE.
INSERT IGNORE INTO `desain_groups` (`nama`, `urutan`, `created_at`, `updated_at`)
SELECT `grup`, 0, NOW(), NOW()
FROM `desain_tabs`
GROUP BY `grup`
ORDER BY `grup`;

-- Beri urutan awal untuk kategori yang baru disalin (urutan = id mengikuti
-- urutan insert di atas, sehingga tampilannya sama seperti sebelumnya).
UPDATE `desain_groups`
SET `urutan` = `id`
WHERE `urutan` = 0;

-- ---------------------------------------------------------------------
-- 3. Tabel data desain
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `desains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_konsumen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produk_id` bigint unsigned DEFAULT NULL,
  `tema` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipe` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `desain_tab_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `desains_produk_id_foreign` (`produk_id`),
  KEY `desains_desain_tab_id_foreign` (`desain_tab_id`),
  KEY `desains_nama_konsumen_index` (`nama_konsumen`),
  CONSTRAINT `desains_desain_tab_id_foreign` FOREIGN KEY (`desain_tab_id`) REFERENCES `desain_tabs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `desains_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3b. Kolom tipe desain (offline/online)
--     Untuk instalasi yang tabel desains-nya sudah ada.
-- ---------------------------------------------------------------------
SET @kolom_tipe_desain := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'desains'
    AND COLUMN_NAME = 'tipe'
);

SET @sql_tipe_desain := IF(
  @kolom_tipe_desain = 0,
  'ALTER TABLE `desains` ADD COLUMN `tipe` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''offline'' AFTER `tema`',
  'SELECT 1'
);

PREPARE stmt_tipe_desain FROM @sql_tipe_desain;
EXECUTE stmt_tipe_desain;
DEALLOCATE PREPARE stmt_tipe_desain;

-- ---------------------------------------------------------------------
-- 4. Permission menu Desain
--    desain_access       = akses Daftar Desain + Proses Desain
--    desain_setup_access = akses Setup Desain
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('desain_access', 'web', NOW(), NOW()),
('desain_setup_access', 'web', NOW(), NOW());

-- ---------------------------------------------------------------------
-- 5. Beri akses ke role super (opsional, bisa diatur ulang di menu Roles)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
FROM `permissions` p
JOIN `roles` r ON r.`name` = 'super' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('desain_access', 'desain_setup_access')
  AND p.`guard_name` = 'web';

-- ---------------------------------------------------------------------
-- 6. OPSIONAL: tandai migrasi sudah dijalankan.
--    Jalankan bagian ini jika nanti server juga memakai `php artisan migrate`,
--    supaya migrasi tidak mencoba membuat tabel/permission yang sama lagi.
-- ---------------------------------------------------------------------
SET @batch_desain := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000001_create_desain_tables', @batch_desain
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_10_27_000001_create_desain_tables'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000002_add_desain_menu_permissions', @batch_desain
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_10_27_000002_add_desain_menu_permissions'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000003_create_desain_groups', @batch_desain
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_10_27_000003_create_desain_groups'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000004_add_tipe_to_desains_table', @batch_desain
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_10_27_000004_add_tipe_to_desains_table'
);
