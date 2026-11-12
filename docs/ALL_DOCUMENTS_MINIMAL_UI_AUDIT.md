# Audit per section — All Documents | BMC QualityHub
2026-11-12 — Draft PR #11

| Section | Diagnosis | Implemented |
| --- | --- | --- |
| Page header | Overlong title/description/CTA space | Compact title and document count, preserve Favorit Saya and protected Tambah Dokumen |
| Status cards | Three very tall cards; storage and local-source narrative | Remove cards; no unverified infrastructure claims |
| Main search | Buried below cards and expanded AI | Move to first functional section; search bar full width, three searchable filters in one desktop row |
| Quick shortcuts | Helpful but unnecessarily verbose | Keep all quickType/quickSearch buttons, compact chips, delete explanatory sentence |
| Advanced filters | Always visible tall date/type form | Native collapsed Filter Lanjutan; opens when date or QC document type query is active |
| AI Smart Search | Expanded form ahead of main search | Move below main search, use native collapsible card; preserve x-data, AI submit, results, and fallback |
| Search status | Long list of indexed metadata fields | Remove nonessential decorative prose |
| Document library | Result cards/table business workflow already functional | Leave document rendering, routes, actions and pagination unchanged |

**Compatibility:** Four selects include \`data-qh-searchable-select\` for PR #9; preserve \`x-model\`, live search, quick actions and applied filters. No controller, routes, model, migration or DB changes.

**Local QA:** Merge Draft PR #11 into the user's existing integration branch without discarding uncommitted composer.lock or code. Run:
\`\`\`powershell
php artisan test --filter=AllDocumentsMinimalUiTest
php artisan test --filter=SearchableFilterSelectTest
php artisan test --filter=ActivityLogRedesignTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`
Browser QA: 1366×768/390×844; favorites/add auth; live query, 4 searchable select controls, quick chips, date filters and reset, AI search/fallback, document cards/table and pagination.

Draft only. No main merge or PT BMC redeployment until QA and Mas IT approval.
