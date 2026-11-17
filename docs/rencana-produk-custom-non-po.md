# Rencana lanjutan: produk custom / non-PO

Status: **fitur resep produk custom diimplementasikan** (tanpa makloon).

## Keputusan implementasi

- Kategori utama **Produk Custom** menjual produk `stok = 0`; subkategori dan produk dibuat lewat menu kategori/produk biasa.
- Kategori utama lain yang berisi produk custom lama dapat diberi penanda **Produk custom (atur resep)** pada form tambah/edit kategori utama tanpa harus memindahkan produk. Penanda ini tidak mengubah pilihan `produksi`; kategori berisi produk berstok ditolak, dan penanda tidak dapat dimatikan selama masih ada resep.
- Resep per varian produk (`produks`) berisi komponen berstok beserta jumlah per unit serta biaya internal per unit atau sekali per pesanan. Tidak ada biaya makloon/vendor.
- Untuk komponen dengan lebih dari satu varian aktif, pemesanan offline memilih kombinasi di pencarian produk; SKU Shopee komposit `CUSTOM_{idProduk}-{idVarian}[-{idVarian}...]` mengkodekan pilihan yang sama. Akhiran `_N` tetap berlaku untuk paket.
- Rincian bahan pesanan disimpan terpisah dari detail penjualan. Stok bahan resep dipotong saat status detail keluar dari grup **awal** (order online maupun offline) dan dikembalikan bila status berubah dari grup **Selesai** ke **batal**. Biaya internal tidak menjadi barang berstok.
- Perubahan status detail order memotong stok sesuai jenis produk: produk berstok pada order offline, atau bahan resep pada produk custom. Order marketplace dengan produk berstok sudah dipotong saat webhook order masuk sehingga tidak dipotong lagi pada perubahan status.
- Detail pesanan lama tanpa `material_ids` tidak otomatis diubah menjadi pesanan ber-resep ketika master produk diubah kemudian. Pembatalan tidak otomatis membuat retur stok kecuali status berpindah dari grup Selesai ke batal.

## Konteks

Kasus produk custom/non-PO cukup sering terjadi. Diperlukan fitur tersendiri untuk mengakomodasi pemenuhan order jenis produk ini. Rincian alur bisnis dan desain fiturnya belum diputuskan; jangan menganggap produk custom otomatis sama dengan produk jadi yang stoknya dicatat.

## Aturan mutasi stok saat pindah status

- Pindah dari grup **awal** ke grup berikutnya memotong stok sekali.
- Produk berstok pada order offline memotong stok produk jadi; produk custom memotong stok bahan resep yang tersimpan di `order_custom_bahans`.
- Order marketplace dengan produk berstok tidak memotong lagi karena stok sudah dipotong saat webhook order masuk.
- Pindah dari grup **Selesai** ke **batal** mengembalikan stok yang pernah dipotong.
- Detail tanpa snapshot bahan resep tidak memotong stok bahan secara retroaktif.

## Pengembangan lanjutan di luar cakupan resep

- Definisi tepat produk custom/non-PO dan hubungannya dengan jenis produk `stok = 0`.
- Proses pemenuhan barang custom: produksi, pembelian, atau sumber lain.
- Kapan hasilnya tersedia untuk order serta bagaimana stok dan biaya dicatat, tanpa memotong ganda stok bahan produksi dan produk jadi.

Perubahan resep tidak otomatis membangun alur pembelian bahan/vendor ataupun pencatatan stok produk jadi custom.
