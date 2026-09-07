-- Backfill cabang_id yang masih NULL agar omzet/filter cabang konsisten
-- Jalankan di phpMyAdmin setelah kolom cabang_id sudah ada

SET @pusat_id := (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1);
SET @pusat_id := IFNULL(@pusat_id, (SELECT id FROM cabangs ORDER BY id LIMIT 1));

UPDATE orders SET cabang_id = @pusat_id WHERE cabang_id IS NULL;
UPDATE belanjas SET cabang_id = @pusat_id WHERE cabang_id IS NULL;
UPDATE kontaks SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

UPDATE marketplaces SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

UPDATE project_mps pm
LEFT JOIN marketplaces m ON m.id = pm.marketplace_id
SET pm.cabang_id = COALESCE(m.cabang_id, @pusat_id)
WHERE pm.cabang_id IS NULL;
