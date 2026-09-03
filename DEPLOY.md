# Deploy - codesaur.net (Windows + XAMPP + Apache + MySQL)

> codesaur.net порталын production серверийн тохиргоо ба авто-deploy гарын авлага.
> Сервер: Windows + XAMPP (Apache, MySQL). Төслийн хавтас: **`C:\xampp\htdocs\codesaur.net`**.
> Deploy механизм: GitHub push -> webhook -> сервер дээр `git pull`.

## 1. Серверийн урьдчилсан шаардлага

| Хэрэгсэл | Тайлбар |
|----------|---------|
| XAMPP | PHP 8.2+ (`ext-gd`, `ext-intl`, `ext-pdo_mysql`, `ext-curl`, `ext-mbstring`) + Apache + MySQL |
| Composer | `composer --version` ажиллах ёстой (`C:\ProgramData\ComposerSetup\bin\composer.bat` эсвэл `C:\xampp\php\composer.bat` - deploy script хоёуланг нь өөрөө олно) |
| Git | Git for Windows (`C:\Program Files\Git\cmd\git.exe`) эсвэл GitHub Desktop - deploy script хоёуланг нь өөрөө олно |
| SSL сертификат | Энэ серверийн Apache SSL хийхгүй - TLS-ийг урд талын nginx reverse proxy terminate хийж, энэ рүү энгийн http-ээр дамжуулна (7-р хэсэг) |

## 2. Эх кодыг татах

```powershell
cd C:\xampp\htdocs
git clone https://github.com/<OWNER>/codesaur.net.git codesaur.net
```

(эсвэл GitHub Desktop: File -> Clone repository -> `C:\xampp\htdocs\codesaur.net`)

## 3. `.env` тохируулах

`docs\conf.example\.env.example`-ийг `.env` болгон хуулж production утгууд тавина:

```
CODESAUR_APP_ENV=production

RAPTOR_DB_DRIVER=mysql
RAPTOR_DB_HOST=localhost
RAPTOR_DB_NAME=codesaur
RAPTOR_DB_USERNAME=codesaur
RAPTOR_DB_PASSWORD=<нууц үг>
RAPTOR_DB_CHARSET=utf8mb4
RAPTOR_DB_COLLATION=utf8mb4_unicode_ci

RAPTOR_JWT_SECRET=<php -r "echo bin2hex(random_bytes(32));">
RAPTOR_WAF_BODY_ENCODING=true

# Авто-deploy webhook (8-р хэсэг)
RAPTOR_DEPLOY_SECRET=<php -r "echo bin2hex(random_bytes(24));">
```

`.env` нь git-д ороогүй тул deploy бүрт хөндөгдөхгүй.

## 4. Хамаарал суулгах

```powershell
cd C:\xampp\htdocs\codesaur.net
composer install --no-dev --optimize-autoloader --no-interaction
```

`composer.lock` git-д орсон тул яг локалд туршсан `codesaur/*` хувилбарууд суулгагдана.
Портал дээрх баримт бичиг (`/docs/...`) нь `vendor/codesaur/*/docs/` markdown-оос
рендерлэгддэг тул lock файл нь сайтад харагдах баримтын хувилбарыг ч тогтооно.
Багцуудын баримтыг шинэчлэх бол локалд `composer update codesaur/*` хийж lock-ийг commit хийнэ.

## 5. Өгөгдлийн сан

```sql
CREATE DATABASE codesaur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'codesaur'@'localhost' IDENTIFIED BY '<нууц үг>';
GRANT ALL PRIVILEGES ON codesaur.* TO 'codesaur'@'localhost';
FLUSH PRIVILEGES;
```

Хүснэгт болон анхны seed өгөгдөл (хэл, орчуулга, эрх, цэс, admin хэрэглэгч) сайтыг
анх нээхэд Raptor өөрөө үүсгэнэ - гараар хүснэгт үүсгэхгүй. Портал агуулга
(багцуудын тайлбар, баримт) өгөгдлийн санд байдаггүй - бүгд кодтой хамт ирнэ.

Анх нээсний дараа `/dashboard` -> Тохиргоо (Settings) дээр сайтын нэр, тайлбар,
и-мэйл, утас, copyright зэргийг codesaur.net-ийнхээр солино; Raptor-ийн жишээ
хуудас/мэдээг "Clear sample data" товчоор цэвэрлэнэ (тэдгээр цэс мөрөнд гарч байдаг).

