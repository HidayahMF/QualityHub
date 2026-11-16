# QA — Super Admin Bulk Delete Data Dummy (PR #18)

## Tujuan
Hapus beberapa dokumen **yang secara manual sudah diverifikasi sebagai data dummy** melalui All Documents, tanpa melakukan Arsip → Hapus per dokumen.

## Cara pakai
1. Login sebagai Super Admin → All Documents.
2. Pilih checkbox di baris dokumen (mobile dan desktop didukung). "Pilih semua di halaman ini" hanya memilih **maksimal 15 dokumen halaman saat ini**, bukan semua hasil filter.
3. Klik **Hapus Permanen (N)**.
4. Baca kembali nama dan kode setiap dokumen pada modal.
5. Centang pengakuan bahwa semua pilihan adalah data dummy, ketik **HAPUS** (huruf besar), lalu klik Hapus Permanen.
6. Periksa notifikasi dan Activity Log setelah penghapusan.

## Safety guarantees
- Route DELETE khusus Super Admin, tetap di bawah middleware auth, akun aktif, session freshness, CSRF, dan throttle 3 request/menit.
- Server memvalidasi 1–15 ID unik, jumlah yang dikonfirmasi, semua dokumen harus masih aktif, acknowledgement dan string persis `HAPUS`.
- Backend **tidak** mencari kata "dummy" atau mengklasifikasikan dokumen otomatis; hanya ID yang dipilih eksplisit. **Data produksi tetap bisa dihapus oleh Super Admin yang salah memilih**, sehingga verifikasi nama di modal adalah syarat operasional.
- Jalur hapus permanen biasa tetap mewajibkan arsip; hanya endpoint baru ini yang dapat menghapus dokumen aktif yang dipilih Super Admin.
- Delete memakai `DocumentPurgeService` dengan DB transaction dan row lock per dokumen, validasi file path private storage, proteksi file bersama, hapus versi/relasi terkait, Activity Log permanen.
- Audit menambahkan `bulk_operation_id`, `reason=bulk_test_document_cleanup`, dan status pembersihan file.
- Status antar-dokumen **bukan transaksi global**. Jika terjadi error setelah beberapa dokumen berhasil dihapus, proses berhenti dan warning menyebut jumlah yang selesai. Jangan mengulangi tanpa muat ulang dan cek log.
- Masalah file cleanup dicatat dalam log/audit dan ditampilkan sebagai warning; data DB bisa sudah terhapus.
- Tidak ada perubahan schema, migrations, layanan AI, proses Retooling, master PPIC/MTC, maupun pipeline dokumen lain.

## Testing
```powershell
npm ci
npm run build
php artisan test --filter=BulkDummyDocumentDeleteTest
php artisan test --filter=DocumentPermanentDeleteTest
php artisan view:cache
php artisan view:clear
```
Manual di staging (bukan production data): pilih 2 data dummy, periksa judul/kode di modal, coba batal, coba konfirmasi salah, lalu konfirmasi benar. Pastikan dua dokumen dan revisinya terhapus, dokumen produksi yang tidak dipilih tetap ada; role admin/user/viewer tidak memiliki checkbox maupun akses endpoint. Test mobile dan desktop. Cek folder bmc_documents dan Activity Log.

## Batas rilis
PR Draft sampai CI, review kode keamanan, dan QA staging selesai. Tidak merge atau deploy server BMC secara otomatis. Lakukan backup dan persetujuan PIC BMC sebelum mengaktifkan penghapusan massal pada server produksi.
