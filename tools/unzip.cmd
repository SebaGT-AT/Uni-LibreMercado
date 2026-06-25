@echo off
setlocal

if "%~1"=="-Z1" (
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "Add-Type -AssemblyName System.IO.Compression.FileSystem; $archive = [System.IO.Compression.ZipFile]::OpenRead('%~2'); try { foreach ($entry in $archive.Entries) { Write-Output $entry.FullName } } finally { $archive.Dispose() }"
  exit /b %errorlevel%
)

if "%~1"=="-p" (
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "Add-Type -AssemblyName System.IO.Compression.FileSystem; $archive = [System.IO.Compression.ZipFile]::OpenRead('%~2'); try { $entry = $archive.GetEntry('%~3'); if (-not $entry) { throw 'Entry not found: %~3' }; $stream = $entry.Open(); try { $stream.CopyTo([Console]::OpenStandardOutput()) } finally { $stream.Dispose() } } finally { $archive.Dispose() }"
  exit /b %errorlevel%
)

echo Unsupported unzip arguments: %*
exit /b 1
