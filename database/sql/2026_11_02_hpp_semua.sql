-- Semua migrasi HPP saja: 2026_10_30_000006 s.d. 2026_11_02_000001.
-- Tidak menyertakan fitur Desain atau Pemakaian Bahan.
-- MySQL 8 / MariaDB. Pilih database aplikasi; backup sebelum impor.
-- Jalankan file ini saja, jangan bersamaan dengan SQL HPP terpisah.

-- Semua migrasi HPP dan permission hpp_access dalam satu file (MySQL 8 / MariaDB).
-- Pilih database aplikasi dan backup sebelum impor melalui phpMyAdmin.
-- Jalankan file ini saja; tidak perlu mengimpor tiga file SQL HPP terpisah.
-- Aman dijalankan ulang: data HPP yang sudah diedit tidak ditimpa.

-- ============================================================
-- Bagian: database/sql/2026_10_30_hpp_referensi.sql
-- ============================================================
-- SQL HPP Bahan & ID Card untuk phpMyAdmin (MySQL 8 / MariaDB).
-- Pilih database aplikasi terlebih dahulu dan backup sebelum menjalankan.
-- Setara migrasi 2026_10_30_000006 dan 2026_10_30_000007.
-- Aman dijalankan ulang: tidak mengubah rincian biaya/SKU yang sudah diedit.
-- Bagian berikutnya menyamakan angka pecahan dan produk tambahan.

