param(
    [switch]$SkipTests,
    [switch]$Installer
)

$ErrorActionPreference = 'Stop'
$solutionRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$solution = Join-Path $solutionRoot 'TikShopPrinterAgent.sln'
$project = Join-Path $solutionRoot 'src\TikShopPrinterAgent\TikShopPrinterAgent.csproj'
$publishDirectory = Join-Path $solutionRoot 'artifacts\publish\win-x64'

if (-not $SkipTests) {
    dotnet test $solution --configuration Release
}

dotnet publish $project `
    --configuration Release `
    --runtime win-x64 `
    --self-contained true `
    --output $publishDirectory `
    -p:PublishSingleFile=true `
    -p:IncludeNativeLibrariesForSelfExtract=true

Write-Host "Executable: $publishDirectory\TikShopPrinterAgent.exe"

if ($Installer) {
    $compiler = Join-Path ${env:ProgramFiles(x86)} 'Inno Setup 6\ISCC.exe'
    if (-not (Test-Path $compiler)) {
        throw 'Inno Setup 6 no está instalado.'
    }

    & $compiler (Join-Path $solutionRoot 'installer\TikShopPrinterAgent.iss')
}
