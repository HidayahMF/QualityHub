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
