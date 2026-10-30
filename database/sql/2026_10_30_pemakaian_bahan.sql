-- SQL Pemakaian Bahan untuk phpMyAdmin (MySQL 8 / MariaDB).
-- Pilih database aplikasi lalu jalankan seluruh file pada tab SQL.
-- Backup database sebelum menjalankan SQL ini.
-- Aman dijalankan ulang; tidak membuat transaksi stok dan tidak menghapus tabel/data lama.

-- Beberapa instalasi lama belum punya cabang_id di produk_pakais.
-- Halaman Pemakaian dan laporan membutuhkan kolom ini.
SET @kolom_cabang_pakai := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'produk_pakais'
      AND COLUMN_NAME = 'cabang_id'
);
SET @sql_kolom_cabang_pakai := IF(
    @kolom_cabang_pakai = 0,
    'ALTER TABLE `produk_pakais` ADD COLUMN `cabang_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 1'
);
PREPARE stmt_kolom_cabang_pakai FROM @sql_kolom_cabang_pakai;
EXECUTE stmt_kolom_cabang_pakai;
DEALLOCATE PREPARE stmt_kolom_cabang_pakai;

-- Isi cabang catatan lama dari mutasi stok terkait, lalu fallback PUSAT.
-- Jangan ubah cabang yang sudah terisi.
SET @pusat_pakai := (SELECT `id` FROM `cabangs` WHERE `kode` = 'PUSAT' LIMIT 1);
UPDATE `produk_pakais` pp
LEFT JOIN `produk_stoks` ps ON ps.`id` = pp.`produk_stok_id`
SET pp.`cabang_id` = COALESCE(ps.`cabang_id`, @pusat_pakai)
WHERE pp.`cabang_id` IS NULL;

-- Simpan harga katalog per unit untuk pemakaian baru. Catatan lama tetap NULL
-- dan laporan akan menggunakan harga katalog saat laporan dibuka.
SET @kolom_harga_pakai := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'produk_pakais'
      AND COLUMN_NAME = 'harga'
);
SET @sql_kolom_harga_pakai := IF(
    @kolom_harga_pakai = 0,
    'ALTER TABLE `produk_pakais` ADD COLUMN `harga` DECIMAL(15,2) NULL AFTER `hpp`',
    'SELECT 1'
);
PREPARE stmt_kolom_harga_pakai FROM @sql_kolom_harga_pakai;
EXECUTE stmt_kolom_harga_pakai;
DEALLOCATE PREPARE stmt_kolom_harga_pakai;

-- Jika pernah memakai menu Pakai Bahan terpisah, pindahkan catatannya ke
-- produk_pakais. Mutasi stok yang lama tetap dipakai, TIDAK dikurangi lagi.
-- Catatan lama di bahan_pakais tidak dihapus.
SET @tabel_bahan_lama := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bahan_pakais'
);
SET @sql_salin_bahan_lama := IF(
    @tabel_bahan_lama > 0,
    'INSERT INTO `produk_pakais` (`produk_id`, `cabang_id`, `produk_stok_id`, `user_id`, `jumlah`, `hpp`, `harga`, `keterangan`, `created_at`, `updated_at`)
     SELECT b.`produk_id`, b.`cabang_id`, b.`produk_stok_id`, b.`user_id`, b.`jumlah`, b.`hpp`, b.`harga`, b.`keterangan`, b.`created_at`, b.`updated_at`
     FROM `bahan_pakais` b
     WHERE NOT EXISTS (
         SELECT 1 FROM `produk_pakais` p
         WHERE p.`produk_stok_id` <=> b.`produk_stok_id`
           AND (b.`produk_stok_id` IS NOT NULL OR (p.`produk_id` = b.`produk_id` AND p.`created_at` <=> b.`created_at`))
     )',
    'SELECT 1'
);
PREPARE stmt_salin_bahan_lama FROM @sql_salin_bahan_lama;
EXECUTE stmt_salin_bahan_lama;
DEALLOCATE PREPARE stmt_salin_bahan_lama;

-- Tandai dua migrasi yang sudah digantikan oleh SQL ini agar bila di kemudian
-- hari artisan migrate dijalankan, kolom harga tidak ditambahkan dua kali.
SET @batch_pemakaian_bahan := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000003_add_harga_to_produk_pakais_table', @batch_pemakaian_bahan
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000003_add_harga_to_produk_pakais_table'
);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000004_move_bahan_pakais_to_produk_pakais', @batch_pemakaian_bahan
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000004_move_bahan_pakais_to_produk_pakais'
);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000005_add_missing_cabang_to_produk_pakais', @batch_pemakaian_bahan
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_30_000005_add_missing_cabang_to_produk_pakais'
);
