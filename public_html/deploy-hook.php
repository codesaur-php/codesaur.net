<?php

/**
 * deploy-hook.php - GitHub webhook receiver (push -> auto deploy)
 *
 * URL: https://codesaur.net/deploy-hook.php
 *
 * GitHub push хийхэд энэ endpoint-руу POST ирнэ. HMAC-SHA256 signature-ийг
 * RAPTOR_DEPLOY_SECRET-аар шалгаж, зөвхөн жинхэнэ GitHub push (main branch)-д
 * scripts\deploy.ps1-ийг ажиллуулна (git reset --hard + composer + cache).
 *
 * GitHub webhook тохиргоо (repo -> Settings -> Webhooks -> Add webhook):
 *   Payload URL  : https://codesaur.net/deploy-hook.php
 *   Content type : application/json
 *   Secret       : <RAPTOR_DEPLOY_SECRET-тэй ИЖИЛ утга>
 *   Events       : Just the push event
 *
 * Энэ файл нь public_html дотор бөгөөд .htaccess-ийн "!-f" дүрмээр index.php-д
 * route хийгдэхгүй шууд ажиллана. Secret-гүйгээр юу ч хийхгүй (403).
 */

require __DIR__ . '/../vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(\dirname(__DIR__))->load();

\header('Content-Type: text/plain; charset=utf-8');

$secret = $_ENV['RAPTOR_DEPLOY_SECRET'] ?? '';
if ($secret === '') {
    \http_response_code(500);
    exit("RAPTOR_DEPLOY_SECRET тохируулаагүй байна (.env)\n");
}

// Зөвхөн POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    \http_response_code(405);
    exit("Method not allowed\n");
}

$payload = \file_get_contents('php://input');

// HMAC-SHA256 signature шалгах (timing-safe)
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . \hash_hmac('sha256', $payload, $secret);
if ($sigHeader === '' || !\hash_equals($expected, $sigHeader)) {
    \http_response_code(403);
    exit("Invalid signature\n");
}

$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

// GitHub-ийн "ping" (webhook нэмэхэд илгээдэг)
if ($event === 'ping') {
    exit("pong\n");
}

if ($event !== 'push') {
    \http_response_code(202);
    exit("ignored event: $event\n");
}

// Зөвхөн main branch
$data = \json_decode($payload, true);
$ref  = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    \http_response_code(202);
    exit("ignored ref: $ref\n");
}

// deploy.ps1-ийг СИНХРОН ажиллуулж, гаралтыг log хийнэ. (Apache service-ийн дор
// detached процесс найдваргүй тул синхрон + бүрэн log авна.) composer хурдан тул
// ихэвчлэн хэдхэн секунд. PowerShell-ийг бүтэн зам-аар дуудна (Apache PATH хязгаарлагдмал).
$root    = \dirname(__DIR__);
$deploy  = $root . '\\scripts\\deploy.ps1';
$hookLog = $root . '\\scripts\\deploy-hook.log';

$psExe = ($_SERVER['SystemRoot'] ?? 'C:\\Windows') . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
if (!\is_file($psExe)) {
    $psExe = 'powershell.exe';
}

if (!\function_exists('exec')) {
    @\file_put_contents($hookLog, \date('Y-m-d H:i:s') . " ERROR: exec() идэвхгүй (php.ini disable_functions)\n", \FILE_APPEND);
    \http_response_code(500);
    exit("exec disabled\n");
}

$cmd = "\"$psExe\" -ExecutionPolicy Bypass -NonInteractive -File \"$deploy\" -Force 2>&1";
$out = [];
$code = null;
\exec($cmd, $out, $code);

$entry = \date('Y-m-d H:i:s') . " webhook deploy: exit=$code\n"
    . "  cmd: $cmd\n"
    . "  whoami: " . \trim(\shell_exec('whoami') ?: '?') . "\n"
    . (empty($out) ? "  (no output - powershell ажиллуулж чадсангүй?)\n" : "  " . \implode("\n  ", $out) . "\n");

// Log rotation: deploy-hook.log нь webhook бүрд бичигддэг тул хязгааргүй өсөхөөс
// сэргийлж, 512KB-аас хэтэрвэл сүүлийн ~400 мөрийг үлдээж таслана.
if (\is_file($hookLog) && \filesize($hookLog) > 512 * 1024) {
    $lines = @\file($hookLog, \FILE_IGNORE_NEW_LINES);
    if (\is_array($lines)) {
        @\file_put_contents($hookLog, \implode("\n", \array_slice($lines, -400)) . "\n");
    }
}
@\file_put_contents($hookLog, $entry, \FILE_APPEND);

\http_response_code(202);
echo "deploy triggered (exit $code)\n";
