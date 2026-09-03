<?php

namespace Web\Portal;

/**
 * Class Markdown
 * ---------------------------------------------------------------
 * Гадаад хамааралгүй, хөнгөн Markdown -> HTML хөрвүүлэгч.
 *
 * codesaur багцуудын docs/ дахь GitHub-flavored markdown баримтуудыг
 * портал дээр рендерлэхэд зориулагдсан. Дэмжих бүтцүүд:
 *
 *   - ATX гарчиг (# .. ######) - id аттрибуттай (anchor холбоос)
 *   - Догол мөр, хатуу мөр таслалт (мөрийн төгсгөлд 2 хоосон зай)
 *   - Fenced код блок (``` болон ```lang) - жагсаалт дотор ч ажиллана
 *   - Эрэмбэлэгдсэн / эрэмбэлэгдээгүй жагсаалт (nested)
 *   - Хүснэгт (GFM pipe table, align дэмжлэгтэй)
 *   - Blockquote, hr
 *   - Inline: code, bold, italic, strikethrough, link, image, autolink
 *   - Backslash escape
 *
 * Аюулгүй байдал: текст бүхэлдээ htmlspecialchars-аар escape хийгдэнэ -
 * raw HTML дамжуулахгүй (docs дахь <!-- --> тайлбар зэрэг нь текст
 * хэлбэрээр харагдана). Зөвхөн энэ класс өөрөө tag үүсгэнэ.
 *
 * Холбоосын хаягийг $linkResolver callback-аар дахин бичих боломжтой
 * (жишээ: docs/mn/api.md -> портал route, LICENSE -> GitHub blob).
 *
 * @package Web\Portal
 */
class Markdown
{
    /**
     * Fenced код блокуудыг түр орлуулах placeholder-ийн тэмдэг.
     */
    private const PH = "\x1B";

    /**
     * @var array<int, string> Placeholder -> рендерлэсэн <pre> блок
     */
    private array $codeBlocks = [];

    /**
     * @var array<int, array{level: int, text: string, id: string}> Цуглуулсан гарчгууд
     */
    private array $headings = [];

    /**
     * @var array<string, int> Давхардсан гарчгийн id тоолуур
     */
    private array $ids = [];

    /**
     * @var callable|null Холбоосын хаяг хувиргагч: fn(string $href): string
     */
    private $linkResolver;

    /**
     * @param callable|null $linkResolver Холбоосын хаягийг хувиргах callback
     */
    public function __construct(?callable $linkResolver = null)
    {
        $this->linkResolver = $linkResolver;
    }

    /**
     * Markdown текстийг HTML болгох.
     *
     * @param string $markdown Markdown эх текст
     * @return string HTML
     */
    public function convert(string $markdown): string
    {
        $this->codeBlocks = [];
        $this->headings = [];
        $this->ids = [];

        $text = \str_replace(["\r\n", "\r"], "\n", $markdown);
        $text = $this->extractFencedCode($text);
        $html = $this->parseBlocks(\explode("\n", $text));

        return $this->restoreCodeBlocks($html);
    }

    /**
     * Сүүлийн convert() дуудалтаас цуглуулсан гарчгууд (TOC үүсгэхэд).
     *
     * @return array<int, array{level: int, text: string, id: string}>
     */
    public function getHeadings(): array
    {
        return $this->headings;
    }

