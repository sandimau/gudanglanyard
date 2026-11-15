-- =====================================================================
-- FITUR ASET TETAP
-- File ini setara dengan migrasi:
--   2026_11_15_000001_create_akun_jurnals_tables
--   2026_11_15_000002_create_produk_asets_tables
--   2026_11_15_000003_seed_akun_aset_dan_permissions
--
-- Cara pakai:
--   1. Pilih database di phpMyAdmin.
--   2. Tab Import -> pilih file ini -> Go.
--   3. Setelah import, jalankan di terminal:
--        php artisan permission:cache-reset
--        php artisan config:clear
--
-- File ini aman dijalankan ulang (idempotent):
--   - Tabel memakai CREATE TABLE IF NOT EXISTS.
--   - Kolom buku_besars.jurnal_id hanya ditambah jika belum ada.
--   - Akun, permission, dan role grant hanya ditambah jika belum ada.
--
-- Prasyarat: tabel cabangs, produks, kontaks, akun_details,
--            akun_kategoris, permissions, roles, role_has_permissions
--            sudah ada (struktur bawaan aplikasi).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Header jurnal (akun_jurnals)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `akun_jurnals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `source_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `source_batch_filter` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` bigint NOT NULL DEFAULT '0',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `cabang_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'posted',
  `posted_at` timestamp NULL DEFAULT NULL,
  `reversed_by_jurnal_id` bigint unsigned DEFAULT NULL,
  `reverses_jurnal_id` bigint unsigned DEFAULT NULL,
  `idempoten_kunci` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idempoten_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `akun_jurnals_nomor_unique` (`nomor`),
  UNIQUE KEY `akun_jurnals_idempoten_kunci_unique` (`idempoten_kunci`),
  KEY `akun_jurnals_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `akun_jurnals_cabang_id_index` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Audit log jurnal (akun_jurnal_audit_logs)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `akun_jurnal_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jurnal_id` bigint unsigned NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `before_state` json DEFAULT NULL,
  `after_state` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `akun_jurnal_audit_logs_jurnal_id_index` (`jurnal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tambah kolom jurnal_id ke buku_besars (jika belum ada)
-- ---------------------------------------------------------------------
SET @kolom_jurnal_id := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'buku_besars'
    AND COLUMN_NAME = 'jurnal_id'
);

SET @sql_jurnal_id := IF(
  @kolom_jurnal_id = 0,
  'ALTER TABLE `buku_besars` ADD COLUMN `jurnal_id` BIGINT UNSIGNED NULL AFTER `detail_id`',
  'DO 0'
);
PREPARE stmt_jurnal_id FROM @sql_jurnal_id;
EXECUTE stmt_jurnal_id;
DEALLOCATE PREPARE stmt_jurnal_id;

SET @index_jurnal_id := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'buku_besars'
    AND INDEX_NAME = 'buku_besars_jurnal_id_index'
);

SET @sql_index_jurnal_id := IF(
  @index_jurnal_id = 0,
  'ALTER TABLE `buku_besars` ADD INDEX `buku_besars_jurnal_id_index` (`jurnal_id`)',
  'DO 0'
);
PREPARE stmt_index_jurnal_id FROM @sql_index_jurnal_id;
EXECUTE stmt_index_jurnal_id;
DEALLOCATE PREPARE stmt_index_jurnal_id;

-- ---------------------------------------------------------------------
-- 4. Master aset (produk_asets)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `produk_asets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cabang_id` bigint unsigned DEFAULT NULL,
  `produk_id` bigint unsigned NOT NULL,
  `tanggal_beli` date NOT NULL,
  `waktuPenyusutan` int NOT NULL DEFAULT '1',
  `harga` bigint NOT NULL DEFAULT '0',
  `jumlah` int NOT NULL DEFAULT '1',
  `saldo` bigint NOT NULL DEFAULT '0',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `kontak_id` bigint unsigned DEFAULT NULL,
  `akun_detail_id` bigint unsigned DEFAULT NULL,
  `jurnal_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produk_asets_cabang_id_foreign` (`cabang_id`),
  KEY `produk_asets_produk_id_foreign` (`produk_id`),
  KEY `produk_asets_kontak_id_foreign` (`kontak_id`),
  KEY `produk_asets_akun_detail_id_foreign` (`akun_detail_id`),
  KEY `produk_asets_jurnal_id_foreign` (`jurnal_id`),
  KEY `produk_asets_saldo_tanggal_beli_index` (`saldo`,`tanggal_beli`),
  CONSTRAINT `produk_asets_akun_detail_id_foreign` FOREIGN KEY (`akun_detail_id`) REFERENCES `akun_details` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produk_asets_cabang_id_foreign` FOREIGN KEY (`cabang_id`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produk_asets_jurnal_id_foreign` FOREIGN KEY (`jurnal_id`) REFERENCES `akun_jurnals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produk_asets_kontak_id_foreign` FOREIGN KEY (`kontak_id`) REFERENCES `kontaks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produk_asets_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Detail aset / riwayat saldo (produk_aset_details)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `produk_aset_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `aset_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `kode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debet` bigint NOT NULL DEFAULT '0',
  `kredit` bigint NOT NULL DEFAULT '0',
  `jurnal_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produk_aset_details_jurnal_id_foreign` (`jurnal_id`),
  KEY `produk_aset_details_aset_id_kode_index` (`aset_id`,`kode`),
  KEY `produk_aset_details_aset_id_tanggal_index` (`aset_id`,`tanggal`),
  CONSTRAINT `produk_aset_details_aset_id_foreign` FOREIGN KEY (`aset_id`) REFERENCES `produk_asets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `produk_aset_details_jurnal_id_foreign` FOREIGN KEY (`jurnal_id`) REFERENCES `akun_jurnals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Seed akun standar aset
-- ---------------------------------------------------------------------
INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '12101 Aset Tetap', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'aset tetap' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '12101 Aset Tetap' AND d.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '12201 Akumulasi Penyusutan Aset Tetap', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'aset tetap' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '12201 Akumulasi Penyusutan Aset Tetap' AND d.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '21101 Hutang Usaha', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'hutang' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '21101 Hutang Usaha' AND d.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '42101 Keuntungan Penjualan Aset', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'pendapatan' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '42101 Keuntungan Penjualan Aset' AND d.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '52301 Beban Penyusutan Aset Tetap', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'operasional' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '52301 Beban Penyusutan Aset Tetap' AND d.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO `akun_details` (`nama`, `saldo`, `akun_kategori_id`, `created_at`, `updated_at`)
SELECT '55101 Kerugian Penjualan Aset', 0, k.id, NOW(), NOW()
FROM `akun_kategoris` k
WHERE k.nama = 'operasional' AND k.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `akun_details` d
    WHERE d.nama = '55101 Kerugian Penjualan Aset' AND d.deleted_at IS NULL
  )
LIMIT 1;

-- ---------------------------------------------------------------------
-- 7. Seed permission aset_access & aset_manage
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'aset_access', 'web', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions`
  WHERE `name` = 'aset_access' AND `guard_name` = 'web'
);

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'aset_manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions`
  WHERE `name` = 'aset_manage' AND `guard_name` = 'web'
);

-- Berikan permission ke role super, admin, Manager, dan Keuangan.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r
  ON r.guard_name = 'web'
 AND r.name IN ('super', 'admin', 'Manager', 'Keuangan')
LEFT JOIN `role_has_permissions` rp
  ON rp.permission_id = p.id
 AND rp.role_id = r.id
WHERE p.name IN ('aset_access', 'aset_manage')
  AND p.guard_name = 'web'
  AND rp.permission_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
