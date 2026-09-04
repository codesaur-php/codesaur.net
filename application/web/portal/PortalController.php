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
        $raptor = PortalContent::package('raptor');

        $this->webTemplate(__DIR__ . '/raptor.html', [
            'layout' => 'portal',
            'title' => 'Raptor Framework',
            'description' => $raptor['summary'][$lang],
            'lang' => $lang,
            't' => $t,
            'raptor' => $raptor,
            'modules' => PortalContent::raptorModules($code),
            'packages' => self::withInstalledVersions(PortalContent::packages()),
            'github_org' => PortalContent::GITHUB_ORG,
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

        $this->webTemplate(__DIR__ . '/packages.html', [
            'layout' => 'portal',
            'title' => $t['packages_title'],
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

        $this->webTemplate(__DIR__ . '/package.html', [
            'layout' => 'portal',
            'title' => $package['name'],
            'description' => $package['summary'][$lang],
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
     * Багц бүрд суулгасан хувилбар (Composer runtime API) болон
     * Aikido Intel хуудасны холбоос нэмэх.
     *
     * Raptor өөрөө root багц тул хувилбар нь composer.json extra.version-оос
     * биш Packagist-аас харагдана - түүнд version талбар нэмэхгүй.
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
