<?php

namespace Tests\Unit\Web;

use Tests\Support\RaptorTestCase;

use Web\Portal\Markdown;

/**
 * Портал markdown хөрвүүлэгчийн тест.
 *
 * codesaur багцуудын docs/ дахь markdown бүтцүүд (гарчиг, код блок,
 * жагсаалт, хүснэгт, холбоос) зөв HTML болж, raw HTML escape хийгдэж
 * байгааг шалгана.
 */
class MarkdownTest extends RaptorTestCase
{
    public function testHeadingsGetAnchorIdsIncludingCyrillic(): void
    {
        $md = new Markdown();
        $html = $md->convert("# codesaur/router\n\n## 1. Монгол тайлбар\n\n### Key Features");

        $this->assertStringContainsString('<h1 id="codesaurrouter">codesaur/router</h1>', $html);
        $this->assertStringContainsString('<h2 id="1-монгол-тайлбар">1. Монгол тайлбар</h2>', $html);
        $this->assertStringContainsString('<h3 id="key-features">Key Features</h3>', $html);

        $headings = $md->getHeadings();
        $this->assertCount(3, $headings);
        $this->assertSame(2, $headings[1]['level']);
        $this->assertSame('1-монгол-тайлбар', $headings[1]['id']);
    }

    public function testDuplicateHeadingIdsAreSuffixed(): void
    {
        $html = (new Markdown())->convert("## Example\n\n## Example");
        $this->assertStringContainsString('id="example"', $html);
        $this->assertStringContainsString('id="example-1"', $html);
    }

    public function testFencedCodeIsEscapedAndLanguageClassApplied(): void
    {
        $html = (new Markdown())->convert("```php\n\$a = '<b>' . \$b;\n```");
        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString('<p><pre>', $html);
    }

    public function testRawHtmlIsEscaped(): void
    {
        $html = (new Markdown())->convert("Hello <script>alert(1)</script> world");
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testInlineFormatting(): void
    {
        $html = (new Markdown())->convert("**bold** and *italic* with `code` and ~~gone~~");
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
        $this->assertStringContainsString('<del>gone</del>', $html);
    }

    public function testSnakeCaseIdentifiersAreNotItalicized(): void
    {
        $html = (new Markdown())->convert("use `record_id` not record_organization_id and my_var_name here");
        $this->assertStringNotContainsString('<em>', $html);
    }

    public function testNestedListsAndOrderedStart(): void
    {
        $md = "- one\n- two\n  - two.a\n  - two.b\n- three\n\n3. third\n4. fourth";
        $html = (new Markdown())->convert($md);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString("<li>two\n<ul>", $html);
        $this->assertStringContainsString('<li>two.a</li>', $html);
        $this->assertStringContainsString('<ol start="3">', $html);
        $this->assertStringContainsString('<li>fourth</li>', $html);
    }

    public function testCodeBlockInsideListItem(): void
    {
        $md = "1. Install:\n\n   ```bash\n   composer require codesaur/router\n   ```\n\n2. Done";
        $html = (new Markdown())->convert($md);
        $this->assertStringContainsString('<pre><code class="language-bash">composer require codesaur/router</code></pre>', $html);
        $this->assertStringContainsString('<li>Done</li>', $html);
    }

    public function testTableWithAlignment(): void
    {
        $md = "| Package | Purpose |\n|:--------|--------:|\n| `codesaur/router` | Routing |\n| a \\| b | c |";
        $html = (new Markdown())->convert($md);
        $this->assertStringContainsString('<th style="text-align:left">Package</th>', $html);
        $this->assertStringContainsString('<th style="text-align:right">Purpose</th>', $html);
        $this->assertStringContainsString('<td style="text-align:left"><code>codesaur/router</code></td>', $html);
        $this->assertStringContainsString('a | b', $html);
    }

    public function testBlockquoteAndHorizontalRule(): void
    {
        $html = (new Markdown())->convert("> **Note:** careful\n> second line\n\n---\n\nAfter");
        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<strong>Note:</strong>', $html);
        $this->assertStringContainsString('<hr>', $html);
        $this->assertStringContainsString('<p>After</p>', $html);
    }

    public function testLinksUseResolverAndExternalLinksOpenInNewTab(): void
    {
        $md = new Markdown(function (string $href): string {
            $path = \explode('#', $href)[0];
            return \str_ends_with($path, '.md') ? '/docs/router/' . \basename($path, '.md') : $href;
        });
        $html = $md->convert("See [API](docs/en/api.md#router) and [GitHub](https://github.com/codesaur-php) or https://codesaur.net now");

        $this->assertStringContainsString('<a href="/docs/router/api">API</a>', $html);
        $this->assertStringContainsString('<a href="https://github.com/codesaur-php" target="_blank" rel="noopener">GitHub</a>', $html);
        $this->assertStringContainsString('<a href="https://codesaur.net" target="_blank" rel="noopener">https://codesaur.net</a>', $html);
    }

    public function testImagesAndAutolinks(): void
    {
        $html = (new Markdown())->convert("![CI](https://img.shields.io/badge/ci-ok-green) <https://example.com/x?a=1&b=2>");
        $this->assertStringContainsString('<img src="https://img.shields.io/badge/ci-ok-green" alt="CI">', $html);
        $this->assertStringContainsString('<a href="https://example.com/x?a=1&amp;b=2" target="_blank" rel="noopener">', $html);
    }

    public function testBackslashEscapes(): void
    {
        $html = (new Markdown())->convert("not \\*bold\\* and \\`code\\`");
        $this->assertStringContainsString('not *bold* and `code`', $html);
        $this->assertStringNotContainsString('<em>', $html);
    }

    public function testHardLineBreak(): void
    {
        $html = (new Markdown())->convert("line one  \nline two");
        $this->assertStringContainsString("line one<br>\nline two", $html);
    }

    public function testRealPackageReadmeConverts(): void
    {
        $file = \dirname(__DIR__, 3) . '/vendor/codesaur/router/README.md';
        if (!\is_file($file)) {
            $this->markTestSkipped('vendor/codesaur/router not installed');
        }
        $md = new Markdown();
        $html = $md->convert((string) \file_get_contents($file));
        $this->assertStringContainsString('<h1 id="codesaurrouter">codesaur/router</h1>', $html);
        $this->assertStringContainsString('language-bash', $html);
        $this->assertStringContainsString('language-php', $html);
        $this->assertNotEmpty($md->getHeadings());
    }
}
