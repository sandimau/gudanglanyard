-- Instalasi fitur resep produk custom untuk database MySQL/MariaDB yang SUDAH ada.
-- Pilih database aplikasi di phpMyAdmin, lalu impor file ini satu kali.
-- Prasyarat: tabel produk_kategori_utamas, produk_kategoris, produk_models,
-- produks, orders/order_details, project_mp_details, ambil_barangs, dan migrations
-- sudah ada. SQL ini TIDAK membuat ulang tabel lama dan TIDAK mengubah order lama.
-- Aman dijalankan ulang: kolom baru dicek, tabel baru memakai IF NOT EXISTS,
-- dan baris kategori/migrasi hanya ditambahkan bila belum ada.

-- Kolom produksi mungkin sudah ada pada database yang dikelola di luar migrasi awal.
SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produk_kategori_utamas' AND COLUMN_NAME = 'produksi') = 0,
    'ALTER TABLE `produk_kategori_utamas` ADD COLUMN `produksi` tinyint(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produk_models' AND COLUMN_NAME = 'produksi') = 0,
    'ALTER TABLE `produk_models` ADD COLUMN `produksi` tinyint(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produk_kategori_utamas' AND COLUMN_NAME = 'is_custom') = 0,
    'ALTER TABLE `produk_kategori_utamas` ADD COLUMN `is_custom` tinyint(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

CREATE TABLE IF NOT EXISTS `produk_custom_reseps` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `produk_id` bigint unsigned NOT NULL,
    `jenis` varchar(12) NOT NULL,
    `komponen_model_id` bigint unsigned DEFAULT NULL,
    `jumlah` decimal(12,2) NOT NULL DEFAULT 1.00,
    `keterangan` varchar(255) DEFAULT NULL,
    `biaya` int unsigned DEFAULT NULL,
    `per_order` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `produk_custom_reseps_produk_id_foreign` (`produk_id`),
    KEY `produk_custom_reseps_komponen_model_id_foreign` (`komponen_model_id`),
    CONSTRAINT `produk_custom_reseps_produk_id_foreign`
        FOREIGN KEY (`produk_id`) REFERENCES `produks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `produk_custom_reseps_komponen_model_id_foreign`
        FOREIGN KEY (`komponen_model_id`) REFERENCES `produk_models` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_details' AND COLUMN_NAME = 'material_ids') = 0,
    'ALTER TABLE `order_details` ADD COLUMN `material_ids` varchar(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_details' AND COLUMN_NAME = 'custom_resep_disalin_at') = 0,
    'ALTER TABLE `order_details` ADD COLUMN `custom_resep_disalin_at` timestamp NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_mp_details' AND COLUMN_NAME = 'material_ids') = 0,
    'ALTER TABLE `project_mp_details` ADD COLUMN `material_ids` varchar(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

SET @resep_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_mp_details' AND COLUMN_NAME = 'custom_resep_disalin_at') = 0,
    'ALTER TABLE `project_mp_details` ADD COLUMN `custom_resep_disalin_at` timestamp NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE resep_stmt FROM @resep_sql;
EXECUTE resep_stmt;
DEALLOCATE PREPARE resep_stmt;

CREATE TABLE IF NOT EXISTS `order_custom_bahans` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `sumber` varchar(12) NOT NULL,
    `detail_id` bigint unsigned NOT NULL,
    `produk_id` bigint unsigned NOT NULL,
    `jumlah` int unsigned NOT NULL,
    `hpp` int unsigned NOT NULL DEFAULT 0,
    `ambil_barang_id` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_custom_bahans_sumber_detail_id_produk_id_unique` (`sumber`, `detail_id`, `produk_id`),
    KEY `order_custom_bahans_produk_id_foreign` (`produk_id`),
    KEY `order_custom_bahans_ambil_barang_id_foreign` (`ambil_barang_id`),
    CONSTRAINT `order_custom_bahans_produk_id_foreign`
        FOREIGN KEY (`produk_id`) REFERENCES `produks` (`id`),
    CONSTRAINT `order_custom_bahans_ambil_barang_id_foreign`
        FOREIGN KEY (`ambil_barang_id`) REFERENCES `ambil_barangs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategori utama baru, hanya bila belum ada. Jangan memindahkan produk lama.
INSERT INTO `produk_kategori_utamas`
    (`nama`, `jual`, `beli`, `stok`, `produksi`, `is_custom`, `created_at`, `updated_at`)
SELECT 'Produk Custom', 1, 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `produk_kategori_utamas` WHERE `nama` = 'Produk Custom'
);

UPDATE `produk_kategori_utamas`
SET `is_custom` = 1, `jual` = 1, `beli` = 0, `stok` = 0
WHERE `nama` = 'Produk Custom';

-- Aktifkan resep untuk kategori custom lama tanpa mengubah nilai `produksi`.
UPDATE `produk_kategori_utamas`
SET `is_custom` = 1
WHERE `nama` IN ('CETAK CUSTOM SHOPEE', 'CETAK CUSTOM OFLINE')
  AND `jual` = 1 AND `stok` = 0;

-- Supaya `php artisan migrate` berikutnya tidak mengulang migrasi fitur ini.
-- Catat hanya bila tabel dan semua kolom fitur sudah tersedia.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_000001_create_produk_custom_resep', batch_berikutnya.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) AS batch_berikutnya
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_16_000001_create_produk_custom_resep'
)
AND (SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME IN ('produk_custom_reseps', 'order_custom_bahans')) = 2
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND ((TABLE_NAME = 'produk_kategori_utamas' AND COLUMN_NAME IN ('produksi', 'is_custom'))
         OR (TABLE_NAME = 'produk_models' AND COLUMN_NAME = 'produksi')
         OR (TABLE_NAME IN ('order_details', 'project_mp_details')
             AND COLUMN_NAME IN ('material_ids', 'custom_resep_disalin_at')))) = 7;
