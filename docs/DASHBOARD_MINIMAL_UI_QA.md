# Dashboard Minimal UI Audit — BMC QualityHub
Date: 2026-11-12
Branch: feature/profile-enterprise-avatar (Draft PR #11)

Redesign is limited to the Dashboard Blade view and a presentation-focused feature test.

| Section | Minimal redesign | Preserved data/behavior |
| --- | --- | --- |
| Large blue hero | Compact white header with user greeting, small search, and period selector | User, search GET route, 7/30/90/365-day period selection |
| Main 6 KPI cards | Reduced card height, icons and padding, responsive 3-column layout | Active/archived/revisions/department/users/activity; pending and category counts |
| Additional 3 KPI cards | Shorter white cards | Spreadsheet files, file size, new documents and growth |
| Trend | Full-width concise chart card | Existing trend SVG code, tooltip, and period-linked data |
| Category, file, department, activity | Balanced two-column visualization grid | All 4 original chart IDs and data |
| Most accessed, recent docs | Two-column compact list cards | Existing source collection, links, dates, access counters |
| Recent activity | Compact activity list | Description conversion and timestamps |

No controller, model, query, routes, DB migration, JavaScript chart engine, nonce/CSP rules, or data JSON is changed.

QA in local integration branch:
\`\`\`powershell
php artisan test --filter=DashboardMinimalUiTest
php artisan test --filter=AllDocumentsMinimalUiTest
php artisan test --filter=SearchableFilterSelectTest
php artisan test --filter=ActivityLogRedesignTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`
Inspect actual visual 1366×768 and 390×844: chart animations, hover tooltip, legends, search, period select, six main+three supporting KPIs, category/file/department/activity chart data, top/recent docs links, and recent activities. No horizontal overflow, no loss of data.

Leave main/server unchanged. PR remains Draft until local/regression QA and Mas IT approval.


---

## Polishing round — 12 November 2026

### Visual review of user screenshots
- User requested navy/royal-blue BMC background for top welcome card, rather than previous white.
- Nine KPI cards were visually split into groups of different heights; now unified as nine equal-height responsive cards in one 3-column desktop grid.
- Category and file-type charts had mismatched orientations; donut + legend are now aligned side-by-side.
- Department analytics were dramatically shorter than activity composition and left a large blank space; content now uses two independently stacked columns: category → department → most accessed, and file type → activity composition → recent documents.
- Legacy \`xl:col-span-2\` on Recent Documents removed so it doesn't unexpectedly force full-width layout.
- Recent activities changed from two columns to one column with two-line visual clamp, while the source description and timestamp remain unchanged.

### Backend and compatibility
No database, controller, route, model, authentication, analytics query, chart renderer, chart JSON, or CSP nonce changes. The BMC background uses utility classes instead of inline script/style. Date period selector (7/30/90/365) and document search remain GET forms.

### Local QA
\`\`\`powershell
php artisan test --filter=DashboardMinimalUiTest
php artisan test --filter=DashboardPolishTest
php artisan test --filter=AllDocumentsMinimalUiTest
php artisan test --filter=ActivityLogRedesignTest
php artisan test --filter=SearchableFilterSelectTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`
Review Dashboard at 1366×768, 1600×900 and phone 390×844; verify bright text over blue banner, period dropdown, search, 9 KPIs, both donuts and legends, chart tooltips, access/recent document links and one-column activity feed. No production deployment or merge to main before review.
