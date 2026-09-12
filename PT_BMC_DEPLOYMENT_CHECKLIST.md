# BMC QualityHub — PT BMC Deployment Checklist

Status local saat checklist dibuat:
- SH-1 sampai SH-5: CLOSED
- SH-6 Production .env + HTTPS: DEFERRED menunggu server PT BMC
- SH-7 Backup/Restore: CLOSED
- SH-7 Database Least Privilege: CLOSED
- SH-7D NAS/Production ACL: DEFERRED menunggu server/NAS PT BMC
- SH-8A Local Final Security Gate: CLOSED
- SH-8B Local Operational Gate: CLOSED
- Test suite: 110 passed, 425 assertions, 0 failed
- Runtime DB user local: bmc_smartfind_app
- Backup DB user local: bmc_smartfind_backup
- Backup root local: D:/BMC_Backups/SmartFind

## 1. Informasi yang harus diminta dari IT PT BMC

Jangan isi dengan tebakan. Isi hanya setelah IT PT BMC memberikan data resmi.

- [ ] Server OS:
- [ ] IP server:
- [ ] Hostname/FQDN internal:
- [ ] Web server / reverse proxy:
- [ ] Port aplikasi yang diizinkan:
- [ ] Apakah HTTPS wajib:
- [ ] Sumber sertifikat TLS/HTTPS:
- [ ] Lokasi database MariaDB/MySQL:
- [ ] Host database:
- [ ] Port database:
- [ ] Nama database production:
- [ ] Akun DB runtime production:
- [ ] Akun DB backup production:
- [ ] Lokasi NAS/private document storage:
- [ ] Format path NAS/UNC:
- [ ] Service account Windows/Linux yang menjalankan aplikasi:
- [ ] Permission service account ke NAS:
- [ ] Lokasi backup production:
- [ ] Retention backup yang diminta:
- [ ] Jadwal backup yang diminta:
- [ ] SMTP production untuk forgot/reset password:
- [ ] Firewall rule yang diperlukan:
- [ ] Siapa PIC IT untuk deployment:

## 2. Sebelum Deployment

- [ ] Jangan memakai akun DB root untuk Laravel.
- [ ] Jangan memakai akun DB runtime untuk migration/deployment administration.
- [ ] Jangan menaruh private documents di public/.
- [ ] Jangan hardcode IP/hostname production sebelum data resmi tersedia.
- [ ] Jangan menyalin password production ke source code atau Git.
- [ ] Backup existing environment/server sebelum perubahan.
- [ ] Pastikan source yang dideploy adalah versi yang sudah lolos local final gate.

## 3. Production .env

Dikerjakan hanya setelah hostname, HTTPS, database, dan NAS final.

- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_URL menggunakan URL resmi PT BMC
- [ ] SESSION_DRIVER=database
- [ ] SESSION_ENCRYPT=true
- [ ] SESSION_HTTP_ONLY=true
- [ ] SESSION_SECURE_COOKIE=true setelah HTTPS aktif
- [ ] SESSION_SAME_SITE=lax
- [ ] SMARTFIND_SAME_ORIGIN_ENABLED=true setelah hostname final
- [ ] SMARTFIND_CSP_ENABLED=true
- [ ] SMARTFIND_HSTS_ENABLED=true hanya setelah HTTPS benar-benar aktif
- [ ] DB_HOST production benar
- [ ] DB_PORT production benar
- [ ] DB_DATABASE production benar
- [ ] DB_USERNAME menggunakan akun runtime least-privilege
- [ ] DB_PASSWORD tersimpan hanya di environment
- [ ] SMARTFIND_BACKUP_DB_USERNAME menggunakan akun backup khusus
- [ ] SMARTFIND_BACKUP_DB_PASSWORD tersimpan hanya di environment
- [ ] BMC_STORAGE_PATH mengarah ke private storage/NAS resmi
- [ ] SMARTFIND_BACKUP_PATH mengarah ke lokasi backup yang terpisah dari storage utama
- [ ] SMARTFIND_MYSQLDUMP_PATH sesuai server production

## 4. Database Production

