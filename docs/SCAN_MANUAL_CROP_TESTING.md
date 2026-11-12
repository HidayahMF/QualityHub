# Manual crop & ordered photo queue — local acceptance checklist

Branch: `feature/manual-scan-crop-photo-order`

**Never deploy, merge, or test uploads against production.** Use only the local QualityHub app and disposable test pictures.

## Scope
- Editing a photo opens a **manual crop dialog**; it is not the old percentage slider.
- Drag the blue selection to position it, or resize from **left, right, top, bottom, and all four corners**.
- Crop applies to the **active photo only**, using a canvas matching the current rotation/zoom/brightness/contrast/grayscale settings.
- Confirming crop replaces the active queued File; cancel preserves it.
- The queue displays `Foto 1`, `Foto 2`, etc., plus `Halaman PDF X dari N` and first/last markers.
- Moving a photo changes the queue order and the order of `files[]` submitted to the backend.

## Automated checks (PowerShell, local branch)

```powershell
cd D:\BMC_Project\QualityHub-IT
git fetch origin
git switch --track origin/feature/manual-scan-crop-photo-order
php artisan test --filter=ScanManualPhotoEditorTest
php artisan test --filter=RetoolingScanTest
php artisan test --filter=AxleBrakeProductionScanTest
php artisan test --filter=DocumentScanTypeManagementTest
php artisan view:cache
php artisan view:clear
git diff --check origin/main...HEAD
git status -sb
```

The Retooling test suite has **pre-existing failures** because it references older local master tables whereas production code queries `mtc.LINE` / `ppic.LINEprd`. Treat any such errors as baseline issues, not proof they were caused by this UI branch; still record and share exact results. Do not run full tests on the production database.

## Browser acceptance (localhost, Super Admin/Admin)
1. Open **Scan / Capture** and add eight disposable photo files. Verify each card reads `Foto 1` ... `Foto 8`, with `Halaman PDF 1 dari 8 · PERTAMA` and `Halaman PDF 8 dari 8 · TERAKHIR`. Filenames stay separately readable.
2. Select **Foto 3**, click **Pilih Area Crop Manual**. A dialog opens with full selection.
3. Pull the top handle down and left handle right; move the selection, then resize with a corner. Confirm only the inside pixels are kept after clicking **Terapkan Crop**.
4. **Layout regression:** at 100% browser zoom on a laptop (e.g., 1366×768), open crop. Verify the white/blue dialog sits above the sidebar/header, the full header and **Terapkan Crop** button are visible, and you can scroll inside the dialog if its contents do not fit. Repeat in Edge mobile device emulation (e.g., 390×844), including finger/touch pointer selection. Clicking the ✕ button or Escape must restore normal page scrolling without editing the photo.
5. Check the edited image replaced **Foto 3 only**. Other thumbnails stay unchanged; clicking **Batal** must not modify a picture.
6. Select another photo, rotate it 90 degrees, then open crop. Confirm the selection matches the rotated image and output.
7. Reorder **Foto 8** to the first slot using arrow buttons. Verify the number/first marker now matches its new position and the old first photo moves to slot two.
8. Remove a middle photo and add another. The page numbers must be consecutive and the first/last labels accurate.
9. Submit **only if safe local test DB/storage are confirmed**. Verify all pages in the PDF match queue order and crop results, and no old crop slider appears.
10. If using touch/mobile, repeat selection move and side/corner resizing with a finger. Confirm the dialog fits the screen and no accidental page scrolling occurs during drag.
11. Check `php artisan test --filter=ScanManualPhotoEditorTest` passes, and inspect the browser console for JavaScript errors.

## Out of scope
- Repairing MTC/PPIC Retooling integration (separate branch/PR).
- HTTPS, production configuration, or database migration.
- Merging into main, rebuilding or redeploying Portainer.

This change adds no new database migration and does not change the backend scan file upload contract.
