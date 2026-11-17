-- Rekonsiliasi order offline/online yang stoknya SUDAH dipotong.
-- MySQL/MariaDB, dijalankan di tab SQL phpMyAdmin. Backup database dahulu.
-- TIDAK mengubah produk_stoks atau saldo. Jalankan tiap langkah secara terpisah
-- (satu klik Kirim per perintah) agar tidak melampaui max_statement_time.
-- Tabel penampung biasa, bukan TEMPORARY: phpMyAdmin bisa berganti koneksi.
-- Jika pernah menjalankan versi lama, tabel calon_ambil_barang_lama_20261117
-- mungkin sudah ada. Periksa isinya SEBELUM menghapus/mengulang dari awal.
-- Hanya satu detail dan satu mutasi tepat per dokumen+produk yang ditandai.
-- Mutasi parsial, detail ganda, retur, atau bukti tidak jelas tidak ditandai.

-- LANGKAH 1: Ringkas mutasi SEKALI (hindari pencarian ulang seluruh stok
-- untuk setiap order). Jalankan CREATE dahulu, kemudian ALTER terpisah.
CREATE TABLE rekon_stok_20261117 AS
SELECT detail_id, produk_id, cabang_id, kode,
       TRIM(keterangan) AS bukti_mutasi, COUNT(*) AS banyak_mutasi,
       MIN(id) AS produk_stok_id, MIN(created_at) AS tanggal_mutasi,
       SUM(COALESCE(kurang, 0)) AS kurang, SUM(COALESCE(tambah, 0)) AS tambah
FROM produk_stoks
WHERE deleted_at IS NULL AND detail_id IS NOT NULL
  AND kode IN ('jual', 'shp', 'btl')
GROUP BY detail_id, produk_id, cabang_id, kode, TRIM(keterangan);

ALTER TABLE rekon_stok_20261117
ADD INDEX rekon_stok_dokumen (detail_id, produk_id, cabang_id, kode);

-- LANGKAH 2: Hitung jumlah detail online per dokumen+produk SEKALI.
-- project_mp_details pada beberapa database belum punya indeks project_id.
CREATE TABLE rekon_detail_project_20261117 AS
SELECT project_id, produk_id, COUNT(*) AS banyak_detail
FROM project_mp_details
WHERE jumlah > 0
GROUP BY project_id, produk_id;

ALTER TABLE rekon_detail_project_20261117
ADD INDEX rekon_detail_project_dokumen (project_id, produk_id);

-- LANGKAH 3: Indeks mutasi yang sudah dipakai arsip (tanpa mengubah arsip).
CREATE TABLE rekon_arsip_stok_20261117 AS
SELECT produk_stok_id FROM ambil_barangs WHERE produk_stok_id IS NOT NULL;

ALTER TABLE rekon_arsip_stok_20261117
ADD INDEX rekon_arsip_stok_id (produk_stok_id);

-- LANGKAH 4: Calon order offline. ID order bisa beririsan dengan ID project;
-- nama pembeli pada keterangan membedakan dua transaksi tersebut.
CREATE TABLE calon_ambil_barang_lama_20261117 AS
SELECT 'order' AS sumber, d.id AS detail_id, d.produk_id,
       s.cabang_id, d.jumlah, s.produk_stok_id,
       o.nota, k.nama AS pembeli, 'Offline' AS jenis,
       o.pengiriman AS shipping, o.created_at AS tanggal_order,
       s.tanggal_mutasi, s.bukti_mutasi
FROM order_details d
JOIN orders o ON o.id = d.order_id AND o.deleted_at IS NULL AND o.marketplace IS NULL
JOIN kontaks k ON k.id = o.kontak_id AND TRIM(k.nama) <> ''
JOIN produks produk ON produk.id = d.produk_id
JOIN produk_models model ON model.id = produk.produk_model_id AND model.stok = 1
JOIN rekon_stok_20261117 s ON s.detail_id = o.id AND s.produk_id = d.produk_id
    AND s.cabang_id = o.cabang_id AND s.kode = 'jual'
    AND s.banyak_mutasi = 1 AND s.tambah = 0 AND s.kurang = d.jumlah
    AND s.bukti_mutasi = CONCAT('barang dijual ke ', TRIM(k.nama))
    AND s.tanggal_mutasi >= o.created_at
LEFT JOIN rekon_stok_20261117 balik ON balik.detail_id = o.id
    AND balik.produk_id = d.produk_id AND balik.cabang_id = o.cabang_id
    AND balik.kode = 'btl' AND balik.tambah > 0
