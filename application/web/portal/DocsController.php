<?php

namespace Web\Portal;

use Psr\Log\LogLevel;

use Web\Template\TemplateController;

/**
 * Class DocsController
 * ---------------------------------------------------------------
 * codesaur.net портал - багцуудын markdown баримт бичгийг рендерлэх контроллер.
 *
 * Баримтын эх сурвалж:
 *   - raptor: төслийн root (README.md, CHANGELOG.md, docs/{lang}/*.md)
 *   - бусад: vendor/codesaur/{key}/ (README.md, CHANGELOG.md, docs/{lang}/*.md)
 *
 * Ингэснээр `composer update` хийхэд портал дээрх баримт автоматаар
 * шинэчлэгдэнэ. Рендерлэсэн HTML нь файлын mtime-ээр түлхүүрлэгдэн
 * кэшлэгдэнэ (portal_doc.{key}.{doc}.{lang}.{mtime}).
 *
 * Аюулгүй байдал: {key} нь PortalContent whitelist, {doc} нь
 * availableDocs() жагсаалтад байгаа slug байх ёстой - файлын зам
 * хэрэглэгчийн оролтоос хэзээ ч шууд үүсэхгүй.
 *
 * @package Web\Portal
 */
class DocsController extends TemplateController
{
    /**
     * Баримтын хаб - бүх багцын баримтын холбоосууд.
     *
     * @return void
     */
    public function index()
    {
        $code = $this->getLanguageCode();
        $lang = PortalContent::lang($code);
        $t = PortalContent::texts($code);

        $packages = PortalController::withInstalledVersions(PortalContent::packages());
        foreach ($packages as $key => &$package) {
            $package['docs'] = self::availableDocs($key, $lang, $t);
        }
        unset($package);

        $this->webTemplate(__DIR__ . '/docs.html', [
            'layout' => 'portal',
            'title' => $t['docs_title'],
            'description' => $t['docs_lead'],
            'lang' => $lang,
            't' => $t,
            'packages' => $packages,
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] Баримт бичгийн хабыг уншиж байна', ['action' => 'portal-docs']);
    }

    /**
     * Нэг баримтыг рендерлэх.
     *
     * @param string $key Багцын slug
     * @param string $doc Баримтын slug (default: guide)
     * @return void
     * @throws \Exception Багц эсвэл баримт олдохгүй бол 404
     */
    public function doc(string $key, string $doc = 'guide')
    {
        $package = PortalContent::package($key);
        if ($package === null) {
            throw new \Exception('Багц олдсонгүй', 404);
        }

        $code = $this->getLanguageCode();
        $lang = PortalContent::lang($code);
        $t = PortalContent::texts($code);

        $docs = self::availableDocs($key, $lang, $t);
        if (!isset($docs[$doc])) {
            throw new \Exception('Баримт олдсонгүй', 404);
        }
        $entry = $docs[$doc];
        $file = $entry['path'];

        $cache = $this->hasService('cache') ? $this->getService('cache') : null;
        $cacheKey = "portal_doc.$key.$doc.$lang." . \filemtime($file);
        $rendered = $cache?->get($cacheKey);
        if (!\is_array($rendered)) {
            $markdown = new Markdown(fn(string $href): string => $this->resolveDocLink($href, $key, $entry['relative'], $package['github']));
            $html = $markdown->convert((string) \file_get_contents($file));
            $headings = $markdown->getHeadings();
            $rendered = ['html' => $html, 'headings' => $headings];
            $cache?->set($cacheKey, $rendered);
        }

        // Хуудасны гарчиг: markdown-ий эхний H1, байхгүй бол баримтын нэр
        $pageTitle = $entry['title'];
        foreach ($rendered['headings'] as $heading) {
            if ($heading['level'] === 1) {
                $pageTitle = $heading['text'];
                break;
            }
        }

        // TOC: зөвхөн H2, H3
        $toc = \array_values(\array_filter($rendered['headings'], fn(array $h): bool => $h['level'] === 2 || $h['level'] === 3));

        $package['key'] = $key;
        $packages = PortalContent::packages();

        $this->webTemplate(__DIR__ . '/doc.html', [
            'layout' => 'portal',
            'title' => $pageTitle . ' - ' . $package['name'],
            'description' => $package['summary'][$lang],
            'lang' => $lang,
            't' => $t,
            'package' => $package,
            'packages' => $packages,
            'docs' => $docs,
            'doc' => $doc,
            'doc_html' => $rendered['html'],
            'toc' => $toc,
            'github_file' => $package['github'] . '/blob/HEAD/' . $entry['relative'],
            'lang_fallback' => $entry['fallback'],
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] {name} / {doc} баримтыг уншиж байна', ['action' => 'portal-doc', 'name' => $package['name'], 'doc' => $doc]);
    }

    /**
     * Багцын файлуудын root хавтас.
     *
     * @param string $key Багцын slug
     * @return string Абсолют зам
     */
    public static function packageRoot(string $key): string
    {
        $root = \dirname(__DIR__, 3);
        return $key === 'raptor' ? $root : "$root/vendor/codesaur/$key";
    }

    /**
     * Багцын боломжит баримтуудын жагсаалт (сонгосон хэлээр, en fallback-тай).
     *
     * @param string $key Багцын slug
     * @param string $lang Хэлний код (mn/en)
     * @param array<string, string> $t Портал UI текстүүд
     * @return array<string, array{title: string, path: string, relative: string, fallback: bool}>
     */
    public static function availableDocs(string $key, string $lang, array $t): array
    {
        $base = self::packageRoot($key);
        if (!\is_dir($base)) {
            return [];
        }
        $docs = [];

        $localized = function (string $file, string $slug, string $title) use ($base, $lang, &$docs): void {
            foreach ([$lang, 'en', 'mn'] as $idx => $code) {
                $path = "$base/docs/$code/$file";
                if (\is_file($path)) {
                    $docs[$slug] = ['title' => $title, 'path' => $path, 'relative' => "docs/$code/$file", 'fallback' => $code !== $lang];
                    return;
                }
            }
        };

        if (\is_file("$base/README.md")) {
            $docs['readme'] = ['title' => $t['doc_readme'], 'path' => "$base/README.md", 'relative' => 'README.md', 'fallback' => false];
        }
        $localized('README.md', 'guide', $t['doc_guide']);
        $localized('api.md', 'api', $t['doc_api']);
        $localized('review.md', 'review', $t['doc_review']);
        if (!isset($docs['review'])) {
            $localized('code-review.md', 'review', $t['doc_review']);
        }

        // Бусад нэмэлт .md файлууд (жишээ: SESSION-LIFETIME.md, CPANEL.md)
        $known = ['README.md', 'api.md', 'review.md', 'code-review.md'];
        foreach ([$lang, 'en', 'mn'] as $code) {
            foreach (\glob("$base/docs/$code/*.md") ?: [] as $path) {
                $file = \basename($path);
                if (\in_array($file, $known, true)) {
                    continue;
                }
                $slug = \strtolower(\preg_replace('/[^A-Za-z0-9]+/', '-', \substr($file, 0, -3)));
                if (isset($docs[$slug])) {
                    continue;
                }
                $docs[$slug] = ['title' => \substr($file, 0, -3), 'path' => $path, 'relative' => "docs/$code/$file", 'fallback' => $code !== $lang];
            }
        }

        if (\is_file("$base/CHANGELOG.md")) {
            $docs['changelog'] = ['title' => $t['doc_changelog'], 'path' => "$base/CHANGELOG.md", 'relative' => 'CHANGELOG.md', 'fallback' => false];
        }

        return $docs;
    }

    /**
     * Markdown доторх харьцангуй холбоосыг портал route эсвэл GitHub URL болгох.
     *
     * @param string $href Markdown дахь холбоос
     * @param string $key Багцын slug
     * @param string $currentRelative Одоогийн баримтын багц доторх зам (docs/en/api.md)
     * @param string $github Багцын GitHub repo URL
     * @return string
     */
    private function resolveDocLink(string $href, string $key, string $currentRelative, string $github): string
    {
        // Абсолют URL, anchor, mailto - хэвээр
        if (\preg_match('#^(https?:|mailto:|tel:|\#)#i', $href)) {
            return $href;
        }

        $anchor = '';
        if (($pos = \strpos($href, '#')) !== false) {
            $anchor = \substr($href, $pos);
            $href = \substr($href, 0, $pos);
        }
        if ($href === '') {
            return $anchor;
        }

        // Одоогийн баримтын хавтастай харьцуулан normalize хийх
        $dir = \dirname($currentRelative);
        $parts = $dir === '.' ? [] : \explode('/', $dir);
        foreach (\explode('/', \str_replace('\\', '/', $href)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                \array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }
        $relative = \implode('/', $parts);

        // Портал дээр байгаа баримт руу заасан бол route
        if (\preg_match('#^(?:docs/(?:mn|en)/)?([^/]+)\.md$#i', $relative, $m)) {
            $file = $m[1];
            $slug = null;
            if ($relative === 'README.md') {
                $slug = 'readme';
            } elseif ($relative === 'CHANGELOG.md') {
                $slug = 'changelog';
            } elseif (\str_starts_with($relative, 'docs/')) {
                $slug = match (\strtolower($file)) {
                    'readme' => 'guide',
                    'api' => 'api',
                    'review', 'code-review' => 'review',
                    default => \strtolower(\preg_replace('/[^A-Za-z0-9]+/', '-', $file)),
                };
            }
            if ($slug !== null) {
                return $this->generateRouteLink('docs-doc', ['key' => $key, 'doc' => $slug]) . $anchor;
            }
        }

        // Бусад файл (LICENSE, .github/CONTRIBUTING.md, src/...) - GitHub
        return $github . '/blob/HEAD/' . $relative . $anchor;
    }
}