    /**
     * Fenced код блокуудыг placeholder-оор солих.
     *
     * Placeholder нь fence-ийн indent-ийг хадгална - ингэснээр жагсаалтын
     * item дотор байгаа код блок тухайн item-ийн агуулга хэвээр үлдэнэ.
     */
    private function extractFencedCode(string $text): string
    {
        $lines = \explode("\n", $text);
        $out = [];
        $count = \count($lines);
        for ($i = 0; $i < $count; $i++) {
            if (\preg_match('/^(\s*)(`{3,}|~{3,})\s*([\w.+#-]*)\s*$/', $lines[$i], $m)) {
                $indent = $m[1];
                $fence = $m[2];
                $lang = $m[3];
                $indentLen = \strlen($indent);
                $code = [];
                $i++;
                while ($i < $count) {
                    if (\preg_match('/^\s*' . \preg_quote($fence[0], '/') . '{' . \strlen($fence) . ',}\s*$/', $lines[$i])) {
                        break;
                    }
                    $line = $lines[$i];
                    // Fence-ийн indent-ийг код мөрүүдээс хасах
                    if ($indentLen > 0 && \strncmp($line, $indent, $indentLen) === 0) {
                        $line = \substr($line, $indentLen);
                    }
                    $code[] = $line;
                    $i++;
                }
                $index = \count($this->codeBlocks);
                $class = $lang !== '' ? ' class="language-' . \htmlspecialchars(\strtolower($lang), \ENT_QUOTES, 'UTF-8') . '"' : '';
                $this->codeBlocks[$index] = '<pre><code' . $class . '>'
                    . \htmlspecialchars(\implode("\n", $code), \ENT_QUOTES, 'UTF-8')
                    . '</code></pre>';
                $out[] = $indent . self::PH . 'CODE' . $index . self::PH;
                continue;
            }
            $out[] = $lines[$i];
        }
        return \implode("\n", $out);
    }

    /**
     * Placeholder-уудыг рендерлэсэн <pre> блокоор буцааж солих.
     */
    private function restoreCodeBlocks(string $html): string
    {
        // <p> дотор ганцаараа байгаа placeholder-ийг <p>-гүй болгох
        $html = \preg_replace('/<p>(' . self::PH . 'CODE\d+' . self::PH . ')<\/p>/', '$1', $html);
        return \preg_replace_callback(
            '/' . self::PH . 'CODE(\d+)' . self::PH . '/',
            fn(array $m): string => $this->codeBlocks[(int) $m[1]] ?? '',
            $html
        );
    }

