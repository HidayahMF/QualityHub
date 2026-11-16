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

## QA UI — Edge: modal terpotong dan checkbox tak terlihat (16 Nov 2026)
Laporan screenshot localhost: dialog bulk delete menempel/tertutup topbar sticky ketika dibuka; checkbox pernyataan data dummy bisa dicentang tetapi glyph centangnya tidak terlihat.

Perbaikan branch ini:
- Teleport modal saja ke `body` agar tidak terkunci dalam stacking context `main.z-10` di bawah `header.z-30`; gunakan overlay z-index di atas topbar.
- Panel dialog dibatasi `100dvh`; header dan footer tetap terlihat, area utama punya scroll sendiri; daftar dokumen dibatasi tinggi dan dapat di-scroll.
- Modal dirapikan: badge Super Admin, judul dan penjelasan ringkas, jumlah terpilih, nama/kode setiap dokumen, pengakuan dummy, input HAPUS, dan aksi yang mudah dipahami.
- Checkbox `acknowledge_dummy` menggunakan styling centang putih pada latar biru yang sama seperti checkbox tabel; input native dan `x-model` tetap dipertahankan untuk formulir dan keyboard.
- Tidak mengubah controller, service purge, DB, ACL, payload `document_ids[]`, atau Activity Log dalam perbaikan UI ini.

Regression: `BulkDummyDocumentDeleteTest` mencakup marker teleport, scroll/viewport CSS, dan checked-state; juga jalankan ulang `DocumentPermanentDeleteTest` dan npm build.

Uji manual di Windows Edge: pilih 5 dokumen dummy, buka modal; topbar harus tertutupi backdrop, judul modal tidak terpotong, daftar dapat scroll, checkbox pengakuan tampak tanda centangnya, tombol baru aktif setelah checkbox + teks HAPUS benar. Tes juga resolusi pendek dan mobile. Jangan hapus dokumen perusahaan saat QA visual.
