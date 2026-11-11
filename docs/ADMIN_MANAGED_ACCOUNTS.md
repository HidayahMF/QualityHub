# QualityHub — Akun dikelola Super Admin (tanpa SMTP)

## Tiga role utama

- super_admin — mengelola pengguna, membuat akun, mereset password, serta akses administratif.
- admin — mengelola dan merevisi dokumen QC, mencari/mengunduh, tanpa hak kelola akun.
- user (Karyawan) — mencari, melihat, dan mengunduh dokumen QC, tanpa hak ubah data.

Role viewer yang sudah ada tidak diubah diam-diam; akun baru hanya dapat berperan admin atau user. Akun Super Admin tidak boleh dibuat dari UI.

## Alur

1. Super Admin login dan membuka User Management > Tambah Pengguna.
2. Masukkan ID Karyawan (sebagai username), nama, email, departemen aktif, role Admin/Karyawan, dan password sementara yang kuat.
3. Berikan password awal secara pribadi melalui saluran yang disepakati IT; jangan masukkan password ke chat publik atau log.
4. User login dengan ID Karyawan atau email. Jika memakai password sementara, user wajib menggantinya pada My Profile sebelum mengakses fitur lain.
5. Jika lupa password, user menghubungi Super Admin untuk verifikasi identitas.
6. Super Admin membuka User Management > Reset Password, memasukkan password Super Admin dan password baru untuk target.
7. Sistem mencabut seluruh sesi database target, mengganti remember_token, mencatat audit tanpa password, dan mewajibkan target mengganti password sendiri.

## Pengaturan

- Pendaftaran publik /register dinonaktifkan; user baru hanya dapat dibuat Super Admin.
- Forgot Password GET tetap berisi arahan hubungi Super Admin. Reset via SMTP / token tidak digunakan.
- Migrasi menambah must_change_password dengan default false sehingga akun lama tetap bisa masuk.
- Role backend yang ada dipertahankan; akun yang memiliki role lama viewer tidak diubah tanpa proses migrasi terpisah.

## Pengujian lokal (PowerShell)

    php -l app/Http/Controllers/AdminAccountController.php
    php -l app/Http/Requests/Auth/LoginRequest.php
    php artisan test --filter=AdminAccountManagementTest
    php artisan test --filter=PasswordResetTest
    php artisan test --filter=RegistrationTest
    php artisan test --filter=AuthenticationTest
    php artisan view:cache
    php artisan view:clear
    git diff --check

Jalankan tes di database testing lokal; jangan pernah memakai database production untuk tes create/reset.
Verifikasi manual pada akun dummy: create karyawan dan admin, login ID/email, perubahan password wajib, reset oleh Super Admin, sesi lama tercabut, hak akses admin vs karyawan, dan fitur register/email reset nonaktif.

## Sebelum deployment

- Production QualityHub masih diakses lewat HTTP; password perlu HTTPS agar tidak terekspos dalam perjalanan jaringan.
- Uji dulu semua alur, verifikasi migration, dan pastikan backup database tersedia.
- Jangan merge PR sebelum test lokal dan persetujuan deployment. GitHub Actions saat ini hanya build image, belum menjalankan PHPUnit.
