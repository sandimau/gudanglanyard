-- Jalankan SETELAH 2026_10_30_hpp_referensi.sql (MySQL / MariaDB).
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
