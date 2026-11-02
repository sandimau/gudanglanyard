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
