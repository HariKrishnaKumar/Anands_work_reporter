# Software Requirements Specification (SRS)

**Project:** Daily Work Report
**Version:** 1.0
**Date:** September 14, 2026
**Audience:** Client/Stakeholders + Developers

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [Functional Requirements](#3-functional-requirements)
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [Data Requirements](#5-data-requirements)
6. [External Interfaces](#6-external-interfaces)
7. [File Inventory & Use Cases](#7-file-inventory--use-cases)
8. [Assumptions & Dependencies](#8-assumptions--dependencies)
9. [Revision History](#9-revision-history)

---

## 1. Introduction

### 1.1 Purpose

This document defines the software requirements for the **Daily Work Report** application. It serves both non-technical stakeholders (to understand goals and features) and developers (to understand architecture, data flows, and contracts).

### 1.2 Project Goal

> **Enable employees to log their daily work activities with optional file attachments, creating a visible record of contributions that can be synced to Google Sheets for team/management review.**

The core problem this solves:
- Employees do work daily but there's no centralized record
- Managers have no visibility into what each person accomplished
- Ad-hoc email/WhatsApp updates are inconsistent and lost in chat history

### 1.3 Scope

| In Scope | Out of Scope |
|----------|-------------|
| Employee login/logout | User registration (admin creates accounts) |
| Add daily work report (date + description) | Edit/delete existing reports |
| Attach files (images, PDF, DOC) | Real-time collaboration |
| View own reports | Admin dashboard / reporting |
| Google Sheets sync (optional) | Multi-tenant / multi-org |
| Dark/light theme | Mobile app (web only) |

---

## 2. Overall Description

### 2.1 Product Perspective

A **single-server PHP MVP** with no framework. Uses:
- **MariaDB/MySQL** as primary database
- **Google Sheets API** as optional secondary storage for management visibility
- **Vanilla PHP** with AltoRouter for routing
- **Server-rendered HTML** with inline JS for interactions
- **BLOB storage** for file attachments directly in the database

### 2.2 User Classes

| User Type | Description | Access Level |
|-----------|-------------|-------------|
| **Employee** | Primary user. Logs in, submits daily reports, views own history. | Own data only |
| **Dev/Admin** | Uses dev-login for testing. Can access backfill endpoint. | Dev environment only |

### 2.3 Operating Environment

- **Server:** PHP 8.1+, Apache (XAMPP), MariaDB 10.x
- **Browser:** Modern browsers (Chrome, Firefox, Safari, Edge)
- **Network:** Local/LAN deployment (no public internet required for core features)
- **Optional:** Google Cloud project for Sheets sync

### 2.4 Design Constraints

- No PHP framework — pure vanilla with autoloader
- Reports are **immutable** once saved (no edit/delete)
- Files stored as BLOB in database (not filesystem)
- Google Sheets sync is **non-blocking** — failure does not prevent saving
- Single-user session (no concurrent editing)

---

## 3. Functional Requirements

### FR-01: Authentication

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-01.1 | User can log in with email + password | Must |
| FR-01.2 | Password verified against bcrypt hash | Must |
| FR-01.3 | Session created on successful login | Must |
| FR-01.4 | Logout destroys session | Must |
| FR-01.5 | Dev-login available in development environment only | Should |
| FR-01.6 | Unauthenticated users redirected to login | Must |
| FR-01.7 | Already-authenticated users cannot access login page | Must |

**Flows:**
```
Login:
  1. User enters email + password
  2. System validates CSRF token
  3. System queries user by email
  4. System verifies bcrypt password hash
  5. On success: stores user_id + user in session → redirect to /home
  6. On failure: flash error message → redirect to /login

Dev Login (dev environment only):
  1. User clicks "Dev Login" button
  2. System grabs first user from database
  3. Stores in session → redirect to /home
```

### FR-02: Work Report Management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-02.1 | User can add a new work report | Must |
| FR-02.2 | Report requires: work date (YYYY-MM-DD) + description (min 10 chars) | Must |
| FR-02.3 | 2-step flow: preview before save | Must |
| FR-02.4 | Reports are read-only after saving | Must |
| FR-02.5 | User can view individual report details | Must |
| FR-02.6 | User can see list of all own reports on home page | Must |
| FR-02.7 | Reports sorted by date descending (newest first) | Should |

**Flows:**
```
Add Report:
  1. User fills: work date, description, optional files
  2. User clicks "Review"
  3. System validates inputs
  4. System stages uploaded files to temp directory
  5. System shows preview page with all data
  6. User confirms save
  7. System saves report to MariaDB
  8. System stores files as BLOB in MariaDB
  9. System syncs metadata to Google Sheets (if enabled)
  10. Redirects to success page

View Report:
  1. User clicks a report from home page
  2. System loads report + file metadata by ID + user ownership check
  3. Displays: date, description, file list with download/preview links
```

### FR-03: File Upload & Storage

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-03.1 | User can attach multiple files to a report | Must |
| FR-03.2 | Supported types: JPG, PNG, GIF, PDF, DOC, DOCX | Must |
| FR-03.3 | Max file size: 10MB per file | Must |
| FR-03.4 | Max total size: 30MB across all files | Must |
| FR-03.5 | Blocked extensions: PHP, EXE, SH, BAT, JS, VBS, etc. | Must |
| FR-03.6 | MIME type verified via finfo (not extension alone) | Must |
| FR-03.7 | Files stored as BLOB in database | Must |
| FR-03.8 | User can preview images inline | Should |
| FR-03.9 | User can download non-image files | Must |
| FR-03.10 | Files verified against user ownership before serving | Must |

**File Type Safety:**
```
Upload validation pipeline:
  1. Check upload error code
  2. Block dangerous extensions (php, exe, js, vbs, etc.)
  3. Whitelist allowed extensions (jpg, png, gif, pdf, doc, docx)
  4. Verify MIME type via finfo_file() — extension alone is not trusted
  5. Check individual file size ≤ 10MB
  6. Check cumulative size ≤ 30MB
```

### FR-04: Google Sheets Sync

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-04.1 | Sync report metadata to Google Sheets on save | Should |
| FR-04.2 | Upsert by Report ID — no duplicate rows | Must |
| FR-04.3 | Sync failure does NOT block MariaDB save | Must |
| FR-04.4 | Backfill endpoint syncs all existing reports (dev-only) | Should |
| FR-04.5 | Sync includes: Report ID, User ID, Name, Email, Date, Description, Attachment count/names, Created timestamp | Must |

**Synced Fields:**

| Column | Content |
|--------|---------|
| A | Report ID (unique key) |
| B | User ID |
| C | User Display Name |
| D | User Email |
| E | Work Date |
| F | Work Description |
| G | Attachment Count |
| H | Attachment Names (comma-separated) |
| I | Created At timestamp |

**What stays in MariaDB only:** File binary data (BLOBs), session data, CSRF tokens.

### FR-05: UI/UX

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-05.1 | Dark mode by default, light mode available | Must |
| FR-05.2 | Theme preference persists in localStorage | Must |
| FR-05.3 | Responsive layout (mobile-first) | Must |
| FR-05.4 | Bottom navigation bar (Home, Add) | Must |
| FR-05.5 | Personalized greeting (Good Morning/Afternoon/Evening + name) | Should |
| FR-05.6 | Flash messages for success/error feedback | Must |
| FR-05.7 | Empty state when no reports exist | Should |
| FR-05.8 | Character counter on description field | Should |
| FR-05.9 | Drag-and-drop file upload area | Should |
| FR-05.10 | Confirmation modal before saving report | Should |
| FR-05.11 | Loading overlay during save | Should |
| FR-05.12 | ARIA labels for accessibility | Should |

---

## 4. Non-Functional Requirements

### NFR-01: Security

| Requirement | Implementation |
|-------------|---------------|
| CSRF protection | Token generated per session, validated on every POST |
| Password hashing | bcrypt via `password_verify()` |
| Session-based auth | `$_SESSION['user_id']` checked on every protected route |
| Input sanitization | `sanitizeString()` — trim + strip_tags on user input |
| Output escaping | `e()` — htmlspecialchars with ENT_QUOTES on all output |
| File upload security | Extension blacklist + whitelist, MIME verification via finfo, size limits |
| Ownership verification | All file/report queries include `WHERE user_id = ?` |
| Dev-login guard | Route + service both check `isDevEnvironment()` |

### NFR-02: Performance

| Requirement | Implementation |
|-------------|---------------|
| Database connection pooling | PDO singleton pattern (`Database::$instance`) |
| Prepared statements | All queries use prepared statements (no SQL injection, slight perf gain) |
| BLOB storage | Avoids filesystem I/O overhead for small-to-medium files |
| No framework overhead | Vanilla PHP, minimal dependencies |
| File staging | Files moved to temp on preview, stored on save — avoids re-uploading |

### NFR-03: Scalability

| Aspect | Current State | Future Consideration |
|--------|--------------|---------------------|
| Users | Small team (< 50) | No horizontal scaling needed |
| Reports | ~50/week | MariaDB handles easily |
| File storage | BLOB in DB | For >1000 reports, migrate to S3/filesystem |
| Google Sheets | One spreadsheet | Rate limits may require queuing |
| Concurrent users | Low | Session-based auth is single-server |

### NFR-04: Availability

| Requirement | Implementation |
|-------------|---------------|
| MariaDB is primary store | All data persisted here first |
| Google Sheets is secondary | Sync is async, non-blocking, fail-open |
| Failure isolation | Sheets API errors caught and logged, never thrown to user |
| Session persistence | PHP native sessions (file-based) |

### NFR-05: Usability

| Requirement | Implementation |
|-------------|---------------|
| Mobile-first responsive | CSS with media queries for small screens |
| Keyboard accessible | Upload area responds to Enter/Space |
| Screen reader friendly | ARIA labels on interactive elements |
| Clear error messages | Flash messages with specific validation errors |
| Visual feedback | Loading overlay, button state changes on submit |
| Intuitive flow | Add → Preview → Save (2-step prevents accidental submissions) |

---

## 5. Data Requirements

### 5.1 Database Schema

```
┌─────────────────────┐
│       users          │
├─────────────────────┤
│ id (PK, auto)       │
│ username (UNIQUE)    │
│ email (UNIQUE)       │
│ display_name         │
│ password_hash        │
│ created_at           │
│ updated_at           │
└─────────┬───────────┘
          │ 1:N
          ▼
┌─────────────────────────┐
│     work_reports         │
├─────────────────────────┤
│ id (PK, auto)           │
│ user_id (FK → users)    │
│ work_date (DATE)         │
│ description (TEXT)       │
│ created_at               │
│ updated_at               │
└─────────┬───────────────┘
          │ 1:N
          ▼
┌─────────────────────────────┐
│    work_report_files         │
├─────────────────────────────┤
│ id (PK, auto)               │
│ work_report_id (FK → reports)│
│ original_filename            │
│ mime_type                    │
│ file_size (INT)              │
│ file_data (LONGBLOB)         │
│ created_at                   │
└─────────────────────────────┘
```

### 5.2 Data Flow Diagrams

**Report Save Flow:**
```
Browser                    Server                     Database
  │                          │                           │
  │  POST /report/save       │                           │
  │  (draft + staged files)  │                           │
  │ ────────────────────────>│                           │
  │                          │  INSERT INTO work_reports  │
  │                          │ ─────────────────────────>│
  │                          │  SELECT (get inserted row) │
  │                          │ <─────────────────────────│
  │                          │                           │
  │                          │  For each staged file:    │
  │                          │  file_get_contents()      │
  │                          │  INSERT INTO work_report_  │
  │                          │  files (BLOB)             │
  │                          │ ─────────────────────────>│
  │                          │                           │
  │                          │  [Optional] Sync to       │
  │                          │  Google Sheets            │
  │                          │ ──────> Google API        │
  │                          │                           │
  │  302 → /report/success   │                           │
  │ <────────────────────────│                           │
```

**File Serve Flow:**
```
Browser                    Server                     Database
  │                          │                           │
  │  GET /files/123          │                           │
  │ ────────────────────────>│                           │
  │                          │  SELECT file_data WHERE    │
  │                          │  id=123 AND user_id=?     │
  │                          │ ─────────────────────────>│
  │                          │ <─────────────────────────│
  │                          │                           │
  │  200 + Content-Type      │                           │
  │  + binary stream         │                           │
  │ <────────────────────────│                           │
```

### 5.3 Data Retention

- Reports are **permanent** — no soft delete, no hard delete
- Files are **permanent** — stored as BLOB with cascade delete if report is removed
- Temp files in `storage/temp/` cleaned up after staging (or by 1-hour cleanup)
- Sessions destroyed on logout

---

## 6. External Interfaces

### 6.1 Google Sheets API

| Property | Value |
|----------|-------|
| API Version | Google Sheets API v4 |
| Authentication | Service account (JSON key file) |
| Scopes | `https://www.googleapis.com/auth/spreadsheets` |
| Endpoint | `spreadsheets_values` (get, append, update) |
| Upsert Logic | Column A = Report ID; find row → update or append |
| Error Handling | try/catch, log to error.log, return false (non-blocking) |

### 6.2 MariaDB

| Property | Value |
|----------|-------|
| Connection | PDO via `Database::getConnection()` |
| Charset | utf8mb4 / utf8mb4_unicode_ci |
| Prepared Statements | All queries |
| Error Mode | `PDO::ERRMODE_EXCEPTION` |
| Emulate Prepares | Disabled (real prepared statements) |

---

## 7. File Inventory & Use Cases

### 7.1 Entry Point

| File | Purpose | Use Case |
|------|---------|----------|
| `public/index.php` | **Application entry point.** Loads autoloader, starts session, loads `.env`, registers all routes with AltoRouter, dispatches request to controller. | Every HTTP request hits this file first. |

### 7.2 Configuration

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Config/Database.php` | **PDO singleton + `.env` loader.** `loadEnv()` reads `.env` via Dotenv. `getConnection()` creates one PDO instance reused everywhere. `isDevEnvironment()` checks `APP_ENV`. | Database connection, environment detection. |
| `src/Config/GoogleSheets.php` | **Google Sheets config reader.** Static methods for: `isEnabled()`, `getCredentialsPath()`, `getSpreadsheetId()`, `getWorksheet()`. All read from `$_ENV`. | Sheets integration config. |
| `.env` | **Environment variables.** DB credentials, app URL, Google Sheets settings. **DO NOT MODIFY.** | Runtime configuration. |
| `.env.example` | **Template for `.env`.** Shows required variables without real values. | Setup reference. |
| `composer.json` | **PHP dependencies.** phpdotenv, altorouter, google/apiclient. PSR-4 autoload for `App\` namespace, auto-loads `helpers.php`. | Dependency management. |

### 7.3 Controllers

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Controllers/AuthController.php` | **Authentication controller.** Handles login form display, login POST (email+password validation), dev-login (one-click), and logout (session destroy). | User login/logout flow. |
| `src/Controllers/HomeController.php` | **Dashboard controller.** Loads all reports for logged-in user with file counts, renders home page with greeting and report list. | Home page after login. |
| `src/Controllers/ReportController.php` | **Report CRUD controller.** Add form, preview (validate + stage files), save (persist to DB + Sheets), success page, view individual report. | Core report management flow. |
| `src/Controllers/FileController.php` | **File server controller.** Serves file BLOBs from database with correct Content-Type and Content-Disposition headers. Verifies user ownership. | File preview (images) and download (PDF/DOC). |
| `src/Controllers/SheetsController.php` | **Google Sheets backfill controller.** Syncs all MariaDB reports to Google Sheets. Dev-only route. Returns JSON status. | One-time data sync / backfill. |

### 7.4 Services

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Services/AuthService.php` | **Authentication service.** `login()` verifies email+password via UserRepository. `devLogin()` returns first user. | Login logic (used by AuthController). |
| `src/Services/WorkReportService.php` | **Report orchestration service.** Validates inputs, coordinates save (DB + files + Sheets), retrieves reports with file metadata. | Central business logic for reports. |
| `src/Services/FileStorageService.php` | **File handling service.** Validates uploads (extension, MIME, size), stages files to temp dir, stores as BLOB via repository, cleans up temp. | File upload validation and storage. |
| `src/Services/GoogleSheetsSyncService.php` | **Google Sheets sync service.** `syncReport()` upserts single report. `syncAll()` backfills all reports. Uses Report ID as unique key. | Google Sheets integration. |

### 7.5 Repositories

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Repositories/UserRepository.php` | **User data access.** Queries: findById, findByEmail, findByUsername, create, getFirstUser. | User lookups for auth and Sheets sync. |
| `src/Repositories/WorkReportRepository.php` | **Report data access.** Queries: findByIdAndUser, findAllByUser, create, addFileWithData, getFilesByReport, getFileById. | All report and file database operations. |
| `src/Repositories/GoogleSheetsRepository.php` | **Google Sheets API wrapper.** Methods: findRowByReportId, appendRow, updateRow, getAllReportIds. Handles Sheets API authentication. | Google Sheets read/write operations. |

### 7.6 Interfaces

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Interfaces/WorkReportRepositoryInterface.php` | **Contract for report repository.** Defines: findByIdAndUser, findAllByUser, create, addFileWithData, getFilesByReport, getFileById. | Type hint in WorkReportService. |
| `src/Interfaces/UserRepositoryInterface.php` | **Contract for user repository.** Defines: findById, findByEmail, findByUsername, create. | Type hint in AuthService. |
| `src/Interfaces/GoogleSheetsRepositoryInterface.php` | **Contract for Sheets repository.** Defines: findRowByReportId, appendRow, updateRow, getAllReportIds. | Type hint in GoogleSheetsSyncService. |
| `src/Interfaces/FileStorageInterface.php` | **Contract for file storage.** Defines: store, getFullPath, exists, delete, getMimeType. | **UNUSED** — LocalFileStorage implements it but nothing calls it. |

### 7.7 Middleware

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Middleware/AuthMiddleware.php` | **Auth guard.** `check()` — redirects to login if not authenticated. `guest()` — redirects to home if already authenticated. | Called at start of every protected controller method. |

### 7.8 Storage

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Storage/LocalFileStorage.php` | **Filesystem file storage.** Implements FileStorageInterface. Stores files in `storage/uploads/{userId}/`. | **UNUSED** — BLOB storage replaced this. Dead code. |

### 7.9 Helpers

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Helpers/helpers.php` | **Global helper functions.** Auto-loaded by Composer. Contains: flash messages (`setFlash`, `getFlash`), URL helpers (`baseUrl`, `url`, `redirect`), CSRF (`csrfToken`, `csrfField`, `validateCsrf`), auth (`isLoggedIn`, `currentUserId`, `currentUser`, `requireAuth`), output escaping (`e`), formatting (`formatDate`, `fullDate`, `getGreeting`, `formatFileSize`, `getFileIcon`), validation (`sanitizeString`, `isDevEnvironment`). | Used across all controllers and views. |

### 7.10 Views

| File | Purpose | Use Case |
|------|---------|----------|
| `src/Views/layouts/main.php` | **Base HTML layout.** DOCTYPE, head (CSS, theme script), header with logo + theme toggle + logout. All pages include this. | Common page structure. |
| `src/Views/layouts/footer.php` | **Page footer.** Bottom navigation bar (Home, Add), `$extraScripts` injection, app.js include. | Common page ending. |
| `src/Views/auth/login.php` | **Login page.** Email + password form, flash error display, dev-login button (dev only). | User authentication. |
| `src/Views/home/index.php` | **Home/dashboard page.** Greeting, "Add Today's Work" CTA, report list with file counts, empty state. | Post-login landing page. |
| `src/Views/report/add.php` | **Add report form.** Work date picker, description textarea with char counter, drag-and-drop file upload area, inline JS for file handling. | Creating new reports. |
| `src/Views/report/review.php` | **Preview before save.** Shows date, description, attached files. Save button with confirmation modal. | Review step before permanent save. |
| `src/Views/report/view.php` | **Report detail view.** Read-only display of date, description, files with download/preview links. | Viewing submitted reports. |
| `src/Views/report/success.php` | **Save confirmation.** Success icon, summary of saved report, links to home or add another. | Post-save feedback. |
| `src/Views/report/not_found.php` | **404 for reports.** Shown when report ID doesn't exist or user doesn't own it. | Error handling. |

### 7.11 Public Assets

| File | Purpose | Use Case |
|------|---------|----------|
| `public/index.php` | **Entry point.** Routes all requests. | See 7.1. |
| `public/.htaccess` | **Apache rewrite rules.** Routes all requests through index.php. | URL rewriting. |
| `public/test_route.php` | **Dev test file.** Testing route resolution. | **Should be deleted** — dev artifact. |
| `public/assets/css/` | **Stylesheets.** variables.css, base.css, components.css, responsive.css. | UI styling. |
| `public/assets/js/app.js` | **Frontend JS.** Theme toggle, flash auto-dismiss, bottom nav. | Client-side interactions. |

### 7.12 Database

| File | Purpose | Use Case |
|------|---------|----------|
| `database/schema.sql` | **Full schema + seed data.** Creates database, 3 tables, indexes, foreign keys, 3 test users. | Initial database setup. |
| `database/fix_passwords.sql` | **Password reset script.** Updates all users to bcrypt hash of 'password123'. | **Should be deleted** — one-time migration, already in schema.sql. |

### 7.13 Documentation

| File | Purpose | Use Case |
|------|---------|----------|
| `google.md` | **Google Sheets setup guide.** Step-by-step: create spreadsheet, Cloud project, service account, share sheet, `.env` config, troubleshooting. | Sheets integration setup. |
| `SRS.md` | **This document.** Software Requirements Specification. | Project reference. |

### 7.14 Tests

| Directory | Purpose | Use Case |
|-----------|---------|----------|
| `tests/playwright/` | **E2E browser tests.** Playwright-based UI testing. | Automated UI verification. |

### 7.15 Storage

| Directory | Purpose | Use Case |
|-----------|---------|----------|
| `storage/uploads/` | **File upload directory.** Contains `.gitkeep`. Currently unused (BLOB mode). | Reserved for future filesystem storage. |
| `storage/temp/` | **Staging directory.** Temporary files between preview and save steps. Auto-created. | File upload staging. |
| `storage/google-credentials.json` | **Google service account key.** Gitignored. | Sheets API authentication. |

### 7.16 Empty / Unused

| Item | Status |
|------|--------|
| `routes/` directory | Empty — routes are in `public/index.php` |
| `src/Interfaces/FileStorageInterface.php` | Dead code — no consumers |
| `src/Storage/LocalFileStorage.php` | Dead code — BLOB storage replaced it |
| `public/test_route.php` | Dev artifact |
| `database/fix_passwords.sql` | One-time migration |

---

## 8. Assumptions & Dependencies

### Assumptions

1. PHP 8.1+ is available with extensions: PDO MySQL, finfo, session, mbstring
2. MariaDB 10.x or MySQL 5.7+ is running
3. Apache with mod_rewrite enabled (for `.htaccess`)
4. Google Cloud project exists with Sheets API enabled (optional)
5. Users are pre-seeded in database (no self-registration)
6. Single-server deployment (no load balancer, no CDN)

### Dependencies

| Dependency | Version | Purpose |
|------------|---------|---------|
| `vlucas/phpdotenv` | ^5.6 | Load `.env` variables |
| `altorouter/altorouter` | ^2.0 | HTTP routing |
| `google/apiclient` | * | Google Sheets API client |

### PHP Extensions Required

- `pdo_mysql` — MariaDB connection
- `session` — User sessions
- `mbstring` — Multibyte string handling
- `finfo` — MIME type detection for file uploads
- `json` — JSON encoding/decoding

---

## 9. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-09-14 | Code Analyzer Agent | Initial SRS based on codebase analysis |
