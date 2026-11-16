# BMC QualityHub — Bulk Archive (QA)

## Alur pengguna
1. Login sebagai **Admin** atau **Super Admin**, buka **All Documents**.
2. Centang dokumen yang mau diarsipkan (1–15 dokumen pada halaman aktif). Pilih semua hanya halaman saat ini.
3. Klik tombol biru **Arsipkan (N)**, periksa nama/kode dalam modal.
4. Ketik alasan arsip (3–1000 karakter), lalu klik **Arsipkan (N)**.
5. Sistem memindahkan seluruh pilihan ke **Archived Documents**, mencatat alasan/aktor/tanggal pada setiap dokumen dan Activity Log.
6. Untuk mengembalikan dokumen, gunakan alur **Pulihkan** yang sudah ada pada Archived Documents.

## Batasan dan keamanan
- **Admin dan Super Admin** dapat melakukan Bulk Archive; user/viewer tidak bisa. Bulk Delete Permanen tetap hanya **Super Admin**.
- Server memvalidasi jumlah ID cocok dengan pilihan (1–15 unik), semua dokumen ada dan berstatus aktif, serta alasan arsip wajib valid.
- **Satu transaksi DB untuk seluruh batch**: bila ada dokumen sudah diarsipkan, hilang, atau penulisan log gagal, semua perubahan di-rollback.
- Semua file current revision, revisi terdahulu, dan metadata scan/produksi tetap disimpan tanpa dimodifikasi.
- Audit setiap dokumen memakai action `archive_document` yang sama seperti arsip satuan, dengan metadata `bulk_operation_id` dan `bulk_action=archive`.
- Tidak ada perubahan migration, integrasi MTC/PPIC, AI, storage, atau status role.
- Pemilihan tidak melintasi halaman pagination/hasil pencarian tanpa eksplisit memilih ulang.
- UI responsif dan memakai portal modal agar judul serta tombol tidak terpotong header BMC.

## Testing
```powershell
php artisan test --filter=BulkDocumentArchiveTest
php artisan test --filter=BulkDummyDocumentDeleteTest
php artisan test --filter=DocumentPermanentDeleteTest
npm run build
php artisan view:cache
php artisan view:clear
```

**Manual di database testing yang terisolasi:** login Admin, pilih dua dokumen dummy aktif, buka modal, batal dan cek tidak ada perubahan. Buka lagi, masukkan alasan, arsipkan. Pastikan dua dokumen pindah ke Archived Documents, file & riwayat revisi tetap tersedia, Activity Log memiliki alasan, dan Pulihkan berjalan. Login Karyawan tidak boleh melihat checkbox; login Super Admin harus melihat dua aksi tetapi tidak boleh menyamakan Arsip dengan Hapus Permanen. Uji di desktop Edge dan mode HP.

## Rilis
PR disiapkan sebagai Draft. Merge `main` hanya setelah CI PASS dan QA manual disetujui. Jangan deploy ke server BMC dari branch testing.
