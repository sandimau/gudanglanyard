# Rencana lanjutan: produk custom / non-PO

Status: **ditunda; belum untuk diimplementasikan**.

## Konteks

Kasus produk custom/non-PO cukup sering terjadi. Diperlukan fitur tersendiri untuk mengakomodasi pemenuhan order jenis produk ini. Rincian alur bisnis dan desain fiturnya belum diputuskan; jangan menganggap produk custom otomatis sama dengan produk jadi yang stoknya dicatat.

## Batas keputusan alur Ambil Barang saat ini

- Halaman **Ambil Barang** hanya memuat detail order dengan jenis produk yang melacak stok (`produk_models.stok = 1`).
- Detail order dengan jenis produk `stok = 0` tidak muncul di halaman itu dan tidak mendapat penanda **Belum ambil barang**.
- Produk dengan `stok = 1` tetapi saldo fisik/sistem kosong tetap termasuk cakupan Ambil Barang; ini berbeda dari jenis produk `stok = 0`.
- Fitur untuk produk custom/non-PO akan dibahas dan dirancang terpisah kemudian.

## Hal yang perlu diputuskan sebelum implementasi fase berikutnya

- Definisi tepat produk custom/non-PO dan hubungannya dengan jenis produk `stok = 0`.
- Proses pemenuhan barang custom: produksi, pembelian, atau sumber lain.
- Kapan hasilnya tersedia untuk order serta bagaimana stok dan biaya dicatat, tanpa memotong ganda stok bahan produksi dan produk jadi.
- Penanda dan tindakan pada detail order custom yang berbeda dari **Ambil Barang** untuk produk stok.

Catatan ini hanya menyimpan rencana; tidak mengubah perilaku aplikasi.
