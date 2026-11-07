<?php

namespace Web\Portal;

use Psr\SimpleCache\CacheInterface;

/**
 * Class PortalReleases
 * ---------------------------------------------------------------
 * codesaur.net портал - Raptor болон codesaur багцуудын хувилбаруудын
 * жагсаалтыг багц бүрийн CHANGELOG.md-ээс автоматаар бүрдүүлнэ.
 *
 * Энэ нь /releases хуудас болон RSS feed-ийг гараар бичлэг нэмэхгүйгээр
 * дүүргэдэг: `composer update` (эсвэл upstream Raptor-ыг шингээх) бүрд
 * шинэ хувилбарууд өөрөө нэмэгдэнэ.
 *
 * CHANGELOG-ийн гарчиг Keep a Changelog хэлбэртэй байх ёстой:
 *   ## [6.0.1] - 2026-07-03
 *
 * @package Web\Portal
 */
class PortalReleases
{
    /**
     * Багц бүрээс авах хамгийн сүүлийн хувилбарын тоо.
     */
    public const PER_PACKAGE = 5;

    /**
     * Бүх багцын сүүлийн хувилбарууд, огноогоор буурахаар эрэмбэлсэн.
     *
     * @param CacheInterface|null $cache
     * @param int $limit Нийт хэдийг буцаах
     * @return array<int, array{key: string, name: string, version: string, date: string, anchor: string}>
     */
    public static function latest(?CacheInterface $cache = null, int $limit = 30): array
    {
        $files = [];
        $stamp = 0;
        foreach (\array_keys(PortalContent::packages()) as $key) {
            $file = DocsController::packageRoot($key) . '/CHANGELOG.md';
            if (\is_file($file)) {
                $files[$key] = $file;
                $stamp += (int) \filemtime($file);
            }
        }

        $cacheKey = "portal_releases.$stamp";
        $releases = $cache?->get($cacheKey);
        if (!\is_array($releases)) {
            $releases = [];
            $packages = PortalContent::packages();
            foreach ($files as $key => $file) {
                foreach (self::parse((string) \file_get_contents($file)) as $release) {
                    $releases[] = ['key' => $key, 'name' => $packages[$key]['name']] + $release;
                }
            }
            \usort($releases, fn(array $a, array $b): int => [$b['date'], $b['version']] <=> [$a['date'], $a['version']]);
            $cache?->set($cacheKey, $releases);
        }

        return \array_slice($releases, 0, $limit);
    }

    /**
     * CHANGELOG.md-ээс хувилбарын гарчгуудыг унших.
     *
     * @param string $markdown CHANGELOG-ийн агуулга
     * @param int $max Хамгийн ихдээ хэдийг унших
     * @return array<int, array{version: string, date: string, anchor: string}>
     */
    public static function parse(string $markdown, int $max = self::PER_PACKAGE): array
    {
        \preg_match_all(
            '/^##\s+\[(\d+\.\d+\.\d+)\]\s+-\s+(\d{4}-\d{2}-\d{2})\s*$/m',
            $markdown,
            $matches,
            \PREG_SET_ORDER
        );
        $releases = [];
        foreach (\array_slice($matches, 0, $max) as $m) {
            $releases[] = [
                'version' => $m[1],
                'date' => $m[2],
                'anchor' => self::anchor("[{$m[1]}] - {$m[2]}"),
            ];
        }
        return $releases;
    }

    /**
     * Markdown::headingId()-тэй ижил дүрмээр гарчгийн anchor id үүсгэх.
     *
     * Холбоос: Markdown::headingId()-ийн дүрмийг өөрчилбөл энэ функцийг хамт
     * шинэчилнэ, эс бөгөөс /releases-ийн холбоос хувилбар руугаа гүйлгэхгүй.
     *
     * Coupling (English): must stay in sync with Markdown::headingId(), otherwise
     * the /releases links land on the changelog page without scrolling to the version.
     *
     * @param string $text Гарчгийн текст
     * @return string
     */
    public static function anchor(string $text): string
    {
        $plain = \mb_strtolower(\trim($text), 'UTF-8');
        $plain = \preg_replace('/[^\p{L}\p{N}\s-]/u', '', $plain);
        return (string) \preg_replace('/\s+/u', '-', \trim($plain));
    }
}
