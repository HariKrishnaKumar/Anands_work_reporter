# Google Sheets Integration — Setup Guide

## Spreadsheet Requirements

### Create a Google Spreadsheet

1. Go to [Google Drive](https://drive.google.com/) and create a new Spreadsheet
2. Name the spreadsheet: **Daily Work Reports**
3. Name the worksheet (tab) exactly: **Work Reports**

### Required Columns

| Column | Header | Description | Example |
|--------|--------|-------------|---------|
| A | Report ID | Auto-generated MariaDB primary key | `1` |
| B | User ID | The user who created the report | `1` |
| C | User Name | Display name of the user | `Hari Krishna` |
| D | User Email | Email address of the user | `hari@test.com` |
| E | Work Date | Date of the work report | `2026-09-13` |
| F | Work Description | Full text description of work done | `Worked on UI redesign...` |
| G | Attachment Count | Number of files attached | `2` |
| H | Attachment Names | Comma-separated filenames | `screenshot.png, report.pdf` |
| I | Created At | Timestamp when the report was saved | `2026-09-13 15:30:00` |

### Example Row

| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| 1 | 1 | Hari Krishna | hari@test.com | 2026-09-13 | Redesigned the login page and fixed responsive layout issues | 2 | screenshot.png, notes.pdf | 2026-09-13 15:30:00 |

---

## Google Cloud Setup

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Select a project** → **New Project**
3. Name: `daily-work-report` → **Create**

### Step 2: Enable APIs

Enable both of these APIs in your project:
- **Google Sheets API** — search "Google Sheets API" → click **Enable**
- **Google Drive API** — search "Google Drive API" → click **Enable**

### Step 3: Create a Service Account

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials** → **Service Account**
3. Name: `sheets-sync`
4. Description: `Daily Work Report Sheets Sync`
5. Click **Create and Continue** → **Done**

### Step 4: Download Credentials

1. Click the newly created service account
2. Go to **Keys** tab → **Add Key** → **Create new key**
3. Select **JSON** → **Create**
4. Save the downloaded file as:
   ```
   daily-work-report/storage/google-credentials.json
   ```

### Step 5: Share the Spreadsheet

1. Open your Google Sheet
2. Click **Share**
3. Paste the `client_email` from the JSON file (looks like `sheets-sync@your-project.iam.gserviceaccount.com`)
4. Set permission to **Editor**
5. Click **Send**

---

## Application Configuration

### `.env` Settings

```ini
# Google Sheets Integration
GOOGLE_SHEETS_ENABLED=true
GOOGLE_SHEETS_CREDENTIALS_PATH=storage/google-credentials.json
GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id_here
GOOGLE_SHEETS_WORKSHEET="Work Reports"
```

### Finding Your Spreadsheet ID

Open your Google Sheet. The URL looks like:
```
https://docs.google.com/spreadsheets/d/1aBcDeFgHiJkLmNoPqRsTuVwXyZ/edit#gid=0
```
The **Spreadsheet ID** is the long string between `/d/` and `/edit`:
```
1aBcDeFgHiJkLmNoPqRsTuVwXyZ
```

---

## How It Works

### Sync on Report Save

1. User saves a report through the normal UI
2. Report is saved to MariaDB (primary database)
3. File attachments are saved as BLOB in MariaDB
4. **After** MariaDB save succeeds, report metadata is synced to Google Sheets
5. If Google Sheets sync fails, the MariaDB report is **NOT lost** — failure is logged only

### Upsert Logic (No Duplicates)

- Report ID (column A) is used as the unique key
- If the Report ID already exists in the sheet, that row is **updated**
- If the Report ID does not exist, a new row is **appended**
- Running sync multiple times will never create duplicate rows

### What Gets Synced

| Synced to Sheets | Stays in MariaDB only |
|-------------------|----------------------|
| Report ID | File binary data (images, PDFs, DOCs) |
| User ID | BLOB content |
| User Name | File upload temp data |
| User Email | Session data |
| Work Date | CSRF tokens |
| Work Description | |
| Attachment Count | |
| Attachment Names (filenames only) | |
| Created At | |

---

## Backfill Existing Data

To sync reports that were created before the Google Sheets integration:

1. Make sure `APP_ENV=development` in `.env`
2. Visit: `http://localhost/daily-work-report/public/sheets/sync-all`
3. You will see a JSON response:
   ```json
   {
     "status": "ok",
     "spreadsheet_id": "your_id",
     "worksheet": "Work Reports",
     "synced": 15,
     "failed": 0
   }
   ```

This route is only available in development mode and is idempotent (safe to run multiple times).

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `Google credentials file not found` | JSON file missing | Place it at `storage/google-credentials.json` |
| `GOOGLE_SHEETS_SPREADSHEET_ID is not set` | Empty spreadsheet ID | Set it in `.env` |
| `Google Sheets integration is disabled` | `GOOGLE_SHEETS_ENABLED` is `false` | Set to `true` in `.env` |
| `403 Forbidden` from Google API | Sheet not shared with service account | Share the sheet with the `client_email` from JSON |
| `404 Not Found` from Google API | Wrong spreadsheet ID | Double-check the ID from the URL |
| `Sync failed` in error log | Any Google API error | Check `C:\xampp\apache\logs\error.log` for details |

---

## File Locations

```
daily-work-report/
├── storage/
│   └── google-credentials.json    ← Google service account key (gitignored)
├── .env                           ← Contains GOOGLE_SHEETS_* settings
├── src/
│   ├── Config/
│   │   └── GoogleSheets.php       ← Config reader
│   ├── Interfaces/
│   │   └── GoogleSheetsRepositoryInterface.php
│   ├── Repositories/
│   │   └── GoogleSheetsRepository.php  ← Google API implementation
│   ├── Services/
│   │   └── GoogleSheetsSyncService.php ← Sync orchestration
│   └── Controllers/
│       └── SheetsController.php   ← Backfill route handler
└── public/
    └── index.php                  ← Route: GET /sheets/sync-all (dev only)
```
