# QualityHub — Legacy PowerPoint PPT Upload QA (16 November 2026)

## Scope
- Add Document + Upload Revision menerima `.ppt` (OLE/CFB) dan tetap menerima `.pptx` (ZIP/OOXML).
- Maksimal 20 MiB per file. Dokumen asli disimpan di private BMC storage dengan UUID; filter format "PPT" di All Documents; detail menampilkan label PPT.
- Tidak ada perubahan database, dokumen existing, auth/role, Retooling, Scan/Capture, limit 100 foto, atau dependency.
- **PPT/PPTX tidak dipreview sebagai slide di browser**: tombol Open mengirim file sebagai download.
- `.pptm`, `.pps`, `.ppsx`, ZIP tersamar, DOC/XLS tersamar, serta OLE/CFB tidak valid tetap tidak didukung.

## Security gate
1. Allowlist extension dan server MIME; tidak mengandalkan nama ekstensi saja.
2. `.ppt`: header OLE, versi/byte-order, FAT/DIFAT, directory, stream PowerPoint Document + Current User, reject indikator VBA/macro yang dikenal. Tidak mengeksekusi/mengekstrak media atau menjalankan PowerPoint.
3. `.pptx`: validasi ZIP/OOXML dan macro/ActiveX dari PR #13 tetap ada.
4. Kedua format tetap menggunakan auth admin, batas 20 MiB, private storage, transaksi/revisi, serta audit log existing.
5. Pemeriksaan struktur **bukan antivirus**. Dokumen legacy Office dengan objek kompleks bisa membutuhkan pemeriksaan manual tambahan. Upload tidak membuat konten aktif otomatis aman bagi penerima.

## Automated QA — local *testing* database only
```powershell
php -l app/Services/LegacyPowerPointInspector.php
php -l app/Services/DocumentUploadSecurityService.php
php -l app/Http/Controllers/DocumentController.php
php -l app/Http/Controllers/DocumentVersionController.php
php -l tests/Feature/LegacyPptUploadTest.php
php artisan test --filter=LegacyPptUploadTest
php artisan test --filter=PptxUploadTest
php artisan test --filter=CategoryAssignmentTest
php artisan test --filter=DocxPreviewTest
php artisan test --filter=SearchableFilterSelectTest
npm ci
npm run build
php artisan view:cache
php artisan view:clear
git diff --check
```

## Real file acceptance — REQUIRED
Use safe, non-sensitive, genuine `.ppt` (e.g. `Manajemen-Proyek-Perangkat-Lunak-Pertemuan-10.ppt` 0.52 MiB) and an original `.pptx` exported by PowerPoint. Do **not** merely rename a DOC/XLS/ZIP extension.

- Super Admin/Admin: Add Document > select active category > select PPT > Save; confirm success, a new document/revision 0, and accurate file type and size.
- Download and reopen the *same* file with PowerPoint; confirm slides are intact. Test Open as download (never inline).
- Create a PPT revision on an active document; verify previous revision remains accessible, audit log remains intact.
- All Documents: filter by PPT; confirm only PPT documents appear. Verify PPTX filter continues to work.
- Try a macro-bearing PPT, forged OLE, renamed DOC/XLS, and `.pptm`: reject with visible error; no document row, orphan file, or unintended redirect to `/profile/photo`.
- User/Viewer: reject upload/revision operations (403); preserve read/download permissions for active documents.
- Verify in Edge and Chrome with actual file. If file fails, record exact validation message and a redacted local Laravel log; do not weaken security validation blindly.

## Deployment gate
Draft PR only; do not merge/redeploy until PHP/Blade regression and real Office PPT round-trip PASS.
GitHub "Build and publish images" is **not** a PHPUnit or browser test. IT BMC must approve production rollout, back up DB/documents first, verify image digest and deployed version, then smoke test.

## Hosted full-suite constraint (16 Nov 2026)
GitHub CI targeted upload regression: **55 passed / 370 assertions** (LegacyPpt 11, PPTX 13, Category 18, DOCX 3, Searchable Filter 10). Syntax, Vite and Blade also passed.
When running `php artisan test` against in-memory SQLite on GitHub Actions, the broad suite reported **47 failures / 318 passed**. Failures are concentrated in Retooling/Axle/Brake integration that expects external `mtc.LINE` master schema, and older Example/registration tests. These are not automatically attributed to this PPT change. The full-suite step is diagnostic/non-blocking in generic SQLite CI, **not** evidence the entire app is regression-free.
Before merging, run the affected workflow on the approved local/staging BMC test environment with valid MTC schema, and compare baseline `main` where relevant. Do not patch production Retooling or disabled registration merely to make CI green.

## Windows MIME regression — 16 Nov 2026
Windows PowerShell local `test/ppt-legacy-pr16` at commit `a38e82f`:
- Local `LegacyPptUploadTest` reported 2 failures (upload and revision) at Laravel's initial `mimetypes` rule, 9 others passed.
- `PptxUploadTest`: 13 PASS / 92 assertions; `npm run build`: PASS.
- Exact Windows MIME returned by fileinfo was **not present in the terminal log** and remains unverified.
- Fix on this PR: legacy PPT skips *only* Laravel's platform-dependent first-layer `mimetypes` condition. The document security service still requires the real .ppt extension, file-size limit, OLE header, valid CFB FAT and PowerPoint streams, and macro checks.
- If the Windows MIME is unrecognized by the configured whitelist, fallback is recorded as `ole_structure_verified_mime_fallback` **only after** structural validation succeeds. A renamed fake file must still fail and leave no storage/DB artifacts.
- New regression cases simulate Windows unknown MIME for a valid OLE PPT and a fake document. PPTX/other formats keep existing MIME rules.
- Do not claim that real PowerPoint files are accepted until manual upload/download/reopen succeeds on Windows.

Re-run on Windows after `git pull --ff-only origin feature/legacy-powerpoint-ppt-upload-20261116` while on the test branch:
```powershell
git fetch origin
git merge --ff-only origin/feature/legacy-powerpoint-ppt-upload-20261116
php artisan test --filter=LegacyPptUploadTest
php artisan test --filter=PptxUploadTest
npm run build
```
Do not reset local `composer.lock` or delete the untracked `code` directory.
