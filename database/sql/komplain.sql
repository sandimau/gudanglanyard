-- SQL manual untuk tiga migrasi fitur Komplain (MySQL / MariaDB).
-- Jalankan pada database yang BELUM menjalankan migrasi 2026_11_03_000001 s.d. 000003.
-- Tidak perlu dijalankan jika ketiga migrasi tersebut sudah tercatat di tabel migrations.
-- Urutan: tabel komplains, kolom pada detail, kemudian permission.

-- 2026_11_03_000001_create_komplains_table.php
CREATE TABLE `komplains` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cabang_id` BIGINT UNSIGNED NULL,
    `order_id` BIGINT UNSIGNED NULL,
    `order_detail_id` BIGINT UNSIGNED NULL,
    `project_mp_id` BIGINT UNSIGNED NULL,
    `project_mp_detail_id` BIGINT UNSIGNED NULL,
    `produk_id` BIGINT UNSIGNED NULL,
    `kontak_id` BIGINT UNSIGNED NULL,
    `nama_konsumen` VARCHAR(255) NULL,
    `nota` VARCHAR(255) NULL,
    `tipe` VARCHAR(15) NOT NULL DEFAULT 'offline',
    `jumlah` INT UNSIGNED NOT NULL DEFAULT 1,
    `bagian_salah` VARCHAR(255) NOT NULL,
    `keterangan` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `catatan_admin` VARCHAR(255) NULL,
    `kerugian` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `replacement_order_detail_id` BIGINT UNSIGNED NULL,
    `replacement_project_mp_detail_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `komplains_status_tipe_index` (`status`, `tipe`),
    CONSTRAINT `komplains_cabang_id_foreign` FOREIGN KEY (`cabang_id`) REFERENCES `cabangs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `komplains_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_order_detail_id_foreign` FOREIGN KEY (`order_detail_id`) REFERENCES `order_details` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_project_mp_id_foreign` FOREIGN KEY (`project_mp_id`) REFERENCES `project_mps` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_project_mp_detail_id_foreign` FOREIGN KEY (`project_mp_detail_id`) REFERENCES `project_mp_details` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_kontak_id_foreign` FOREIGN KEY (`kontak_id`) REFERENCES `kontaks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_replacement_order_detail_id_foreign` FOREIGN KEY (`replacement_order_detail_id`) REFERENCES `order_details` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `komplains_replacement_project_mp_detail_id_foreign` FOREIGN KEY (`replacement_project_mp_detail_id`) REFERENCES `project_mp_details` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2026_11_03_000002_add_komplain_id_to_detail_tables.php
ALTER TABLE `order_details`
    ADD COLUMN `komplain_id` BIGINT UNSIGNED NULL AFTER `order_id`,
    ADD CONSTRAINT `order_details_komplain_id_foreign` FOREIGN KEY (`komplain_id`) REFERENCES `komplains` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `project_mp_details`
    ADD COLUMN `komplain_id` BIGINT UNSIGNED NULL AFTER `project_id`,
    ADD CONSTRAINT `project_mp_details_komplain_id_foreign` FOREIGN KEY (`komplain_id`) REFERENCES `komplains` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2026_11_03_000003_add_komplain_permission.php
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'komplain_access', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'komplain_access' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'komplain_approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'komplain_approve' AND `guard_name` = 'web');

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE p.`name` = 'komplain_access' AND p.`guard_name` = 'web'
  AND r.`guard_name` = 'web'
  AND r.`name` IN ('super', 'Manager', 'Marketing', 'CS_ONLINE', 'supervisor', 'Setting', 'Produksi');

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE p.`name` = 'komplain_approve' AND p.`guard_name` = 'web'
  AND r.`guard_name` = 'web'
  AND r.`name` IN ('super', 'Manager');

-- Tandai migrasi sebagai selesai agar php artisan migrate tidak mencoba mengulanginya.
SET @komplain_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_03_000001_create_komplains_table', @komplain_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_03_000001_create_komplains_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_03_000002_add_komplain_id_to_detail_tables', @komplain_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_03_000002_add_komplain_id_to_detail_tables');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_03_000003_add_komplain_permission', @komplain_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_03_000003_add_komplain_permission');

-- Sesudah impor, jalankan: php artisan permission:cache-reset
