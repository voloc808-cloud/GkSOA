param([switch]$SQLite)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpPath = (Get-Command php).Source
$extensionRoot = Join-Path (Split-Path $phpPath -Parent) 'ext'
$phpArgs = @()
$loadedModules = & $phpPath -m
# Load available database drivers so the backend .env can select MySQL or SQLite.
foreach ($extensionName in @('mbstring', 'fileinfo', 'pdo_sqlite', 'pdo_mysql')) {
    $extensionPath = Join-Path $extensionRoot "php_$extensionName.dll"
    if ((Test-Path -LiteralPath $extensionPath) -and $loadedModules -notcontains $extensionName) { $phpArgs += @('-d', "extension=$extensionPath") }
}
foreach ($directory in @('backend','gateway')) {
    if (!(Test-Path -LiteralPath (Join-Path $projectRoot "$directory/vendor/autoload.php"))) { throw "Run composer install in $directory first." }
    $envFile = Join-Path $projectRoot "$directory/.env"
    if (!(Test-Path -LiteralPath $envFile)) { Copy-Item -LiteralPath (Join-Path $projectRoot "$directory/.env.example") -Destination $envFile }
}
$processes=@()
$preservedEnvironment=@{}
foreach ($key in @('DB_CONNECTION','DB_DATABASE','SERVICE_MODULE','STUDENT_SERVICE_URL','TOPIC_SERVICE_URL','REGISTRATION_SERVICE_URL','FRONTEND_URL')) {
    $preservedEnvironment[$key]=[Environment]::GetEnvironmentVariable($key,'Process')
}
try {
if ($SQLite) {
    $sqlitePath = Join-Path $projectRoot 'backend/database/demo.sqlite'
    if (!(Test-Path -LiteralPath $sqlitePath)) { New-Item -ItemType File -Path $sqlitePath | Out-Null }
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $sqlitePath
}
# Verify credentials before making schema changes or starting servers.
& $phpPath @phpArgs (Join-Path $projectRoot 'scripts/check-database.php')
if ($LASTEXITCODE -ne 0) { throw 'Database connection check failed. See the diagnostic above. For a separate SQLite demo, run ./scripts/start.ps1 -SQLite.' }
# Migrate/seed once before opening the service processes.
& $phpPath @phpArgs (Join-Path $projectRoot 'backend/artisan') migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Migration failed. See the Artisan error above; database connectivity has already been checked.' }
& $phpPath @phpArgs (Join-Path $projectRoot 'backend/artisan') db:seed --force
if ($LASTEXITCODE -ne 0) { throw 'Seeding failed.' }
$launches = @(
    @{Folder='backend'; Module='sinhvien'; Port=8002},
    @{Folder='backend'; Module='detai'; Port=8003},
    @{Folder='backend'; Module='dangky'; Port=8004},
    @{Folder='gateway'; Module='all'; Port=8000},
    @{Folder='frontend'; Module='all'; Port=8080}
)
$env:STUDENT_SERVICE_URL='http://127.0.0.1:8002'
$env:TOPIC_SERVICE_URL='http://127.0.0.1:8003'
$env:REGISTRATION_SERVICE_URL='http://127.0.0.1:8004'
$env:FRONTEND_URL='http://127.0.0.1:8080'
foreach ($launch in $launches) {
    $env:SERVICE_MODULE=$launch.Module
    $port=$launch.Port
    if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) { throw "Port $port is already occupied." }
}
    foreach ($launch in $launches) {
        $env:SERVICE_MODULE=$launch.Module
        $workingPath=Join-Path $projectRoot $launch.Folder
        $arguments=$phpArgs + @('-S',"127.0.0.1:$($launch.Port)",'-t',$(if ($launch.Folder -eq 'frontend') {'.'} else {'public'}))
        # Quote paths with spaces for Start-Process on Windows.
        $argumentLine=($arguments | ForEach-Object { '"' + $_ + '"' }) -join ' '
        $processes += Start-Process -FilePath $phpPath -ArgumentList $argumentLine -WorkingDirectory $workingPath -WindowStyle Hidden -PassThru
    }
    Write-Host 'Frontend: http://127.0.0.1:8080 | Gateway: http://127.0.0.1:8000/api'
    Write-Host 'Press Ctrl+C to stop the processes started by this script.'
    while ($true) {
        Start-Sleep -Seconds 2
        foreach ($process in $processes) { if ($process.HasExited) { throw "Process $($process.Id) stopped unexpectedly." } }
    }
} finally {
    foreach ($process in $processes) { if (!$process.HasExited) { Stop-Process -Id $process.Id } }
    foreach ($key in $preservedEnvironment.Keys) { [Environment]::SetEnvironmentVariable($key,$preservedEnvironment[$key],'Process') }
}

