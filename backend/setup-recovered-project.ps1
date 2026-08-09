[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

Write-Host ''
Write-Host 'BloodCare backend recovery setup' -ForegroundColor Cyan
Write-Host "Project: $projectRoot"
Write-Host ''

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP was not found. Open this project from a terminal where Laravel Herd PHP is available.'
}

if (-not (Test-Path 'vendor/autoload.php')) {
    if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
        throw 'The vendor folder is missing and Composer was not found. Install Composer, then run this script again.'
    }

    Write-Host 'Installing the locked Composer dependencies...' -ForegroundColor Yellow
    composer install --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) {
        throw 'Composer install failed.'
    }
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from the safe example.' -ForegroundColor Green
}

$appKeyLine = Get-Content '.env' | Where-Object { $_ -match '^APP_KEY=' } | Select-Object -First 1
if (-not $appKeyLine -or $appKeyLine -eq 'APP_KEY=') {
    Write-Host 'Generating a fresh Laravel application key...' -ForegroundColor Yellow
    php artisan key:generate --force
    if ($LASTEXITCODE -ne 0) {
        throw 'Laravel could not generate APP_KEY.'
    }
}

Write-Host 'Clearing old generated configuration and views...' -ForegroundColor Yellow
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

if (-not (Test-Path 'public/storage')) {
    php artisan storage:link
}

Write-Host ''
Write-Host 'Local application recovery is complete.' -ForegroundColor Green
Write-Host 'Before starting Laravel, open .env and set DB_PASSWORD to your MySQL blood_app password.' -ForegroundColor White
Write-Host 'Confirm that MySQL is running on 127.0.0.1:3307, then run:' -ForegroundColor White
Write-Host '  php artisan migrate:status' -ForegroundColor Cyan
Write-Host '  php artisan migrate' -ForegroundColor Cyan
Write-Host '  php artisan test' -ForegroundColor Cyan
Write-Host '  php artisan serve --host=127.0.0.1 --port=8001' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Do not run migrate:fresh: it deletes existing database records.' -ForegroundColor Yellow
