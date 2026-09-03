# ======================================================================
# deploy.ps1 - Windows (XAMPP + Apache) auto-deploy via git pull
# ======================================================================
#
# GitHub-аас өөрчлөлт байвал татаж, composer install, cache цэвэрлэх.
# (Migration-ийг Dashboard-аас гараар apply хийнэ - deploy auto-apply хийхгүй.)
# Webhook (public_html\deploy-hook.php) эсвэл Scheduled Task-аар ажиллуулна.
#
# Гараар: powershell -ExecutionPolicy Bypass -File scripts\deploy.ps1
# Хүчээр (өөрчлөлтгүй ч бүгдийг ажиллуулах): ... deploy.ps1 -Force
#
# git/php/composer-ийг PATH-д байхгүй байсан ч өөрөө хайж олно
# (Scheduled Task-ийн орчинд PATH хязгаарлагдмал байдаг тул чухал).
# .env, public_html/public/, protected/ нь git-д ороогүй тул хөндөгдөхгүй.
# ======================================================================

param([switch]$Force)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$log = Join-Path $root 'scripts\deploy.log'

# Log rotation: deploy.log хэт томрохоос сэргийлнэ. 512KB-аас хэтэрвэл зөвхөн
# сүүлийн 500 мөрийг үлдээж таслана. Add-Content append хийдэг тул энэ
# таслалтгүй бол файл хязгааргүй өснө.
if ((Test-Path $log) -and ((Get-Item $log).Length -gt 512KB)) {
    try {
        $tail = Get-Content $log -Tail 500 -ErrorAction Stop
        Set-Content -Path $log -Value $tail -Encoding utf8
    } catch {}
}

function Log($m) {
    $line = ((Get-Date).ToString('yyyy-MM-dd HH:mm:ss') + "  " + $m)
    Write-Host $line
    Add-Content -Path $log -Value $line -Encoding utf8
}

# --- хэрэгслүүдийг олох -------------------------------------------------
function Find-Exe($name, $candidates) {
    $c = (Get-Command $name -ErrorAction SilentlyContinue).Source
    if ($c) { return $c }
    foreach ($p in $candidates) {
        $f = Get-ChildItem $p -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($f) { return $f.FullName }
    }
    return $null
}

$git = Find-Exe 'git' @(
    "$env:ProgramFiles\Git\cmd\git.exe",
    "${env:ProgramFiles(x86)}\Git\cmd\git.exe",
    "$env:LOCALAPPDATA\GitHubDesktop\app-*\resources\app\git\cmd\git.exe"
)
$php = Find-Exe 'php' @("C:\xampp\php\php.exe")

# composer: composer.bat (PATH/Setup/XAMPP), эсвэл repo доторх composer.phar
$composerBat = Find-Exe 'composer' @(
    "C:\ProgramData\ComposerSetup\bin\composer.bat",
    "C:\xampp\php\composer.bat"
)
$composerPhar = if (Test-Path (Join-Path $root 'composer.phar')) { Join-Path $root 'composer.phar' } else { $null }

if (-not $git) { Log "ERROR: git олдсонгүй. Git for Windows суулга."; exit 1 }
if (-not $php) { Log "ERROR: php олдсонгүй (C:\xampp\php\php.exe)."; exit 1 }

# git "dubious ownership": webhook нь Apache-аар (NT AUTHORITY\SYSTEM) ажиллахад
# repo нь өөр эзэмшигчтэй тул git татгалздаг. `git config --global` нь SYSTEM-д
# найдваргүй тул дуудлага БҮРД `-c safe.directory=*`-ийг inline дамжуулна
# (config файлаас хамаарахгүй, баталгаатай).
$gitSafe = @('-c', 'safe.directory=*')

# Итгэмжлэл асуухыг ХОРИГЛОНО. Webhook нь SYSTEM-ээр, дэлгэцгүй ажилладаг тул
# credential prompt гарвал хариулах хүн байхгүй бөгөөд git үүрд өлгөөтэй үлдэнэ.
# Өлгөөтэй үлдсэн git процесс нь repo-гийн pack файлыг memory-map хийж барьдаг
# тул хавтас нь устгах ч, шинэчлэх ч боломжгүй болно. Токен хүчингүй болбол
# шууд алдаа өгч, лог руу бичээд гарах ёстой.
#
# Critical (English): the webhook runs as SYSTEM with no desktop, so a credential
# prompt hangs git forever instead of failing. Both switches must stay together -
# GIT_TERMINAL_PROMPT stops git's own prompt, credential.interactive stops the
# Git Credential Manager GUI. Never remove one without the other.
$env:GIT_TERMINAL_PROMPT = '0'
$gitSafe += @('-c', 'credential.interactive=false')

