# Department Management — Penghapusan Permanen Aman

**Fitur baru (Draft PR)**, tidak dijalankan langsung terhadap database BMC.

## Syarat dan perilaku
- Hanya Super Admin yang memiliki izin.
- Departemen harus **nonaktif**; departemen aktif tidak bisa dihapus.
- Harus **0 kategori** dan **0 pengguna**, termasuk yang nonaktif. Tidak ada cascade delete atau auto-detach pengguna.
- User memilih **Hapus** di baris departemen, melihat modal peringatan, mengetik **kode departemen persis** sebelum tombol **Hapus Permanen** aktif.
- Server memeriksa ulang semua syarat di dalam transaksi dengan row lock, lalu menghapus baris departemen dan menulis Activity Log \`delete_department\`. Audit menyimpan identitas departemen yang telah dihapus.
- Error ditampilkan sebagai notifikasi; tidak terjadi penghapusan parsial. Kondisi FK race di database juga ditangani.
- Tidak ada migration atau perubahan data saat fitur di-install.

## Contoh dari screenshot produksi
- **Quality Control**: 4 kategori + 11 pengguna → TIDAK bisa dihapus.
- **TEST** dan **UAT Temporary Department**: ditampilkan 0 kategori/0 pengguna dan status tidak aktif → kandidat penghapusan; keputusan terakhir selalu berdasarkan pengecekan server saat tombol ditekan.
- **Jangan hapus data di server** hanya untuk uji coba. Buat departemen dummy di database lokal/test.

## QA lokal sebelum merger
\`\`\`powershell
php artisan test --filter=DepartmentPermanentDeleteTest
php artisan test --filter=DepartmentManagementTest
php artisan test --filter=SearchableFilterSelectTest
php artisan test --filter=ActivityLogRedesignTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`

Uji manual:
1. Pada localhost dengan akun Super Admin, nonaktifkan departemen dummy dan periksa tombol Hapus.
2. Buka modal. Coba isi kode salah: konfirmasi tetap disabled. Isi kode benar, baru konfirmasi.
3. Cek daftar berkurang satu dan Activity Log menampilkan **Hapus Permanen Departemen**.
4. Pastikan departemen aktif, yang memiliki kategori nonaktif, atau pengguna nonaktif semuanya tetap terlindungi.
5. Uji keyboard, responsif mobile, dan posisi tombol pada tabel horizontal.
6. Audit seluruh file \`composer.lock\` yang modified dan \`code\` yang untracked secara terpisah; jangan reset atau clean.

**Release gate:** setelah pengujian dan persetujuan Mas IT, baru merge ke main sehingga workflow Docker otomatis berjalan. Hindari operasi penghapusan riil tanpa backup.
