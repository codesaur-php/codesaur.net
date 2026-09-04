<?php

namespace Web\Portal;

/**
 * Class PortalSearch
 *
 * Порталын статик хуудсуудын хайлт.
 *
 * Сайтын хайлт (Web\Service\SearchController) нь өгөгдлийн сангийн
 * Pages / News / Products хүснэгтээс хайдаг. Гэтэл порталын гол агуулга
 * буюу нүүр, /raptor, /packages, /package/{key} хуудсууд PortalContent
 * дотор код хэлбэрээр, баримт бичгүүд нь багцуудын markdown файлд байдаг
 * тул тэдгээр нь хайлтад огт оролцдоггүй байв. Энэ класс тэр хоёр эх
 * үүсвэрээс хайлтын индекс бүтээж өгнө.
 *
 * Индекс нь зөвхөн уншдаг өгөгдлөөс (PHP массив + markdown файл) бүтэх
 * тул хүсэлт бүрт дахин бүтээхгүйгээр кэшлэгдэнэ - PortalContent засвар
 * болон markdown файлын өөрчлөлт хоёулаа deploy-ээр л ирдэг бөгөөд
 * deploy нь cache/*.cache-г цэвэрлэдэг (scripts/deploy.ps1).
 *
 * Coupling (English): the cached index is invalidated ONLY by the deploy
 * script clearing cache/*.cache. If you ever change PortalContent or the
 * docs markdown on a live server without a deploy, clear the cache too.
 *
 * @package Web\Portal
 */
final class PortalSearch
{
    /**
     * Нэг удаад буцаах хамгийн их үр дүн.
     */
    public const LIMIT = 20;

    /**
     * Snippet-ийн урт (тааралдсан үгийн эргэн тойрны тэмдэгтийн тоо).
     */
    private const SNIPPET = 220;

    /**
     * Хайлтын индекс бүтээх.
     *
     * Буцаах бүрдэл бүр:
     *   route  - route-ийн нэр (generateRouteLink-д дамжина)
     *   params - route-ийн параметр
     *   title  - үр дүнд харагдах гарчиг
     *   label  - үр дүнгийн төрлийн шошго (badge)
     *   text   - хайлт хийх бүтэн текст
     *
     * @param string $lang Хэлний код (mn/en)
     * @param array<string, string> $t PortalContent::texts() текстүүд
     * @return array<int, array{route: string, params: array, title: string, label: string, text: string}>
     */
    public static function index(string $lang, array $t): array
    {
        return \array_merge(self::pageEntries($lang, $t), self::docEntries($lang, $t));
    }

    /**
     * Индексээс хайх.
     *
     * Гарчигт тохирсон үр дүнг эхэнд, дараа нь текстэд тохирсныг байрлуулна.
     *
     * @param string $q Хайх үг (2+ тэмдэгт)
     * @param array<int, array> $index PortalSearch::index()-ийн үр дүн
     * @return array<int, array{type: string, type_label: string, route: string, params: array, title: string, description: string}>
     */
    public static function search(string $q, array $index): array
    {
        $needle = \trim($q);
        if (\mb_strlen($needle) < 2) {
            return [];
        }

        $titleHits = [];
        $textHits = [];
        foreach ($index as $entry) {
            $inTitle = \mb_stripos($entry['title'], $needle) !== false;
            $position = \mb_stripos($entry['text'], $needle);
            if (!$inTitle && $position === false) {
                continue;
            }

            $row = [
                'type' => 'portal',
                'type_label' => $entry['label'],
                'route' => $entry['route'],
                'params' => $entry['params'],
                'title' => $entry['title'],
                'description' => $position === false
                    ? self::snippet($entry['text'], 0)
                    : self::snippet($entry['text'], $position)
            ];

            if ($inTitle) {
                $titleHits[] = $row;
            } else {
                $textHits[] = $row;
            }
        }

        return \array_slice(\array_merge($titleHits, $textHits), 0, self::LIMIT);
    }

