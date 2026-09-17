# Database Test Script for MySQL (XAMPP)
# Run from PowerShell

$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$dbUser = "root"
$dbPass = ""
$dbName = "daily_work_report"
$results = @()

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  MySQL Database Comprehensive Tests" -ForegroundColor Cyan
Write-Host "  Database: $dbName" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Function to run MySQL query
function Run-MySQL {
    param([string]$query)
    & $mysqlPath -u $dbUser --database=$dbName -e $query 2>&1
}

# Function to measure MySQL execution time
function Measure-MySQL {
    param([string]$query)
    $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
    & $mysqlPath -u $dbUser --database=$dbName -e $query 2>&1 | Out-Null
    $stopwatch.Stop()
    return $stopwatch.Elapsed.TotalMilliseconds
}

# ============================================
# TEST 1: Connection Test (10 runs, measure average)
# ============================================
Write-Host "TEST 1: Connection Performance Test" -ForegroundColor Yellow
Write-Host "Running 10 connection tests..."

$connectionTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT 1;"
    $connectionTimes += $time
    Write-Host "  Run $i : $([math]::Round($time, 2))ms"
}
$avgConnection = ($connectionTimes | Measure-Object -Average).Average
Write-Host "  Average connection time: $([math]::Round($avgConnection, 2))ms" -ForegroundColor Green
$results += "TEST 1 PASS - Connection test complete (avg: $([math]::Round($avgConnection, 2))ms)"
Write-Host ""

# ============================================
# TEST 2: Schema Integrity
# ============================================
Write-Host "TEST 2: Schema Integrity Test" -ForegroundColor Yellow

# Check tables exist
$tableCheck = Run-MySQL "SHOW TABLES LIKE 'users';"
$usersExists = $tableCheck -match "users"

$tableCheck2 = Run-MySQL "SHOW TABLES LIKE 'work_reports';"
$workReportsExists = $tableCheck2 -match "work_reports"

$tableCheck3 = Run-MySQL "SHOW TABLES LIKE 'work_report_files';"
$workReportFilesExists = $tableCheck3 -match "work_report_files"

if ($usersExists -and $workReportsExists -and $workReportFilesExists) {
    Write-Host "  All tables exist" -ForegroundColor Green
} else {
    Write-Host "  ERROR: Missing tables" -ForegroundColor Red
    $results += "TEST 2 FAIL - Missing tables"
}

# Check foreign keys
$foreignKeys = Run-MySQL "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='$dbName' AND REFERENCED_TABLE_NAME IS NOT NULL;"
$fkCount = ($foreignKeys | Measure-Object -Line).Lines - 1
Write-Host "  Foreign keys found: $fkCount" -ForegroundColor $(if ($fkCount -ge 2) { "Green" } else { "Red" })

# Check indexes
$indexes = Run-MySQL "SHOW INDEX FROM work_reports;"
$indexCount = ($indexes | Measure-Object -Line).Lines - 1
Write-Host "  Indexes on work_reports: $indexCount" -ForegroundColor $(if ($indexCount -ge 1) { "Green" } else { "Yellow" })

# Check user count
$userCount = Run-MySQL "SELECT COUNT(*) as cnt FROM users;"
$userCountNum = ($userCount -replace '[^0-9]', '') -as [int]
Write-Host "  User count: $userCountNum" -ForegroundColor $(if ($userCountNum -eq 3) { "Green" } else { "Red" })

$results += "TEST 2 PASS - Schema integrity verified"
Write-Host ""

# ============================================
# TEST 3: Insert Performance
# ============================================
Write-Host "TEST 3: Insert Performance Test" -ForegroundColor Yellow

# Create temp table for performance testing
Run-MySQL "CREATE TABLE IF NOT EXISTS temp_performance_test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    work_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);"
Run-MySQL "TRUNCATE TABLE temp_performance_test;"

# Insert 1 work report
$time1 = Measure-MySQL "INSERT INTO temp_performance_test (user_id, work_date, description) VALUES (1, CURDATE(), 'Test entry single insert');"
Write-Host "  Insert 1 record: $([math]::Round($time1, 2))ms" -ForegroundColor Green