## 6. Upload файлууд

`public_html\public\` (Dashboard-аас upload хийсэн зураг, файл), `protected\`, `cache\`, `logs\`
нь git-д ороогүй тул deploy хөндөхгүй. Backup-д `public_html\public\` + өгөгдлийн санг оруулна.

## 7. Сүлжээний бүтэц ба Apache тохиргоо

Хүсэлтийн зам:

```
Хэрэглэгч --https--> nginx reverse proxy (66.181.175.192, TLS энд төгсдөг)
                          |
                          `--http--> энэ сервер (66.181.175.190) Apache :80
                                        DocumentRoot: C:/xampp/htdocs/codesaur.net/public_html
```

`codesaur.net`-ийн DNS нь **proxy руу (`.192`)** заадаг бөгөөд энэ сервер рүү (`.190`)
шууд хандахгүй. Сертификат proxy дээр байх тул энэ серверийн Apache-д SSL vhost
ХЭРЭГГҮЙ - зөвхөн :80 vhost байна:

**`www` дэд домэйн байхгүй.** Proxy нь `www.codesaur.net`-д өөр сайтын агуулга ба
`*.gerege.mn` сертификат буцаадаг байсан тул www-гийн A бичлэгийг DNS-ээс (Namecheap
BasicDNS) устгасан. Vhost дахь `ServerAlias www.codesaur.net` болон `.htaccess`-ийн
www -> apex redirect нь хоосон зогсож байгаа ч аюулгүйн тор болж үлдсэн: www-г хожим
DNS-д буцааж нэмвэл хүсэлт default vhost (өөр сайт) руу унахгүй, apex руу 301-ээр
очно. Тиймээс тэднийг устгах шаардлагагүй.

`C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName codesaur.net
    ServerAlias www.codesaur.net
    DocumentRoot "C:/xampp/htdocs/codesaur.net/public_html"
    <Directory "C:/xampp/htdocs/codesaur.net/public_html">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/codesaur.net-error.log"
    CustomLog "logs/codesaur.net-access.log" combined
</VirtualHost>
```

- **Document root заавал `public_html/`** - `.env`, `application/`, `vendor/` түүнээс дээр байрлаж URL-аар хүрэшгүй.
- `AllowOverride All` заавал: `public_html/.htaccess` нь www -> non-www, http -> https чиглүүлэлт болон `index.php` руу route хийдэг.
- `httpd.conf`-д `Include conf/extra/httpd-vhosts.conf`, `LoadModule rewrite_module` идэвхтэй эсэхийг шалгана. Дараа нь Apache-г restart.

### АНХААР: https redirect ба X-Forwarded-Proto

TLS proxy дээр төгсдөг тул Apache энэ түвшинд **үргэлж `HTTPS=off`** гэж хардаг.
Иймд `public_html/.htaccess`-ийн https албадах redirect бүр
`RewriteCond %{HTTP:X-Forwarded-Proto} !https` нөхцөлөөр хамгаалагдсан байх ёстой.
Энэ хамгаалалтгүй бол хүсэлт бүр өөр рүүгээ 301 буцаж, төгсгөлгүй давталт
(ERR_TOO_MANY_REDIRECTS) үүсч сайт бүхэлдээ унана - codesaur.net дээр яг ингэж
унасан бөгөөд durvunberkh.mn дээр 2026-07-20-нд адил тохиолдол гарсан.
Проксигоос `X-Forwarded-Proto: https` ирдэг нь баталгаажсан
(`SessionMiddleware` үүнийг уншиж cookie-д `secure` тавьдаг).

Шалгах:

```powershell
curl.exe -s -o NUL -w "code=%{http_code} redirects=%{num_redirects}\n" -L https://codesaur.net/
```

`code=200 redirects=0` байх ёстой. `redirects=5`, `code=301` гарвал дээрх хамгаалалт
`.htaccess`-ээс хасагдсан гэсэн үг.

## 8. Авто-deploy - GitHub Webhook

`main`-д push хиймэгц GitHub сервер рүү дохио илгээж **шууд** deploy хийнэ (polling-гүй).

**a) Secret үүсгэх** (нэг удаа):
```powershell
php -r "echo bin2hex(random_bytes(24));"
```