CREATE TABLE IF NOT EXISTS `hpp_referensis` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jenis` VARCHAR(20) NOT NULL,
    `nama` VARCHAR(255) NOT NULL,
    `biaya` JSON NOT NULL,
    `urutan` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hpp_referensis_jenis_urutan_index` (`jenis`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambah penghubung SKU hanya jika belum ada (instalasi yang sudah migrate dilewati).
SET @hpp_col_1s := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis' AND COLUMN_NAME = 'produk_id_1s');
SET @hpp_sql := IF(@hpp_col_1s = 0,
    'ALTER TABLE `hpp_referensis` ADD COLUMN `produk_id_1s` BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE hpp_stmt FROM @hpp_sql; EXECUTE hpp_stmt; DEALLOCATE PREPARE hpp_stmt;

SET @hpp_col_2s := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis' AND COLUMN_NAME = 'produk_id_2s');
SET @hpp_sql := IF(@hpp_col_2s = 0,
    'ALTER TABLE `hpp_referensis` ADD COLUMN `produk_id_2s` BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE hpp_stmt FROM @hpp_sql; EXECUTE hpp_stmt; DEALLOCATE PREPARE hpp_stmt;

SET @hpp_fk_1s := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis'
      AND COLUMN_NAME = 'produk_id_1s' AND REFERENCED_TABLE_NAME = 'produks');
SET @hpp_sql := IF(@hpp_fk_1s = 0,
    'ALTER TABLE `hpp_referensis` ADD CONSTRAINT `hpp_referensis_produk_id_1s_foreign` FOREIGN KEY (`produk_id_1s`) REFERENCES `produks` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE hpp_stmt FROM @hpp_sql; EXECUTE hpp_stmt; DEALLOCATE PREPARE hpp_stmt;

SET @hpp_fk_2s := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis'
      AND COLUMN_NAME = 'produk_id_2s' AND REFERENCED_TABLE_NAME = 'produks');
SET @hpp_sql := IF(@hpp_fk_2s = 0,
    'ALTER TABLE `hpp_referensis` ADD CONSTRAINT `hpp_referensis_produk_id_2s_foreign` FOREIGN KEY (`produk_id_2s`) REFERENCES `produks` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE hpp_stmt FROM @hpp_sql; EXECUTE hpp_stmt; DEALLOCATE PREPARE hpp_stmt;

-- Data awal dari sheet; baris yang sudah ada (meskipun namanya diubah) tidak ditimpa.
INSERT INTO `hpp_referensis` (`jenis`, `nama`, `biaya`, `urutan`, `created_at`, `updated_at`)
SELECT seed.`jenis`, seed.`nama`, seed.`biaya`, seed.`urutan`, NOW(), NOW()
FROM (
    SELECT 'bahan' AS `jenis`, '1,5 CM' AS `nama`, '{"tali":744,"print1":675,"print2":675,"press":400,"cantolan":625,"mataItik":72,"stopper":113,"finishing":400}' AS `biaya`, 0 AS `urutan`
    UNION ALL SELECT 'bahan', '2 CM', '{"tali":800,"print1":844,"print2":844,"press":400,"cantolan":625,"mataItik":76,"stopper":127,"finishing":400}', 1
    UNION ALL SELECT 'bahan', '2,5 CM', '{"tali":1089,"print1":964,"print2":964,"press":400,"cantolan":675,"mataItik":72,"stopper":142,"finishing":400}', 2
    UNION ALL SELECT 'bahan', '03 CM', '{"tali":1222,"print1":1125,"print2":1125,"press":400,"cantolan":0,"mataItik":0,"stopper":0,"finishing":0}', 3
    UNION ALL SELECT 'bahan', '04 CM', '{"tali":1567,"print1":1350,"print2":1350,"press":400,"cantolan":0,"mataItik":0,"stopper":0,"finishing":0}', 4
    UNION ALL SELECT 'bahan', '2 cm gelang', '{"tali":66,"print1":281,"print2":281,"press":133,"cantolan":0,"mataItik":0,"stopper":500,"finishing":0}', 5
    UNION ALL SELECT 'bahan', '2 cm satin lanyard', '{"tali":190,"print1":338,"print2":338,"press":400,"cantolan":585,"mataItik":77,"stopper":113,"finishing":400}', 6
    UNION ALL SELECT 'bahan', '1,5 CM (varian kedua)', '{"tali":622,"print1":675,"print2":675,"press":400,"cantolan":625,"mataItik":72,"stopper":113,"finishing":400}', 7
    UNION ALL SELECT 'bahan', '2 CM (varian kedua)', '{"tali":667,"print1":844,"print2":844,"press":400,"cantolan":625,"mataItik":72,"stopper":135,"finishing":400}', 8
    UNION ALL SELECT 'idcard', 'OFFSET 10rb', '{"kertas":300,"print":70,"overlay":200,"penebal":125,"plong":100,"press":50,"finishing":50}', 0
    UNION ALL SELECT 'idcard', 'OFFSET 20rb', '{"kertas":300,"print":55,"overlay":200,"penebal":125,"plong":100,"press":50,"finishing":50}', 1
    UNION ALL SELECT 'idcard', 'KONIKA 10 Pcs', '{"kertas":400,"print":130,"overlay":200,"penebal":150,"plong":100,"press":50,"finishing":50}', 2
    UNION ALL SELECT 'idcard', 'EPSON 10pcs', '{"kertas":580,"print":25,"overlay":220,"penebal":0,"plong":100,"press":50,"finishing":50}', 3
    UNION ALL SELECT 'idcard', 'RFID 10pcs / KONIKA', '{"kertas":400,"print":130,"overlay":200,"penebal":1300,"plong":100,"press":50,"finishing":50}', 4
    UNION ALL SELECT 'idcard', 'PROXY 10pcs / KONIKA', '{"kertas":400,"print":130,"overlay":200,"penebal":1300,"plong":100,"press":50,"finishing":50}', 5
    UNION ALL SELECT 'idcard', 'SILVER 10pcs', '{"kertas":577,"print":25,"overlay":200,"penebal":125,"plong":100,"press":50,"finishing":50}', 6
    UNION ALL SELECT 'aksesori', 'Emboss', '{"harga":200}', 16
    UNION ALL SELECT 'aksesori', 'Panel', '{"harga":100}', 17
    UNION ALL SELECT 'aksesori', 'Gliter', '{"harga":100}', 18
    UNION ALL SELECT 'aksesori', 'Foil', '{"harga":100}', 19
    UNION ALL SELECT 'aksesori', 'Magnetik', '{"harga":200}', 20
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `hpp_referensis` existing
    WHERE existing.`jenis` = seed.`jenis` AND existing.`urutan` = seed.`urutan`);

-- Tandai kedua migrasi agar php artisan migrate kelak tidak membuat ulang tabel/kolom.
SET @batch_hpp := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000006_create_hpp_referensis_table', @batch_hpp
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000006_create_hpp_referensis_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000007_link_hpp_bahan_to_produks', @batch_hpp
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000007_link_hpp_bahan_to_produks');

-- ============================================================
-- Bagian: database/sql/2026_10_30_hpp_excel_sync.sql
-- ============================================================
-- Sinkronisasi data bawaan dari Excel (MySQL / MariaDB).
-- Setara migrasi 2026_10_30_000008_sync_hpp_from_excel.php.
-- Koreksi hanya komponen bawaan yang masih sama dengan versi awal.
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(33500 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '1,5 CM' AND JSON_EXTRACT(`biaya`, '$.tali') = 744;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.stopper', ROUND(15250 / 120, 8))
WHERE `jenis` = 'bahan' AND `nama` = '2 CM' AND JSON_EXTRACT(`biaya`, '$.stopper') = 127;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(49000 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '2,5 CM' AND JSON_EXTRACT(`biaya`, '$.tali') = 1089;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(55000 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '03 CM' AND JSON_EXTRACT(`biaya`, '$.tali') = 1222;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(70500 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '04 CM' AND JSON_EXTRACT(`biaya`, '$.tali') = 1567;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.print1', 337.5)
WHERE `jenis` = 'bahan' AND `nama` = '2 cm satin lanyard' AND JSON_EXTRACT(`biaya`, '$.print1') = 338;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.print2', 337.5)
WHERE `jenis` = 'bahan' AND `nama` = '2 cm satin lanyard' AND JSON_EXTRACT(`biaya`, '$.print2') = 338;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(28000 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '1,5 CM (varian kedua)' AND JSON_EXTRACT(`biaya`, '$.tali') = 622;
UPDATE `hpp_referensis` SET `biaya` = JSON_SET(`biaya`, '$.tali', ROUND(30000 / 45, 8))
WHERE `jenis` = 'bahan' AND `nama` = '2 CM (varian kedua)' AND JSON_EXTRACT(`biaya`, '$.tali') = 667;

-- Tambahkan hanya nama yang belum ada; nilai yang sudah disimpan pengguna tetap utuh.
INSERT INTO `hpp_referensis` (`jenis`, `nama`, `biaya`, `urutan`, `created_at`, `updated_at`)
SELECT seed.`jenis`, seed.`nama`, seed.`biaya`, seed.`urutan`, NOW(), NOW()
FROM (
    SELECT 'idcard' AS `jenis`, 'K NAMA EPSON 2 muka' AS `nama`, '{"kertas":840,"print":25,"overlay":220,"penebal":0,"plong":100,"press":50,"finishing":50}' AS `biaya`, 21 AS `urutan`
    UNION ALL SELECT 'idcard', 'K NAMA LASER 2 muka', '{"kertas":400,"print":110,"overlay":200,"penebal":0,"plong":100,"press":50,"finishing":50}', 22
    UNION ALL SELECT 'idcard', 'K NAMA EPSON 1 muka', '{"kertas":300,"print":25,"overlay":200,"penebal":0,"plong":100,"press":50,"finishing":50}', 23
    UNION ALL SELECT 'idcard', 'K NAMA LASER 1 muka', '{"kertas":155,"print":130,"overlay":200,"penebal":0,"plong":100,"press":50,"finishing":50}', 24
    UNION ALL SELECT 'idcard', 'STICKER PVC', '{"kertas":225,"print":130,"overlay":200,"penebal":0,"plong":100,"press":50,"finishing":50}', 25
    UNION ALL SELECT 'idcard', 'STICKER PVC COLD PRESS', '{"kertas":450,"print":130,"overlay":0,"penebal":0,"plong":111,"press":50,"finishing":0}', 26
    UNION ALL SELECT 'idcard', 'Hologram K-POP', '{"kertas":941,"print":110,"overlay":216,"penebal":81,"plong":100,"press":50,"finishing":0}', 27
    UNION ALL SELECT 'idcard', 'Logo Hologram UK 1,5cm', '{"kertas":0,"print":81,"overlay":0,"penebal":0,"plong":0,"press":0,"finishing":0}', 28
    UNION ALL SELECT 'idcard', 'Glitter Sticker (Laser) 10pcs', '{"kertas":500,"print":200,"overlay":350,"penebal":207,"plong":100,"press":50,"finishing":0}', 29
    UNION ALL SELECT 'idcard', 'Glitter Sticker (EPSON) 10pcs', '{"kertas":540,"print":200,"overlay":350,"penebal":0,"plong":100,"press":50,"finishing":0}', 30
    UNION ALL SELECT 'idcard', 'Glitter Sticker (Offset) 10pcs', '{"kertas":351,"print":78,"overlay":350,"penebal":81,"plong":100,"press":50,"finishing":0}', 31
    UNION ALL SELECT 'resin', 'RESIN A4_1 pcs', '{"stiker":3150,"cetak":600,"resin":197,"cutting":2000,"makloon":150}', 32
    UNION ALL SELECT 'resin', 'RESIN A4_10 pcs', '{"stiker":315,"cetak":60,"resin":197,"cutting":200,"makloon":150}', 33
    UNION ALL SELECT 'resin', 'RESIN A4_126 pcs', '{"stiker":25,"cetak":5,"resin":197,"cutting":15,"makloon":150}', 34
    UNION ALL SELECT 'bahan', '1,5 CM (sablon)', '{"tali":622,"print1":750,"print2":750,"press":0,"cantolan":585,"mataItik":77,"stopper":113,"finishing":400}', 35
    UNION ALL SELECT 'bahan', '02 CM (sablon)', '{"tali":666,"print1":750,"print2":750,"press":0,"cantolan":630,"mataItik":77,"stopper":135,"finishing":400}', 36
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `hpp_referensis` existing
    WHERE existing.`jenis` = seed.`jenis` AND existing.`nama` = seed.`nama`);

SET @batch_hpp_excel := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000008_sync_hpp_from_excel', @batch_hpp_excel
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000008_sync_hpp_from_excel');

-- ============================================================
-- Bagian: database/sql/2026_10_31_hpp_tali_satuan.sql
-- ============================================================
-- Tambahan perhitungan tali per satuan.
-- MySQL 8 / MariaDB; setara migrasi 2026_10_31_000001_add_hpp_tali_satuan.php.
-- Memakai tabel hpp_referensis yang sudah ada; tidak mengubah data yang sudah diedit.

INSERT INTO `hpp_referensis` (`jenis`, `nama`, `biaya`, `urutan`, `created_at`, `updated_at`)
SELECT seed.`jenis`, seed.`nama`, seed.`biaya`, seed.`urutan`, NOW(), NOW()
FROM (
    SELECT 'tali' AS `jenis`, 'tali isu 1CM' AS `nama`, '{"modal":24500,"bagi":45}' AS `biaya`, 0 AS `urutan`
    UNION ALL SELECT 'tali', 'tali isu 1,5CM', '{"modal":33500,"bagi":45}', 1
    UNION ALL SELECT 'tali', 'tali isu 2CM Grade A', '{"modal":36000,"bagi":45}', 2
    UNION ALL SELECT 'tali', 'tali isu 2,5CM', '{"modal":49000,"bagi":45}', 3
    UNION ALL SELECT 'tali', 'tali isu 3CM', '{"modal":55000,"bagi":45}', 4
    UNION ALL SELECT 'tali', 'tali isu 4CM', '{"modal":70500,"bagi":45}', 5
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `hpp_referensis` existing
    WHERE existing.`jenis` = seed.`jenis` AND existing.`nama` = seed.`nama`);

-- Tandai migrasi agar php artisan migrate tidak mengulanginya setelah impor SQL manual.
SET @batch_hpp_tali := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_31_000001_add_hpp_tali_satuan', @batch_hpp_tali
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_31_000001_add_hpp_tali_satuan');

-- Permission khusus HPP, terpisah dari produk_access. Akses role lain diatur lewat Roles.
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
VALUES ('hpp_access', 'web', NOW(), NOW());

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
FROM `permissions` p
JOIN `roles` r ON r.`name` = 'super' AND r.`guard_name` = 'web'
WHERE p.`name` = 'hpp_access' AND p.`guard_name` = 'web';

SET @batch_hpp_permission := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_31_000002_add_hpp_permission', @batch_hpp_permission
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_31_000002_add_hpp_permission');

-- Bagian: 2026_11_02_hpp_idcard_satuan.sql
-- Setara migrasi 2026_11_02_000001_add_hpp_idcard_satuan.php.
-- Jalankan setelah tabel hpp_referensis ada. Aman dijalankan ulang;
-- nilai yang sudah disunting tidak diubah.
INSERT INTO `hpp_referensis` (`jenis`, `nama`, `biaya`, `urutan`, `created_at`, `updated_at`)
SELECT 'idcard_satuan', seed.`nama`, seed.`biaya`, seed.`urutan`, NOW(), NOW()
FROM (
    SELECT 'Offset A3 0.3' AS `nama`, '{"modal":3000,"bagi":null}' AS `biaya`, 0 AS `urutan`
    UNION ALL SELECT 'Offset A4 Lem 0.15', '{"modal":2100,"bagi":5}', 1
    UNION ALL SELECT 'Pvc Laser A3 0.2', '{"modal":4000,"bagi":null}', 2
    UNION ALL SELECT 'Inkjet A3 0.35', '{"modal":5800,"bagi":10}', 3
    UNION ALL SELECT 'Inkjet Silver a3', '{"modal":5775,"bagi":null}', 4
    UNION ALL SELECT 'Overlay A4 0.1', '{"modal":1100,"bagi":5}', 5
    UNION ALL SELECT 'Print Epson', '{"modal":25,"bagi":null}', 6
    UNION ALL SELECT 'Print Laser', '{"modal":130,"bagi":null}', 7
    UNION ALL SELECT 'Print Ofset Jkt', '{"modal":75,"bagi":null}', 8
    UNION ALL SELECT 'Inley S50 Fudan Sc', '{"modal":12500,"bagi":null}', 9
    UNION ALL SELECT 'Inlay Em4100/Proximity', '{"modal":11750,"bagi":null}', 10
    UNION ALL SELECT 'Press', '{"modal":50,"bagi":null}', 11
    UNION ALL SELECT 'Plong', '{"modal":100,"bagi":null}', 12
    UNION ALL SELECT 'Emboss', '{"modal":200,"bagi":null}', 13
    UNION ALL SELECT 'Resin', '{"modal":153,"bagi":null}', 14
    UNION ALL SELECT 'Pisau Pond / Cm', '{"modal":null,"bagi":null}', 15
    UNION ALL SELECT 'Lubang Pisau Pond', '{"modal":null,"bagi":null}', 16
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `hpp_referensis` existing
    WHERE existing.`jenis` = 'idcard_satuan' AND existing.`nama` = seed.`nama`
);

SET @batch_idcard_satuan := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000001_add_hpp_idcard_satuan', @batch_idcard_satuan
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000001_add_hpp_idcard_satuan');
