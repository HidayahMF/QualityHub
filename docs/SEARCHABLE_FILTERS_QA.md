# QualityHub — searchable dropdown filters (Select2-style) QA

Branch: `feature/searchable-filter-selects` (separate from the manual crop PR).

## Change
A CSP-safe, blue/white searchable dropdown is now progressively enhanced on the filter forms of:

1. **All Documents** — Department, Category, File Type, QC Document Type
2. **Archived Documents** — Department, Category
3. **Categories** — Department, Status
4. **Favorites** — Department, Category, File Type
5. **Activity Log** — User, Action, Document
6. **Departments** — Status
7. **Users** — Status, Role

**17 single-selects total.** Each native `<select>` remains in the form for Laravel's existing GET parameters and Alpine `x-model` / `@change`. The enhanced control is keyboard accessible, supports accent-insensitive typing, and shows up to 120 matches at once (all options can be found with a more specific search).

This is a *Select2-style* implementation without jQuery or externally hosted assets, to avoid network/CDN dependency and conflicts with the existing restrictive CSP. No schema/database changes.

## Local PowerShell checks

```powershell
cd D:\BMC_Project\QualityHub-IT
git fetch origin
git switch --track origin/feature/searchable-filter-selects
php artisan test --filter=SearchableFilterSelectTest
npm ci
npm run build
php artisan view:cache
php artisan view:clear
git diff --check origin/main...HEAD
```

Run the checks **against local/test data only**, never the production DB. Do not merge or redeploy until verified.

## Browser acceptance
- Open All Documents, click **Semua Departemen**. Dropdown displays a focused search input. Type part of a department name; only matching options appear.
- Choose a department. Verify the existing live-search behavior still applies. Reload/reset filter and verify the label and result list remain in sync.
- Repeat with **Semua Kategori**, **Semua Jenis File**, and **Semua Jenis Dokumen**; confirm QC type presets/quick-search reset remain functional.
- Open Archives, Categories, Favorites, Activity Log, Users, Departments. For each filter, type a search term, choose a result, click **Terapkan Filter**, and check results and query string. The selected option should remain visible after reload.
- Verify that typing a nonexistent option displays **Tidak ada pilihan yang cocok** and does **not** silently change the current selection.
- Select **Semua ...** again to reset that filter, and use **Atur Ulang** to reset the entire filter form.
- On a narrow/mobile viewport, confirm the dropdown does not disappear behind sidebar/cards. It must fit inside the viewport and can open above the trigger if below space is insufficient.
- Keyboard: Tab to the filter button; Enter to open; type; Arrow Up/Down then Enter to select; Escape to close; Tab should move to the next control.
- Try 150+ mock options in a disposable local browser fixture: the result list caps rendering at 120 but typing can still find options beyond the first 120.
- Confirm no console errors and no dependency on an external CDN.

## Release boundary
- PR remains Draft until PHP/asset build and browser checks pass.
- No changes to upload, account management, database migrations, or production deployment.
