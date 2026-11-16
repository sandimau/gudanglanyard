# Rencana lanjutan: produk custom / non-PO

Status: **fitur resep produk custom diimplementasikan** (tanpa makloon).

## Keputusan implementasi

- Kategori utama **Produk Custom** menjual produk `stok = 0`; subkategori dan produk dibuat lewat menu kategori/produk biasa.
- Kategori utama lain yang berisi produk custom lama dapat diberi penanda **Produk custom (atur resep)** pada form tambah/edit kategori utama tanpa harus memindahkan produk. Penanda ini tidak mengubah pilihan `produksi`; kategori berisi produk berstok ditolak, dan penanda tidak dapat dimatikan selama masih ada resep.
- Resep per varian produk (`produks`) berisi komponen berstok beserta jumlah per unit serta biaya internal per unit atau sekali per pesanan. Tidak ada biaya makloon/vendor.
- Untuk komponen dengan lebih dari satu varian aktif, pemesanan offline memilih kombinasi di pencarian produk; SKU Shopee komposit `CUSTOM_{idProduk}-{idVarian}[-{idVarian}...]` mengkodekan pilihan yang sama. Akhiran `_N` tetap berlaku untuk paket.
- Rincian bahan pesanan disimpan terpisah dari detail penjualan. Halaman **Ambil Barang** menampilkan bahan resep dan produk stok biasa bersama-sama; stok bahan baru dipotong pada konfirmasi pengambilan, bukan saat order/status dibuat. Biaya internal tidak menjadi barang yang diambil.
- Perubahan status detail order offline/online menuju grup **Produksi** ditolak bila produk `stok = 1` belum diambil, atau bila masih ada bahan resep custom yang belum diambil. Grup lain (misalnya Desain/Setting atau batal) tetap dapat dipilih sesuai alur status yang berlaku.
- Detail pesanan lama tanpa `material_ids` tidak otomatis diubah menjadi pesanan ber-resep ketika master produk diubah kemudian. Pengambilan yang sudah terjadi tetap merupakan mutasi fisik; pembatalan tidak otomatis membuat retur stok (sesuai alur pembatalan marketplace yang ada).

## Konteks

Kasus produk custom/non-PO cukup sering terjadi. Diperlukan fitur tersendiri untuk mengakomodasi pemenuhan order jenis produk ini. Rincian alur bisnis dan desain fiturnya belum diputuskan; jangan menganggap produk custom otomatis sama dengan produk jadi yang stoknya dicatat.

## Batas keputusan alur Ambil Barang saat ini

- Halaman **Ambil Barang** hanya memuat detail order dengan jenis produk yang melacak stok (`produk_models.stok = 1`).
- Detail order dengan jenis produk `stok = 0` tidak muncul di halaman itu dan tidak mendapat penanda **Belum ambil barang**.
- Produk dengan `stok = 1` tetapi saldo fisik/sistem kosong tetap termasuk cakupan Ambil Barang; ini berbeda dari jenis produk `stok = 0`.
- Produk custom baru memakai rincian bahan di halaman yang sama, tanpa mengubah aturan pengambilan produk jadi berstok.

## Pengembangan lanjutan di luar cakupan resep

- Definisi tepat produk custom/non-PO dan hubungannya dengan jenis produk `stok = 0`.
- Proses pemenuhan barang custom: produksi, pembelian, atau sumber lain.
- Kapan hasilnya tersedia untuk order serta bagaimana stok dan biaya dicatat, tanpa memotong ganda stok bahan produksi dan produk jadi.
- Penanda dan tindakan pada detail order custom yang berbeda dari **Ambil Barang** untuk produk stok.

Perubahan resep tidak otomatis membangun alur pembelian bahan/vendor ataupun pencatatan stok produk jadi custom.
