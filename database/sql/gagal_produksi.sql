-- SQL manual fitur Produk Gagal (MySQL / MariaDB).
-- Jalankan hanya pada database yang BELUM menjalankan keenam migrasi
-- 2026_11_04_000001 s.d. 2026_11_04_000006.
-- Jika migrasi Laravel sudah dijalankan, JANGAN jalankan file ini lagi.
-- Tabel berikut merupakan hasil akhir gabungan keenam migrasi tersebut.

CREATE TABLE `gagal_produksis` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_detail_id` BIGINT UNSIGNED NULL,
    `tanggal` DATE NOT NULL,
    `jumlah` INT UNSIGNED NOT NULL,
    `tahap` VARCHAR(255) NULL,
    `keterangan` TEXT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `hpp_satuan` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `kerugian` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `nama_produk` VARCHAR(255) NULL,
    `cabang_id` BIGINT UNSIGNED NULL,
    `produk_id` BIGINT UNSIGNED NULL,
    `project_mp_id` BIGINT UNSIGNED NULL,
    `project_mp_detail_id` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    INDEX `gagal_produksis_tanggal_order_detail_id_index` (`tanggal`, `order_detail_id`),
    INDEX `gagal_produksis_cabang_id_index` (`cabang_id`),
    INDEX `gagal_produksis_produk_id_index` (`produk_id`),
    INDEX `gagal_produksis_project_mp_id_index` (`project_mp_id`),
    INDEX `gagal_produksis_project_mp_detail_id_index` (`project_mp_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'gagal_produksi_access', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'gagal_produksi_access' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'gagal_produksi_manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'gagal_produksi_manage' AND `guard_name` = 'web');

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE p.`name` = 'gagal_produksi_access' AND p.`guard_name` = 'web'
  AND r.`guard_name` = 'web'
  AND r.`name` IN ('super', 'admin', 'Manager', 'Marketing', 'CS_ONLINE', 'supervisor', 'Setting', 'Produksi');

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE p.`name` = 'gagal_produksi_manage' AND p.`guard_name` = 'web'
  AND r.`guard_name` = 'web'
  AND r.`name` IN ('super', 'admin', 'Manager', 'supervisor', 'Setting', 'Produksi');

-- Tandai migrasi sebagai selesai supaya Artisan tidak mencoba membuat ulang tabelnya.
SET @gagal_produksi_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
VALUES
    ('2026_11_04_000001_create_gagal_produksis_table', @gagal_produksi_batch),
    ('2026_11_04_000002_add_gagal_produksi_permissions', @gagal_produksi_batch),
    ('2026_11_04_000003_add_kerugian_to_gagal_produksis_table', @gagal_produksi_batch),
    ('2026_11_04_000004_allow_free_product_in_gagal_produksis', @gagal_produksi_batch),
    ('2026_11_04_000005_add_produk_id_to_gagal_produksis', @gagal_produksi_batch),
    ('2026_11_04_000006_link_gagal_produksi_to_project_mps', @gagal_produksi_batch);

-- Sesudah impor manual: php artisan permission:cache-reset