- [ ] Database production tersedia.
- [ ] Charset/collation sesuai aplikasi.
- [ ] Akun runtime hanya memiliki SELECT, INSERT, UPDATE, DELETE yang diperlukan.
- [ ] Akun backup memiliki privilege logical-backup yang diperlukan.
- [ ] Laravel tidak berjalan sebagai root.
- [ ] Migration dijalankan menggunakan credential deployment/admin yang terkontrol.
- [ ] Setelah migration, runtime dikembalikan ke akun least-privilege.
- [ ] php artisan migrate:status menunjukkan seluruh migration Ran.

## 5. NAS / Private Storage

- [ ] Path NAS/private storage dapat diakses service account aplikasi.
- [ ] Service account hanya diberi permission yang memang diperlukan.
- [ ] User biasa tidak mendapat direct filesystem access melalui web server.
- [ ] Private storage tidak berada di public web root.
- [ ] Read/write test berhasil.
- [ ] Upload document berhasil.
- [ ] Open/preview document berhasil.
- [ ] Download document berhasil.
- [ ] Revision upload berhasil.
- [ ] Integrity audit tidak menemukan missing/orphan file.

## 6. Build dan Laravel Production

- [ ] composer install --no-dev --optimize-autoloader
- [ ] npm ci
- [ ] npm run build
- [ ] Tidak ada public/hot
- [ ] php artisan optimize:clear
- [ ] Production cache dibuat sesuai kebutuhan server.
- [ ] APP_KEY tersedia dan tidak diganti sembarangan.
- [ ] Folder runtime Laravel writable oleh service account yang tepat.
- [ ] Queue/worker dikonfigurasi bila production memakai worker terpisah.

## 7. HTTPS dan Browser Security

- [ ] HTTPS dapat dibuka tanpa certificate warning.
- [ ] HTTP diarahkan ke HTTPS bila diwajibkan.
- [ ] Secure session cookie aktif.
- [ ] Same-origin protection aktif.
- [ ] CSP aktif tanpa unsafe-inline / unsafe-eval.
- [ ] HSTS baru diaktifkan setelah HTTPS stabil.
- [ ] Permissions Policy mempertahankan camera=(self) untuk scan/capture.
- [ ] Login/logout/reset password berjalan normal.
- [ ] Live Smart Search tetap responsif.
- [ ] Camera/scan diuji dari browser/device production yang memang dipakai.

## 8. Backup dan Disaster Recovery

- [ ] Full backup production berhasil.
- [ ] Backup tersimpan di lokasi terpisah dari document storage utama.
- [ ] Database dump tersedia.
- [ ] Private documents ikut ter-backup.
- [ ] SHA-256 manifest dibuat.
- [ ] smartfind:backup-verify PASS.
- [ ] Restore drill dilakukan ke database/storage test, bukan active production.
- [ ] Restore drill berhasil.
- [ ] Retention/jadwal backup disepakati dengan IT PT BMC.

## 9. Final Production Audit

Jalankan setelah seluruh konfigurasi production selesai.

- [ ] php artisan test
- [ ] php artisan smartfind:audit-health
- [ ] php artisan smartfind:audit-reliability
- [ ] php artisan smartfind:audit-integrity
- [ ] php artisan smartfind:audit-storage
- [ ] php artisan smartfind:audit-performance
- [ ] php artisan smartfind:audit-web-security
- [ ] php artisan smartfind:audit-auth-session
- [ ] php artisan smartfind:audit-production
- [ ] php artisan smartfind:backup
- [ ] php artisan smartfind:backup-verify <backup-name>

Target final:
- 0 Critical Fail
- 0 unexpected Warning
- Production audit PASS
- Backup verify PASS
- Live search normal
- Login/session normal
- Upload/open/download/revision normal
- NAS/private storage normal

## 10. Go-Live Sign-Off

- [ ] PIC IT PT BMC menyetujui hostname/IP/HTTPS.
- [ ] PIC IT PT BMC menyetujui NAS/storage permission.
- [ ] Backup dan recovery plan disetujui.
- [ ] Super Admin production sudah dibuat/ditentukan.
- [ ] Default/test account dibersihkan atau dinonaktifkan.
- [ ] Tidak ada credential development dipakai di production.
- [ ] Tidak ada password/secret tercatat di dokumentasi publik.
- [ ] Final smoke test PASS.
- [ ] QualityHub siap digunakan di lingkungan PT BMC.

Catatan:
Checklist ini sengaja tidak mengisi IP, hostname, NAS path, sertifikat, atau credential production karena data tersebut harus berasal dari IT PT BMC dan tidak boleh ditebak.
