param([string]$PhpDirectory = 'C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64')
$ErrorActionPreference = 'Stop'
if (-not (Test-Path -LiteralPath (Join-Path $PhpDirectory 'php.exe'))) { throw 'PHP directory is unavailable.' }
$env:Path = $PhpDirectory + ';' + $env:Path
Set-Location -LiteralPath $PSScriptRoot
composer run dev
