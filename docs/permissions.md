# Matriks hak akses

Default satu instalasi = satu sekolah. Akses objek berlaku seluruh aset sekolah tersebut, bukan pembatasan per ruangan. Policy menolak objek di luar sekolah. Penambahan pembatasan per unit kerja membutuhkan tabel penugasan dan scope query yang diterapkan konsisten pada baca, QR, lampiran, laporan dan cetak.

| Kelompok izin | Administrator | Operator | Penyetuju | Viewer |
| --- | --- | --- | --- | --- |
| dashboard.view, assets.view | Ya | Ya | Ya | Ya |
| reports.view, reports.export, labels.print | Ya | Ya | Ya | Ya |
| attachments.view | Ya | Ya | Ya | Ya |
| assets.create, assets.update | Ya | Ya | Tidak | Tidak |
| procurements.manage | Ya | Ya | Tidak | Tidak |
| transfers.create | Ya | Ya | Tidak | Tidak |
| transfers.approve | Ya | Tidak | Ya | Tidak |
| maintenance.manage, attachments.upload | Ya | Ya | Tidak | Tidak |
| assets.archive, assets.dispose | Ya | Tidak | Tidak | Tidak |
| users.manage, masters.manage, permissions.manage, audit.view | Ya | Tidak | Tidak | Tidak |

Pemetaan non-administrator dapat diubah pada Pengguna → Pemetaan izin. Ini delegasi kepercayaan: memberikan `users.manage` memungkinkan pengelolaan akun lain, termasuk administrator. Memberikan `permissions.manage` memungkinkan mengatur peran lain. Jangan berikan keduanya kepada viewer biasa. Administrator dan peran sendiri tidak dapat diedit pemetaannya. Semua izin ditolak jika akun nonaktif atau tidak dipetakan.

Izin baca aset diperlukan oleh alur objek, QR, laporan dan cetak. Pengadaan membentuk unit, sehingga role pengadaan membutuhkan `procurements.manage` bersama `assets.create`. Upload membutuhkan `assets.view` + `attachments.upload`. Pengubahan metadata membutuhkan `assets.view` + `assets.update`. Penonaktifan/mutasi peran/password menghapus sesi tersimpan dan remember token; middleware tetap membaca ulang status akun pada setiap request, termasuk Livewire.

Pengaju tidak dapat menyetujui/menolak transaksi sendiri, meskipun administrator. Pengaju (atau administrator dengan izin perpindahan) dapat mengajukan/membatalkan draft miliknya. Transaksi final tidak dapat diubah/dihapus. Penghapusan aset atau master tidak disediakan; master dinonaktifkan dan aset diarsipkan. Pengarsipan bukan pelepasan BMN administratif.

Policy/Gate berlaku pada controller, Form Request, service transaksi, unduhan, ekspor dan cetak. Menyembunyikan menu hanya bantuan UX. Audit tidak bisa diubah melalui UI, tetapi administrator database tetap mempunyai kemampuan teknis mengubah catatan.
