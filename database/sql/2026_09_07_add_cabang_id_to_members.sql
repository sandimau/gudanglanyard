-- Manual SQL (Niagahoster / tanpa artisan migrate)
-- Tambah cabang_id ke members
-- Jalankan SETELAH 2026_09_06_create_cabangs_and_add_cabang_id.sql

SET @pusat_id := (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1);
SET @pusat_id := IFNULL(@pusat_id, (SELECT id FROM cabangs ORDER BY id LIMIT 1));

-- members.cabang_id (abaikan error jika kolom sudah ada)
ALTER TABLE members
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER id;

UPDATE members SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

-- FK (abaikan error jika sudah ada)
ALTER TABLE members
  ADD CONSTRAINT members_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

INSERT INTO migrations (`migration`, `batch`)
SELECT '2026_09_07_000001_add_cabang_id_to_members', IFNULL(MAX(`batch`), 0) + 1
FROM migrations
WHERE NOT EXISTS (
  SELECT 1 FROM migrations WHERE migration = '2026_09_07_000001_add_cabang_id_to_members'
);
