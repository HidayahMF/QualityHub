# QualityHub — Label Cycle Time (Menit) pada Retooling

## Konfirmasi unit
Pengguna mengonfirmasi bahwa nilai Cycle Time (kolom `ppic.LINEprd.CT`) menggunakan **menit**, bukan detik.

## Perubahan UI
- Halaman Scan > Retooling > pilihan Type: `CT 25 menit`.
- Ringkasan pemilihan Dari/Ke Type: `Cycle Time: 25 menit`.
- Detail dokumen Retooling aktif dan lama: `Cycle Time Dari: 25.00 menit`, `Cycle Time Ke: 22.00 menit`.
- Ketika belum memilih Type, placeholder `Cycle Time: -` tetap tampil (tanpa satuan pada nilai kosong).

## Batas perubahan (penting)
- **Tidak ada konversi angka x60 atau /60**: nilai CT master PPIC disimpan sebagai snapshot apa adanya.
- Tidak ada perubahan tabel, migration, API, master PPIC/MTC, filter, ACL, tanggal Retooling, shift, atau perilaku Scan/Revision.
- Dokumen historis tetap menggunakan snapshot CT yang lama terlebih dahulu; perubahan master baru tidak mengubah snapshot tersebut.

## Pengujian
```powershell
php artisan test --filter=RetoolingCycleTimeMinutesUiTest
php artisan view:cache
php artisan view:clear
npm run build
```

Manual: pilih Type dari dan ke di Scan Retooling; periksa opsi CT, ringkasan CT, dan detail hasil scan. Periksa dokumen lama dan angka snapshot tetap sama. Uji dengan koneksi MTC/PPIC perusahaan dalam lingkungan staging sebelum merge/deploy. Full `RetoolingScanTest` memerlukan tabel master eksternal dan tidak otomatis dapat diuji dengan SQLite CI umum.

## Release gate
Draft PR. Pastikan angka yang benar adalah **menit** berdasarkan master PPIC BMC; review perubahan UI, test, lalu merge setelah disetujui. Jangan redeploy server BMC tanpa proses rilis terpisah.