LEFT JOIN rekon_arsip_stok_20261117 dipakai ON dipakai.produk_stok_id = s.produk_stok_id
LEFT JOIN ambil_barangs a ON a.sumber = 'order' AND a.detail_id = d.id
WHERE d.deleted_at IS NULL AND d.jumlah > 0 AND o.cabang_id IS NOT NULL
  AND balik.detail_id IS NULL AND dipakai.produk_stok_id IS NULL AND a.id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM order_details d2
      WHERE d2.order_id = o.id AND d2.produk_id = d.produk_id
        AND d2.deleted_at IS NULL AND d2.jumlah > 0 AND d2.id <> d.id
  )
  AND NOT EXISTS (
      SELECT 1 FROM rekon_stok_20261117 s2
      WHERE s2.detail_id = o.id AND s2.produk_id = d.produk_id
        AND s2.cabang_id = o.cabang_id AND s2.kode = 'jual'
        AND s2.bukti_mutasi = s.bukti_mutasi AND s2.produk_stok_id <> s.produk_stok_id
  );

-- LANGKAH 5: Tambahkan order online dengan mutasi 'shp' yang menyebut
-- nomor nota persis di akhir keterangan. Status Produk saja tidak cukup.
INSERT INTO calon_ambil_barang_lama_20261117
    (sumber, detail_id, produk_id, cabang_id, jumlah, produk_stok_id,
     nota, pembeli, jenis, shipping, tanggal_order, tanggal_mutasi, bukti_mutasi)
SELECT 'project', d.id, d.produk_id, s.cabang_id, d.jumlah, s.produk_stok_id,
       p.nota, p.konsumen, 'Produk', p.shipping, p.created_at,
       s.tanggal_mutasi, s.bukti_mutasi
FROM project_mp_details d
JOIN project_mps p ON p.id = d.project_id AND p.marketplace_id IS NOT NULL
JOIN rekon_detail_project_20261117 hitung ON hitung.project_id = p.id
    AND hitung.produk_id = d.produk_id AND hitung.banyak_detail = 1
JOIN produks produk ON produk.id = d.produk_id
JOIN produk_models model ON model.id = produk.produk_model_id AND model.stok = 1
JOIN rekon_stok_20261117 s ON s.detail_id = p.id AND s.produk_id = d.produk_id
    AND s.cabang_id = p.cabang_id AND s.kode = 'shp'
    AND s.banyak_mutasi = 1 AND s.tambah = 0 AND s.kurang = d.jumlah
    AND RIGHT(s.bukti_mutasi, CHAR_LENGTH(p.nota) + 2) = CONCAT('(', p.nota, ')')
    AND s.tanggal_mutasi >= p.created_at
LEFT JOIN rekon_stok_20261117 balik ON balik.detail_id = p.id
    AND balik.produk_id = d.produk_id AND balik.cabang_id = p.cabang_id
    AND balik.kode = 'btl' AND balik.tambah > 0
LEFT JOIN rekon_arsip_stok_20261117 dipakai ON dipakai.produk_stok_id = s.produk_stok_id
LEFT JOIN ambil_barangs a ON a.sumber = 'project' AND a.detail_id = d.id
WHERE d.jumlah > 0 AND p.cabang_id IS NOT NULL
  AND p.nota IS NOT NULL AND p.nota <> ''
  AND balik.detail_id IS NULL AND dipakai.produk_stok_id IS NULL AND a.id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM rekon_stok_20261117 s2
      WHERE s2.detail_id = p.id AND s2.produk_id = d.produk_id
        AND s2.cabang_id = p.cabang_id AND s2.kode = 'shp'
        AND RIGHT(s2.bukti_mutasi, CHAR_LENGTH(p.nota) + 2) = CONCAT('(', p.nota, ')')
        AND s2.produk_stok_id <> s.produk_stok_id
  );

-- LANGKAH 6: Pratinjau. STOP jika ada baris yang meragukan.
SELECT sumber, nota, detail_id, produk_id, cabang_id, jumlah,
       produk_stok_id, tanggal_order, tanggal_mutasi, bukti_mutasi
FROM calon_ambil_barang_lama_20261117
ORDER BY sumber, nota, detail_id;

-- LANGKAH 7: Jalankan HANYA jika pratinjau benar. Tidak memotong stok.
INSERT IGNORE INTO ambil_barangs
    (sumber, detail_id, produk_id, cabang_id, jumlah, produk_stok_id,
     user_id, created_at, nota, pembeli, jenis, shipping, tanggal_order)
SELECT c.sumber, c.detail_id, c.produk_id, c.cabang_id, c.jumlah,
       c.produk_stok_id, NULL, NOW(), c.nota, c.pembeli,
       c.jenis, c.shipping, c.tanggal_order
FROM calon_ambil_barang_lama_20261117 c
WHERE NOT EXISTS (
    SELECT 1 FROM ambil_barangs a WHERE a.sumber = c.sumber AND a.detail_id = c.detail_id
)
AND NOT EXISTS (
    SELECT 1 FROM ambil_barangs a WHERE a.produk_stok_id = c.produk_stok_id
);

-- LANGKAH 8: Setelah INSERT berhasil, jalankan pembersihan, satu perintah
-- per klik. Hanya hapus tabel penampung milik skrip ini, BUKAN produk_stoks.
DROP TABLE calon_ambil_barang_lama_20261117;
DROP TABLE rekon_arsip_stok_20261117;
DROP TABLE rekon_detail_project_20261117;
DROP TABLE rekon_stok_20261117;
