# Graph Report - daily-work-report  (2026-09-17)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 243 nodes · 373 edges · 26 communities (16 shown, 10 thin omitted)
- Extraction: 92% EXTRACTED · 8% INFERRED · 0% AMBIGUOUS · INFERRED: 30 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `887d6364`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- helpers.php
- WorkReportRepository
- GoogleSheets
- Database
- composer.json
- @playwright/test
- initLoginHero
- UserRepository
- authorization.spec.js
- file-upload.spec.js
- app.js
- package.json
- gstack-qa.spec.js
- verify-all-breakpoints.js
- login-desktop.spec.js
- review-save.spec.js

## God Nodes (most connected - your core abstractions)
1. `WorkReportRepository` - 29 edges
2. `UserRepository` - 18 edges
3. `AuthMiddleware` - 17 edges
4. `GoogleSheets` - 16 edges
5. `GoogleSheetsRepository` - 16 edges
6. `WorkReportService` - 15 edges
7. `@playwright/test` - 14 edges
8. `GoogleSheetsSyncService` - 13 edges
9. `initLoginHero()` - 13 edges
10. `FileStorageService` - 12 edges

## Surprising Connections (you probably didn't know these)
- `GoogleSheetsSyncService` --references--> `WorkReportRepository`  [EXTRACTED]
  src/Services/GoogleSheetsSyncService.php → src/Repositories/WorkReportRepository.php
- `WorkReportService` --references--> `GoogleSheetsSyncService`  [EXTRACTED]
  src/Services/WorkReportService.php → src/Services/GoogleSheetsSyncService.php
- `GoogleSheetsSyncService` --references--> `UserRepository`  [EXTRACTED]
  src/Services/GoogleSheetsSyncService.php → src/Repositories/UserRepository.php
- `HomeController` --references--> `WorkReportService`  [EXTRACTED]
  src/Controllers/HomeController.php → src/Services/WorkReportService.php
- `ReportController` --references--> `FileStorageService`  [EXTRACTED]
  src/Controllers/ReportController.php → src/Services/FileStorageService.php

## Import Cycles
- None detected.

## Communities (26 total, 10 thin omitted)

### Community 0 - "helpers.php"
Cohesion: 0.10
Nodes (15): FileController, baseUrl(), csrfField(), csrfToken(), currentUser(), currentUserId(), e(), getFlash() (+7 more)

### Community 1 - "WorkReportRepository"
Cohesion: 0.09
Nodes (6): HomeController, ReportController, PDO, WorkReportRepository, FileStorageService, WorkReportService

### Community 2 - "GoogleSheets"
Cohesion: 0.15
Nodes (8): Google\Client, Google\Service\Sheets, Google\Service\Sheets\ValueRange, Sheets, GoogleSheets, SheetsController, GoogleSheetsRepository, GoogleSheetsSyncService

### Community 3 - "Database"
Cohesion: 0.13
Nodes (4): Dotenv\Dotenv, Database, PDO, Unsplash

### Community 4 - "composer.json"
Cohesion: 0.13
Nodes (14): autoload, files, psr-4, config, optimize-autoloader, description, name, App\\ (+6 more)

### Community 5 - "@playwright/test"
Cohesion: 0.14
Nodes (8): @playwright/test, { defineConfig }, { chromium }, { test, expect }, { test, expect }, { test, expect }, breakpoints, { test, expect }

### Community 6 - "initLoginHero"
Cohesion: 0.27
Nodes (12): initLoginHero(), applyTextColor(), displayImage(), fetchImages(), getCachedImagesStale(), getLuminance(), getRandomQuery(), getRandomQuote() (+4 more)

### Community 7 - "UserRepository"
Cohesion: 0.24
Nodes (4): AuthController, PDO, UserRepository, AuthService

### Community 8 - "authorization.spec.js"
Cohesion: 0.21
Nodes (8): { clickAddReport }, { test, expect }, { clickAddReport }, fs, { test, expect }, clickAddReport(), { clickAddReport }, { test, expect }

### Community 9 - "file-upload.spec.js"
Cohesion: 0.22
Nodes (7): IMPORTANT: keep the reminder string free of backticks and $(...) constructs., ref_fs, ref_path, { clickAddReport }, fs, path, { test, expect }

### Community 10 - "app.js"
Cohesion: 0.27
Nodes (6): getSystemTheme(), getTheme(), initNavPill(), initSearch(), setTheme(), toggleTheme()

### Community 11 - "package.json"
Cohesion: 0.22
Nodes (8): devDependencies, @playwright/test, name, private, scripts, test, test:headed, version

### Community 12 - "gstack-qa.spec.js"
Cohesion: 0.29
Nodes (5): fs, issues, path, SS_DIR, { test, expect }

### Community 13 - "verify-all-breakpoints.js"
Cohesion: 0.33
Nodes (4): ref_playwright, { chromium }, viewports, { chromium }

### Community 14 - "login-desktop.spec.js"
Cohesion: 0.40
Nodes (4): desktopBps, mobileBps, tabletBps, { test, expect }

### Community 16 - "review-save.spec.js"
Cohesion: 0.50
Nodes (3): { clickAddReport }, fs, { test, expect }

## Knowledge Gaps
- **49 isolated node(s):** `@playwright/test`, `name`, `private`, `test`, `test:headed` (+44 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 111 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **10 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `WorkReportRepository` connect `WorkReportRepository` to `helpers.php`, `GoogleSheets`, `Database`?**
  _High betweenness centrality (0.088) - this node is a cross-community bridge._
- **Why does `AuthMiddleware` connect `helpers.php` to `WorkReportRepository`, `UserRepository`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **Why does `UserRepository` connect `UserRepository` to `WorkReportRepository`, `GoogleSheets`, `Database`?**
  _High betweenness centrality (0.050) - this node is a cross-community bridge._
- **What connects `@playwright/test`, `name`, `private` to the rest of the system?**
  _49 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `helpers.php` be split into smaller, more focused modules?**
  _Cohesion score 0.0960960960960961 - nodes in this community are weakly interconnected._
- **Should `WorkReportRepository` be split into smaller, more focused modules?**
  _Cohesion score 0.0928030303030303 - nodes in this community are weakly interconnected._
- **Should `Database` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._