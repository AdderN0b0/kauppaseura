[CmdletBinding()]
param(
    [string]$Message = "Update generated GitHub Pages site"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$SourceRoot = [System.IO.Path]::GetFullPath((Split-Path -Parent $MyInvocation.MyCommand.Path))
$Exporter = Join-Path $SourceRoot "tools\export-static.php"
$Validator = Join-Path $SourceRoot "tools\validate-static.php"
$SiteConfig = Join-Path $SourceRoot "tools\site-config.json"
$Deliverables = Join-Path $SourceRoot "deliverables"
$BuildDir = Join-Path $Deliverables "lakeuden-kauppaseura-build"
$PublishDir = Join-Path $Deliverables "lakeuden-kauppaseura-offline"
$WordPressUrl = "http://lakeuden-kauppaseura.local/"
$ExpectedBranch = "gh-pages"

if (-not (Test-Path -LiteralPath $SiteConfig -PathType Leaf)) {
    throw "Missing public site configuration: '$SiteConfig'."
}

$PublicSiteUrl = (Get-Content -LiteralPath $SiteConfig -Raw | ConvertFrom-Json).productionUrl
if (-not [Uri]::IsWellFormedUriString($PublicSiteUrl, [UriKind]::Absolute)) {
    throw "tools/site-config.json must contain a valid productionUrl."
}

function Get-FullPath {
    param([Parameter(Mandatory = $true)][string]$Path)

    return [System.IO.Path]::GetFullPath($Path)
}

function Assert-Within {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Parent
    )

    $resolvedParent = (Get-FullPath $Parent).TrimEnd(
        [System.IO.Path]::DirectorySeparatorChar,
        [System.IO.Path]::AltDirectorySeparatorChar
    )
    $resolvedPath = Get-FullPath $Path
    $parentPrefix = $resolvedParent + [System.IO.Path]::DirectorySeparatorChar

    if (
        $resolvedPath -ne $resolvedParent -and
        -not $resolvedPath.StartsWith($parentPrefix, [System.StringComparison]::OrdinalIgnoreCase)
    ) {
        throw "Refusing to operate outside '$resolvedParent': '$resolvedPath'."
    }
}

function Get-GitValue {
    param(
        [Parameter(Mandatory = $true)][string]$Repository,
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )

    $value = & git -C $Repository @Arguments 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "Git command failed in '$Repository': git $($Arguments -join ' ')"
    }

    return (($value | Out-String).Trim())
}

function Get-OptionalGitValue {
    param(
        [Parameter(Mandatory = $true)][string]$Repository,
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )

    $value = & git -C $Repository @Arguments 2>$null
    if ($LASTEXITCODE -notin @(0, 1)) {
        throw "Git command failed in '$Repository': git $($Arguments -join ' ')"
    }

    return (($value | Out-String).Trim())
}

function Assert-CleanGitWorktree {
    param(
        [Parameter(Mandatory = $true)][string]$Repository,
        [Parameter(Mandatory = $true)][string]$Label
    )

    $changes = Get-GitValue -Repository $Repository -Arguments @(
        "status",
        "--porcelain",
        "--untracked-files=normal"
    )
    if ($changes) {
        throw "$Label has uncommitted changes. Commit, stash, or remove them before publishing.`n$changes"
    }
}

function Normalize-RemoteUrl {
    param([Parameter(Mandatory = $true)][string]$Url)

    $normalized = $Url.Trim() -replace "\\", "/"
    $normalized = $normalized -replace "^git@github\.com:", "https://github.com/"
    $normalized = $normalized -replace "\.git$", ""
    return $normalized.ToLowerInvariant()
}

$PhpCommand = Get-Command php -ErrorAction SilentlyContinue
$Php = if ($PhpCommand) {
    $PhpCommand.Source
} else {
    $phpSearchRoots = @(
        (Join-Path $env:APPDATA "Local\lightning-services")
        (Join-Path $env:LOCALAPPDATA "Programs\Local\resources\extraResources\lightning-services")
    ) | Where-Object { Test-Path -LiteralPath $_ -PathType Container }

    $phpCandidates = @(
        foreach ($phpSearchRoot in $phpSearchRoots) {
            Get-ChildItem -LiteralPath $phpSearchRoot -Filter "php.exe" -File -Recurse -ErrorAction SilentlyContinue
        }
    )
    $preferredPhp = $phpCandidates |
        Where-Object { $_.FullName -match "[\\/]win64[\\/]php\.exe$" } |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1
    if (-not $preferredPhp) {
        $preferredPhp = $phpCandidates |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
    }
    if ($preferredPhp) {
        $preferredPhp.FullName
    }
}

