param([string]$Database='quanly_doan')
$ErrorActionPreference='Stop'
$phpPath=(Get-Command php).Source
$loaded=& $phpPath -m
$extensions=Join-Path (Split-Path $phpPath -Parent) 'ext'
$phpArgs=@()
foreach($name in @('pdo_mysql','pdo_sqlite','mbstring')) {
    if($loaded -notcontains $name) {
        $dll=Join-Path $extensions "php_$name.dll"
        if(!(Test-Path -LiteralPath $dll)) { throw "Enable PHP extension $name first." }
        $phpArgs+=@('-d',"extension=$dll")
    }
}
& $phpPath @phpArgs (Join-Path $PSScriptRoot 'transfer-to-mysql.php') $Database
if($LASTEXITCODE -ne 0) { throw 'Transfer was not completed. See the diagnostic above.' }