    /**
     * Мөрүүдийг block түвшинд задлан HTML болгох.
     *
     * @param array<int, string> $lines
     */
    private function parseBlocks(array $lines): string
    {
        $html = '';
        $count = \count($lines);
        $i = 0;
        while ($i < $count) {
            $line = $lines[$i];

            // Хоосон мөр
            if (\trim($line) === '') {
                $i++;
                continue;
            }

            // Код блокийн placeholder (ганцаараа мөрөнд)
            if (\preg_match('/^\s*' . self::PH . 'CODE\d+' . self::PH . '\s*$/', $line)) {
                $html .= \trim($line) . "\n";
                $i++;
                continue;
            }

            // Гарчиг
            if (\preg_match('/^ {0,3}(#{1,6})\s+(.*?)\s*#*\s*$/', $line, $m)) {
                $level = \strlen($m[1]);
                $text = $this->inline($m[2]);
                $id = $this->headingId($m[2]);
                $this->headings[] = ['level' => $level, 'text' => \strip_tags($text), 'id' => $id];
                $html .= "<h$level id=\"$id\">$text</h$level>\n";
                $i++;
                continue;
            }

            // Хэвтээ зураас
            if (\preg_match('/^ {0,3}([-*_])(\s*\1){2,}\s*$/', $line)) {
                $html .= "<hr>\n";
                $i++;
                continue;
            }

            // Blockquote
            if (\preg_match('/^ {0,3}>/', $line)) {
                $quote = [];
                while ($i < $count && \preg_match('/^ {0,3}>\s?(.*)$/', $lines[$i], $m)) {
                    $quote[] = $m[1];
                    $i++;
                }
                $html .= "<blockquote>\n" . $this->parseBlocks($quote) . "</blockquote>\n";
                continue;
            }

            // Хүснэгт: толгой мөр + тусгаарлагч мөр
            if (\str_contains($line, '|')
                && isset($lines[$i + 1])
                && \preg_match('/^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/', $lines[$i + 1])
            ) {
                $header = $this->splitRow($line);
                $aligns = \array_map(function (string $cell): string {
                    $cell = \trim($cell);
                    $left = \str_starts_with($cell, ':');
                    $right = \str_ends_with($cell, ':');
                    if ($left && $right) {
                        return 'center';
                    }
                    return $right ? 'right' : ($left ? 'left' : '');
                }, $this->splitRow($lines[$i + 1]));
                $i += 2;
                $rows = [];
                while ($i < $count && \trim($lines[$i]) !== '' && \str_contains($lines[$i], '|')) {
                    $rows[] = $this->splitRow($lines[$i]);
                    $i++;
                }
                $html .= $this->renderTable($header, $aligns, $rows);
                continue;
            }

            // Жагсаалт
            if (\preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $line)) {
                $html .= $this->parseList($lines, $i);
                continue;
            }

            // Догол мөр: дараагийн block эхлэх хүртэл мөрүүдийг цуглуулах
            $para = [];
            while ($i < $count && \trim($lines[$i]) !== '' && !$this->isBlockStart($lines[$i], $lines[$i + 1] ?? null)) {
                $para[] = $lines[$i];
                $i++;
            }
            if (empty($para)) {
                // Хамгаалалт: block start гэж танигдсан ч дээрх аль ч салбарт ороогүй мөр
                $para[] = $lines[$i];
                $i++;
            }
            $html .= '<p>' . $this->inlineParagraph($para) . "</p>\n";
        }
        return $html;
    }

    /**
     * Мөр нь шинэ block-ийн эхлэл мөн эсэх (догол мөрийг таслах).
     */
    private function isBlockStart(string $line, ?string $next): bool
    {
        if (\preg_match('/^ {0,3}#{1,6}\s/', $line)) {
            return true;
        }
        if (\preg_match('/^ {0,3}>/', $line)) {
            return true;
        }
        if (\preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $line)) {
            return true;
        }
        if (\preg_match('/^ {0,3}([-*_])(\s*\1){2,}\s*$/', $line)) {
            return true;
        }
        if (\preg_match('/^\s*' . self::PH . 'CODE\d+' . self::PH . '\s*$/', $line)) {
            return true;
        }
        if ($next !== null && \str_contains($line, '|')
            && \preg_match('/^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/', $next)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Жагсаалтын block-ийг задлах (nested дэмжлэгтэй).
     *
     * @param array<int, string> $lines Бүх мөрүүд
     * @param int $i Одоогийн байрлал (reference - жагсаалт дууссан мөр рүү шилжинэ)
     */
    private function parseList(array $lines, int &$i): string
    {
        $count = \count($lines);
        \preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $lines[$i], $m);
        $baseIndent = \strlen($m[1]);
        $ordered = \ctype_digit($m[2][0]);
        $start = $ordered ? (int) $m[2] : 1;

        $items = [];
        $current = null;
        $contentIndent = 0;

        while ($i < $count) {
            $line = $lines[$i];

            if (\trim($line) === '') {
                // Хоосон мөр: дараагийн хоосон биш мөр жагсаалтад хамаарах эсэхийг шалгах
                $j = $i + 1;
                while ($j < $count && \trim($lines[$j]) === '') {
                    $j++;
                }
                if ($j >= $count) {
                    $i = $j;
                    break;
                }
                $nextIndent = \strlen($lines[$j]) - \strlen(\ltrim($lines[$j]));
                $nextIsItem = \preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $lines[$j], $nm) === 1;
                if ($nextIndent >= $contentIndent || ($nextIsItem && \strlen($nm[1]) >= $baseIndent && \strlen($nm[1]) < $contentIndent)) {
                    if ($current !== null) {
                        $current[] = '';
                    }
                    $i = $j;
                    continue;
                }
                $i = $j;
                break;
            }

            $indent = \strlen($line) - \strlen(\ltrim($line));

            if (\preg_match('/^(\s*)([-*+]|\d+[.)])\s+(.*)$/', $line, $lm) && \strlen($lm[1]) < $contentIndent + ($current === null ? 1 : 0)) {
                $itemIndent = \strlen($lm[1]);
                if ($itemIndent < $baseIndent) {
                    break; // Гадна түвшний жагсаалт
                }
                $itemOrdered = \ctype_digit($lm[2][0]);
                if ($itemIndent === $baseIndent && $itemOrdered !== $ordered) {
                    break; // Төрөл өөрчлөгдвөл шинэ жагсаалт
                }
                if ($current !== null) {
                    $items[] = $current;
                }
                $current = [$lm[3]];
                $contentIndent = $itemIndent + \strlen($lm[2]) + 1;
                $i++;
                continue;
            }

            if ($current === null) {
                break;
            }

            if ($indent >= $contentIndent) {
                $current[] = \substr($line, $contentIndent);
                $i++;
                continue;
            }

            // Lazy continuation: өмнөх мөр хоосон биш бол догол мөрийн үргэлжлэл
            $last = \end($current);
            if ($last !== '' && $last !== false && !\preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $line)) {
                $current[] = \ltrim($line);
                $i++;
                continue;
            }
            break;
        }
        if ($current !== null) {
            $items[] = $current;
        }

        $tag = $ordered ? 'ol' : 'ul';
        $attr = ($ordered && $start !== 1) ? " start=\"$start\"" : '';
        $html = "<$tag$attr>\n";
        foreach ($items as $item) {
            $inner = \trim($this->parseBlocks($item));
            // Ганц догол мөр бол <p>-гүй болгох (tight list)
            if (\preg_match('/^<p>(.*)<\/p>$/s', $inner, $pm) && !\str_contains($pm[1], '<p>')) {
                $inner = $pm[1];
            } elseif (\str_starts_with($inner, '<p>')) {
                // Эхний догол мөрийг тайлж, үлдсэн block-уудыг хэвээр үлдээх
                $inner = \preg_replace('/^<p>(.*?)<\/p>\n?/s', "$1\n", $inner, 1);
            }
            $html .= "<li>$inner</li>\n";
        }
        return $html . "</$tag>\n";
    }

    /**
     * Хүснэгтийн мөрийг нүднүүдэд задлах (escape хийсэн \| хэвээр үлдэнэ).
     *
     * @return array<int, string>
     */
    private function splitRow(string $row): array
    {
        $row = \trim($row);
        if (\str_starts_with($row, '|')) {
            $row = \substr($row, 1);
        }
        if (\str_ends_with($row, '|') && !\str_ends_with($row, '\\|')) {
            $row = \substr($row, 0, -1);
        }
        $cells = \preg_split('/(?<!\\\\)\|/', $row);
        return \array_map('trim', $cells);
    }

    /**
     * Хүснэгтийг HTML болгох.
     *
     * @param array<int, string> $header
     * @param array<int, string> $aligns
     * @param array<int, array<int, string>> $rows
     */
    private function renderTable(array $header, array $aligns, array $rows): string
    {
        $cols = \count($header);
        $cell = function (string $tag, int $idx, string $text) use ($aligns): string {
            $align = $aligns[$idx] ?? '';
            $style = $align !== '' ? " style=\"text-align:$align\"" : '';
            return "<$tag$style>" . $this->inline($text) . "</$tag>";
        };

        $html = "<div class=\"table-responsive\"><table>\n<thead><tr>";
        foreach ($header as $idx => $text) {
            $html .= $cell('th', $idx, $text);
        }
        $html .= "</tr></thead>\n<tbody>\n";
        foreach ($rows as $row) {
            $html .= '<tr>';
            for ($idx = 0; $idx < $cols; $idx++) {
                $html .= $cell('td', $idx, $row[$idx] ?? '');
            }
            $html .= "</tr>\n";
        }
        return $html . "</tbody>\n</table></div>\n";
    }

    /**
     * Догол мөрийн мөрүүдийг нэгтгэж inline боловсруулах (хатуу мөр таслалттай).
     *
     * @param array<int, string> $lines
     */
    private function inlineParagraph(array $lines): string
    {
        $parts = [];
        foreach ($lines as $idx => $line) {
            $hardBreak = \preg_match('/ {2,}$/', $line) === 1 && $idx < \count($lines) - 1;
            $parts[] = $this->inline(\rtrim($line)) . ($hardBreak ? '<br>' : '');
        }
        return \implode("\n", $parts);
    }

    /**
     * Inline markdown -> HTML.
     */
    private function inline(string $text): string
    {
        $escapes = [];
        $codes = [];

        // Backslash escape: \* \_ \` \| \[ гэх мэт
        $text = \preg_replace_callback('/\\\\([\\\\`*_{}\[\]()#+\-.!|~<>])/', function (array $m) use (&$escapes): string {
            $escapes[] = \htmlspecialchars($m[1], \ENT_QUOTES, 'UTF-8');
            return self::PH . 'ESC' . (\count($escapes) - 1) . self::PH;
        }, $text);

        // Inline код (нэг буюу олон backtick)
        $text = \preg_replace_callback('/(`+)(.+?)\1/s', function (array $m) use (&$codes): string {
            $codes[] = '<code>' . \htmlspecialchars(\trim($m[2]), \ENT_QUOTES, 'UTF-8') . '</code>';
            return self::PH . 'INL' . (\count($codes) - 1) . self::PH;
        }, $text);

        $text = \htmlspecialchars($text, \ENT_QUOTES, 'UTF-8');

        // Зураг
        $text = \preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]*)&quot;)?\)/', function (array $m): string {
            $title = isset($m[3]) ? ' title="' . $m[3] . '"' : '';
            return '<img src="' . $m[2] . '" alt="' . $m[1] . '"' . $title . '>';
        }, $text);

        // Холбоос [text](url "title")
        $text = \preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)(?:\s+&quot;([^&]*)&quot;)?\)/', function (array $m): string {
            $href = $this->resolveLink(\html_entity_decode($m[2], \ENT_QUOTES, 'UTF-8'));
            $title = isset($m[3]) ? ' title="' . $m[3] . '"' : '';
            $external = \preg_match('#^https?://#i', $href) ? ' target="_blank" rel="noopener"' : '';
            return '<a href="' . \htmlspecialchars($href, \ENT_QUOTES, 'UTF-8') . '"' . $title . $external . '>' . $m[1] . '</a>';
        }, $text);

        // Autolink <https://...>
        $text = \preg_replace('/&lt;(https?:\/\/[^\s&]+(?:&amp;[^\s&]+)*)&gt;/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);

        // Хүчтэй, налуу, зураастай
        $text = \preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $text);
        $text = \preg_replace('/(?<![\w])__(?=\S)(.+?)(?<=\S)__(?![\w])/s', '<strong>$1</strong>', $text);
        $text = \preg_replace('/(?<![\w*])\*(?=\S)(.+?)(?<=\S)\*(?![\w*])/s', '<em>$1</em>', $text);
        $text = \preg_replace('/(?<![\w_])_(?=\S)(.+?)(?<=\S)_(?![\w_])/s', '<em>$1</em>', $text);
        $text = \preg_replace('/~~(?=\S)(.+?)(?<=\S)~~/s', '<del>$1</del>', $text);

        // Энгийн URL-ийг холбоос болгох (аль хэдийн href/тext дотор байгааг алгасна)
        $text = \preg_replace_callback('/(?<![="\'>\w\/])(https?:\/\/[^\s<]+?)(?=[.,;:)]*(?:\s|$|<))/', function (array $m): string {
            return '<a href="' . $m[1] . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
        }, $text);

        // Placeholder-уудыг буцаах
        $text = \preg_replace_callback('/' . self::PH . 'INL(\d+)' . self::PH . '/', fn(array $m): string => $codes[(int) $m[1]] ?? '', $text);
        $text = \preg_replace_callback('/' . self::PH . 'ESC(\d+)' . self::PH . '/', fn(array $m): string => $escapes[(int) $m[1]] ?? '', $text);

        return $text;
    }

    /**
     * Холбоосын хаягийг resolver-оор хувиргах.
     */
    private function resolveLink(string $href): string
    {
        if ($this->linkResolver !== null) {
            return (string) \call_user_func($this->linkResolver, $href);
        }
        return $href;
    }

    /**
     * Гарчгийн текстээс GitHub маягийн anchor id үүсгэх (кирилл дэмжлэгтэй).
     */
    private function headingId(string $text): string
    {
        $plain = \preg_replace('/`([^`]*)`/', '$1', $text);
        $plain = \preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $plain);
        $plain = \str_replace(['*', '_', '~'], '', $plain);
        $plain = \mb_strtolower(\trim($plain), 'UTF-8');
        $plain = \preg_replace('/[^\p{L}\p{N}\s-]/u', '', $plain);
        $plain = \preg_replace('/\s+/u', '-', \trim($plain));
        if ($plain === '') {
            $plain = 'section';
        }
        if (isset($this->ids[$plain])) {
            $this->ids[$plain]++;
            return $plain . '-' . $this->ids[$plain];
        }
        $this->ids[$plain] = 0;
        return $plain;
    }
}
