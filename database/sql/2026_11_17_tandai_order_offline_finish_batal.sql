-- Khusus order OFFLINE berstatus finish/batal yang belum ada di arsip.
-- Jalankan di tab SQL phpMyAdmin; pratinjau dulu, lalu INSERT bila sesuai.
-- Dapat dijalankan berulang. Tidak menambah/mengurangi produk_stoks.
-- Status finish/batal TIDAK membuktikan barang diambil secara fisik dan
-- TIDAK memperbaiki selisih cabang stok (mis. order 9256). Periksa stok
-- antar-cabang secara terpisah. created_at = waktu penandaan, bukan waktu
-- pengambilan historis. Produk tanpa pencatatan stok tidak masuk daftar.

-- 1. PRATINJAU
SELECT o.id AS order_id, o.nota, d.id AS detail_id, d.produk_id,
       d.jumlah, o.cabang_id, ps.nama AS status_order
FROM order_details d
JOIN orders o ON o.id = d.order_id
JOIN produksis ps ON ps.id = d.produksi_id
JOIN produks p ON p.id = d.produk_id
JOIN produk_models m ON m.id = p.produk_model_id AND m.stok = 1
LEFT JOIN ambil_barangs a ON a.sumber = 'order' AND a.detail_id = d.id
WHERE o.marketplace IS NULL AND o.deleted_at IS NULL AND d.deleted_at IS NULL
  AND LOWER(TRIM(ps.nama)) IN ('finish', 'batal') AND d.jumlah > 0
  AND o.cabang_id IS NOT NULL AND a.id IS NULL
ORDER BY o.id, d.id;

-- 2. INSERT (jalankan hanya setelah memeriksa pratinjau).
-- produk_stok_id sengaja NULL karena status saja bukan bukti mutasi tertentu.
INSERT IGNORE INTO ambil_barangs
    (sumber, detail_id, produk_id, cabang_id, jumlah, produk_stok_id,
     user_id, created_at, nota, pembeli, jenis, shipping, tanggal_order)
SELECT 'order', d.id, d.produk_id, o.cabang_id, d.jumlah, NULL,
       NULL, NOW(), o.nota, k.nama,
       IF(LOWER(TRIM(ps.nama)) = 'batal', 'Offline (batal)', 'Offline (finish)'),
       o.pengiriman, o.created_at
FROM order_details d
JOIN orders o ON o.id = d.order_id
JOIN produksis ps ON ps.id = d.produksi_id
JOIN produks p ON p.id = d.produk_id
JOIN produk_models m ON m.id = p.produk_model_id AND m.stok = 1
LEFT JOIN kontaks k ON k.id = o.kontak_id
LEFT JOIN ambil_barangs a ON a.sumber = 'order' AND a.detail_id = d.id
WHERE o.marketplace IS NULL AND o.deleted_at IS NULL AND d.deleted_at IS NULL
  AND LOWER(TRIM(ps.nama)) IN ('finish', 'batal') AND d.jumlah > 0
  AND o.cabang_id IS NOT NULL AND a.id IS NULL;
