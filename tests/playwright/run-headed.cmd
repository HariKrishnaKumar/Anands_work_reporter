@echo off
cd /d "%~dp0"
npx playwright test --headed --project="Desktop 1366" --reporter=list
