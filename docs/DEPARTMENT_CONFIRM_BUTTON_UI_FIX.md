# Fix Visual Konfirmasi Hapus Departemen — 13 November 2026

**Gejala (uji localhost):** tombol **Hapus Permanen** di sebelah **Batal** tampak hampir putih dan teksnya tidak terbaca, meskipun modal dan input kode muncul.

**Penyebab:** tombol sebelumnya hanya memiliki \`text-white\` sebagai class tetap. Class latar (\`bg-rose-700\` atau \`bg-blue-800\`) ditentukan melalui \`x-bind:class="confirmButtonClass"\`. Jika class dinamis tidak tersedia pada stylesheet build atau belum diaplikasikan, tampil tombol putih-transparan dengan teks putih.

**Perbaikan:** pisahkan markup tombol konfirmasi menjadi dua tombol \`type=button\` eksklusif:
- Tombol **Aktifkan/Nonaktifkan** berwarna biru dengan class \`bg-blue-800\` statis, tampil saat \`normalAction\`.
- Tombol **Hapus Permanen** berwarna merah dengan class \`bg-rose-700\` statis, tampil saat \`destructive\`. Saat disabled, gunakan \`disabled:bg-rose-100\` dan \`disabled:text-rose-800\` agar teks masih terlihat.
- Kedua tombol tetap memakai \`x-bind:disabled="cannotConfirm"\`, \`x-on:click="confirm"\`, dan modal yang sama. Validasi kode tetap dilakukan di server menggunakan \`hash_equals\`; tidak ada penghapusan otomatis.
- Tidak perlu mengubah CSS global, struktur database, middleware, maupun halaman selain modal Departemen.

**QA:** jalankan \`php artisan test --filter=DepartmentConfirmationButtonUiTest\`, \`php artisan test --filter=DepartmentPermanentDeleteTest\`, \`php artisan test --filter=DepartmentActionRedirectRegressionTest\`, lalu \`npm run build\` dan \`php artisan view:cache\`. Uji Chrome/Edge: tombol merah harus terlihat jelas sejak modal terbuka; sebelum kode cocok tampil merah muda dengan teks merah; ketika kode benar tombol menjadi merah solid. Uji klik Batal dan Aktivkan tetap biru.

**Safety:** PR #12 Draft, belum merge ke main; tes manual dengan departemen dummy LOCAL saja, jangan hapus departemen produksi BMC.