foreach ($requiredFile in @($Exporter, $Validator)) {
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw "Required publication tool not found: $requiredFile"
    }
}

if (-not $Php) {
    throw "PHP was not found. Start the site in Local or install PHP, then try again."
}

if (-not (Test-Path -LiteralPath (Join-Path $SourceRoot ".git"))) {
    throw "The source root is not a Git working tree: $SourceRoot"
}

if (-not (Test-Path -LiteralPath (Join-Path $PublishDir ".git"))) {
    throw "The gh-pages publication worktree is missing: $PublishDir"
}

Assert-Within -Path $BuildDir -Parent $Deliverables
Assert-Within -Path $PublishDir -Parent $Deliverables

$sourceBranch = Get-GitValue -Repository $SourceRoot -Arguments @(
    "branch",
    "--show-current"
)
if ($sourceBranch -ne "main") {
    throw "Publication must run from source branch 'main'; current branch is '$sourceBranch'."
}

$publicationBranch = Get-GitValue -Repository $PublishDir -Arguments @(
    "branch",
    "--show-current"
)
if ($publicationBranch -ne $ExpectedBranch) {
    throw "Publication checkout must be on '$ExpectedBranch'; current branch is '$publicationBranch'."
}

$sourceRemote = Normalize-RemoteUrl (
    Get-GitValue -Repository $SourceRoot -Arguments @("remote", "get-url", "origin")
)
$publicationRemote = Normalize-RemoteUrl (
    Get-GitValue -Repository $PublishDir -Arguments @("remote", "get-url", "origin")
)
if ($sourceRemote -ne $publicationRemote) {
    throw "Source and publication checkouts use different origin remotes."
}

Assert-CleanGitWorktree -Repository $SourceRoot -Label "Source main"
Assert-CleanGitWorktree -Repository $PublishDir -Label "gh-pages publication checkout"

try {
    $response = Invoke-WebRequest -Uri $WordPressUrl -UseBasicParsing -TimeoutSec 15
    if ([int]$response.StatusCode -lt 200 -or [int]$response.StatusCode -ge 400) {
        throw "HTTP $($response.StatusCode)"
    }
}
catch {
    throw "WordPress is unavailable at $WordPressUrl Start the site in Local and try again."
}

Write-Host "Building static export..."
& $Php $Exporter $BuildDir
if ($LASTEXITCODE -ne 0) {
    throw "Static export failed. The gh-pages checkout was not changed."
}

if (-not (Test-Path -LiteralPath (Join-Path $BuildDir "index.html") -PathType Leaf)) {
    throw "Static export did not create index.html. The gh-pages checkout was not changed."
}

Write-Host "Validating generated build..."
& $Php $Validator $BuildDir
if ($LASTEXITCODE -ne 0) {
    throw "Static validation failed. The gh-pages checkout was not changed."
}

Write-Host "Copying validated output into the gh-pages checkout..."
Get-ChildItem -LiteralPath $PublishDir -Force |
    Where-Object { $_.Name -ne ".git" } |
    ForEach-Object {
        Assert-Within -Path $_.FullName -Parent $PublishDir
        Remove-Item -LiteralPath $_.FullName -Recurse -Force
    }

Get-ChildItem -LiteralPath $BuildDir -Force |
    Copy-Item -Destination $PublishDir -Recurse -Force

Write-Host "Validating publication checkout..."
& $Php $Validator $PublishDir
if ($LASTEXITCODE -ne 0) {
    throw "The copied gh-pages output failed validation. Nothing was committed or pushed."
}

$gitName = Get-OptionalGitValue -Repository $PublishDir -Arguments @(
    "config",
    "--get",
    "user.name"
)
$gitEmail = Get-OptionalGitValue -Repository $PublishDir -Arguments @(
    "config",
    "--get",
    "user.email"
)
if (-not $gitName -or -not $gitEmail) {
    throw "Git user.name and user.email must be configured in the gh-pages checkout before publishing."
}

& git -C $PublishDir add -A
if ($LASTEXITCODE -ne 0) {
    throw "Could not stage generated gh-pages output."
}

$changes = Get-GitValue -Repository $PublishDir -Arguments @(
    "status",
    "--porcelain"
)
if (-not $changes) {
    Write-Host "No generated changes to publish."
    exit 0
}

& git -C $PublishDir commit -m $Message
if ($LASTEXITCODE -ne 0) {
    throw "Git commit failed. Nothing was pushed."
}

& git -C $PublishDir push origin $ExpectedBranch
if ($LASTEXITCODE -ne 0) {
    throw "Git push failed. The generated commit remains local in the gh-pages checkout."
}

Write-Host ""
Write-Host "Published validated output:"
Write-Host $PublicSiteUrl
