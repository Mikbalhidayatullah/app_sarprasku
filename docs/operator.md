# Panduan operator

## Memulai

Administrator membuat akun; tidak ada menu daftar publik. Lupa password menggunakan email terdaftar dan SMTP sekolah. Administrator menonaktifkan akun pegawai yang tak lagi bertugas; jangan menghapus akun karena identitas pelaku harus tetap terhubung ke riwayat.

Lengkapi Master Data: identitas sekolah dan zona waktu IANA (misalnya Asia/Jakarta, Asia/Makassar, Asia/Jayapura), lokasi, ruangan, orang penanggung jawab, klasifikasi, sumber dana dan status kepemilikan. Periksa identitas sekolah sebelum mencetak label. Kode master disimpan sebagai teks. Pegawai pada master tidak otomatis menjadi akun login.

Gunakan dokumen sekolah untuk klasifikasi, NUP/register dan kepemilikan. Sumber dana tidak otomatis menentukan BMN. Status awal “Belum diverifikasi” tidak menyatakan barang BMN. Jangan membuat angka pengganti bila dokumen belum ada.

## Mencatat barang lama

Data Barang → Catat barang. Satu formulir untuk satu unit. Nomor inventaris internal harus unik di sekolah; kode klasifikasi, NUP resmi dan nomor seri pabrik memiliki kolom terpisah. Kosongkan tanggal/nilai yang belum diketahui; nilai nol berarti benar-benar diketahui bernilai nol. Tahun boleh diisi bila hanya tahun yang diketahui. Jika tanggal diketahui, tahun diturunkan otomatis dan tahun yang bertentangan ditolak.

Posisi awal adalah lokasi saat mulai dicatat, bukan rekonstruksi riwayat sebelumnya. Catatan awal menyatakan histori sebelumnya belum diverifikasi. Setelah disimpan, perubahan posisi melalui perpindahan; kondisi melalui pemeriksaan; status operasional melalui perubahan status. UUID dan QR tetap saat berpindah. Koreksi metadata masuk timeline/audit.

## Pengadaan dan pendanaan

Pengadaan → Catat pengadaan. Tambahkan rincian nama, kuantitas, nilai per unit, kepemilikan dan prefix nomor inventaris yang unik. Contoh `KRS-2026-A` sebanyak 20 menghasilkan `KRS-2026-A-0001` s.d. `KRS-2026-A-0020`. Nomor tersebut internal; tidak membuat NUP atau kode klasifikasi resmi. Prefix yang bertabrakan ditolak dan seluruh transaksi dibatalkan otomatis.

Tambahkan satu atau beberapa sumber dana, tahun anggaran, referensi dokumen dan nominal. Total alokasi harus tepat sama dengan jumlah kuantitas × harga seluruh rincian. Angka desimal input memakai titik, misalnya `1000000.50`; tampilan menggunakan Rupiah Indonesia. Batas 1.000 unit/transaksi untuk membatasi beban hosting. Lengkapi merek/nomor seri tiap unit setelah pembentukan.

Nominal sumber dana berada pada pengadaan, tidak dialokasikan ulang per unit. Detail aset menampilkan pendanaan pengadaan dengan penjelasan cakupan. Jangan menjumlahkan nominal yang sama dari banyak halaman unit. Pengadaan yang sudah tercatat final tidak diedit langsung; perubahan nilai/perolehan unit terkait juga dikunci. Koreksi dokumen pengadaan perlu verifikasi administratif dan prosedur koreksi pengadaan terpisah di versi berikutnya.

## Perpindahan

Detail barang → Ajukan perpindahan → pilih ruangan/penanggung jawab tujuan, tanggal efektif hari ini, alasan → Simpan draft. Unggah dokumen pada detail draft sebelum final. Draft belum mengubah posisi; klik Ajukan saat lengkap. Akun penyetuju yang berbeda meninjau asal, tujuan, alasan dan dokumen, lalu menyetujui atau menolak dengan alasan. Pengaju dapat membatalkan draft/pengajuan yang belum final.

Hanya satu draft/pengajuan aktif per unit. Hari efektif mengikuti zona waktu sekolah, bukan jam komputer operator. Pengajuan kemarin harus dibatalkan dan dibuat ulang; perpindahan mundur/terjadwal tidak tersedia. Persetujuan memeriksa posisi terkini dan memperbarui posisi + riwayat sekaligus. Klik berulang tidak menggandakan perpindahan.

Kesalahan setelah persetujuan: buka transaksi lalu Buat transaksi koreksi. Isi tujuan yang benar dan alasan pembetulan. Transaksi baru merujuk transaksi lama dan harus disetujui orang lain; histori lama tetap ada. Penamaan ruangan/pegawai kemudian tidak mengubah snapshot asal/tujuan pada transaksi lama.

## Kondisi, pemeliharaan, lampiran

Detail barang → Catat perubahan kondisi, isi pemeriksa, alasan dan tanggal. Pemeliharaan mencatat keluhan, tindakan, pelaksana/vendor, biaya (kosong jika belum diketahui), hasil dan kondisi sesudahnya. Pada tahap ini keduanya **hanya hari ini**, agar tidak mengganti kondisi terkini dengan catatan terlambat tanpa rekonstruksi. Status “Dalam pemeliharaan” terpisah dari kondisi “Rusak ringan”; ubah status secara eksplisit sesuai hasil pemeriksaan.

Unggah foto JPEG/PNG atau PDF maksimal 5 MB per berkas. Berkas disimpan privat dan unduhan memerlukan izin; tidak tersedia URL storage publik. Lampiran yang terkait perpindahan final tidak dapat ditambah/diganti langsung. Foto pemeriksaan dapat ditambahkan pada lampiran unit dengan keterangan dokumen. Tidak ada pemindaian antivirus otomatis; sekolah perlu tata kelola pemeriksaan berkas dan endpoint antivirus bila diperlukan.

Arsip mempertahankan barang dan riwayat. Pelepasan/penghapusan internal membutuhkan izin khusus dan referensi dokumen. Tindakan ini tidak menyelesaikan prosedur resmi penghapusan BMN.

## Laporan dan label

Terapkan filter Data Barang atau Laporan. Ekspor CSV/PDF mempertahankan filter serta izin server. Filter lokasi/kondisi/penanggung jawab menunjukkan posisi terkini aset; laporan perpindahan memuat snapshot historis asal/tujuan. Filter tanggal mengikuti tanggal transaksi sesuai jenis laporan. PDF dibatasi 1.000 baris; CSV dialirkan bertahap. CSV memberi penanda teks untuk formula/identitas bernol awal; sebagian aplikasi spreadsheet menampilkan apostrof jika tidak menafsirkan penanda Excel.

Pilih satu/beberapa barang atau gunakan filter → Cetak label. Maksimal 100 label sekaligus. Preview dan PDF memakai ukuran sekolah (lebar 70–95 mm, tinggi 40–80 mm) di kertas A4. Gunakan skala 100%, jangan “fit to page”. Identitas tidak dipotong; ukuran terlalu kecil untuk teks identitas akan ditolak. Nama sekolah/barang boleh diringkas pada label.

QR berisi URL token, bukan harga, dana, dokumen atau data pribadi. Pemindai harus login dan memiliki izin. Gunakan domain APP_URL yang stabil, HTTPS dan dapat diakses perangkat pemindai; jangan memakai localhost dalam label yang akan digunakan banyak perangkat. Uji hasil cetak dengan ponsel sebelum mencetak massal; pemindaian fisik belum diverifikasi dalam implementasi ini.
