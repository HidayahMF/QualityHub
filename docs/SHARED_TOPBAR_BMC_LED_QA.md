# Shared BMC Blue Navbar with Subtle LED Accent

Scope: Shared authenticated layout only, so the change applies consistently to Dashboard, Documents, Departments, Categories, User Management, Activity Log, and Profile without editing the individual pages.

**Design**: navy-to-royal-blue header (#152757 → #2355b5), white title/user name and soft-blue subtitles. The authenticated session pill is semi-transparent. The account dropdown stays white with its existing content and interactions. Mobile menu button has a light-blue/white accessible treatment.

**LED effect**: two 3px decorative vertical rails at the left and right edges, plus a 2px slow animated gradient at the lower edge. The light moves over 7.5–12 seconds without JavaScript. No flashing effect and no external dependency. CSS respects `prefers-reduced-motion: reduce` and `prefers-contrast: more`.

**No backend impact**: no changes to controller, routes, models, database, auth, user avatar handling, or account dropdown JavaScript. PR remains Draft until local QA.

QA locally:
```powershell
php artisan test --filter=SharedTopbarBrandingTest
php artisan test --filter=DepartmentManagementTest
php artisan test --filter=DepartmentPermanentDeleteTest
php artisan test --filter=DepartmentActionRedirectRegressionTest
php artisan test --filter=DepartmentConfirmationButtonUiTest
npm run build
php artisan view:cache
php artisan view:clear
```

Check browser at desktop width 1536px, tablet 768px, mobile 390px; inspect title readability, profile dropdown white text contrast, avatar, mobile sidebar button, left-right LED on dark blue, reduced-motion behavior, and scrolling/sticky topbar. Do not merge to main until QA and Mas IT approval.


## Global header premium polish / subtitle contrast (13 Nov 2026)

Bug reproduced visually on All Documents: dark-gray subtitle `Pengelolaan dokumen internal BMC` blended into the navy header. The old content styling retained `.qualityhub-topbar h2 + p { color: #5b6b7f }` from the white-header design.

**Fix**
- Removed the obsolete dark subtitle selector rather than using `!important` as a patch.
- Added explicit `.qualityhub-topbar .qh-topbar-subtitle` color `#edf4ff`, medium weight and 14 px sizing. White title and avatar account text remain unchanged.
- Profile role / chevron use light blue with full opacity. High-contrast mode forces white text.
- Compact header uses `min-w-0` and `truncate` for long page titles/subtitles, keeping the account controls visible.
- Existing 2-edge BMC LED effect, responsive mobile menu, photo/avatar links and profile dropdown remain intact.
- All page descriptions are centralized in `resources/views/layouts/app.blade.php`; fix applies to every authenticated page.

**QA**
```powershell
php artisan test --filter=SharedTopbarBrandingTest
npm run build
php artisan view:cache
php artisan view:clear
```
Check All Documents, Dashboard, Department Management, Activity Log, User Management and Profile in desktop browser after Ctrl+F5. Verify subtitle visibly light, even near the royal-blue right edge; account dropdown still displays dark text on white. Mobile sidebar and keyboard navigation must remain accessible.

No controller, route, database or production change. PR #12 remains Draft until local QA.
