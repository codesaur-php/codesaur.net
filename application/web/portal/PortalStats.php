<?php

namespace Web\Portal;

use Psr\SimpleCache\CacheInterface;

/**
 * Class PortalStats
 * ---------------------------------------------------------------
 * codesaur.net портал - багц бүрийн хэмжигдэхүйц баримтууд (кодын мөр,
 * runtime хамаарал, тестийн тоо) - "хөнгөн" гэх мэт субьектив үгийн оронд
 * бодит тоо харуулахад ашиглана.
 *
 * Бүх тоо файлын системээс автоматаар тооцогдоно - гараар хөтлөхгүй:
 *   - raptor: төслийн root (application/*.php, tests/, composer.json)
 *   - бусад: vendor/codesaur/{key}/ (src/, tests/, composer.json)
 * Тиймээс `composer update` хийхэд тоо дагаад шинэчлэгдэнэ.
 *
 * Үр дүн нь composer.json-ийн mtime-аар түлхүүрлэгдэн кэшлэгдэнэ
 * (portal_stats.{key}.{mtime}) - хуудас бүр файл тоолохгүй.
 *
 * @package Web\Portal
 */
class PortalStats
{
    /**
     * Нэг багцын статистик.
     *
     * @param string $key Багцын slug (PortalContent::packages() түлхүүр)
     * @param CacheInterface|null $cache
     * @return array{lines: int, tests: int, deps: array<string, string>, ci: array{php: string[], os: string[]}}
     */
    public static function package(string $key, ?CacheInterface $cache = null): array
    {
        $root = DocsController::packageRoot($key);
        $composer = "$root/composer.json";
        $cacheKey = "portal_stats.$key." . (\is_file($composer) ? \filemtime($composer) : 0);

        $stats = $cache?->get($cacheKey);
        if (\is_array($stats)) {
            return $stats;
        }

        $srcDir = $key === 'raptor' ? "$root/application" : "$root/src";
        $stats = [
            'lines' => self::countLines($srcDir),
            'tests' => self::countTests("$root/tests"),
            'deps' => self::runtimeDependencies($composer),
            'ci' => self::ciMatrix("$root/.github/workflows"),
        ];
        $cache?->set($cacheKey, $stats);
        return $stats;
    }

    /**
     * Бүх багцын статистик + нийлбэр (нүүр хуудасны "Compiling" блок).
     *
     * @param CacheInterface|null $cache
     * @return array{packages: array<string, array>, tests: int, zero_deps: int}
     */
    public static function all(?CacheInterface $cache = null): array
    {
        $packages = [];
        $tests = 0;
        $zeroDeps = 0;
        $php = $os = [];
        foreach (\array_keys(PortalContent::packages()) as $key) {
            $stats = self::package($key, $cache);
            $packages[$key] = $stats;
            $tests += $stats['tests'];
            if ($key !== 'raptor' && empty($stats['deps'])) {
                $zeroDeps++;
            }
            $php = \array_merge($php, $stats['ci']['php'] ?? []);
            $os = \array_merge($os, $stats['ci']['os'] ?? []);
        }
        $php = \array_values(\array_unique($php));
        \sort($php);
        $os = \array_values(\array_unique($os));
        \sort($os);
        return [
            'packages' => $packages,
            'tests' => $tests,
            'zero_deps' => $zeroDeps,
            'ci' => ['php' => $php, 'os' => $os],
        ];
    }

    /**
     * GitHub Actions workflow-уудаас тестлэгддэг PHP хувилбар, үйлдлийн системийг унших.
     *
     * Зөвхөн `php-version` болон `os` / `runs-on` мөрүүдийг энгийн regex-ээр
     * уншина (YAML parser шаардахгүй) - workflow-ийн бодит matrix-ийг харуулна.
     *
     * @param string $dir .github/workflows хавтас
     * @return array{php: string[], os: string[]}
     */
    public static function ciMatrix(string $dir): array
    {
        $php = $os = [];
        foreach (\glob("$dir/*.yml") ?: [] as $file) {
            $yaml = (string) \file_get_contents($file);
            // php-version: '8.2'  |  php-version: ['8.2', '8.3']  |  php-version:\n  - "8.2"
            if (\preg_match_all('/php-version:\s*((?:\[[^\]]*\])|(?:[\'"]?\d\.\d[\'"]?)|(?:\s*\n(?:\s*-\s*[\'"]?\d\.\d[\'"]?\s*\n?)+))/', $yaml, $m)) {
                foreach ($m[1] as $block) {
                    \preg_match_all('/\d\.\d/', $block, $versions);
                    $php = \array_merge($php, $versions[0]);
                }
            }
            if (\preg_match_all('/\b(ubuntu|windows|macos)-/i', $yaml, $m)) {
                foreach ($m[1] as $name) {
                    $os[] = ['ubuntu' => 'Linux', 'windows' => 'Windows', 'macos' => 'macOS'][\strtolower($name)];
                }
            }
        }
        $php = \array_values(\array_unique($php));
        \sort($php);
        $os = \array_values(\array_unique($os));
        \sort($os);
        return ['php' => $php, 'os' => $os];
    }

    /**
     * Хавтас доторх бүх .php файлын мөрийн нийлбэр.
     *
     * @param string $dir
     * @return int
     */
    public static function countLines(string $dir): int
    {
        if (!\is_dir($dir)) {
            return 0;
        }
        $lines = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $lines += \substr_count((string) \file_get_contents($file->getPathname()), "\n");
            }
        }
        return $lines;
    }

    /**
     * PHPUnit тест методын тоо (`public function test*` эсвэл `#[Test]`).
     *
     * @param string $dir
     * @return int
     */
    public static function countTests(string $dir): int
    {
        if (!\is_dir($dir)) {
            return 0;
        }
        $tests = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && \str_ends_with($file->getFilename(), 'Test.php')) {
                $tests += \preg_match_all(
                    '/public\s+function\s+test\w*\s*\(|#\[Test\]/',
                    (string) \file_get_contents($file->getPathname())
                );
            }
        }
        return $tests;
    }

    /**
     * composer.json-ийн `require` доторх Composer багцууд (php, ext-* хасна).
     *
     * @param string $composer composer.json-ийн зам
     * @return array<string, string> Багцын нэр => version constraint
     */
    public static function runtimeDependencies(string $composer): array
    {
        if (!\is_file($composer)) {
            return [];
        }
        $json = \json_decode((string) \file_get_contents($composer), true);
        $deps = [];
        foreach ($json['require'] ?? [] as $name => $constraint) {
            if ($name === 'php' || \str_starts_with($name, 'ext-')) {
                continue;
            }
            $deps[$name] = $constraint;
        }
        return $deps;
    }
}
