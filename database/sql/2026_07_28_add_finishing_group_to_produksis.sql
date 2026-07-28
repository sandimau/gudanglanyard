-- =============================================================================
-- TAMBAH GRUP FINISHING (finishing_idcard, finishing_lanyard, makloon)
-- Setara dengan: database/migrations/2026_07_28_131149_add_finishing_group_to_produksis.php
--
-- Cara pakai di Hostinger (phpMyAdmin):
--   1. BACKUP database dulu (Export)
--   2. Jalankan per BLOK (urutan 0 → 4), satu per satu
--   3. Jika ada error "sudah ada" / "tidak ada baris" → lewati langkah itu
--   4. Setelah selesai, cek BLOK VERIFIKASI di bawah
--
-- Kompatibel MySQL 5.7+ / MariaDB
-- =============================================================================


-- =============================================================================
-- BLOK 0 — PREVIEW (hanya baca, tidak mengubah data)
-- =============================================================================

-- Status produksi terkait, sebelum diubah
SELECT id, nama, grup, urutan
FROM produksis
WHERE nama IN ('Finishing_IDCARD', 'Finishing_LANYARD', 'finishing_idcard', 'finishing_lanyard', 'makloon');


-- =============================================================================
-- BLOK 1 — Rename Finishing_IDCARD jadi finishing_idcard, pindah ke grup FINISHING
-- Lewati jika BLOK 0 sudah menunjukkan baris 'finishing_idcard' ada
-- =============================================================================

UPDATE produksis
SET nama = 'finishing_idcard',
    grup = 'FINISHING',
    urutan = 1
WHERE nama = 'Finishing_IDCARD';


-- =============================================================================
-- BLOK 2 — Rename Finishing_LANYARD jadi finishing_lanyard, pindah ke grup FINISHING
-- Lewati jika BLOK 0 sudah menunjukkan baris 'finishing_lanyard' ada
-- =============================================================================

UPDATE produksis
SET nama = 'finishing_lanyard',
    grup = 'FINISHING',
    urutan = 2
WHERE nama = 'Finishing_LANYARD';


-- =============================================================================
-- BLOK 3 — Tambah status baru 'makloon' di grup FINISHING
-- Lewati jika BLOK 0 sudah menunjukkan baris 'makloon' ada
-- =============================================================================

INSERT INTO produksis (nama, grup, warna, urutan, created_at, updated_at)
SELECT 'makloon', 'FINISHING', '#FFA800', 3, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM produksis WHERE nama = 'makloon'
);


-- =============================================================================
-- BLOK 4 — Tandai migration Laravel sudah jalan (supaya artisan migrate tidak ulang)
-- Lewati jika baris ini sudah ada di tabel migrations
-- =============================================================================

INSERT INTO migrations (migration, batch)
SELECT '2026_07_28_131149_add_finishing_group_to_produksis', new_batch.batch
FROM (SELECT COALESCE(MAX(batch), 0) + 1 AS batch FROM migrations) AS new_batch
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_07_28_131149_add_finishing_group_to_produksis'
);


-- =============================================================================
-- BLOK VERIFIKASI — Jalankan setelah semua blok di atas
-- =============================================================================

-- 1) Grup FINISHING harus berisi persis 3 baris ini
SELECT id, nama, grup, warna, urutan
FROM produksis
WHERE grup = 'FINISHING'
ORDER BY urutan;

-- 2) Pastikan tidak ada lagi baris grup lama (Finishing_IDCARD / Finishing_LANYARD)
SELECT id, nama, grup
FROM produksis
WHERE nama IN ('Finishing_IDCARD', 'Finishing_LANYARD');

-- 3) Pastikan tidak ada duplikat nama 'makloon'
SELECT nama, COUNT(*) AS jumlah
FROM produksis
WHERE nama = 'makloon'
GROUP BY nama
HAVING COUNT(*) > 1;

-- 4) Pastikan migration tercatat
SELECT * FROM migrations
WHERE migration = '2026_07_28_131149_add_finishing_group_to_produksis';
