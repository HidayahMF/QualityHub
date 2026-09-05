# BMC SmartFind — Restore / Recovery Procedure 17G

Prosedur ini sengaja **tidak meng-overwrite production secara otomatis**. Recovery yang aman dilakukan ke **database baru + document root baru**, diverifikasi, lalu baru dipindahkan menjadi target aktif.

## 1. Verifikasi backup sebelum restore

```powershell
php artisan smartfind:backup-verify smartfind-YYYYMMDD-HHMMSS-xxxxxxxx
```

Harus menghasilkan:

```text
BACKUP VERIFICATION: PASS
```

Jangan restore backup yang gagal verifikasi.

## 2. Buat safety backup kondisi saat ini

Sebelum recovery, backup kondisi current jika masih dapat diakses:

```powershell
php artisan down --retry=60
php artisan smartfind:backup
```

Catat nama backup tersebut.

## 3. Siapkan database recovery BARU

Jangan langsung DROP / overwrite `bmc_smartfind`.

Contoh buat database:

```sql
CREATE DATABASE bmc_smartfind_recovery
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Restore dump ke database baru menggunakan client MySQL/MariaDB.

Contoh XAMPP Windows:

```powershell
$env:MYSQL_PWD = "PASSWORD_DATABASE_JIKA_ADA"

cmd /c """C:\xampp\mysql\bin\mysql.exe"" --host=127.0.0.1 --port=3306 --user=root bmc_smartfind_recovery < ""D:\BMC_Backups\SmartFind\smartfind-YYYYMMDD-HHMMSS-xxxxxxxx\database\bmc_smartfind.sql"""

Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
```

Jika database tidak memakai password, `MYSQL_PWD` tidak perlu di-set.

## 4. Restore private documents ke root BARU

Contoh:

```powershell
New-Item -ItemType Directory -Force "D:\BMC_Data\SmartFindDocuments_Recovery"

robocopy `
  "D:\BMC_Backups\SmartFind\smartfind-YYYYMMDD-HHMMSS-xxxxxxxx\documents" `
  "D:\BMC_Data\SmartFindDocuments_Recovery" `
  /E /COPY:DAT /DCOPY:DAT /R:2 /W:2
```

Jangan copy langsung menimpa private storage current.

## 5. Arahkan environment ke recovery target

Sementara:

```env
DB_DATABASE=bmc_smartfind_recovery
BMC_STORAGE_PATH=D:/BMC_Data/SmartFindDocuments_Recovery
```

Kemudian:

```powershell
php artisan optimize:clear
php artisan migrate:status
php artisan smartfind:audit-storage --details
php artisan smartfind:audit-integrity --details
```

Target:

```text
PRIVATE STORAGE AUDIT: PASS
DATA INTEGRITY AUDIT: PASS
Critical Fail: 0
```

## 6. Jalankan smoke / regression test

```powershell
php artisan test
```

Lakukan pengecekan manual minimal:

- Login Super Admin.
- Document Library tampil.
- Open / download beberapa dokumen.
- Revision History tampil.
- Search bekerja.
- Analytics spreadsheet bisa dibuka.
- Department / Category / User Management tetap tersedia.
- Activity Log dapat dibaca.

## 7. Cutover

Hanya setelah recovery target lolos seluruh audit.

Pilihan paling aman adalah mempertahankan database/root recovery sebagai target baru dan mengubah konfigurasi production ke lokasi tersebut.

Jangan menghapus database/root lama pada hari yang sama. Simpan sebagai quarantine/rollback point sampai recovery dinyatakan stabil.

## 8. Hidupkan aplikasi

```powershell
php artisan optimize:clear
php artisan up
```

## Prinsip penting

- Jangan `migrate:fresh`.
- Jangan menghapus orphan file otomatis.
- Jangan restore ke storage current sebelum backup diverifikasi.
- Backup database tanpa private documents bukan full recovery.
- Backup private documents tanpa database juga bukan full recovery.
- SHA-256 manifest mendeteksi corruption/incomplete copy, bukan membuktikan authenticity terhadap attacker yang dapat mengubah file dan manifest sekaligus.
