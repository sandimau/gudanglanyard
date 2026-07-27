-- =============================================================================
-- REFACTOR BUKU BESAR SALDO
-- Setara dengan: database/migrations/2026_07_21_100000_refactor_buku_besar_saldo.php
-- Pola sama dengan produk_stoks + produk_last_stoks
--
-- Cara pakai di Hostinger (phpMyAdmin):
--   1. BACKUP database dulu (Export)
--   2. Jalankan per BLOK (urutan 0 → 6), satu per satu
--   3. Jika ada error "sudah ada" / "tidak ada kolom" → lewati langkah itu
--   4. Setelah selesai, cek BLOK VERIFIKASI di bawah
--
-- Kompatibel MySQL 5.7+ / MariaDB (tanpa window function)
-- =============================================================================


-- =============================================================================
-- BLOK 0 — PREVIEW (hanya baca, tidak mengubah data)
-- =============================================================================

SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'buku_besars'
  AND COLUMN_NAME = 'saldo';

SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'akun_last_saldos';


-- =============================================================================
-- BLOK 1 — Buat tabel cache akun_last_saldos
-- Lewati jika BLOK 0 sudah menunjukkan tabel ada
-- =============================================================================

CREATE TABLE IF NOT EXISTS akun_last_saldos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    akun_detail_id BIGINT UNSIGNED NULL,
    saldo DECIMAL(15, 2) NULL,
    tahun INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY akun_last_saldos_akun_tahun_unique (akun_detail_id, tahun),
    CONSTRAINT akun_last_saldos_akun_detail_id_foreign
        FOREIGN KEY (akun_detail_id) REFERENCES akun_details (id)
        ON UPDATE CASCADE ON DELETE CASCADE
);


-- =============================================================================
-- BLOK 2 — Kosongkan cache lalu rebuild saldo per akun per tahun
-- Rumus: saldo(tahun) = total mutasi (debet - kredit) dari awal s/d akhir tahun itu
-- =============================================================================

TRUNCATE TABLE akun_last_saldos;

INSERT INTO akun_last_saldos (akun_detail_id, saldo, tahun, created_at, updated_at)
SELECT
    y.akun_detail_id,
    (
        SELECT COALESCE(SUM(COALESCE(b.debet, 0) - COALESCE(b.kredit, 0)), 0)
        FROM buku_besars b
        WHERE b.akun_detail_id = y.akun_detail_id
          AND YEAR(b.created_at) <= y.tahun
    ) AS saldo,
    y.tahun,
    NOW(),
    NOW()
FROM (
    SELECT DISTINCT akun_detail_id, YEAR(created_at) AS tahun
    FROM buku_besars
    WHERE akun_detail_id IS NOT NULL
) AS y
ORDER BY y.akun_detail_id, y.tahun;


-- =============================================================================
-- BLOK 3 — Sync akun_details.saldo dari cache tahun terakhir
-- =============================================================================

UPDATE akun_details ad
LEFT JOIN (
    SELECT als.akun_detail_id, als.saldo
    FROM akun_last_saldos als
    INNER JOIN (
        SELECT akun_detail_id, MAX(tahun) AS max_tahun
        FROM akun_last_saldos
        GROUP BY akun_detail_id
    ) sub ON als.akun_detail_id = sub.akun_detail_id AND als.tahun = sub.max_tahun
) last_saldo ON last_saldo.akun_detail_id = ad.id
SET ad.saldo = COALESCE(last_saldo.saldo, 0)
WHERE EXISTS (
    SELECT 1 FROM buku_besars b WHERE b.akun_detail_id = ad.id
);


-- =============================================================================
-- BLOK 4 — Hapus kolom saldo dari buku_besars (sudah tidak dipakai aplikasi)
-- Lewati jika BLOK 0 menunjukkan kolom saldo sudah tidak ada
-- =============================================================================

ALTER TABLE buku_besars DROP COLUMN saldo;


-- =============================================================================
-- BLOK 5 — Tandai migration Laravel sudah jalan
-- Lewati jika baris ini sudah ada di tabel migrations
-- =============================================================================

INSERT INTO migrations (migration, batch)
SELECT
    '2026_07_21_100000_refactor_buku_besar_saldo',
    COALESCE(MAX(batch), 0) + 1
FROM migrations
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_07_21_100000_refactor_buku_besar_saldo'
);


-- =============================================================================
-- BLOK VERIFIKASI — Jalankan setelah semua blok di atas
-- =============================================================================

-- 1) Tidak boleh ada duplikat (akun_detail_id + tahun)
SELECT akun_detail_id, tahun, COUNT(*) AS jumlah
FROM akun_last_saldos
GROUP BY akun_detail_id, tahun
HAVING COUNT(*) > 1;

-- 2) Bandingkan cache vs hitung langsung (harus sama untuk tahun berjalan)
SELECT
    a.id AS akun_detail_id,
    als.saldo AS cache_saldo,
    (
        SELECT COALESCE(SUM(COALESCE(b.debet, 0) - COALESCE(b.kredit, 0)), 0)
        FROM buku_besars b
        WHERE b.akun_detail_id = a.id
          AND YEAR(b.created_at) = YEAR(CURDATE())
    )
    + COALESCE((
        SELECT als2.saldo
        FROM akun_last_saldos als2
        WHERE als2.akun_detail_id = a.id
          AND als2.tahun < YEAR(CURDATE())
        ORDER BY als2.tahun DESC
        LIMIT 1
    ), 0) AS hitung_ulang_saldo
FROM akun_details a
LEFT JOIN akun_last_saldos als
    ON als.akun_detail_id = a.id
   AND als.tahun = YEAR(CURDATE())
WHERE EXISTS (
    SELECT 1 FROM buku_besars b WHERE b.akun_detail_id = a.id
)
HAVING cache_saldo IS NOT NULL
   AND cache_saldo <> hitung_ulang_saldo
LIMIT 20;

-- 3) Pastikan kolom saldo sudah hilang dari buku_besars
SHOW COLUMNS FROM buku_besars LIKE 'saldo';

-- 4) Pastikan migration tercatat
SELECT * FROM migrations
WHERE migration = '2026_07_21_100000_refactor_buku_besar_saldo';
