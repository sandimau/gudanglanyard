-- MySQL 8 / MariaDB (phpMyAdmin). Backup database sebelum impor.
-- Setara migrasi 2026_11_02_000004, 2026_11_02_000005, dan koreksi 000006.
-- Syarat: migrasi HPP sampai 2026_11_02_000003 sudah diterapkan.
-- Gunakan SQL ini sebagai ALTERNATIF php artisan migrate, bukan keduanya.
-- Jika migrasi terkait sudah tercatat di tabel migrations, data tidak diproses ulang.

-- 000004: Modal tali menjadi harga beli dan HPP SKU yang terhubung.
-- Bila lebih dari satu baris merujuk SKU yang sama, baris dengan id terbesar menang.
UPDATE `produks` AS p
JOIN `hpp_referensis` AS h ON h.`produk_id_tali` = p.`id` AND h.`jenis` = 'tali'
SET p.`harga_beli_hpp` = CAST(JSON_UNQUOTE(JSON_EXTRACT(h.`biaya`, '$.modal')) AS DECIMAL(18,8)),
    p.`hpp` = ROUND(CAST(JSON_UNQUOTE(JSON_EXTRACT(h.`biaya`, '$.modal')) AS DECIMAL(18,8))),
    p.`updated_at` = NOW()
WHERE JSON_EXTRACT(h.`biaya`, '$.modal') IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `hpp_referensis` AS newer
      WHERE newer.`jenis` = 'tali' AND newer.`produk_id_tali` = p.`id`
        AND newer.`id` > h.`id` AND JSON_EXTRACT(newer.`biaya`, '$.modal') IS NOT NULL
  )
  AND NOT EXISTS (SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_11_02_000004_sync_modal_tali_to_produk_hpp');

SET @batch_hpp_modal := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000004_sync_modal_tali_to_produk_hpp', @batch_hpp_modal
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000004_sync_modal_tali_to_produk_hpp');

-- 000005: Hubungkan perhitungan tali dengan baris bahan lanyard.
SET @hpp_bahan_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis' AND COLUMN_NAME = 'bahan_id_tali');
SET @hpp_bahan_sql := IF(@hpp_bahan_col = 0,
    'ALTER TABLE `hpp_referensis` ADD COLUMN `bahan_id_tali` BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE hpp_bahan_stmt FROM @hpp_bahan_sql; EXECUTE hpp_bahan_stmt; DEALLOCATE PREPARE hpp_bahan_stmt;

SET @hpp_bahan_fk := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hpp_referensis'
      AND COLUMN_NAME = 'bahan_id_tali' AND REFERENCED_TABLE_NAME = 'hpp_referensis');
SET @hpp_bahan_sql := IF(@hpp_bahan_fk = 0,
    'ALTER TABLE `hpp_referensis` ADD CONSTRAINT `hpp_referensis_bahan_id_tali_foreign` FOREIGN KEY (`bahan_id_tali`) REFERENCES `hpp_referensis` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE hpp_bahan_stmt FROM @hpp_bahan_sql; EXECUTE hpp_bahan_stmt; DEALLOCATE PREPARE hpp_bahan_stmt;

UPDATE `hpp_referensis` AS t
JOIN `hpp_referensis` AS b ON b.`jenis` = 'bahan' AND b.`nama` = CASE t.`nama`
    WHEN 'tali isu 1,5CM' THEN '1,5 CM'
    WHEN 'tali isu 2CM Grade A' THEN '2 CM'
    WHEN 'tali isu 2,5CM' THEN '2,5 CM'
    WHEN 'tali isu 3CM' THEN '03 CM'
    WHEN 'tali isu 4CM' THEN '04 CM'
    ELSE NULL END
SET t.`bahan_id_tali` = b.`id`
WHERE t.`jenis` = 'tali'
  AND NOT EXISTS (SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_11_02_000005_link_hpp_tali_to_bahan');

-- Hanya komponen tali di bahan yang diubah; biaya lainnya tetap.
UPDATE `hpp_referensis` AS b
JOIN `hpp_referensis` AS t ON t.`bahan_id_tali` = b.`id` AND t.`jenis` = 'tali'
SET b.`biaya` = JSON_SET(b.`biaya`, '$.tali', ROUND(
        CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.modal')) AS DECIMAL(18,8)) /
        CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.bagi')) AS DECIMAL(18,8)), 8)),
    b.`updated_at` = NOW()
WHERE b.`jenis` = 'bahan'
  AND t.`nama` IN ('tali isu 1,5CM', 'tali isu 2CM Grade A', 'tali isu 2,5CM', 'tali isu 3CM', 'tali isu 4CM')
  AND JSON_EXTRACT(t.`biaya`, '$.modal') IS NOT NULL
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.bagi')) AS DECIMAL(18,8)) > 0
  AND NOT EXISTS (SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_11_02_000005_link_hpp_tali_to_bahan');

SET @batch_hpp_satuan := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000005_link_hpp_tali_to_bahan', @batch_hpp_satuan
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000005_link_hpp_tali_to_bahan');

-- 000006: Koreksi arah data: Modal berasal dari harga pembelian terakhir.
-- Pulihkan HPP yang terlanjur ditimpa dari harga pembelian terakhir yang tersedia.
-- HPP rata-rata lama tidak dapat direkonstruksi dari harga terakhir saja.
UPDATE `produks` AS p
JOIN (SELECT `produk_id`, MAX(`id`) AS `id` FROM `belanja_details`
      WHERE `deleted_at` IS NULL GROUP BY `produk_id`) AS latest ON latest.`produk_id` = p.`id`
JOIN `belanja_details` AS d ON d.`id` = latest.`id`
SET p.`hpp` = ROUND(d.`harga`)
WHERE EXISTS (SELECT 1 FROM `hpp_referensis` AS t
    WHERE t.`jenis` = 'tali' AND t.`produk_id_tali` = p.`id`)
  AND NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000006_read_modal_tali_from_last_purchase');

UPDATE `produks` AS p
SET p.`harga_beli_hpp` = NULL
WHERE EXISTS (SELECT 1 FROM `hpp_referensis` AS t
    WHERE t.`jenis` = 'tali' AND t.`produk_id_tali` = p.`id`)
  AND NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000006_read_modal_tali_from_last_purchase');

UPDATE `hpp_referensis` AS t
LEFT JOIN (SELECT `produk_id`, MAX(`id`) AS `id` FROM `belanja_details`
           WHERE `deleted_at` IS NULL GROUP BY `produk_id`) AS latest ON latest.`produk_id` = t.`produk_id_tali`
LEFT JOIN `belanja_details` AS d ON d.`id` = latest.`id`
SET t.`biaya` = JSON_SET(t.`biaya`, '$.modal', d.`harga`), t.`updated_at` = NOW()
WHERE t.`jenis` = 'tali' AND t.`produk_id_tali` IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000006_read_modal_tali_from_last_purchase');

UPDATE `hpp_referensis` AS b
JOIN `hpp_referensis` AS t ON t.`bahan_id_tali` = b.`id` AND t.`jenis` = 'tali'
SET b.`biaya` = JSON_SET(b.`biaya`, '$.tali', ROUND(
        CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.modal')) AS DECIMAL(18,8)) /
        CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.bagi')) AS DECIMAL(18,8)), 8)),
    b.`updated_at` = NOW()
WHERE b.`jenis` = 'bahan' AND t.`produk_id_tali` IS NOT NULL
  AND JSON_EXTRACT(t.`biaya`, '$.modal') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.modal')) <> 'null'
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(t.`biaya`, '$.bagi')) AS DECIMAL(18,8)) > 0
  AND NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000006_read_modal_tali_from_last_purchase');

SET @batch_hpp_koreksi := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_000006_read_modal_tali_from_last_purchase', @batch_hpp_koreksi
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_02_000006_read_modal_tali_from_last_purchase');
