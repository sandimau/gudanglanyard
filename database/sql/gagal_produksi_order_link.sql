-- SQL manual untuk migrasi 2026_11_04_000006_link_gagal_produksi_to_project_mps.
-- Gunakan hanya bila lima migrasi sebelumnya SUDAH dijalankan, tetapi migrasi 000006 BELUM.
-- Jangan jalankan jika migrasi 000006 sudah tercatat pada tabel migrations.

ALTER TABLE `gagal_produksis`
    ADD COLUMN `project_mp_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `project_mp_detail_id` BIGINT UNSIGNED NULL,
    ADD INDEX `gagal_produksis_project_mp_id_index` (`project_mp_id`),
    ADD INDEX `gagal_produksis_project_mp_detail_id_index` (`project_mp_detail_id`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_04_000006_link_gagal_produksi_to_project_mps', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