# --- өөрчлөлт шалгах ---------------------------------------------------
& $git @gitSafe fetch origin --quiet 2>$null
$local  = (& $git @gitSafe rev-parse HEAD 2>$null)
$remote = (& $git @gitSafe rev-parse '@{u}' 2>$null)
if (-not $local -or -not $remote) {
    Log "ERROR: git rev-parse амжилтгүй (repo/ownership/auth шалга)"
    exit 1
}
$local  = "$local".Trim()
$remote = "$remote".Trim()

if (($local -eq $remote) -and -not $Force) {
    # Өөрчлөлтгүй - чимээгүй гарна (polling-д log дүүргэхгүй)
    exit 0
}

Log "==> Deploy эхэллээ ($root). local=$($local.Substring(0,7)) remote=$($remote.Substring(0,7))"

# Production бол deploy target тул remote-той яг тэнцүүлнэ. pull --ff-only нь
# local tracked өөрчлөлт (жишээ composer.lock-ийн platform/CRLF зөрүү) дээр
# зогсдог тул reset --hard ашиглана. fetch дээр хийгдсэн, $remote тооцоологдсон.
# .env, protected/, public_html/public/ нь git-д ороогүй тул хөндөгдөхгүй.
Log "==> git reset --hard remote ($($remote.Substring(0,7)))"
& $git @gitSafe reset --hard $remote
if ($LASTEXITCODE -ne 0) { Log "ERROR: git reset --hard амжилтгүй (repo/ownership шалга)"; exit 1 }

# --- autoload-ыг ЭХЛЭЭД шинэчлэх ---------------------------------------
# git reset шинэ файлуудыг дискэнд тавьдаг ч vendor/composer/autoload_psr4.php
# нь хуучнаараа үлддэг. Шинэ namespace нэмсэн deploy дээр энэ хоёрын хооронд
# орж ирсэн хүсэлт "Class ... not found" гэж унадаг. composer install бүтэн
# хэдэн секунд явдаг; dump-autoload нь 1 секунд тул цонхыг богиносгоно.
#
# Critical (English): run dump-autoload BEFORE composer install. Between the
# git reset and the autoloader refresh, requests hitting a newly added
# namespace fatal with "Class not found". Do not reorder these two steps.
if ($composerBat -or $composerPhar) {
    Log "==> autoload шинэчлэх (богино цонх)"
    if ($composerBat) {
        & $composerBat dump-autoload --no-dev --optimize --no-interaction --working-dir="$root"
    } else {
        & $php $composerPhar dump-autoload --no-dev --optimize --no-interaction --working-dir="$root"
    }
}

# --- composer install --------------------------------------------------
Log "==> composer install (production)"
if ($composerBat) {
    & $composerBat install --no-dev --optimize-autoloader --no-interaction --working-dir="$root"
} elseif ($composerPhar) {
    & $php $composerPhar install --no-dev --optimize-autoloader --no-interaction --working-dir="$root"
} else {
    Log "ERROR: composer олдсонгүй. composer.phar-г repo root-д тавь эсвэл Composer суулга."
}

# --- migration ---------------------------------------------------------
# Migration-уудыг Dashboard (/dashboard/migrations -> Apply pending)-аас гараар
# ажиллуулна. Deploy автоматаар apply хийхгүй (хяналт хэрэглэгчид).

# --- cache цэвэрлэх ----------------------------------------------------
# CacheService нь дээд түвшний cache/ хавтаст {sha1}.cache файл бичдэг.
# Deploy-ийн дараа хуучирсан menu/орчуулга/settings/RBAC болон портал баримтын
# (portal_doc.*) cache-ийг арилгахын тулд cache/*.cache-г устгана.
# .htaccess / .gitignore guard файлууд хөндөгдөхгүй (зөвхөн *.cache filter).
Log "==> cache цэвэрлэх (cache\)"
if (Test-Path "$root\cache") {
    Get-ChildItem "$root\cache" -Filter *.cache -Recurse -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
}

Log "==> Deploy дууслаа OK"
