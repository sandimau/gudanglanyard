-- MySQL 8 / MariaDB (phpMyAdmin). Pilih database aplikasi dan backup lebih dulu.
-- Setara migrasi 2026_11_02_000002_link_hpp_tali_to_produks.php dan
-- 2026_11_02_000003_add_harga_beli_hpp_to_produks.php.
-- Jalankan setelah hpp_referensis dan produks ada. Aman untuk instalasi yang
-- kedua migration tersebut sudah dijalankan: kolom/FK tidak dibuat ulang.
-- Jangan jalankan bersamaan dengan php artisan migrate untuk kedua migration ini.

SET @hpp_tali_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis' AND COLUMN_NAME = 'produk_id_tali');
SET @hpp_tali_sql := IF(@hpp_tali_col = 0,
    'ALTER TABLE `hpp_referensis` ADD COLUMN `produk_id_tali` BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE hpp_tali_stmt FROM @hpp_tali_sql; EXECUTE hpp_tali_stmt; DEALLOCATE PREPARE hpp_tali_stmt;

SET @hpp_tali_fk := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis'
      AND COLUMN_NAME = 'produk_id_tali' AND REFERENCED_TABLE_NAME = 'produks');
SET @hpp_tali_sql := IF(@hpp_tali_fk = 0,
    'ALTER TABLE `hpp_referensis` ADD CONSTRAINT `hpp_referensis_produk_id_tali_foreign` FOREIGN KEY (`produk_id_tali`) REFERENCES `produks` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE hpp_tali_stmt FROM @hpp_tali_sql; EXECUTE hpp_tali_stmt; DEALLOCATE PREPARE hpp_tali_stmt;

SET @hpp_harga_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produks' AND COLUMN_NAME = 'harga_beli_hpp');
SET @hpp_tali_sql := IF(@hpp_harga_col = 0,
    'ALTER TABLE `produks` ADD COLUMN `harga_beli_hpp` DECIMAL(18,8) NULL', 'SELECT 1');
PREPARE hpp_tali_stmt FROM @hpp_tali_sql; EXECUTE hpp_tali_stmt; DEALLOCATE PREPARE hpp_tali_stmt;

-- Isi produk yang sudah terhubung; jangan menimpa harga HPP yang pernah disimpan.
-- Jika beberapa perhitungan merujuk SKU yang sama, gunakan baris terbaru (id terbesar).
UPDATE `produks` AS p
JOIN `hpp_referensis` AS h ON h.`produk_id_tali` = p.`id` AND h.`jenis` = 'tali'
JOIN (SELECT `produk_id_tali`, MAX(`id`) AS `id`
      FROM `hpp_referensis` WHERE `jenis` = 'tali' AND `produk_id_tali` IS NOT NULL
      GROUP BY `produk_id_tali`) AS latest ON latest.`id` = h.`id`
SET p.`harga_beli_hpp` = ROUND(
        CAST(JSON_UNQUOTE(JSON_EXTRACT(h.`biaya`, '$.modal')) AS DECIMAL(18,8)) /
        CAST(JSON_UNQUOTE(JSON_EXTRACT(h.`biaya`, '$.bagi')) AS DECIMAL(18,8)), 8),
    p.`updated_at` = NOW()
WHERE p.`harga_beli_hpp` IS NULL
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(h.`biaya`, '$.bagi')) AS DECIMAL(18,8)) > 0;

-- Catat sebagai Ran agar artisan migrate tidak mengulang perubahan skema ini.
SET @batch_hpp_tali_inventory := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000002_link_hpp_tali_to_produks', @batch_hpp_tali_inventory
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000002_link_hpp_tali_to_produks');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000003_add_harga_beli_hpp_to_produks', @batch_hpp_tali_inventory
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000003_add_harga_beli_hpp_to_produks');
