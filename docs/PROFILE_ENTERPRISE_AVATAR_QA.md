# QualityHub — My Profile Enterprise & Private Avatar (Draft PR)

**Base:** main (includes merged PR #7). PR #8 and PR #9 are separate draft branches. This PR does not change Scan/Capture, searchable filters, or the Super Admin account/role logic.

## Scope
- Blue-white enterprise profile hero and responsive cards.
- Private profile photo: choose/preview/upload/replace/remove; existing initial fallback in profile, sidebar, topbar, and dropdown.
- Read-only employee ID, department, role and active status sourced from existing user data.
- Existing profile update and password change forms preserved; native CSP-safe show/hide password controls and a client-side password-strength hint added.
- New nullable \`users.profile_photo_path\` migration. Photos stored on **local private disk** under \`profile-photos/{authenticated_user_id}/...\`, authenticated and owner-only image route, no public symlink.
- Existing Docker \`app_storage\` volume persists \`storage/app\`. IT must validate storage capacity, backups, and deploy migrations before using in production.

## Release boundary
This branch is **DRAFT**. No merge to \`main\`, database production migration, upload to production, or redeploy until tests and IT approval.
- Branch is based on \`main\`, NOT on the user's local integration/security-fixes branch; therefore local \`composer.lock\` security changes are NOT included.
- PR #10 (1000 pages) remains cancelled/closed.
- Does not modify role gates, session revocation, token flows, or user-management logic.
- Photo storage cleanup on permanent admin user deletion should be validated before production (avoid orphaned profile photo files).
- No media conversion or image crop included; images are validated by MIME, extension generated from MIME, byte size and pixel dimensions.

## Local QA (Windows PowerShell)
Ensure \`.env\` and document disk are **isolated local test data**, not operational BMC DB/storage, before \`migrate\` or upload tests.
\`\`\`powershell
cd D:\BMC_Project\QualityHub-IT
git status -sb
# If composer.lock is modified from earlier security tests, stash ONLY that file first:
# git stash push -m "wip security composer lock" -- composer.lock
git fetch origin
git switch --track origin/feature/profile-enterprise-avatar
php artisan route:list --name=profile
# On verified disposable local DB only:
php artisan migrate:status
php artisan migrate
php artisan test --filter=ProfilePhotoTest
php artisan test --filter=ProfileTest
php artisan test --filter=AdminAccountManagementTest
npm ci
npm run build
php artisan view:cache
php artisan view:clear
git diff --check origin/main...HEAD
git status -sb
\`\`\`

If branch already exists locally, use \`git switch feature/profile-enterprise-avatar\` then \`git pull --ff-only origin feature/profile-enterprise-avatar\`.
Avoid changing PHP vendor or composer.lock until local version compatibility is confirmed.

## Browser acceptance
1. Use **only test accounts** on desktop and a phone on a trusted, private LAN.
2. Profile hero, account name/email/role/status, read-only employee ID and department all match database values.
3. Profile photo input previews JPG/PNG/WEBP without auto-submitting, shows selected filename, rejects invalid or >2MB uploads.
4. Save photo, refresh and navigate to Dashboard: avatar appears in the sidebar, topbar, profile dropdown and My Profile; reload still displays the persisted photo.
5. Replace photo; old private file removed. Delete photo; fallback initial appears everywhere.
6. Attempt to access \`/profile/photo\` logged-out or from another user; photo must not be accessible. Response must be no-store, inline, image only.
7. Update name/email through original form, validate uniqueness/errors; role, employee ID and department must stay read-only.
8. Password show/hide buttons work with keyboard and mouse; strength hint changes, while server password constraints and mandatory reset PR #7 remain intact.
9. Test at 1366×768 desktop and ~390×844 mobile, no overlapping sidebar or horizontal scrolling. Check CSP/console errors.
10. On migration rollback, ensure data/backup strategy is approved; **never experiment with real BMC production DB**.

## Outstanding
- Need actual local Laravel/PHPUnit, Vite asset build, image upload, mobile browser and integration QA. Connected GitHub editing cannot execute the user's Windows localhost.
- Need staging validation of secure deployment, persistent Docker volume, and the 2026-11-12 migration before production.


---

## UI revision · compact BMC blue-white (12 Nov 2026)

Updated in PR #11 after reviewing four user-provided profile UI references.

- Removed the full-height blue hero, duplicated identity display, long descriptions, extra account-summary card, and oversized success banner.
- Desktop: slim title/header, one compact profile card on the left, two clean forms on the right; responsive stacking for mobile.
- Photo upload/preview/replace/delete, private authenticated storage, profile update, password update, CSP-safe show/hide and inline password strength remain available.
- Role/status and employee ID/department are informational only; Super Admin permissions and temporary-password gate are unchanged.
- No new migrations, backend routes, controller changes, CDN, or new JavaScript libraries added by this UI revision.
- Updated legacy ProfileTest to assert actual succinct section labels rather than an obsolete explanatory paragraph.

### Targeted QA
1. Desktop width 1366×768 and 1600×900: no huge hero or repeated name/email, minimal scrolling, readable inputs and focused buttons.
2. Mobile ~390×844: one-column cards, accessible photo controls and inline password toggle, no overflow.
3. Upload, replace, delete and preview avatar using local dummy image. Verify sidebar/topbar reflect saved image.
4. Editing email/name, server validation and resetting temporary password retain original PR #7 behavior.
5. Run ProfilePhotoTest, ProfileTest, AdminAccountManagementTest and regression tests for PR #8 and #9 on separate local integration branch.
6. Only local/test DB/storage. Never migrate or deploy without IT approval.


---

## UI balancing pass — 12 November 2026
- Screenshot QA revealed a large unused area below the left identity card while the security form occupied only the right column.
- Replaced column-split standalone cards with **one unified account/photo panel** (photo left, profile form right) and a **full-width password panel below**. Desktop form has three horizontal password fields; mobile remains stacked.
- Moved read-only employee ID/department into the account form area; removed repeated metadata/unused whitespace from photo panel.
- Preserved photo routes, IDs, preview JS hooks, validation, CSRF, profile form names, PR #7 password gate, and password toggle/strength hooks.
- Pure Blade/Tailwind UI revision, no backend/database/middleware change in this commit.
- Test on desktop 1366×768 and 1600×900, mobile 390×844; screenshots of before/after and verify no horizontal overflow, no obvious empty gap.


---

## User Management account photos (PR #11)

- User Management's account list shows each account's stored avatar; users without a photo retain their initial.
- Photo is fetched through \`GET /users/{user}/photo\` and returns private/no-store headers.
- The photo endpoint enforces the **Super Admin** role itself, matching User Management; guest and ordinary employee access must be denied.
- Existing owner-only \`/profile/photo\` and avatar upload/delete routes remain unchanged.
- A URL with an invalid user ID or an avatar path that does not belong to that account must return 404; there is no public storage link.
- Run \`php artisan test --filter=UserManagementAvatarTest\` and \`php artisan test --filter=ProfilePhotoTest\` and \`php artisan test --filter=AdminAccountManagementTest\` before merging.
- Manually test one account with photo and one account without, using desktop and phone. No database migration is added by this account-list change.


---

## Activity Log account photos (PR #11)
- The activity table shows the linked user's actual profile photo. Entries with no photo, system entries, and entries with a removed user display initials/fallback.
- Endpoint: \`GET /activity-logs/users/{user}/photo\`, guarded by the same \`isAdmin()\` authorization used by ActivityLogController (Super Admin and Admin Dokumen). This is separate from the User Management photo route, which remains Super Admin only.
- Existing private local storage, path ownership validation, and private/no-store image responses reused. No public image URL, new migration, or audit-record mutation.
- \`ActivityLog::with('user')\` already eager loads the user, so no additional database relationship query per row.
- QA: \`php artisan test --filter=ActivityLogAvatarTest\`, \`--filter=UserManagementAvatarTest\`, \`--filter=ProfilePhotoTest\`, \`--filter=AdminAccountManagementTest\`. Verify same user avatar in My Profile, Users, and Activity Log; fallback for blank/system/deleted user; unauthorized account denied.


---

## Full-page Activity Log UI redesign · 2026-11-12
- Replaced 6-column audit table with balanced responsive feed: avatar/actor, action/target/short summary, time/IP.
- Removed tall metric cards and verbose header paragraphs; introduced compact four-KPI strip and search bar.
- Advanced GET filters retain exact names: \`search\`, \`user_id\`, \`action\`, \`document_id\`, \`ip_address\`, \`date_from\`, \`date_to\`. Native \`<details>\` reveals advanced filters and opens automatically when filters are active.
- Kept original Indonesian action-label map and \`data-qh-searchable-select\` hooks for later PR #9 integration.
- Each audit entry uses native disclosure \`<details><summary>\`, without CSP-incompatible Alpine inline state or x-collapse. Full original description, user agent, metadata and database-generated target data remain accessible.
- All existing doc links, private photo avatars, audit role permissions, controller queries, pagination, status, and audit records unchanged. No migration.
- New \`ActivityLogRedesignTest\` covers metadata, filters, system activity and authorization.

### QA before merge
\`\`\`powershell
php artisan test --filter=ActivityLogRedesignTest
php artisan test --filter=ActivityLogAvatarTest
php artisan test --filter=UserManagementAvatarTest
php artisan test --filter=ProfilePhotoTest
php artisan test --filter=AdminAccountManagementTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`
- Review 1366×768 desktop, 390×844 mobile, and entries with long user agents or metadata.
- Verify multi-page links and filters, nested metadata, empty results and image fallback.
- Do not merge PR #11 or redeploy without full QA and Mas IT approval.


---

## Activity Log detail density and metadata handling — 12 Nov 2026
Screenshots revealed a multi-screen detail panel: empty values caused oversized rows, and the left info area was half blank.

- Detail now uses a short "Rincian Audit" header; compact two-column audit fields and bounded metadata list are aligned in balanced cards.
- Metadata with actual values uses compact side-by-side label/value rows, wrapped text, and an independently scrollable list (keyboard focusable). Numeric zero and boolean false remain visible as meaningful values.
- Missing/null/blank metadata keys remain available under the native, collapsed "Atribut kosong (N)" disclosure, so no audit content is removed or mutated.
- Long user-agent strings remain available under "Informasi Teknis", collapsed by default. Full original description is still available with bounded scrolling.
- No Alpine inline expressions or additional JavaScript; compatible with CSP. Audit queries, permissions, photo route, filters, pagination and database models unchanged.
- Run \`php artisan test --filter=ActivityLogRedesignTest\`, \`php artisan test --filter=ActivityLogAvatarTest\`, \`php artisan test --filter=SearchableFilterSelectTest\`, \`npm run build\`, and \`php artisan view:cache\` on local integration branch before any merge/deploy.
- Browser QA: log with many empty metadata fields, zero/false/array values, long user agent, mobile ~390px, and reopening details across pagination. Ensure no horizontal overflow and internal metadata scroller is keyboard accessible.
