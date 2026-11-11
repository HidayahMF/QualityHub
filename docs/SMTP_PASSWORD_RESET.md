# Konfigurasi email reset password BMC QualityHub

## Status dan keamanan
- Endpoint `POST /forgot-password` memakai Laravel Password Broker. Token berlaku 60 menit dan permintaan dibatasi oleh Laravel.
- `MAIL_MAILER=log` / `array` **tidak mengirim email**. Aplikasi sekarang menampilkan pesan layanan belum tersedia dan tidak membuat token reset untuk mode tersebut.
- **Jangan menggunakan `MAIL_MAILER=failover` dengan fallback `log`**. Pada gagal SMTP, token reset akan tercatat ke log; aplikasi memblokir kombinasi ini.
- SMTP email belum akan berfungsi hanya karena source code ini di-merge. Mas IT perlu memberikan relay SMTP dan mengatur environment Portainer.

## Data yang diperlukan dari IT (jangan kirim password di chat/GitHub)
1. Host SMTP yang bisa dicapai dari **container Portainer**, bukan sekadar komputer pengguna.
2. Port dan keamanan TLS: misalnya port 587 + `MAIL_SCHEME=smtp` (STARTTLS bila ditawarkan), atau port 465 + `MAIL_SCHEME=smtps` (TLS sejak awal). Pilih sesuai kebijakan server email.
3. Apakah SMTP perlu autentikasi (`MAIL_USERNAME` dan `MAIL_PASSWORD`) atau relay internal tanpa login.
4. Alamat pengirim yang disetujui, seperti mailbox/no-reply internal resmi. Jangan gunakan `hello@example.com` pada production.
5. Izin firewall/DNS dari container ke SMTP dan izin pengiriman ke alamat karyawan.

## Portainer Stack environment — **isi kredensial hanya di Portainer**

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=GANTI_HOST_SMTP_DARI_IT
MAIL_PORT=587
MAIL_USERNAME=GANTI_JIKA_DIBUTUHKAN
MAIL_PASSWORD=GANTI_DI_PORTAINER_SAJA
MAIL_FROM_ADDRESS=GANTI_ALAMAT_PENGIRIM_RESMI
MAIL_FROM_NAME="BMC QualityHub"
```

Nilai hanya contoh. Untuk relay tanpa autentikasi, kosongkan username/password sesuai petunjuk IT.

Pastikan Portainer memakai `docker-compose.external-db.yml` **versi baru** dari branch `main` (bukan Web Editor copy lama). Service `app` harus meneruskan `MAIL_SCHEME`, `MAIL_USERNAME`, dan `MAIL_PASSWORD` ke container.

### HTTPS sangat dianjurkan sebelum reset password dipakai
Link email memuat token reset sensitif. Server QualityHub yang masih diakses lewat `http://` sebaiknya disediakan reverse proxy TLS dengan nama host resmi. Setelah HTTPS tersedia, sesuaikan `APP_URL=https://...` dan `SESSION_SECURE_COOKIE=true`. Jangan mengaktifkan secure cookie pada aplikasi yang masih HTTP saja karena login bisa gagal.

## Pengujian sebelum redeploy production
1. Di branch fitur, jalankan `php artisan test --filter=PasswordResetTest`.
2. Jalankan `php -l app/Http/Controllers/Auth/PasswordResetLinkController.php` dan `php artisan view:cache`, lalu `view:clear`.
3. Pastikan pengujian mode log/array menunjukkan pesan **belum tersedia**, tidak membuat reset token, dan tidak mengirim notifikasi.
4. Setelah di-merge, pastikan GitHub Actions build hijau. Mas IT kemudian mengonfigurasi SMTP di Portainer dan pull image terbaru.
5. Uji dengan **akun dummy** yang alamat emailnya dapat diakses. Buka halaman Lupa Password, kirim reset, buka email melalui mailbox, ubah password, dan login ulang.
6. Verifikasi token lama tidak bisa dipakai lagi dan password lama tidak berhasil untuk login. Jangan membagikan token/link reset ke chat/screenshot.
7. Jika belum menerima email, periksa status SMTP / DNS / firewall dan log aplikasi. SMTP menerima pesan **bukan** jaminan pesan masuk inbox; periksa juga junk/spam dan kebijakan relay.

## Diagnosis aman (tanpa membocorkan password SMTP)
- Jangan mengirim `php artisan config:show mail` lengkap ke chat; konfigurasi ini bisa menampilkan rahasia.
- Jika memeriksa runtime, hanya tunjukkan mailer, host, port, dan nama pengirim. **Sembunyikan MAIL_PASSWORD dan token reset**.
- Jika mailer masih `log`, artinya environment terbaru belum diterapkan atau belum diisi untuk SMTP.

## Rollback
- Jika SMTP belum siap, `MAIL_MAILER=log` membuat reset password **dinonaktifkan secara eksplisit** dan tidak menuliskan token ke log. Fitur lain tetap berjalan.
- Jangan menggunakan mode `failover` yang berakhir di log.
