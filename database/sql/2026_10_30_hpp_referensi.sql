-- SQL HPP Bahan & ID Card untuk phpMyAdmin (MySQL 8 / MariaDB).
-- Pilih database aplikasi terlebih dahulu dan backup sebelum menjalankan.
-- Setara migrasi 2026_10_30_000006 dan 2026_10_30_000007.
-- Aman dijalankan ulang: tidak mengubah rincian biaya/SKU yang sudah diedit.
-- Setelah file ini, jalankan 2026_10_30_hpp_excel_sync.sql untuk menyamakan
-- angka pecahan dan produk tambahan dari tab Modal Lanyard / Modal IDCard.

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
