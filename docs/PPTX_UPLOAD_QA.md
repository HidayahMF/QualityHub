# PPTX Upload / Revision — QA & Security Notes

**Scope:** New document upload, existing document revisions, document filters, and format display. Separate Draft PR from Department delete / LED changes. No schema migration or new dependency.

## Formats supported
- Existing: PDF, XLS, XLSX, DOC, DOCX, CSV, TXT
- New: **PPTX** (standard macro-free PowerPoint OOXML presentation)
- Images from Scan/Capture are a separate workflow and are not expanded by this PR.
- Unsupported: PPT (legacy), PPTM, PPSX, PPTX with VBA / ActiveX, ZIP, executable formats, ODP. Do not claim they are accepted.

## Behavior
1. Laravel first-layer upload rules allow .pptx on Add Document and Upload Revision (20 MB limit).
2. Security service verifies client extension, server MIME, ZIP structure, correct OOXML PowerPoint presentation content type, macro/ActiveX indicators, and bounded expanded archive size.
3. Files retain UUID-based names in private BMC storage; authenticated users download originals through existing routes.
4. Existing Document index extension whitelist recognizes PPTX when active PPTX documents exist. Existing document format label is derived from the extension.
5. **No slide preview**: clicking Open behaves as download, not inline rendering. PPTX file is not executed by the server and no external Office/cloud viewer is used.

## Testing (LOCAL / test database only)
\`\`\`powershell
php artisan test --filter=PptxUploadTest
php artisan test --filter=CategoryAssignmentTest
php artisan test --filter=DocxPreviewTest
php artisan test --filter=SearchableFilterSelectTest
npm run build
php artisan view:cache
php artisan view:clear
\`\`\`

Manual checks:
- Use an actual .pptx exported from Microsoft PowerPoint or LibreOffice Impress, <=20 MB.
- Log in as Super Admin locally, Add Document, choose category, select PPTX, Save; verify metadata, download and re-open in PowerPoint.
- Upload a new PPTX revision; verify both original and current revisions download.
- Filter All Documents by PPTX after uploading.
- Reject renamed .zip/.txt/.pptm and macro-bearing files.
- Validate with user and server timezone preferences (not linked to upload change).
- Ensure only test/local database and storage is used; do not upload customer documents to public demo sites.
- Avoid changing composer.lock or deleting local untracked code folder.

**Release gate:** PR remains Draft until local PHPUnit suite and manual file round-trip pass. Do not merge into main or deploy to BMC without IT review.
