-- Manual SQL (Hostinger / tanpa artisan migrate)
-- Satu SKU produk untuk semua cabang; stok & kontak mengikuti cabang.

SET @pusat_id := (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1);
SET @pusat_id := IFNULL(@pusat_id, (SELECT id FROM cabangs ORDER BY id LIMIT 1));

-- produk_stoks.cabang_id
ALTER TABLE produk_stoks
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER produk_id;

UPDATE produk_stoks SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

ALTER TABLE produk_stoks
  ADD CONSTRAINT produk_stoks_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE produk_stoks
  ADD INDEX produk_stoks_produk_cabang_created_index (produk_id, cabang_id, created_at);

-- produk_last_stoks.cabang_id + unique baru
ALTER TABLE produk_last_stoks
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER produk_id;

UPDATE produk_last_stoks SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

-- drop unique lama jika ada
ALTER TABLE produk_last_stoks DROP INDEX produk_last_stoks_produk_tahun_unique;

ALTER TABLE produk_last_stoks
  ADD UNIQUE KEY produk_last_stoks_produk_cabang_tahun_unique (produk_id, cabang_id, tahun);

ALTER TABLE produk_last_stoks
  ADD CONSTRAINT produk_last_stoks_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- kontaks.cabang_id
ALTER TABLE kontaks
  ADD COLUMN cabang_id BIGINT UNSIGNED NULL AFTER id;

UPDATE kontaks SET cabang_id = @pusat_id WHERE cabang_id IS NULL;

ALTER TABLE kontaks
  ADD CONSTRAINT kontaks_cabang_id_foreign
    FOREIGN KEY (cabang_id) REFERENCES cabangs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;
