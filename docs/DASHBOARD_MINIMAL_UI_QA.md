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