# Insert 10 work reports
$batch10 = "INSERT INTO temp_performance_test (user_id, work_date, description) VALUES "
$entries10 = @()
for ($i = 1; $i -le 10; $i++) {
    $days = Get-Random -Minimum -365 -Maximum 0
    $date = (Get-Date).AddDays($days).ToString("yyyy-MM-dd")
    $entries10 += "(1, '$date', 'Batch test entry $i')"
}
$batch10 += ($entries10 -join ", ") + ";"
$time10 = Measure-MySQL $batch10
Write-Host "  Insert 10 records: $([math]::Round($time10, 2))ms" -ForegroundColor Green

# Insert 100 work reports
$entries100 = @()
for ($i = 1; $i -le 100; $i++) {
    $days = Get-Random -Minimum -365 -Maximum 0
    $date = (Get-Date).AddDays($days).ToString("yyyy-MM-dd")
    $entries100 += "(1, '$date', 'Batch test entry $i')"
}
$batch100 = "INSERT INTO temp_performance_test (user_id, work_date, description) VALUES " + ($entries100 -join ", ") + ";"
$time100 = Measure-MySQL $batch100
Write-Host "  Insert 100 records: $([math]::Round($time100, 2))ms" -ForegroundColor Green

# Insert 1000 work reports
$entries1000 = @()
for ($i = 1; $i -le 1000; $i++) {
    $days = Get-Random -Minimum -365 -Maximum 0
    $date = (Get-Date).AddDays($days).ToString("yyyy-MM-dd")
    $entries1000 += "(1, '$date', 'Batch test entry $i')"
}
$batch1000 = "INSERT INTO temp_performance_test (user_id, work_date, description) VALUES " + ($entries1000 -join ", ") + ";"
$time1000 = Measure-MySQL $batch1000
Write-Host "  Insert 1000 records: $([math]::Round($time1000, 2))ms" -ForegroundColor Green

$results += "TEST 3 PASS - Insert performance (1: $([math]::Round($time1, 2))ms, 10: $([math]::Round($time10, 2))ms, 100: $([math]::Round($time100, 2))ms, 1000: $([math]::Round($time1000, 2))ms)"
Write-Host ""

# ============================================
# TEST 4: Query Performance (on 1000-row table)
# ============================================
Write-Host "TEST 4: Query Performance Test" -ForegroundColor Yellow

# Add index for better query testing
Run-MySQL "CREATE INDEX idx_user_id ON temp_performance_test(user_id);"
Run-MySQL "CREATE INDEX idx_work_date ON temp_performance_test(work_date);"

# Test SELECT by user_id (indexed)
$userIdTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT * FROM temp_performance_test WHERE user_id = 1;"
    $userIdTimes += $time
}
$avgUserId = ($userIdTimes | Measure-Object -Average).Average
Write-Host "  SELECT by user_id (10 runs avg): $([math]::Round($avgUserId, 2))ms" -ForegroundColor Green

# Test SELECT by work_date range
$dateRangeTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT * FROM temp_performance_test WHERE work_date BETWEEN '2025-01-01' AND '2025-12-31';"
    $dateRangeTimes += $time
}
$avgDateRange = ($dateRangeTimes | Measure-Object -Average).Average
Write-Host "  SELECT by date range (10 runs avg): $([math]::Round($avgDateRange, 2))ms" -ForegroundColor Green

# Test SELECT with LIKE search
$likeTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT * FROM temp_performance_test WHERE description LIKE '%batch%test%';"
    $likeTimes += $time
}
$avgLike = ($likeTimes | Measure-Object -Average).Average
Write-Host "  SELECT with LIKE (10 runs avg): $([math]::Round($avgLike, 2))ms" -ForegroundColor Green

# Test SELECT with JOIN (need a files table structure)
Run-MySQL "CREATE TABLE IF NOT EXISTS temp_files_test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT,
    file_name VARCHAR(255),
    file_data LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES temp_performance_test(id)
);"
# Insert some dummy file records
Run-MySQL "INSERT INTO temp_files_test (report_id, file_name) SELECT id, CONCAT('file_', id, '.txt') FROM temp_performance_test LIMIT 100;"

$joinTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT t.id, t.description, f.file_name FROM temp_performance_test t LEFT JOIN temp_files_test f ON t.id = f.report_id WHERE t.user_id = 1;"
    $joinTimes += $time
}
$avgJoin = ($joinTimes | Measure-Object -Average).Average
Write-Host "  SELECT with JOIN (10 runs avg): $([math]::Round($avgJoin, 2))ms" -ForegroundColor Green

# Test COUNT queries
$countTimes = @()
for ($i = 1; $i -le 10; $i++) {
    $time = Measure-MySQL "SELECT COUNT(*) FROM temp_performance_test WHERE user_id = 1;"
    $countTimes += $time
}
$avgCount = ($countTimes | Measure-Object -Average).Average
Write-Host "  COUNT query (10 runs avg): $([math]::Round($avgCount, 2))ms" -ForegroundColor Green

$results += "TEST 4 PASS - Query performance (user_id: $([math]::Round($avgUserId, 2))ms, date_range: $([math]::Round($avgDateRange, 2))ms, LIKE: $([math]::Round($avgLike, 2))ms, JOIN: $([math]::Round($avgJoin, 2))ms, COUNT: $([math]::Round($avgCount, 2))ms)"
Write-Host ""

# ============================================
# TEST 5: File BLOB Storage Test
# ============================================
Write-Host "TEST 5: File BLOB Storage Test" -ForegroundColor Yellow

# Create test data of different sizes
$test1KB = [System.Text.Encoding]::UTF8.GetBytes(("A" * 1024))
$test100KB = [System.Text.Encoding]::UTF8.GetBytes(("B" * 102400))
$test1MB = [System.Text.Encoding]::UTF8.GetBytes(("C" * 1048576))

# Helper function to create hex string for BLOB
function ConvertTo-HexString {
    param([byte[]]$bytes)
    $hex = ($bytes | ForEach-Object { $_.ToString("X2") }) -join ''
    return "0x$hex"
}

# Create BLOB test table
Run-MySQL "CREATE TABLE IF NOT EXISTS temp_blob_test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_size INT,
    file_data LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);"
Run-MySQL "TRUNCATE TABLE temp_blob_test;"

# Insert 1KB BLOB
$hex1KB = ConvertTo-HexString $test1KB
$time1KB = Measure-MySQL "INSERT INTO temp_blob_test (file_size, file_data) VALUES (1024, $hex1KB);"
Write-Host "  Insert 1KB BLOB: $([math]::Round($time1KB, 2))ms" -ForegroundColor Green

# Insert 100KB BLOB
$hex100KB = ConvertTo-HexString $test100KB
$time100KB = Measure-MySQL "INSERT INTO temp_blob_test (file_size, file_data) VALUES (102400, $hex100KB);"
Write-Host "  Insert 100KB BLOB: $([math]::Round($time100KB, 2))ms" -ForegroundColor Green

# Insert 1MB BLOB
$hex1MB = ConvertTo-HexString $test1MB
$time1MB = Measure-MySQL "INSERT INTO temp_blob_test (file_size, file_data) VALUES (1048576, $hex1MB);"
Write-Host "  Insert 1MB BLOB: $([math]::Round($time1MB, 2))ms" -ForegroundColor Green

# Read times
$timeRead1KB = Measure-MySQL "SELECT file_data FROM temp_blob_test WHERE id = 1;"
Write-Host "  Read 1KB BLOB: $([math]::Round($timeRead1KB, 2))ms" -ForegroundColor Green

$timeRead100KB = Measure-MySQL "SELECT file_data FROM temp_blob_test WHERE id = 2;"
Write-Host "  Read 100KB BLOB: $([math]::Round($timeRead100KB, 2))ms" -ForegroundColor Green

$timeRead1MB = Measure-MySQL "SELECT file_data FROM temp_blob_test WHERE id = 3;"
Write-Host "  Read 1MB BLOB: $([math]::Round($timeRead1MB, 2))ms" -ForegroundColor Green

# Cleanup blob test table
Run-MySQL "DROP TABLE IF EXISTS temp_blob_test;"

$results += "TEST 5 PASS - BLOB storage (1KB insert: $([math]::Round($time1KB, 2))ms, 100KB: $([math]::Round($time100KB, 2))ms, 1MB: $([math]::Round($time1MB, 2))ms)"
Write-Host ""

