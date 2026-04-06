# PDF Download / Export (How It Works)

This project has **two different “PDF” features**:

1. **Per-report “Download PDF”** in the Report Details modal

- This is **server-generated** (direct `application/pdf` download), and it renders the **internal/external memo header + logo**.
- There is also a print-friendly HTML fallback if needed.

2. **Analytics “Download PDF”** (from the Analytics dashboard)
    - This **IS server-generated**.
    - It returns a minimal **`application/pdf`** download built directly in PHP.

Both are intentional: the per-report print layout is highly formatted (legal paper memo template), while the analytics PDF is a lightweight export summary.

---

## 1) Per-report “Download PDF” (Report Modal)

### What the user experiences

1. User clicks a report row (many pages use `onclick="ReportModal.open('SR-YYYY-NNNN')"`).
2. A modal opens and loads the report details.
3. The **Download PDF** button becomes enabled.
4. Clicking **Download PDF** downloads a PDF immediately (server-generated).
5. (Optional fallback) Shift-click opens the print-friendly memo page in the same tab.

### Why we avoid the browser print dialog

Some corporate browsers block popups and print dialogs. The default flow therefore uses a **server-generated PDF** so it can download immediately without relying on `window.print()`.

### Corporate browser note (restricted environments)

Some corporate browsers block popups, print dialogs, or background API requests. The modal button is wired to a **direct server-generated PDF download** endpoint:

- `public/api/report_pdf.php?id=SR-YYYY-NNNN`

There is also a print-page fallback that does not require the numeric DB id:

- `public/print_report_by_no.php?id=SR-YYYY-NNNN` → redirects to `print_report.php?report_id=<db id>` after permission checks

In other words:

- `api/report_pdf.php` avoids popups and avoids the print dialog.
- `print_report_by_no.php` avoids popups and avoids needing the DB id, but it still uses the browser print dialog.

### Files involved (per-report)

**UI / Modal Shell**

- `views/layouts/footer.php`
    - Contains the modal markup (including the button with id `modal-download-pdf`).

**JS Controller**

- `public/assets/js/app.js`
    - `ReportModal.init()` binds the “Download PDF” button.
    - `ReportModal.open(reportNo)` fetches report details from the server.
    - It enables the download button using the report number.
    - Default click downloads a PDF from `api/report_pdf.php`.
    - Shift-click uses `print_report_by_no.php` fallback.

**API used to load the report**

- `public/api/report.php`
    - Returns JSON for a report when given `?id=SR-YYYY-NNNN`.
    - Also returns `reportId` (numeric DB primary key), which is required by the print page.

**Server-generated PDF endpoint**

- `public/api/report_pdf.php`
    - Returns `application/pdf` with the memo header and logo (internal vs external).

**Print-friendly HTML page**

- `public/print_report.php`
    - Requires login.
    - Expects `?report_id=<numeric id>`.
    - Renders a memo-style HTML page with a toolbar.

**Print styling**

- `public/assets/css/print-report.css`
    - Uses `@page { size: legal portrait; margin: ... }`.
    - Uses `@media print` to hide the screen toolbar and apply print-safe layout.

---

## 1A) Detailed JS flow (Report Modal)

### Modal button wiring

In `public/assets/js/app.js`:

- The button is located by id: `modal-download-pdf`.
- Default click triggers a download from:
    - `api/report_pdf.php?id=<report_no>`
- Shift-click navigates to:
    - `print_report_by_no.php?id=<report_no>` (print-to-PDF fallback)

### Why the button is disabled initially

The modal opens immediately (with a “Loading…” state), then it fetches the report JSON:

- `GET public/api/report.php?id=<report_no>`

When the JSON is received:

- The code reads `report.reportId`.
- That numeric value is stored as `currentReportDbId`.
- Then `syncDownloadButton()` enables the button.

This prevents download clicks before the modal knows which report number it is showing.

---

## 1B) Report API behavior and security (why some users can’t print certain reports)

`public/api/report.php` returns a report only if the user is allowed to view it:

- **GA Staff / GA President**: can view reports.
- **Security**: only reports for their assigned building.
- **Department**: only reports assigned to their department.

If the user is not permitted, the API returns `404` (“Report not found”) or `403` depending on the scenario.

That matters because:

- If the modal cannot fetch the report JSON, it won’t get `reportId`.
- The download button stays disabled.

---

