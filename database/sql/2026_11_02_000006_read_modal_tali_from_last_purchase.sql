-- MySQL 8 / MariaDB. Setara migrasi 2026_11_02_000006_read_modal_tali_from_last_purchase.php.
-- Jalankan HANYA jika migrasi 000004 dan 000005 telah diterapkan, tetapi 000006 belum.
-- Backup database terlebih dahulu. Alternatif php artisan migrate; jangan jalankan keduanya.
-- HPP rata-rata yang sebelumnya ditimpa tidak dapat direkonstruksi persis:
-- untuk produk yang memiliki riwayat belanja, gunakan harga pembelian terakhir.

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
