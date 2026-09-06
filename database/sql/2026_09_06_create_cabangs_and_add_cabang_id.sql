-- Manual SQL untuk fitur cabang (jika artisan migrate tidak bisa dijalankan)
-- Jalankan di phpMyAdmin / mysql client pada database gudang

CREATE TABLE IF NOT EXISTS `cabangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `kode` varchar(255) DEFAULT NULL,
  `alamat` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cabangs_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cabangs` (`nama`, `kode`, `alamat`, `status`, `created_at`, `updated_at`)
SELECT 'Pusat', 'PUSAT', NULL, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `cabangs` WHERE `kode` = 'PUSAT');

SET @pusat_id := (SELECT `id` FROM `cabangs` WHERE `kode` = 'PUSAT' LIMIT 1);

-- Tambah kolom bila belum ada (abaikan error jika sudah ada)
ALTER TABLE `produk_kategori_utamas` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `produk_kategoris` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `produk_models` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `produks` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `orders` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `belanjas` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;
ALTER TABLE `produk_po` ADD COLUMN `cabang_id` bigint unsigned NULL AFTER `id`;

UPDATE `produk_kategori_utamas` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produk_kategoris` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produk_models` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produks` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `orders` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `belanjas` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produk_po` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produk_pakais` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL;
UPDATE `produksi_produks` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL OR `cabang_id` NOT IN (SELECT `id` FROM `cabangs`);
UPDATE `produk_produksi_bahans` SET `cabang_id` = @pusat_id WHERE `cabang_id` IS NULL OR `cabang_id` NOT IN (SELECT `id` FROM `cabangs`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_06_000001_create_cabangs_and_add_cabang_id', IFNULL(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_06_000001_create_cabangs_and_add_cabang_id'
);
