# Modul persediaan tahap berikutnya

Tidak ada stok barang habis pakai atau data siswa pada tahap pertama. Jangan membuat satu Asset untuk saldo sejumlah barang atau menambahkan kolom quantity yang mengubah makna identitas unit aset.

Tambahkan domain terpisah: stock_items (SKU, satuan), stock_locations, stock_movements (penerimaan/pengeluaran/koreksi), stock_batches opsional, dan dokumen penerima sesuai kebutuhan. Saldo diturunkan dari buku mutasi atau proyeksi saldo yang diperbarui transaksional; locking mencegah pengeluaran melampaui saldo. Unit fisik tahan lama yang perlu QR tetap berada di assets.

Pengadaan dapat diperluas dengan jenis rincian asset/consumable. Rincian asset membentuk unit UUID; consumable membentuk penerimaan persediaan, tidak membentuk identitas aset palsu. Pendanaan pengadaan tetap dihitung sekali. Tentukan satuan/konversi, valuasi, retur, koreksi, otorisasi, dokumen dan rekonsiliasi sebelum coding.

Distribusi ke siswa memerlukan pembahasan tujuan, data minimum, akses, dasar pemrosesan/retensi dan penerima yang tepat. Jangan menambahkan data siswa sekarang. Master sekolah/lokasi dan permission bisa digunakan bersama, tetapi ledger dan laporan persediaan terpisah dari timeline aset per unit. Buat migrasi aditif dan test penerimaan/pengeluaran/retur/concurrency pada MySQL sebelum menggabungkan modul.