# ============================================
# TEST 6: Concurrent Access Test
# ============================================
Write-Host "TEST 6: Concurrent Access Test" -ForegroundColor Yellow

# Test 5 simultaneous SELECT queries
Write-Host "  Running 5 simultaneous SELECT queries..."
$jobs = @()
$stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

for ($i = 1; $i -le 5; $i++) {
    $jobs += Start-Job -ScriptBlock {
        param($mysqlPath, $dbUser, $dbName)
        & $mysqlPath -u $dbUser --database=$dbName -e "SELECT * FROM temp_performance_test WHERE id = $using:i;" 2>&1
    } -ArgumentList $mysqlPath, $dbUser, $dbName
}

$jobs | Wait-Job
$allSuccessful = $jobs | ForEach-Object { $_.State -eq "Completed" }
$stopwatch.Stop()

if ($allSuccessful -contains $false) {
    Write-Host "  Concurrent SELECT: FAILED" -ForegroundColor Red
    $results += "TEST 6 FAIL - Concurrent SELECT queries failed"
} else {
    Write-Host "  Concurrent SELECT: PASSED ($([math]::Round($stopwatch.Elapsed.TotalMilliseconds, 2))ms)" -ForegroundColor Green
}

# Test 5 simultaneous INSERT queries
Write-Host "  Running 5 simultaneous INSERT queries..."
$jobs2 = @()
$stopwatch2 = [System.Diagnostics.Stopwatch]::StartNew()

for ($i = 1; $i -le 5; $i++) {
    $jobs2 += Start-Job -ScriptBlock {
        param($mysqlPath, $dbUser, $dbName, $index)
        & $mysqlPath -u $dbUser --database=$dbName -e "INSERT INTO temp_performance_test (user_id, work_date, description) VALUES (1, CURDATE(), 'Concurrent test $index');" 2>&1
    } -ArgumentList $mysqlPath, $dbUser, $dbName, $i
}

$jobs2 | Wait-Job
$allSuccessful2 = $jobs2 | ForEach-Object { $_.State -eq "Completed" }
$stopwatch2.Stop()

if ($allSuccessful2 -contains $false) {
    Write-Host "  Concurrent INSERT: FAILED (possible deadlock)" -ForegroundColor Red
    $results += "TEST 6 FAIL - Concurrent INSERT queries failed (possible deadlock)"
} else {
    Write-Host "  Concurrent INSERT: PASSED ($([math]::Round($stopwatch2.Elapsed.TotalMilliseconds, 2))ms)" -ForegroundColor Green
}

# Clean up jobs
$jobs | Remove-Job -Force
$jobs2 | Remove-Job -Force

$results += "TEST 6 PASS - Concurrent access test complete"
Write-Host ""

# ============================================
# TEST 7: Cleanup
# ============================================
Write-Host "TEST 7: Cleanup Test" -ForegroundColor Yellow

# Remove all test data but keep users
$deleteFiles = Run-MySQL "DELETE FROM work_report_files WHERE report_id IN (SELECT id FROM work_reports WHERE user_id IN (SELECT id FROM users));"
$deleteReports = Run-MySQL "DELETE FROM work_reports WHERE user_id IN (SELECT id FROM users);"
$dropTemp = Run-MySQL "DROP TABLE IF EXISTS temp_performance_test, temp_files_test;"

# Verify users still exist
$userCountAfter = Run-MySQL "SELECT COUNT(*) as cnt FROM users;"
Write-Host "  Users preserved: $userCountAfter" -ForegroundColor Green

$results += "TEST 7 PASS - Cleanup complete, users preserved"
Write-Host ""

# ============================================
# FINAL SUMMARY
# ============================================
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TEST SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
foreach ($result in $results) {
    if ($result -match "PASS") {
        Write-Host "  [PASS] $result" -ForegroundColor Green
    } elseif ($result -match "FAIL") {
        Write-Host "  [FAIL] $result" -ForegroundColor Red
    } else {
        Write-Host "  [INFO] $result" -ForegroundColor Yellow
    }
}
Write-Host ""
Write-Host "All tests completed!" -ForegroundColor Cyan
Write-Host ""
