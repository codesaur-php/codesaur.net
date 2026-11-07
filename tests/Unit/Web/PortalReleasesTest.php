<?php

namespace Tests\Unit\Web;

use Tests\Support\RaptorTestCase;

use Web\Portal\Markdown;
use Web\Portal\PortalContent;
use Web\Portal\PortalReleases;
use Web\Portal\PortalStats;
use Web\Portal\DocsController;

/**
 * Class PortalReleasesTest
 *
 * Порталын /releases хуудас, RSS болон багцын тоо баримтын шалгалт.
 *
 * - PortalReleases нь CHANGELOG-ийн гарчгийг уншиж, changelog хуудасны
 *   anchor руу холбоно - anchor нь Markdown::headingId()-тэй таарах ёстой.
 * - PortalStats нь workflow-ийн matrix, хамаарал, тестийн тоог файлаас уншина.
 * - Raptor-ийн 20 минутын заавар хоёр хэлээр байх ёстой.
 *
 * @package Tests\Unit\Web
 */
class PortalReleasesTest extends RaptorTestCase
{
    public function testParseReadsKeepAChangelogHeadings(): void
    {
        $md = "# Changelog\n\n## [Unreleased]\n\n## [2.1.0] - 2026-05-01\n[2.1.0]: https://example.com\n\n### Added\n- x\n\n## [2.0.0] - 2026-01-15\n";
        $releases = PortalReleases::parse($md);

        $this->assertCount(2, $releases);
        $this->assertSame('2.1.0', $releases[0]['version']);
        $this->assertSame('2026-05-01', $releases[0]['date']);
        $this->assertSame('2.0.0', $releases[1]['version']);
    }

    public function testAnchorMatchesMarkdownHeadingId(): void
    {
        $md = "## [6.0.1] - 2026-07-03\n[6.0.1]: https://github.com/codesaur-php/Router/compare/v6.0.0...v6.0.1\n";
        $markdown = new Markdown();
        $markdown->convert($md);
        $headings = $markdown->getHeadings();

        $this->assertNotEmpty($headings);
        $this->assertSame($headings[0]['id'], PortalReleases::parse($md)[0]['anchor']);
    }

    public function testLatestIsSortedNewestFirst(): void
    {
        $releases = PortalReleases::latest(null, 50);
        $this->assertNotEmpty($releases, 'CHANGELOG.md файлуудаас хувилбар уншигдсангүй');

        $dates = \array_column($releases, 'date');
        $sorted = $dates;
        \rsort($sorted);
        $this->assertSame($sorted, $dates);
        foreach ($releases as $release) {
            $this->assertArrayHasKey($release['key'], PortalContent::packages());
        }
    }

    public function testCiMatrixReadsListAndInlineForms(): void
    {
        $dir = \sys_get_temp_dir() . '/portal_ci_' . \uniqid();
        \mkdir($dir);
        \file_put_contents("$dir/ci.yml", "jobs:\n  test:\n    runs-on: \${{ matrix.os }}\n    strategy:\n      matrix:\n        os: [ubuntu-latest, windows-latest]\n        php-version:\n          - '8.2'\n          - \"8.3\"\n    steps:\n      - uses: shivammathur/setup-php@v2\n        with:\n          php-version: \${{ matrix.php-version }}\n");
        \file_put_contents("$dir/lint.yml", "jobs:\n  lint:\n    runs-on: macos-latest\n    steps:\n      - with:\n          php-version: ['8.4']\n");

        try {
            $ci = PortalStats::ciMatrix($dir);
            $this->assertSame(['8.2', '8.3', '8.4'], $ci['php']);
            $this->assertSame(['Linux', 'Windows', 'macOS'], $ci['os']);
        } finally {
            \array_map('unlink', \glob("$dir/*.yml"));
            \rmdir($dir);
        }
    }

    public function testRuntimeDependenciesSkipPhpAndExtensions(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'composer');
        \file_put_contents($file, \json_encode(['require' => ['php' => '^8.2', 'ext-json' => '*', 'psr/log' => '^3.0']]));
        try {
            $this->assertSame(['psr/log' => '^3.0'], PortalStats::runtimeDependencies($file));
        } finally {
            \unlink($file);
        }
    }

    public function testTutorialExistsInBothLanguages(): void
    {
        foreach (['mn', 'en'] as $lang) {
            $docs = DocsController::availableDocs('raptor', $lang, PortalContent::texts($lang));
            $this->assertArrayHasKey('tutorial', $docs);
            $this->assertFalse($docs['tutorial']['fallback'], "Заавар $lang хэлээр байх ёстой");
        }
    }
}
