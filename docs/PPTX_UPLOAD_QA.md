# PPTX Upload / Revision — QA & Security Notes

**Scope:** New document upload, existing document revisions, document filters, and format display. Separate Draft PR from Department delete / LED changes. No schema migration or new dependency.

## Formats supported
- Existing: PDF, XLS, XLSX, DOC, DOCX, CSV, TXT
- New: **PPTX** (standard macro-free PowerPoint OOXML presentation)
- Images from Scan/Capture are a separate workflow and are not expanded by this PR.
- Unsupported: PPT (legacy), PPTM, PPSX, PPTX with VBA / ActiveX, ZIP, executable formats, ODP. Do not claim they are accepted.

## Behavior
1. Laravel first-layer upload rules allow .pptx on Add Document and Upload Revision (20 MB limit).
2. Security service verifies client extension, server MIME, ZIP structure, correct OOXML PowerPoint presentation content type, macro/ActiveX indicators, and bounded expanded archive size.
3. Files retain UUID-based names in private BMC storage; authenticated users download originals through existing routes.
4. Existing Document index extension whitelist recognizes PPTX when active PPTX documents exist. Existing document format label is derived from the extension.
5. **No slide preview**: clicking Open behaves as download, not inline rendering. PPTX file is not executed by the server and no external Office/cloud viewer is used.

## Testing (LOCAL / test database only)
\`\`\`powershell
php artisan test --filter=PptxUploadTest
php artisan test --filter=CategoryAssignmentTest
php artisan test --filter=DocxPreviewTest
php artisan test --filter=SearchableFilterSelectTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`

Manual checks:
- Use an actual .pptx exported from Microsoft PowerPoint or LibreOffice Impress, <=20 MB.
- Log in as Super Admin locally, Add Document, choose category, select PPTX, Save; verify metadata, download and re-open in PowerPoint.
- Upload a new PPTX revision; verify both original and current revisions download.
- Filter All Documents by PPTX after uploading.
- Reject renamed .zip/.txt/.pptm and macro-bearing files.
- Validate with user and server timezone preferences (not linked to upload change).
- Ensure only test/local database and storage is used; do not upload customer documents to public demo sites.
- Avoid changing composer.lock or deleting local untracked code folder.

**Release gate:** PR remains Draft until local PHPUnit suite and manual file round-trip pass. Do not merge into main or deploy to BMC without IT review.


## Perbaikan redirect upload ke /profile/photo (13 Nov 2026)

**Reproduksi:** di localhost, setelah klik **Simpan Dokumen** pada PPTX sekitar 4,84 MB, browser membuka \`/profile/photo\` alih-alih pesan keberhasilan/error. Ini dapat terjadi jika validasi gagal dan Laravel memakai \`back()\`/redirect default ke URL terakhir sesi yang tersimpan dari pemuatan foto autentikasi. Form upload harus menjadi tujuan pasti pada error.

**Perbaikan pada controller Add Document dan Upload Revision**
- Validasi form memakai \`Validator::make()\` dan redirect eksplisit ke \`documents.create\` atau \`documents.revisions.create\` untuk semua kegagalan validasi.
- Penolakan service keamanan file, kategori, dan penyimpanan juga kembali ke form terkait dengan error ditampilkan; bukan ke \`/profile/photo\`.
- Sukses tetap redirect ke detail dokumen atau daftar revisi; penyimpanan privat dan rollback tetap dipertahankan.
- File PPTX berbentuk ZIP OOXML dapat dikenali oleh fileinfo sebagai \`application/zip\` atau \`application/octet-stream\`. Validasi MIME awal menerima kemungkinan ini **khusus extension pptx**, tetapi setiap file tetap wajib lolos pemeriksaan signature, Content Types PowerPoint, daftar komponen dan larangan macro/ActiveX dari \`DocumentUploadSecurityService\`. Jenis file lain tetap mengikuti aturan MIME lama.
- Pesan \`file.uploaded\` kini mengingatkan untuk memeriksa batas \`upload_max_filesize\` dan \`post_max_size\` PHP. Batas aplikasi tetap 20 MB.

**QA tambahan:** 5 kasus regresi dengan sesi \`_previous.url=/profile/photo\`, memastikan kegagalan upload baru, metadata tidak lengkap, keamanan PPTX, revisi invalid, dan revisi berbahaya semuanya kembali ke form dengan pesan error. Saat berhasil, cek bahwa upload .pptx asli masuk database, dapat diunduh dan dibuka ulang dengan PowerPoint.

**Periksa ukuran file sebelum testing manual:** file 4,84 MB hanya bisa diterima bila konfigurasi PHP dan web server mengizinkannya. Jalankan:
\`\`\`powershell
php -r "echo 'upload_max_filesize='.ini_get('upload_max_filesize').PHP_EOL.'post_max_size='.ini_get('post_max_size').PHP_EOL;"
\`\`\`
Perintah terminal membaca CLI PHP yang bisa berbeda dari PHP proses web. Untuk XAMPP Apache, cocokkan pengaturan dari \`phpinfo()\` secara lokal; jangan membuat halaman phpinfo dapat diakses di server produksi. File \`php.ini\` perlu set batas yang memadai di kedua tempat dan restart layanan web. Hindari membuka file \`.env\` perusahaan ke publik.

**Release gate:** masih Draft PR #13. Jangan merge atau redeploy ke server BMC sebelum semua tes dan upload asli lokal sukses.
