[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$StatePath = Join-Path $ProjectRoot 'storage\framework\local-pilot.json'

if (-not (Test-Path $StatePath)) {
    Write-Host 'Keeply local pilot is not running.'
    exit 0
}

$state = Get-Content $StatePath -Raw | ConvertFrom-Json
$stopped = 0

foreach ($entry in $state.processes.PSObject.Properties) {
    $process = Get-Process -Id $entry.Value -ErrorAction SilentlyContinue

    if ($process) {
        # Each recorded process is a cmd.exe wrapper; /T also stops its php.exe child.
        & taskkill.exe /PID $process.Id /T /F | Out-Null
        $stopped++
        Write-Host "Stopped $($entry.Name) process $($process.Id)."
    }
}

Remove-Item $StatePath -Force
Write-Host "Keeply local pilot stopped ($stopped active process(es))." -ForegroundColor Green
