# QualityHub — 1.000-page Scan/Capture (Android, iPhone, desktop)

> **Draft feature, not production-ready until real-device and server load tests pass.**
> This branch is based on PR #8 (manual crop, photo ordering, camera redesign).
> PR #9 (searchable filters) is a separate branch, not included in this one.

## Business requirement

One QC Scan/Capture document can contain up to **1,000 ordered photo pages**,
combined into **one searchable/downloadable PDF**. This replaces the 100-photo
hard limit. Admins can use the Android/iPhone native camera or choose existing
photos; the UI retains manual crop, reordering and page numbers.

## Technical mechanism

- 1–50 source photos retain the existing single-request upload. Above 50,
  the browser starts an authenticated private batch session, uploads ten
  photos per HTTP request, and finally submits **only** metadata and a
  batch ID to the existing ScanDocumentController transaction.
- Backend reconstructs the verified staged photo files in their original
  sequence. Uploaded images are scanned again by ScanImageSecurityService,
  then MultiPageScanPdfService produces a single PDF.
- Staged images are stored privately under `storage/app/private/scan-upload-batches`
  (local disk). Access is owner-checked and guarded by role + CSRF. Incomplete
  and out-of-order chunks are rejected. Successful finalization deletes the
  staging directory; stale sessions are pruned after 12 hours on the next
  batch start.
- Queue renders 25 thumbnail cards per page, instead of all 1,000 at once.
  It preserves page numbering and arrow ordering across pagination.
- **Mode 1.000 Halaman** is explicitly opt-in before adding photos:
  Android/iPhone native camera shots and large gallery images are reduced
  to a target <= ~170 KB each when browser conversion succeeds. Standard
  mode preserves the previous ~1.6 MB camera quality and original gallery
  files. Verify fine print readability before accepting a batch.
- Maximum source image payload remains **200 MiB in total**; final PDF
  must also remain **200 MiB**. These are important safety limits, not
  guarantees that all 1,000 full-resolution photos fit. With 1,000
  pages, the per-page average needs to be below ~200 KiB.
- Docker PHP's `max_file_uploads=100` stays unchanged because one request
  contains <=10 files. Docker's `memory_limit` is proposed at 1024M and
  `max_execution_time` at 900s for long PDF generation; IT must verify
  container RAM/CPU and reverse-proxy timeouts before enabling in production.
- **No database migration** and no modifications to document/archive APIs.
- Server failures do NOT silently save partial PDF. On an interrupted
  batch upload the browser keeps selected photos for retry when possible;
  old server-side drafts expire in private storage.

## Local commands

```powershell
cd D:\BMC_Project\QualityHub-IT
git status -sb
git fetch origin
git switch --track origin/feature/scan-1000-pages-batch-upload

php -l app/Services/ScanUploadBatchService.php
php -l app/Http/Controllers/ScanUploadBatchController.php
php -l app/Http/Controllers/ScanDocumentController.php
php -l routes/web.php

php artisan test --filter=ScanUploadBatchTest
php artisan test --filter=MultiPageScanPdfServiceTest
php artisan test --filter=ScanManualPhotoEditorTest
php artisan test --filter=DocumentScanTypeManagementTest
php artisan view:cache
php artisan view:clear
git diff --check origin/feature/manual-scan-crop-photo-order...HEAD
```

Never run these against production. Local DB must be verified first.

## Real-device QA (before production)

1. Confirm laptop/server and phone are on an authorized, trusted LAN; use
   temporary QC demo documents only.
2. Desktop: select 1, 2, 50, 51, 100, 101 photos, test success,
   PDF page order, download, and no partial documents on failure.
3. Phone Android (Chrome) and iPhone (Safari): enable **Mode 1.000 Halaman
   before selecting photos**; take native camera pictures, select from
   gallery, crop selected pages, and reorder across list page boundaries.
4. Verify preview, labels Foto 1 … Foto 1000, 25-card pagination,
   upload progress, modal responsiveness, and that battery/temperature/
   browser memory remain acceptable with real QC content.
5. Test progressively at 100, 250, 500, and **1,000 pages**. Validate
   original document text readability, final PDF page count/order, total
   byte limits, and whether iPhone can select/convert that many photos.
   A small synthetic image test alone is insufficient.
6. Simulate Wi-Fi interruption after 40/100 chunks. The browser should
   show a readable error, keep its photo list for retry, and not create
   an incomplete document or leave long-term public files.
7. Test unprivileged Karyawan access to start/upload/discard endpoints
   (expected 403), owner mismatch, out-of-order offset, >10 per chunk,
   more than 1,000 total, expired batches, and invalid image formats.
8. Check PHP/Apache logs, container memory peak, disk space and request
   timeout at 1,000-page load. The 1024 MiB PHP limit is a *ceiling*,
   not proof the Portainer host has enough resources.
9. Confirm production settings explicitly set
   `SMARTFIND_SCAN_MAX_FILES=1000`, or leave the new compose default.
   Do not redeploy until approved by BMC IT.

## Known limitations before acceptance

- 1,000-page conversion is resource-intensive; some Android/iPhone devices,
  browser photo pickers, HEIC decoders, Apache timeouts, and container RAM
  may still fail. Test representative hardware and real document images.
- Upload drafts are temporary, not indefinitely resumable across page reloads.
  Do not close the page during upload.
- 200 MiB source/PDF maximum remains enforced. Do not increase it
  without an IT-approved memory/storage/load test.
- PR #8, PR #9, and this batch upload PR must remain separate until their
  dependencies, unit tests, and manual QA are complete.
