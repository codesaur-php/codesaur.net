<?php

namespace Tests\Unit\Web;

use Tests\Support\RaptorTestCase;

use Web\Portal\PortalContent;
use Web\Portal\PortalController;

/**
 * Class PortalPackageVersionTest
 *
 * Порталын багцын хуудсууд дээр харагдах хувилбарын шалгалт.
 *
 * Raptor нь Composer-ын хамаарал биш (эх код нь төслийн мод өөрөө) тул
 * бусад багцын адил InstalledVersions-оос хувилбараа авч чаддаггүй -
 * PortalController түүнийг төслийн root дахь CHANGELOG.md-ийн хамгийн
 * дээд гарчгаас уншдаг. CHANGELOG-ийн формат өөрчлөгдвөл хувилбар нь
 * чимээгүйхэн алга болох тул энд түгжиж өгнө.
 *
 * @package Tests\Unit\Web
 */
class PortalPackageVersionTest extends RaptorTestCase
{
    public function testRaptorVersionComesFromTheChangelog(): void
    {
        $packages = PortalController::withInstalledVersions(PortalContent::packages());

        $this->assertArrayHasKey('raptor', $packages);
        $this->assertMatchesRegularExpression(
            '/^v\d+\.\d+\.\d+$/',
            (string) $packages['raptor']['version'],
            'Raptor-ийн хувилбар CHANGELOG.md-ээс уншигдсангүй'
        );
    }

    public function testRaptorVersionMatchesTheTopChangelogHeading(): void
    {
        $changelog = \dirname(__DIR__, 3) . '/CHANGELOG.md';
        $this->assertFileExists($changelog);

        \preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', (string) \file_get_contents($changelog), $matches);
        $this->assertNotEmpty($matches, 'CHANGELOG.md дотор хувилбарын гарчиг олдсонгүй');

        $packages = PortalController::withInstalledVersions(PortalContent::packages());
        $this->assertSame('v' . $matches[1], $packages['raptor']['version']);
    }

    public function testEveryPackageCarriesAnAikidoLink(): void
    {
        $packages = PortalController::withInstalledVersions(PortalContent::packages());

        foreach ($packages as $key => $package) {
            $this->assertSame(
                'https://intel.aikido.dev/packages/packagist/' . $package['name'],
                $package['aikido_link'],
                "$key багцын Aikido холбоос буруу"
            );
        }
    }
}