**b) Серверийн `.env`-д нэмэх:**
```
RAPTOR_DEPLOY_SECRET=<үүсгэсэн_утга>
```

**c) GitHub дээр webhook нэмэх** (repo -> Settings -> Webhooks -> Add webhook):
- **Payload URL**: `https://codesaur.net/deploy-hook.php`
- **Content type**: `application/json`
- **Secret**: дээрх `RAPTOR_DEPLOY_SECRET`-тэй ИЖИЛ утга
- **Which events**: Just the push event
- Add webhook дармагц GitHub "ping" илгээнэ -> хариу `pong` (ногоон тэмдэг) гарвал зөв.

Push бүрт `public_html/deploy-hook.php` нь HMAC signature шалгаж `scripts\deploy.ps1`-ийг
ажиллуулна: `git reset --hard origin/main` -> `composer dump-autoload` -> `composer install --no-dev`
-> `cache\*.cache` цэвэрлэх. Migration автоматаар apply хийхгүй - Dashboard -> Migrations-оос гараар.

> **ВАЖНО - git credential:** Webhook-ийг **Apache хэрэглэгч** (ихэвчлэн `NT AUTHORITY\SYSTEM`)
> ажиллуулдаг бөгөөд тэр хэрэглэгчид GitHub Desktop-ийн credential БАЙХГҮЙ. Repo private бол
> pull хийхэд auth алдаа гарна. Шийдэл: remote URL-д Personal Access Token суулга (нэг удаа):
> ```powershell
> cd C:\xampp\htdocs\codesaur.net
> git remote set-url origin https://<USERNAME>:<TOKEN>@github.com/<OWNER>/codesaur.net.git
> ```
> (GitHub -> Settings -> Developer settings -> Fine-grained PAT, зөвхөн энэ repo, Contents: Read.)
> Public repo бол token хэрэггүй. `exec()` идэвхтэй байх шаардлагатай (XAMPP default OK).

**Гараар deploy / шалгах** (сервер дээр):
```powershell
powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\codesaur.net\scripts\deploy.ps1 -Force
```

**Алдаа олох:** `scripts\deploy-hook.log` (webhook бүр: exit code, whoami, гаралт) ба
`scripts\deploy.log` (deploy.ps1-ийн алхмууд). Хоёулаа git-д ороогүй, 512KB-д өөрөө таслагдана.

`deploy.ps1` нь git-ийн итгэмжлэл асуух цонхыг хааж ажилладаг
(`GIT_TERMINAL_PROMPT=0` + `credential.interactive=false`). Webhook нь SYSTEM
хэрэглэгчээр, дэлгэцгүй ажилладаг тул prompt гарвал хариулах хүн байхгүй бөгөөд
git үүрд өлгөөтэй үлдэж, repo-гийн файлуудыг барьж авдаг. Токен хүчингүй болбол
одоо шууд `git rev-parse амжилтгүй` гэж лог руу бичээд гарна - өлгөөтэй үлдэхгүй.

**Deploy амжилттай болсныг шалгах:** `/dashboard` sidebar-ийн доод хэсэгт
`codesaur.net {version} | {date}` гарна - `composer.json` `extra.version` бүр commit-д
шинэчлэгддэг тул шинэ утга харагдвал deploy буусан гэсэн үг.

## 9. Fallback: Scheduled Task (webhook ажиллахгүй үед)

Webhook хүрэхгүй орчинд (жишээ нь сервер интернэтээс шууд хүрэшгүй) 2 минут тутам
polling хийх Scheduled Task үүсгэж болно - script өөрчлөлтгүй үед чимээгүй гардаг:

```powershell
$action  = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-NoProfile -ExecutionPolicy Bypass -File C:\xampp\htdocs\codesaur.net\scripts\deploy.ps1'
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 2)
Register-ScheduledTask -TaskName 'codesaur.net auto-deploy' -Action $action -Trigger $trigger -RunLevel Highest -User 'SYSTEM'
```

## 10. Бусад deploy замууд

`.github/workflows/deploy.yml` (FTP / SSH / Windows self-hosted runner) Raptor-ийн стандарт
хувилбараар үлдсэн боловч энэ төсөлд secret/variable тохируулаагүй тул CI-ийн дараа
бүх job алгасна. Webhook git-pull зам нь энэ серверийн сонгосон механизм.
