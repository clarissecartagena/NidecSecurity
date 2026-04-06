# NidecSecurity — Complete Development History & Configuration Guide

> **Last updated:** March 10, 2026  
> This file documents every major change made to the system, and explains exactly **where to put your company API URLs, database credentials, and login encryption keys**.

---

## Table of Contents

1. [Where to Put Your Company APIs & Credentials](#1-where-to-put-your-company-apis--credentials)
2. [Where to Put Your Database Credentials](#2-where-to-put-your-database-credentials)
3. [Complete Change History](#3-complete-change-history)
4. [File Map — What Each Important File Does](#4-file-map--what-each-important-file-does)

---

## 1. Where to Put Your Company APIs & Credentials

> **This is the most important section.** Open this ONE file to connect the system to your company server.

### File: `config/api.php`

This is the single file that controls ALL external API connections — both the **Employee API** (for searching employees when creating users) and the **Login API** (for authenticating users against the company DAIRS system).

Open the file and change the following lines:

```php
// ── Employee API ───────────────────────────────────────────────────────────
// Change this to your real internal company server address:
define('COMPANY_API_BASE_URL', 'http://COMPANY_SERVER/api');

// ── Login API ──────────────────────────────────────────────────────────────
// Change this to your real DAIRS login endpoint:
define('COMPANY_LOGIN_URL', 'http://COMPANY_SERVER/API_CALLS/Admin_API/DAIRS.php');

// ── Login Encryption Keys (get these from your API coordinator) ────────────
// AES-128-CTR encryption key used to encrypt the login payload:
define('API_LOGIN_ENCRYPTION_KEY', 'DAIRS-Disciplinary.Action.Issuance.and.Recording.System');

// 16-byte IV (Initialization Vector) for AES-128-CTR:
define('API_LOGIN_ENCRYPTION_IV', '2025_04_04_DAIRS');
```

**Summary of what to change in `config/api.php`:**

| Constant                   | What to Put Here                                              | Who Provides It              |
| -------------------------- | ------------------------------------------------------------- | ---------------------------- |
| `COMPANY_API_BASE_URL`     | The URL of your company's internal employee lookup API server | Your IT/Network team         |
| `COMPANY_LOGIN_URL`        | The full URL of the DAIRS login PHP endpoint                  | Your company API coordinator |
| `API_LOGIN_ENCRYPTION_KEY` | The AES encryption key for login payloads                     | Your company API coordinator |
| `API_LOGIN_ENCRYPTION_IV`  | The 16-character IV for encryption                            | Your company API coordinator |

> **Note:** In development/testing mode (`API_ENV = 'development'`), if the company server is not reachable the system automatically falls back to the local mock API at `http://localhost/nidec_api_mock`. In production mode the mock is disabled entirely and any API failure shows an error.

### How the Login Works (overview)

When a user logs in:

1. The system encrypts the username + password using AES-128-CTR with your key and IV.
2. It sends the encrypted payload to `COMPANY_LOGIN_URL`.
3. The company API decrypts it, validates credentials, and returns employee data.
4. If the employee exists in the local `users` table with the right role, they are logged in.

The login client code is in: `app/api_clients/LoginApiClient.php`

---

## 2. Where to Put Your Database Credentials

### File: `config/database.php`

Open this file and change these lines at the top:

```php
define('DB_HOST', '127.0.0.1'); // Your MySQL server host (usually 127.0.0.1 for XAMPP)
define('DB_NAME', 'nidec_security'); // Your database name
define('DB_USER', 'root'); // Your MySQL username
define('DB_PASS', ''); // Your MySQL password (blank for default XAMPP)
define('DB_PORT', 3306); // MySQL port (default 3306)
```

**For a fresh install**, after setting your credentials:

1. Create the database by importing `database/schema.sql` in phpMyAdmin.
2. Optionally seed demo data by also importing `database/seed.sql`.

---

## 3. Complete Change History

---

### Phase 1 — Employee API Integration

**What was done:**

- Created `config/api.php` — the central place for all API URLs and keys.
- Created `app/api_clients/EmployeeApiClient.php` — low-level HTTP client that calls the company employee API using cURL.
- Created `app/services/EmployeeService.php` — business logic layer on top of the client (input validation, consistent response format).
- Created `public/api/employee_search.php` — the internal AJAX endpoint used by the Add User modal to search for employees by name or ID.

**How it works:**

- When a GA President or GA Staff member opens the "Add User" modal and types an employee name, the browser calls `/api/employee_search.php?q=...`.
- That endpoint calls `EmployeeService`, which calls `EmployeeApiClient`, which calls the company API.
- If the company API is unreachable (e.g. working from home) and `API_ENV = 'development'`, it falls back to a local mock API.

**Files changed/created:**

- `config/api.php` ← **NEW — put your API URLs here**
- `app/api_clients/EmployeeApiClient.php` ← NEW
- `app/services/EmployeeService.php` ← NEW
- `public/api/employee_search.php` ← NEW

---

### Phase 2 — Login API with AES-128-CTR Encryption

**What was done:**

- Created `app/api_clients/LoginApiClient.php` — handles the encrypted login call to the DAIRS API.
- The login payload (username + password) is encrypted using **AES-128-CTR** before transmission.
- Updated `app/services/AuthService.php` to use the new login client instead of direct database password checks.
- The system verifies credentials against the company DAIRS API first, then confirms the returned employee exists in the local `users` table with an assigned role.

**Files changed/created:**

- `app/api_clients/LoginApiClient.php` ← NEW
- `app/services/AuthService.php` ← MODIFIED

---

### Phase 3 — Root URL Routing

**What was done:**

- Added a root `.htaccess` file so the app can be accessed at `http://localhost/NidecSecurity/login.php` (without `/public` in the URL).
- Apache rewrites transparently route all requests to `public/index.php`.
- Asset URLs (CSS, JS, images) are also rewritten so they load correctly.
- Updated `includes/config.php` `app_url()` helper to detect whether the browser is using the root path or the `/public/` path and build correct URLs in both cases.

**Files changed/created:**

- `.htaccess` ← NEW (root of project)
- `public/.htaccess` ← MODIFIED
- `includes/config.php` ← MODIFIED (`app_url()` function updated)

---

### Phase 4 — Database Creation & Seeding

**What was done:**

- Created `database/schema.sql` — the full SQL schema for all tables (`users`, `reports`, `report_history`, `notifications`, `departments`, etc.).
- Created `database/seed.sql` — sample data for testing (demo users, departments, sample reports).
- Created `tools/verify_seed2.php` — a temporary verification script to confirm the seed data loaded correctly.

**Files changed/created:**

- `database/schema.sql` ← NEW
- `database/seed.sql` ← NEW

---

### Phase 5 — bcrypt Password Fix

**What was done:**

- Fixed an issue where user passwords stored in the database were not being verified correctly.
- All passwords in the system are now hashed using PHP's `password_hash()` with `PASSWORD_BCRYPT`.
- Updated `app/services/AuthService.php` to use `password_verify()` for local user checks.
- Updated `database/seed.sql` so all demo user passwords are stored as proper bcrypt hashes.

**Files changed/created:**

- `app/services/AuthService.php` ← MODIFIED
- `database/seed.sql` ← MODIFIED

---

### Phase 6 — View PDF & Copy Link Buttons on Report Modal

**What was done:**

- Added a **"View PDF"** button to the report details modal footer — opens the generated PDF in a new browser tab for preview.
- Added a **"Copy Link"** button — copies a shareable direct link to the report (full absolute URL so it works on other machines).
- Created `app/controllers/ReportViewController.php` and registered the route `/view-report.php` so that direct links open the report modal automatically.
- Fixed the copy link feature to use `window.location.origin` so the URL is always complete (not just a relative path).

**Files changed/created:**

- `views/layouts/footer.php` ← MODIFIED (added View PDF and Copy Link buttons)
- `public/assets/js/app.js` ← MODIFIED (added click handlers for the new buttons)
- `app/controllers/ReportViewController.php` ← NEW
- `routes/web.php` ← MODIFIED (added `/view-report.php` route)

---

### Phase 7 — Full Modal UI/UX Redesign

**What was done:**
This was the largest visual improvement. Every modal in the system was audited and rewritten to follow a single consistent modern design.

**Problems that were fixed:**

- There were TWO separate `.modal-overlay` CSS definitions that conflicted with each other.
- The backdrop was too faint (`hsl(var(--foreground)/0.3)` = barely visible).
- No `backdrop-filter` blur effect.
- The Final Checking modal had its own 45 lines of one-off inline CSS in `<style>` tags inside the PHP file.
- The GA Dashboard "Metric List" modal had its own separate CSS classes (`metric-modal-header`, `metric-modal-close`, etc.) that duplicated the shared classes.
- No loading spinners on form submit buttons.
- No mobile responsiveness (modals didn't adapt on small screens).

**What the unified system now provides:**

- **Single `.modal-overlay` class** used by ALL modals.
- `backdrop-filter: blur(3px)` — premium frosted glass effect behind modals.
- Opacity + visibility transitions (modals fade in/out).
- Gradient headers (`linear-gradient(135deg, ...)`) on all modal headers.
- `.report-modal--sm` compact variant for small confirmation modals.
- `.modal-header-subtitle` — a subtitle line under the modal title (e.g. showing report number).
- `btn[data-loading="true"]` — adds a spinning indicator to any submit button when a form is submitting.
- Mobile sheet pattern (`@media (max-width: 640px)`) — on small screens modals slide up from the bottom instead of dropping from the top.

**Files changed in Phase 7:**

| File                               | What Changed                                                                                                                 |
| ---------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `public/assets/css/style.css`      | Removed duplicate CSS, unified all modals into one system, added gradient headers, blur backdrop, spinner, mobile responsive |
| `views/layouts/footer.php`         | Added `<p class="modal-header-subtitle" id="modal-report-no">` to show report number under the title                         |
| `views/reports/final_checking.php` | Removed 45-line inline `<style>` block; replaced `fc-modal-*` classes with shared `report-modal-*` classes                   |
| `views/dashboard/ga_dashboard.php` | Replaced `metric-modal-header`, `metric-modal-close`, `metric-modal-footer-action` with shared classes                       |
| `views/users/users.php`            | Added form `submit` event listeners that set `data-loading="true"` on submit buttons                                         |
| `public/assets/js/app.js`          | `ReportModal.open()` now shows the report number below the title; `ReportModal.close()` clears it                            |

---

## 4. File Map — What Each Important File Does

```
config/
│
├── api.php           ← ★ PUT YOUR COMPANY API URLS AND ENCRYPTION KEYS HERE ★
├── database.php      ← ★ PUT YOUR DATABASE HOST, NAME, USERNAME, PASSWORD HERE ★
└── constants.php     ← Role names, navigation menu items, icon map

app/
├── api_clients/
│   ├── EmployeeApiClient.php   ← Makes HTTP calls to company employee API
│   └── LoginApiClient.php      ← Makes encrypted HTTP calls to DAIRS login API
│
├── services/
│   ├── AuthService.php         ← Handles login logic (calls company API + local DB check)
│   ├── EmployeeService.php     ← Validates input + calls EmployeeApiClient
│   └── UsersService.php        ← CRUD for users table
│
└── controllers/
    ├── AuthController.php      ← Login / logout page controllers
    └── UsersController.php     ← User management page controller

includes/
├── config.php   ← Session setup, app_url() helper, role helpers (require_once here loads everything)
├── db.php       ← Loads database.php, provides db() function
└── auth.php     ← requireAuth(), requireRole() — page access guards

public/
├── index.php           ← Front controller — all requests come through here
└── api/
    ├── employee_search.php    ← AJAX endpoint for employee lookups in Add User modal
    ├── report.php             ← AJAX endpoint for loading report details in modal
    ├── report_pdf.php         ← Generates PDF preview
    ├── report_pdf_internal.php ← Generates PDF for internal security reports
    └── report_pdf_external.php ← Generates PDF for external security reports

public/assets/
├── css/style.css   ← ALL styles for the entire application (single stylesheet)
└── js/app.js       ← ALL client-side JavaScript (ReportModal, UsersPage, etc.)

views/
├── layouts/
│   ├── footer.php   ← Report Details modal lives here (shared across all pages)
│   └── header.php   ← HTML <head>, loads CSS + Bootstrap
├── reports/
│   └── final_checking.php   ← Final Checking page with its own Remarks modal
└── dashboard/
    └── ga_dashboard.php     ← GA President dashboard with Metric List modal

database/
├── schema.sql   ← Run this first to create all tables
└── seed.sql     ← Run this second to load test/demo data

routes/
└── web.php   ← All URL routes mapped to controllers
```

---

## Quick Start Checklist

When deploying to a new machine or the company server:

- [ ] **1.** Copy the project to your XAMPP `htdocs` folder (or server document root).
- [ ] **2.** Create the MySQL database and import `database/schema.sql`.
- [ ] **3.** Open **`config/database.php`** and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- [ ] **4.** Open **`config/api.php`** and set `COMPANY_API_BASE_URL` and `COMPANY_LOGIN_URL` to your company server addresses.
- [ ] **5.** In **`config/api.php`**, set `API_LOGIN_ENCRYPTION_KEY` and `API_LOGIN_ENCRYPTION_IV` to the values provided by your API coordinator.
- [ ] **6.** In **`config/api.php`**, change `API_ENV` to `'production'` when going live (disables mock API fallback).
- [ ] **7.** Make sure Apache `mod_rewrite` is enabled (required for `.htaccess` routing).
- [ ] **8.** Test login with a real company employee account.

---

_This file was auto-generated to track all development changes. Do not delete it — it is your full history._
