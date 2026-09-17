@echo off
cd /d "%~dp0"
npx playwright test tests/gstack-qa.spec.js --project="Desktop 1366" --reporter=list --headed
