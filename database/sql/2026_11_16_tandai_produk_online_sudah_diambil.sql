-- Tandai semua detail order online dengan Jenis = Produk sebagai sudah diambil.
-- Jenis = Proses (marketplace_buffers terbaru custom = 1) TIDAK ikut.
-- Jalankan setelah fitur ambil_barang (tabel ambil_barangs) terpasang.
-- Backup database dan cek hasil SELECT di bawah sebelum menjalankan INSERT.
-- PENTING: hanya menambah penanda/arsip; TIDAK mengurangi stok dan TIDAK
-- membuktikan barang benar-benar sudah diambil. Gunakan hanya jika seluruh
-- order online Produk yang terpilih memang sudah diambil / stoknya sudah sesuai.
-- created_at adalah waktu penandaan, bukan tanggal pengambilan historis.

-- Pratinjau detail yang akan ditandai (tidak termasuk yang sudah ada di arsip).
SELECT p.id AS project_id, p.nota, d.id AS detail_id, d.produk_id,
       d.jumlah, COALESCE(NULLIF(p.cabang_id, 0), pusat.id) AS cabang_id
FROM project_mp_details d
JOIN project_mps p ON p.id = d.project_id
JOIN produks produk ON produk.id = d.produk_id
JOIN produk_models model ON model.id = produk.produk_model_id AND model.stok = 1
LEFT JOIN marketplace_buffers buffer ON buffer.id = (
    SELECT MAX(b.id) FROM marketplace_buffers b WHERE b.project_id = p.id
)
LEFT JOIN produksis status_produksi ON status_produksi.id = d.produksi_id
LEFT JOIN (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1) pusat ON 1 = 1
LEFT JOIN ambil_barangs a ON a.sumber = 'project' AND a.detail_id = d.id
WHERE p.marketplace_id IS NOT NULL
  AND COALESCE(buffer.custom, 0) = 0
  AND (status_produksi.nama IS NULL OR status_produksi.nama <> 'batal')
  AND d.jumlah > 0
  AND COALESCE(NULLIF(p.cabang_id, 0), pusat.id) IS NOT NULL
  AND a.id IS NULL
ORDER BY p.id, d.id;

-- Jalankan blok INSERT ini HANYA setelah pratinjau sesuai harapan.
-- Aman dijalankan ulang: sumber + detail_id adalah kunci unik.
INSERT INTO ambil_barangs
    (sumber, detail_id, produk_id, cabang_id, jumlah, created_at,
     nota, pembeli, jenis, shipping, tanggal_order)
SELECT 'project', d.id, d.produk_id,
       COALESCE(NULLIF(p.cabang_id, 0), pusat.id), d.jumlah, NOW(),
       p.nota, p.konsumen, 'Produk', p.shipping, p.created_at
FROM project_mp_details d
JOIN project_mps p ON p.id = d.project_id
JOIN produks produk ON produk.id = d.produk_id
JOIN produk_models model ON model.id = produk.produk_model_id AND model.stok = 1
LEFT JOIN marketplace_buffers buffer ON buffer.id = (
    SELECT MAX(b.id) FROM marketplace_buffers b WHERE b.project_id = p.id
)
LEFT JOIN produksis status_produksi ON status_produksi.id = d.produksi_id
LEFT JOIN (SELECT id FROM cabangs WHERE kode = 'PUSAT' ORDER BY id LIMIT 1) pusat ON 1 = 1
LEFT JOIN ambil_barangs a ON a.sumber = 'project' AND a.detail_id = d.id
WHERE p.marketplace_id IS NOT NULL
  AND COALESCE(buffer.custom, 0) = 0
  AND (status_produksi.nama IS NULL OR status_produksi.nama <> 'batal')
  AND d.jumlah > 0
  AND COALESCE(NULLIF(p.cabang_id, 0), pusat.id) IS NOT NULL
  AND a.id IS NULL;
