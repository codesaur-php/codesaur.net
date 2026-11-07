<?php

namespace Web\Portal;

use Psr\Log\LogLevel;

use Web\Template\TemplateController;

/**
 * Class PortalController
 * ---------------------------------------------------------------
 * codesaur.net портал - Raptor фреймворк болон экосистемийн багцуудын
 * танилцуулга хуудсуудын контроллер.
 *
 * Энэ контроллер нь:
 *   - Raptor фреймворкийн танилцуулга (raptor)
 *   - Бүх багцын жагсаалт (packages)
 *   - Нэг багцын дэлгэрэнгүй (package)
 *
 * Агуулга нь PortalContent-оос (PHP массив) ирнэ, өгөгдлийн сан шаардахгүй.
 *
 * @package Web\Portal
 */
class PortalController extends TemplateController
{
    /**
     * Raptor фреймворкийн танилцуулга хуудас.
     *
     * @return void
     */
    public function raptor()
    {
        $code = $this->getLanguageCode();
        $lang = PortalContent::lang($code);
        $t = PortalContent::texts($code);
        $packages = self::withInstalledVersions(PortalContent::packages());
        $raptor = $packages['raptor'];
        $raptor['stats'] = PortalStats::package('raptor', $this->cacheService());

        $this->allowBrowserCache();
        $this->webTemplate(__DIR__ . '/raptor.html', [
            'layout' => 'portal',
            'title' => $t['seo_raptor_title'],
            'description' => $raptor['summary'][$lang],
            'json_ld' => PortalContent::jsonLd($raptor, $lang, $this->pageUrl()),
            'lang' => $lang,
            't' => $t,
            'raptor' => $raptor,
            'modules' => PortalContent::raptorModules($code),
            'packages' => $packages,
            'github_org' => PortalContent::GITHUB_ORG,
            'aikido_intel' => PortalContent::AIKIDO_INTEL,
            'aikido_checked' => PortalContent::AIKIDO_CHECKED,
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] Raptor танилцуулга хуудсыг уншиж байна', ['action' => 'portal-raptor']);
    }

    /**
     * Бүх багцын жагсаалт.
     *
     * @return void
     */
    public function packages()
    {
        $code = $this->getLanguageCode();
        $t = PortalContent::texts($code);

        $this->allowBrowserCache();
        $this->webTemplate(__DIR__ . '/packages.html', [
            'layout' => 'portal',
            'title' => $t['seo_packages_title'],
            'description' => $t['packages_lead'],
            'lang' => PortalContent::lang($code),
            't' => $t,
            'packages' => self::withInstalledVersions(PortalContent::packages()),
            'packagist_user' => PortalContent::PACKAGIST_USER,
            'github_org' => PortalContent::GITHUB_ORG,
            'aikido_intel' => PortalContent::AIKIDO_INTEL,
            'aikido_checked' => PortalContent::AIKIDO_CHECKED,
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] Багцуудын жагсаалтыг уншиж байна', ['action' => 'portal-packages']);
    }

    /**
     * Нэг багцын дэлгэрэнгүй хуудас.
     *
     * @param string $key Багцын slug (PortalContent::packages() түлхүүр)
     * @return void
     * @throws \Exception Багц олдохгүй бол 404
     */
    public function package(string $key)
    {
        $package = PortalContent::package($key);
        if ($package === null) {
            throw new \Exception('Багц олдсонгүй', 404);
        }

        $code = $this->getLanguageCode();
        $lang = PortalContent::lang($code);
        $t = PortalContent::texts($code);

        $packages = self::withInstalledVersions(PortalContent::packages());
        $package = $packages[$key];
        $package['key'] = $key;
        $package['depends'] = self::codesaurDependencies($key);
        $package['docs'] = DocsController::availableDocs($key, $lang, $t);
        $package['stats'] = PortalStats::package($key, $this->cacheService());

        $this->allowBrowserCache();
        $this->webTemplate(__DIR__ . '/package.html', [
            'layout' => 'portal',
            'title' => $package['name'] . ' - ' . $t['seo_package_suffix'],
            'description' => $package['summary'][$lang],
            'json_ld' => PortalContent::jsonLd($package, $lang, $this->pageUrl()),
            'lang' => $lang,
            't' => $t,
            'package' => $package,
            'packages' => $packages,
            'aikido_intel' => PortalContent::AIKIDO_INTEL,
            'aikido_checked' => PortalContent::AIKIDO_CHECKED,
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] {name} багцын хуудсыг уншиж байна', ['action' => 'portal-package', 'name' => $package['name']]);
    }

    /**
     * Шинэчлэлүүд - бүх багцын сүүлийн хувилбарууд (CHANGELOG.md-ээс).
     *
     * @return void
     */
    public function releases()
    {
        $code = $this->getLanguageCode();
        $t = PortalContent::texts($code);

        $this->allowBrowserCache();
        $this->webTemplate(__DIR__ . '/releases.html', [
            'layout' => 'portal',
            'title' => $t['releases_title'],
            'description' => $t['releases_lead'],
            'lang' => PortalContent::lang($code),
            't' => $t,
            'releases' => PortalReleases::latest($this->cacheService()),
            'packages' => PortalContent::packages(),
        ])->render();

        $this->log('web', LogLevel::NOTICE, '[{server_request.code}] Шинэчлэлүүдийг уншиж байна', ['action' => 'portal-releases']);
    }

    /**
     * Кэш сервис (байхгүй бол null - портал кэшгүй ч ажиллана).
     *
     * @return \Psr\SimpleCache\CacheInterface|null
     */
    private function cacheService(): ?\Psr\SimpleCache\CacheInterface
    {
        return $this->hasService('cache') ? $this->getService('cache') : null;
    }

    /**
     * Одоогийн хуудасны query-гүй бүтэн URL (JSON-LD url талбарт).
     *
     * @return string
     */
    private function pageUrl(): string
    {
        return (string) $this->getRequest()->getUri()->withQuery('')->withFragment('');
    }

    /**
     * Raptor фреймворкийн хувилбарыг төслийн CHANGELOG.md-ээс унших.
     *
     * Raptor нь Composer-ын хамаарал биш (эх код нь энэ төслийн мод өөрөө)
     * тул InstalledVersions түүнийг мэдэхгүй. Гэхдээ фреймворкийн CHANGELOG
     * төслийн root-д хэвээр байдаг - portal нь /docs/raptor/changelog дээр
     * яг түүнийг рендерлэдэг - бөгөөд түүний хамгийн дээд гарчиг нь одоо
     * ажиллаж буй фреймворкийн хувилбар юм.
     *
     * Coupling (English): upstream Raptor-ыг шингээх бүрд CHANGELOG.md-ийн
     * шинэ хувилбарын гарчгийг заавал нэмнэ - багцын хуудсууд дээр харагдах
     * Raptor-ийн хувилбар үүнээс л гардаг.
     *
     * @return string|null 'v5.2.1' хэлбэртэй, эсвэл олдохгүй бол null
     */
    private static function raptorVersion(): ?string
    {
        $changelog = DocsController::packageRoot('raptor') . '/CHANGELOG.md';
        $head = @\file_get_contents($changelog, false, null, 0, 4096);
        if ($head === false
            || \preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', $head, $matches) !== 1
        ) {
            return null;
        }
        return 'v' . $matches[1];
    }
    /**
     * Багц бүрд суулгасан хувилбар (Composer runtime API) болон
     * Aikido Intel хуудасны холбоос нэмэх.
     *
     * Raptor бол Composer-ын хамаарал биш - түүний эх код нь энэ төслийн
     * мод өөрөө тул InstalledVersions түүнийг мэдэхгүй. Иймд хувилбарыг
     * төслийн root дахь CHANGELOG.md-ээс уншина (raptorVersion()).
     *
     * @param array<string, array> $packages
     * @return array<string, array>
     */
    public static function withInstalledVersions(array $packages): array
    {
        foreach ($packages as $key => &$package) {
            $package['key'] = $key;
            $package['version'] = null;
            $package['aikido_link'] = PortalContent::aikidoLink($package['name']);
            if ($key === 'raptor') {
                $package['version'] = self::raptorVersion();
                continue;
            }
            try {
                if (\class_exists(\Composer\InstalledVersions::class)
                    && \Composer\InstalledVersions::isInstalled($package['name'])
                ) {
                    $package['version'] = \Composer\InstalledVersions::getPrettyVersion($package['name']);
                }
            } catch (\Throwable $e) {
                $package['version'] = null;
            }
        }
        unset($package);
        return $packages;
    }

    /**
     * Багцын composer.json-оос codesaur/* хамаарлуудыг унших.
     *
     * @param string $key Багцын slug
     * @return array<string, string> Хамаарлын багцын slug => version constraint
     */
    public static function codesaurDependencies(string $key): array
    {
        $file = DocsController::packageRoot($key) . '/composer.json';
        if (!\is_file($file)) {
            return [];
        }
        $json = \json_decode((string) \file_get_contents($file), true);
        $deps = [];
        foreach ($json['require'] ?? [] as $name => $constraint) {
            if (\str_starts_with($name, 'codesaur/')) {
                $deps[\substr($name, 9)] = $constraint;
            }
        }
        return $deps;
    }
}
