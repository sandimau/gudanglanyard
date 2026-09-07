-- Manual SQL (Niagahoster / tanpa artisan migrate)
-- Tambah cabang_id ke marketplaces & project_mps
-- Jalankan SETELAH 2026_09_06_create_cabangs_and_add_cabang_id.sql

SET @pusat_id := (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1);
SET @pusat_id := IFNULL(@pusat_id, (SELECT id FROM cabangs ORDER BY id LIMIT 1));

-- marketplaces.cabang_id (abaikan error jika kolom sudah ada)
ALTER TABLE marketplaces
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER id;

UPDATE marketplaces SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

-- project_mps.cabang_id
ALTER TABLE project_mps
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER id;

UPDATE project_mps pm
LEFT JOIN marketplaces m ON m.id = pm.marketplace_id
SET pm.cabang_id = COALESCE(m.cabang_id, @pusat_id)
WHERE pm.cabang_id IS NULL;

-- FK (abaikan error jika sudah ada)
ALTER TABLE marketplaces
  ADD CONSTRAINT marketplaces_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE project_mps
  ADD CONSTRAINT project_mps_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

INSERT INTO migrations (`migration`, `batch`)
SELECT '2026_09_06_000002_add_cabang_id_to_marketplaces_and_project_mps', IFNULL(MAX(`batch`), 0) + 1
FROM migrations
WHERE NOT EXISTS (
  SELECT 1 FROM migrations WHERE migration = '2026_09_06_000002_add_cabang_id_to_marketplaces_and_project_mps'
);
