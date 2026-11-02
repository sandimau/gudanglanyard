-- Jalankan SETELAH 2026_10_30_hpp_referensi.sql dan 2026_10_30_hpp_excel_sync.sql.
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