    /**
     * Статик хуудсуудын бүрдэл (нүүр, /raptor, /packages, /package/{key}).
     *
     * @param string $lang Хэлний код
     * @param array<string, string> $t Порталын текстүүд
     * @return array<int, array>
     */
    private static function pageEntries(string $lang, array $t): array
    {
        $packages = PortalContent::packages();
        $entries = [];

        // Нүүр хуудас
        $entries[] = [
            'route' => 'home',
            'params' => [],
            'title' => $t['hero_title'],
            'label' => $t['search_portal'],
            'text' => self::joinTexts($t, ['hero_', 'stat_', 'eco_', 'why_', 'story_', 'community_'])
        ];

        // Raptor фреймворкийн хуудас
        $raptorText = self::joinTexts($t, ['raptor_', 'arch_', 'quick_']);
        if (isset($packages['raptor'])) {
            $raptorText .= ' ' . self::packageText($packages['raptor'], $lang);
        }
        foreach (PortalContent::raptorModules($lang) as $module) {
            $raptorText .= ' ' . self::flatten($module);
        }
        $entries[] = [
            'route' => 'raptor',
            'params' => [],
            'title' => $t['nav_raptor'] . ' - ' . $t['arch_title'],
            'label' => $t['search_portal'],
            'text' => $raptorText
        ];

        // Багцуудын жагсаалт
        $entries[] = [
            'route' => 'packages',
            'params' => [],
            'title' => $t['packages_title'],
            'label' => $t['search_portal'],
            'text' => self::joinTexts($t, ['packages_', 'package_'])
                . ' ' . \implode(' ', \array_column($packages, 'title'))
        ];

        // Багц тус бүрийн хуудас
        foreach ($packages as $key => $package) {
            $entries[] = [
                'route' => 'package',
                'params' => ['key' => $key],
                'title' => $package['title'] . ' - ' . $package['name'],
                'label' => $t['search_portal'],
                'text' => self::packageText($package, $lang)
            ];
        }

        return $entries;
    }

    /**
     * Баримт бичгийн бүрдэл - багц бүрийн markdown файлууд.
     *
     * @param string $lang Хэлний код
     * @param array<string, string> $t Порталын текстүүд
     * @return array<int, array>
     */
    private static function docEntries(string $lang, array $t): array
    {
        $entries = [];
        foreach (PortalContent::packages() as $key => $package) {
            foreach (DocsController::availableDocs($key, $lang, $t) as $slug => $doc) {
                $text = @\file_get_contents($doc['path']);
                if ($text === false) {
                    continue;
                }
                $entries[] = [
                    'route' => 'docs-doc',
                    'params' => ['key' => $key, 'doc' => $slug],
                    'title' => $package['title'] . ' - ' . $doc['title'],
                    'label' => $t['nav_docs'],
                    'text' => self::plain($text)
                ];
            }
        }
        return $entries;
    }

    /**
     * Багцын мэдээллийг нэг текст болгох.
     *
     * @param array $package PortalContent::packages()-ийн нэг бүрдэл
     * @param string $lang Хэлний код
     * @return string
     */
    private static function packageText(array $package, string $lang): string
    {
        $parts = [
            $package['title'] ?? '',
            $package['name'] ?? '',
            $package['summary'][$lang] ?? '',
            $package['description'][$lang] ?? '',
            $package['install'] ?? '',
            \implode(' ', $package['psr'] ?? []),
            \implode(' ', $package['requires'] ?? []),
            \implode(' ', $package['features'][$lang] ?? []),
            $package['example'] ?? ''
        ];

        foreach ($package['classes'] ?? [] as $class) {
            $parts[] = self::flatten($class);
        }

        return self::plain(\implode(' ', $parts));
    }

    /**
     * Өгөгдсөн угтваруудтай бүх текстийг нэгтгэх.
     *
     * @param array<string, string> $t Порталын текстүүд
     * @param array<int, string> $prefixes Түлхүүрийн угтварууд
     * @return string
     */
    private static function joinTexts(array $t, array $prefixes): string
    {
        $parts = [];
        foreach ($t as $key => $value) {
            foreach ($prefixes as $prefix) {
                if (\str_starts_with($key, $prefix)) {
                    $parts[] = \is_array($value) ? self::flatten($value) : (string) $value;
                    break;
                }
            }
        }
        return self::plain(\implode(' ', $parts));
    }

    /**
     * Олон түвшинт массивыг нэг мөр текст болгох.
     *
     * @param mixed $value Ямар ч гүнтэй массив эсвэл скаляр
     * @return string
     */
    private static function flatten($value): string
    {
        if (\is_array($value)) {
            return \implode(' ', \array_map([self::class, 'flatten'], $value));
        }
        return \is_scalar($value) ? (string) $value : '';
    }

    /**
     * Markdown / HTML тэмдэглэгээг цэвэрлэж нэг мөр текст болгох.
     *
     * @param string $text Түүхий текст
     * @return string
     */
    private static function plain(string $text): string
    {
        $text = \strip_tags($text);
        $text = \str_replace(['`', '*', '_', '#', '|', '>'], ' ', $text);
        return \trim(\preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Тааралдсан байрлалын эргэн тойрны хэсгийг таслаж авах.
     *
     * @param string $text Бүтэн текст
     * @param int $position Тааралдсан байрлал
     * @return string
     */
    private static function snippet(string $text, int $position): string
    {
        if (\mb_strlen($text) <= self::SNIPPET) {
            return $text;
        }

        $start = \max(0, $position - (int) (self::SNIPPET / 3));
        $snippet = \mb_substr($text, $start, self::SNIPPET);

        return ($start > 0 ? '...' : '') . \trim($snippet) . '...';
    }
}
