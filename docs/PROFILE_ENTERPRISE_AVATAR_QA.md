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
