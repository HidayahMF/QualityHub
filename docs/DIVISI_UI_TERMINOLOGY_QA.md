# QualityHub — Rename Departemen → Divisi (UI)

## Cakupan
Semua istilah yang terlihat pengguna diganti menjadi **Divisi**. Berlaku untuk menu/sidebar, judul halaman, header, KPI, formulir, filter, tabel, konfirmasi, validasi, Activity Log action label, dan profil.

## Kompatibilitas penting
- UI: **Divisi**, **Tambah Divisi**, **Edit Divisi**, **Total Divisi**, **Divisi Aktif**, **Divisi Tidak Aktif**.
- Internal tetap: model `Department`, tabel `departments`, kolom `department_id`, dan route `/departments` serta nama `departments.*`.
- Internal tetap: form `department_id`, `new_category_department_id`, filter `department`, event log `create_department`, `delete_department` dan kawan-kawan.
- Internal tetap: komponen JS `departmentStatusConfirm`, selector `data-department-confirmation-code`, dan seluruh guard akses Super Admin.
- Tidak ada migrasi database atau perubahan dokumen. Log audit historis tidak ditulis ulang.

## QA lokal — jalankan sebelum merge
```powershell
php artisan test --filter=DivisionTerminologyUiTest
php artisan test --filter=DepartmentManagementTest
php artisan test --filter=DepartmentPermanentDeleteTest
php artisan test --filter=DepartmentActionRedirectRegressionTest
php artisan test --filter=CategoryAssignmentTest
php artisan test --filter=SharedTopbarBrandingTest
php artisan test --filter=SearchableFilterSelectTest
php artisan view:cache
php artisan view:clear
npm run build
```

Uji manual: halaman Divisi (tambah/edit/aktif/nonaktif/hapus aman), Dashboard, Categories, Users, pendaftaran, filter dokumen, profil, dan label Activity Log. Periksa pesan validasi. Pastikan akun non-Super-Admin tetap ditolak untuk pengelolaan Divisi.

**Status:** Branch/PR Draft. Belum ada hasil pengujian PHP/browser dari laptop. Tidak ada deployment ke server PT BMC.