## 1C) Print page behavior (`print_report.php`)

### What `print_report.php` does

- Validates `report_id` is numeric.
- Loads the report and related metadata (department, submitter, GA staff review, GA president approvals, attachments).
- Enforces access rules via `can_view_report()`:
    - GA roles can view all.
    - Security is restricted to their building.
    - Department is restricted to their assigned department.
- Renders HTML designed specifically for printing.

### Internal vs External template selection

`print_report.php` chooses a template based on the submitter’s `security_type`:

- `internal` → uses internal memo header styling and internal logo.
- `external` → uses external memo header styling and external logo.

This is why two logo files may be used:

- `assets/images/internal-logo.png`
- `assets/images/external-logo.png`

(If a logo file is missing, printing still works; the logo just won’t render.)

### Evidence images / attachments

The print page can show an image if one is present:

- Primary: `reports.evidence_image_path`
- Fallback: first image found in `report_attachments`.

Paths are sanitized so the print page won’t render unsafe `../` paths.

---

## 1D) How the browser turns it into a PDF

When the user clicks **Print / Save as PDF** on the print page:

- `window.print()` triggers the browser’s print dialog.
- The browser applies CSS:
    - `@media print` rules
    - `@page` paper sizing rules
- Chromium-based browsers provide a destination option **Save as PDF**.

Important notes:

- This is rendered by the browser, not PHP.
- The generated PDF can vary slightly by browser/version (font metrics, pagination, etc.).
- The layout is strongly guided by `public/assets/css/print-report.css` to keep it consistent.

---

## 2) Analytics “Download PDF” (Server-generated)

### What the user experiences

From the analytics dashboard there’s a “Download PDF” link. Clicking it downloads a PDF file directly.

### Files involved (analytics export)

- `includes/analytics_dashboard.php`
    - Contains the UI links `#download-csv` and `#download-pdf`.

- `public/assets/js/app.js`
    - `AnalyticsDashboardPage.setDownloadLinks()` builds the link URLs.
    - It points to `public/api/analytics.php?export=pdf&...filters...`.

- `public/api/analytics.php`
    - Handles `export=pdf` by producing a small PDF using `output_simple_pdf()`.
    - Responds with:
        - `Content-Type: application/pdf`
        - `Content-Disposition: attachment; filename="analytics_export_YYYYmmdd_HHMMSS.pdf"`

### How the analytics PDF is generated

`public/api/analytics.php` builds a minimal single-page PDF using PDF syntax:

- A title line and a list of key metrics (total reports, pending GA, overdue count, etc.).
- Helvetica font (Type1).
- Simple text layout.

This file is intentionally simple and does not attempt to reproduce the full memo layout.

---

## Quick file map (everything connected)

Per-report print-to-PDF:

- `views/layouts/footer.php` (modal button markup)
- `public/assets/js/app.js` (ReportModal logic)
- `public/api/report.php` (fetch report JSON + permissions)
- `public/print_report.php` (print-friendly HTML memo + permissions)
- `public/assets/css/print-report.css` (legal-size print layout)
- `public/assets/css/style.css` (base tokens + shared styles)

Analytics export PDF:

- `includes/analytics_dashboard.php` (download UI)
- `public/assets/js/app.js` (sets download href)
- `public/api/analytics.php` (server-side PDF/CSV export)

Not used for the modal PDF flow:

- `public/download.php` (a download UI page; not wired into the ReportModal “Download PDF” button)

---

## Troubleshooting

### “Download PDF” button is disabled

Most common causes:

- The report is still loading (wait a second).
- The API request to `public/api/report.php` failed (not authenticated).
- Permissions blocked the report (Security cross-building, Department wrong department).

### Print page says “Access denied.”

`public/print_report.php` enforces access rules independently.

Even if a user somehow had the numeric `report_id`, they still can’t print a report they aren’t allowed to view.

### PDF layout looks off

- Try Chrome/Edge (Chromium) first.
- Ensure “Paper size: Legal” is selected if the browser doesn’t auto-apply `@page`.
- Check `public/assets/css/print-report.css` for the `@page` settings.

---

## Developer notes

- The per-report “PDF” feature is intentionally **browser-driven** for fidelity and simplicity.
- The analytics PDF is intentionally **server-driven** for fast exporting without print UI.
- Avoid adding external PDF libraries unless absolutely necessary (this project currently works without them).
