[CmdletBinding()]
param(
    [ValidateRange(1024, 65535)]
    [int] $Port = 8000
)

$ErrorActionPreference = 'Stop'

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$StateDirectory = Join-Path $ProjectRoot 'storage\framework'
$StatePath = Join-Path $StateDirectory 'local-pilot.json'
$LogDirectory = Join-Path $ProjectRoot 'storage\logs'
$DatabasePath = Join-Path $ProjectRoot 'database\database.sqlite'

function Resolve-PhpExecutable {
    $command = Get-Command php -ErrorAction SilentlyContinue

    if ($command) {
        return $command.Source
    }

    $packages = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'
    $candidate = Get-ChildItem $packages -Directory -Filter 'PHP.PHP.8.3_*' -ErrorAction SilentlyContinue |
        ForEach-Object { Join-Path $_.FullName 'php.exe' } |
        Where-Object { Test-Path $_ } |
        Select-Object -First 1

    if (-not $candidate) {
        throw 'PHP 8.3 was not found. Install PHP 8.3 or add php.exe to PATH.'
    }

    return $candidate
}

if (Test-Path $StatePath) {
    $existingState = Get-Content $StatePath -Raw | ConvertFrom-Json
    $activeProcesses = @($existingState.processes.PSObject.Properties.Value | Where-Object {
        Get-Process -Id $_ -ErrorAction SilentlyContinue
    })

    if ($activeProcesses.Count -gt 0) {
        throw "Keeply's local pilot is already running. Use scripts\stop-local-pilot.ps1 first."
    }

    Remove-Item $StatePath -Force
}

$listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($listener) {
    throw "Port $Port is already in use. Start with another port, for example: .\scripts\start-local-pilot.ps1 -Port 8010"
}

$Php = Resolve-PhpExecutable
$createdEnvironment = -not (Test-Path (Join-Path $ProjectRoot '.env'))

if ($createdEnvironment) {
    Copy-Item (Join-Path $ProjectRoot '.env.example') (Join-Path $ProjectRoot '.env')
}

if (-not (Test-Path $DatabasePath)) {
    New-Item $DatabasePath -ItemType File | Out-Null
}

New-Item $StateDirectory -ItemType Directory -Force | Out-Null
New-Item $LogDirectory -ItemType Directory -Force | Out-Null

$env:APP_ENV = 'local'
$env:APP_DEBUG = 'false'
$env:APP_URL = "http://127.0.0.1:$Port"
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $DatabasePath
$env:FILESYSTEM_DISK = 'local'
$env:QUEUE_CONNECTION = 'database'
$env:MAIL_MAILER = 'log'
$env:SESSION_DRIVER = 'database'
$env:CACHE_STORE = 'database'

Push-Location $ProjectRoot

try {
    if ($createdEnvironment) {
        & $Php artisan key:generate --force
        if ($LASTEXITCODE -ne 0) {
            throw 'Could not generate the local application key.'
        }
    }

    & $Php artisan config:clear
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not clear cached configuration.'
    }

    & $Php artisan migrate --force
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not apply the local pilot migrations.'
    }

    # Processes are created through WMI so they are not children of this
    # console: closing the window that ran this script no longer kills Keeply.
    # WMI processes do not inherit this session's variables, so the local
    # pilot settings are passed on each command line.
    $settings = @(
        'APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_CONNECTION', 'DB_DATABASE', 'FILESYSTEM_DISK',
        'QUEUE_CONNECTION', 'MAIL_MAILER', 'SESSION_DRIVER', 'CACHE_STORE'
    ) | ForEach-Object { "set `"$_=$((Get-Item "env:$_").Value)`"" }
    $prefix = $settings -join ' && '

    function Start-Detached([string] $Name, [string] $Arguments) {
        $out = Join-Path $LogDirectory "pilot-$Name.log"
        $err = Join-Path $LogDirectory "pilot-$Name-error.log"
        $command = "cmd.exe /d /c `"$prefix && `"$Php`" artisan $Arguments 1>`"$out`" 2>`"$err`"`""
        $result = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
            CommandLine = $command
            CurrentDirectory = $ProjectRoot
        }

        if ($result.ReturnValue -ne 0) {
            throw "Could not start the $Name process (WMI code $($result.ReturnValue))."
        }

        return Get-Process -Id $result.ProcessId
    }

    $web = Start-Detached 'web' "serve --host=127.0.0.1 --port=$Port"
    $queue = Start-Detached 'queue' 'queue:work --queue=emails,default --sleep=3 --tries=3 --timeout=120'
    $scheduler = Start-Detached 'scheduler' 'schedule:work'

    $state = [ordered]@{
        started_at = (Get-Date).ToString('o')
        url = "http://127.0.0.1:$Port"
        processes = [ordered]@{
            web = $web.Id
            queue = $queue.Id
            scheduler = $scheduler.Id
        }
    }
    $state | ConvertTo-Json | Set-Content $StatePath -Encoding UTF8

    Start-Sleep -Seconds 2

    foreach ($process in @($web, $queue, $scheduler)) {
        if ($process.HasExited) {
            throw "A local pilot process exited during startup. Check storage\logs\pilot-*-error.log."
        }
    }

    $response = Invoke-WebRequest -UseBasicParsing "http://127.0.0.1:$Port/"
    if ($response.StatusCode -ne 200 -or $response.Content -notmatch 'Keeply') {
        throw 'The Keeply landing page did not pass the local HTTP smoke check.'
    }

    Write-Host ''
    Write-Host 'Keeply local pilot is running.' -ForegroundColor Green
    Write-Host "Open: http://127.0.0.1:$Port"
    Write-Host 'Web, queue worker, and scheduler: healthy'
    Write-Host 'Mail delivery: storage\logs\laravel.log'
    Write-Host 'Stop: .\scripts\stop-local-pilot.ps1'
} catch {
    foreach ($process in @($web, $queue, $scheduler)) {
        if ($process -and -not $process.HasExited) {
            Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
        }
    }

    Remove-Item $StatePath -Force -ErrorAction SilentlyContinue
    throw
} finally {
    Pop-Location
}
