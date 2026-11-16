-- Fitur Ambil Barang: SQL manual untuk phpMyAdmin (MySQL / MariaDB).
-- Setara dengan migrasi:
--   2026_11_16_000001_create_ambil_barangs_table
--   2026_11_16_000002_add_archive_fields_to_ambil_barangs
--
-- Cara pakai: backup database, pilih database aplikasi di phpMyAdmin,
-- buka tab SQL, lalu jalankan seluruh isi file ini (atau impor file .sql).
-- Pastikan tabel cabangs, orders, order_details, project_mps,
-- project_mp_details, produks, produk_models, produksis, produk_stoks,
-- kontaks, dan migrations sudah tersedia beserta kolom cabang_id.
-- Jangan jalankan berbarengan dengan php artisan migrate untuk fitur ini.
-- Dapat dijalankan ulang bila perlu: tabel/kolom/migrasi hanya dibuat jika belum ada;
-- backfill memakai kunci unik supaya order tidak ditandai dua kali.
-- DDL di MySQL memicu implicit commit, jadi jangan bungkus dengan TRANSACTION.

CREATE TABLE IF NOT EXISTS `ambil_barangs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sumber` VARCHAR(20) NOT NULL,
    `detail_id` BIGINT UNSIGNED NOT NULL,
    `produk_id` BIGINT UNSIGNED NOT NULL,
    `cabang_id` BIGINT UNSIGNED NOT NULL,
    `jumlah` INT UNSIGNED NOT NULL,
    `produk_stok_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `nota` VARCHAR(100) NULL,
    `pembeli` VARCHAR(255) NULL,
    `jenis` VARCHAR(30) NULL,
    `shipping` VARCHAR(255) NULL,
    `tanggal_order` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ambil_barangs_sumber_detail_id_unique` (`sumber`, `detail_id`),
    KEY `ambil_barangs_cabang_id_produk_id_index` (`cabang_id`, `produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cek_stok_kosongs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cabang_id` BIGINT UNSIGNED NOT NULL,
    `produk_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `cek_stok_kosongs_cabang_id_produk_id_index` (`cabang_id`, `produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika migrasi pertama sudah pernah dijalankan tetapi migrasi arsip belum,
-- tambahkan hanya kolom yang masih kurang (kompatibel dengan MySQL/MariaDB).
SET @kolom_ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ambil_barangs' AND COLUMN_NAME = 'nota');
SET @perintah := IF(@kolom_ada = 0, 'ALTER TABLE `ambil_barangs` ADD COLUMN `nota` VARCHAR(100) NULL', 'SELECT 1');
PREPARE stmt FROM @perintah; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @kolom_ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ambil_barangs' AND COLUMN_NAME = 'pembeli');
SET @perintah := IF(@kolom_ada = 0, 'ALTER TABLE `ambil_barangs` ADD COLUMN `pembeli` VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @perintah; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @kolom_ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ambil_barangs' AND COLUMN_NAME = 'jenis');
SET @perintah := IF(@kolom_ada = 0, 'ALTER TABLE `ambil_barangs` ADD COLUMN `jenis` VARCHAR(30) NULL', 'SELECT 1');
PREPARE stmt FROM @perintah; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @kolom_ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ambil_barangs' AND COLUMN_NAME = 'shipping');
SET @perintah := IF(@kolom_ada = 0, 'ALTER TABLE `ambil_barangs` ADD COLUMN `shipping` VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @perintah; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @kolom_ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ambil_barangs' AND COLUMN_NAME = 'tanggal_order');
SET @perintah := IF(@kolom_ada = 0, 'ALTER TABLE `ambil_barangs` ADD COLUMN `tanggal_order` TIMESTAMP NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @perintah; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @pusat_id := COALESCE(
    (SELECT `id` FROM `cabangs` WHERE `kode` = 'PUSAT' ORDER BY `id` LIMIT 1),
    (SELECT `id` FROM `cabangs` WHERE `status` = 1 ORDER BY `id` LIMIT 1),
    0
);

-- Backfill order offline yang stoknya sudah dipotong otomatis dengan kode
-- 'jual'. Hanya tandai bila JUMLAH SELURUH detail untuk order+produk dapat
-- dicocokkan dengan mutasi pada cabang yang sama. Jangan menebak data parsial.
-- ID order bisa sama dengan ID project; kasus project yang juga diproses
-- dikecualikan agar mutasi 'jual' tidak keliru diatribusikan.
INSERT IGNORE INTO `ambil_barangs`
    (`sumber`, `detail_id`, `produk_id`, `cabang_id`, `jumlah`, `created_at`,
     `nota`, `pembeli`, `jenis`, `shipping`, `tanggal_order`)
SELECT 'order', d.id, d.produk_id, COALESCE(NULLIF(o.cabang_id, 0), @pusat_id),
       d.jumlah, NOW(), o.nota, k.nama, 'Offline', o.pengiriman, o.created_at
FROM `order_details` d
JOIN `orders` o ON o.id = d.order_id
JOIN `produks` produk ON produk.id = d.produk_id
JOIN `produk_models` model ON model.id = produk.produk_model_id AND model.stok = 1
LEFT JOIN `kontaks` k ON k.id = o.kontak_id
JOIN (
    SELECT d2.order_id, d2.produk_id, SUM(d2.jumlah) AS total_detail
    FROM `order_details` d2
    JOIN `orders` o2 ON o2.id = d2.order_id
    WHERE d2.deleted_at IS NULL AND o2.deleted_at IS NULL
      AND o2.marketplace IS NULL AND d2.jumlah > 0
    GROUP BY d2.order_id, d2.produk_id
) kebutuhan ON kebutuhan.order_id = d.order_id AND kebutuhan.produk_id = d.produk_id
JOIN (
    SELECT detail_id, produk_id, cabang_id, SUM(COALESCE(kurang, 0)) AS total_mutasi
    FROM `produk_stoks`
    WHERE kode = 'jual' AND deleted_at IS NULL
    GROUP BY detail_id, produk_id, cabang_id
) stok ON stok.detail_id = d.order_id AND stok.produk_id = d.produk_id
      AND stok.cabang_id = COALESCE(NULLIF(o.cabang_id, 0), @pusat_id)
      AND stok.total_mutasi >= kebutuhan.total_detail
WHERE d.deleted_at IS NULL AND o.deleted_at IS NULL
  AND o.marketplace IS NULL AND d.jumlah > 0
  AND NOT EXISTS (
      SELECT 1 FROM `project_mp_details` pd
      JOIN `produksis` ps ON ps.id = pd.produksi_id
      WHERE pd.project_id = d.order_id AND pd.produk_id = d.produk_id
        AND ps.grup <> 'awal' AND ps.grup <> 'batal'
  );

-- Backfill order Shopee lama yang sudah dipotong saat order diterima
-- (mutasi berkode 'shp'). Order baru tanpa mutasi 'shp' tidak ditandai.
INSERT IGNORE INTO `ambil_barangs`
    (`sumber`, `detail_id`, `produk_id`, `cabang_id`, `jumlah`, `created_at`,
     `nota`, `pembeli`, `jenis`, `shipping`, `tanggal_order`)
SELECT 'project', d.id, d.produk_id, COALESCE(NULLIF(p.cabang_id, 0), @pusat_id),
       d.jumlah, NOW(), p.nota, p.konsumen, 'Produk', p.shipping, p.created_at
FROM `project_mp_details` d
JOIN `project_mps` p ON p.id = d.project_id
JOIN `produks` produk ON produk.id = d.produk_id
JOIN `produk_models` model ON model.id = produk.produk_model_id AND model.stok = 1
JOIN (
    SELECT d2.project_id, d2.produk_id, SUM(d2.jumlah) AS total_detail
    FROM `project_mp_details` d2
    WHERE d2.jumlah > 0
    GROUP BY d2.project_id, d2.produk_id
) kebutuhan ON kebutuhan.project_id = d.project_id AND kebutuhan.produk_id = d.produk_id
JOIN (
    SELECT detail_id, produk_id, cabang_id, SUM(COALESCE(kurang, 0)) AS total_mutasi
    FROM `produk_stoks`
    WHERE kode = 'shp' AND deleted_at IS NULL
    GROUP BY detail_id, produk_id, cabang_id
) stok ON stok.detail_id = d.project_id AND stok.produk_id = d.produk_id
      AND stok.cabang_id = COALESCE(NULLIF(p.cabang_id, 0), @pusat_id)
      AND stok.total_mutasi >= kebutuhan.total_detail
WHERE d.jumlah > 0;

-- Jika tabel pengambilan sudah pernah dibuat tanpa kolom arsip, lengkapi
-- snapshot yang masih kosong tanpa mengganti data arsip yang sudah tersimpan.
UPDATE `ambil_barangs` a
JOIN `order_details` d ON a.sumber = 'order' AND d.id = a.detail_id
JOIN `orders` o ON o.id = d.order_id
LEFT JOIN `kontaks` k ON k.id = o.kontak_id
SET a.nota = COALESCE(NULLIF(a.nota, ''), o.nota),
    a.pembeli = COALESCE(NULLIF(a.pembeli, ''), k.nama),
    a.jenis = COALESCE(NULLIF(a.jenis, ''), 'Offline'),
    a.shipping = COALESCE(NULLIF(a.shipping, ''), o.pengiriman),
    a.tanggal_order = COALESCE(a.tanggal_order, o.created_at)
WHERE a.sumber = 'order';

UPDATE `ambil_barangs` a
JOIN `project_mp_details` d ON a.sumber = 'project' AND d.id = a.detail_id
JOIN `project_mps` p ON p.id = d.project_id
SET a.nota = COALESCE(NULLIF(a.nota, ''), p.nota),
    a.pembeli = COALESCE(NULLIF(a.pembeli, ''), p.konsumen),
    a.jenis = COALESCE(NULLIF(a.jenis, ''), 'Produk'),
    a.shipping = COALESCE(NULLIF(a.shipping, ''), p.shipping),
    a.tanggal_order = COALESCE(a.tanggal_order, p.created_at)
WHERE a.sumber = 'project';

-- Daftarkan kedua migrasi agar Artisan tidak mencoba membuat ulang tabel.
SET @ambil_barang_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_000001_create_ambil_barangs_table', @ambil_barang_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_000001_create_ambil_barangs_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_000002_add_archive_fields_to_ambil_barangs', @ambil_barang_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_000002_add_archive_fields_to_ambil_barangs'
);

-- Verifikasi: masing-masing query seharusnya menampilkan tabel/record.
SHOW TABLES LIKE 'ambil_barangs';
SHOW TABLES LIKE 'cek_stok_kosongs';
SELECT migration, batch FROM migrations
WHERE migration IN (
    '2026_11_16_000001_create_ambil_barangs_table',
    '2026_11_16_000002_add_archive_fields_to_ambil_barangs'
);
