<?php

namespace Web\Service;

use Psr\Log\LogLevel;

use Dashboard\Content\NewsModel;
use Dashboard\Content\PagesModel;

use Dashboard\Shop\ProductsModel;

use Web\Portal\PortalContent;
use Web\Portal\PortalSearch;

use Web\Template\TemplateController;

/**
 * Class SearchController
 *
 * Вэб сайтын хайлтын контроллер.
 * Pages, News, Products хүснэгтүүдээс LIKE хайлт хийж үр дүнг харуулна.
 * Мөн порталын статик хуудсууд болон багцуудын markdown баримтаас
 * PortalSearch-ээр хайж, өгөгдлийн сангийн үр дүнгийн ард нэмнэ.
 *
 * @package Web\Service
 */
class SearchController extends TemplateController
{
    /**
     * Хайлтын хуудсыг харуулах.
     *
     * Хамгийн багадаа 2 тэмдэгт оруулах шаардлагатай.
     *
     * Хайдаг талбарууд:
     *  - Pages:    title, slug, description, content, source, link
     *  - News:     title, slug, description, content, source
     *  - Products: title, slug, description, content, link
     *
     * Content талбараас img tag алгасаж strip_tags хийсэн текстээр шүүнэ.
     *
     * Өгөгдлийн сангийн үр дүнгийн дараа порталын статик хуудсууд
     * (нүүр, /raptor, /packages, /package/{key}) болон багцуудын
     * markdown баримтаас олдсоныг нэмнэ - PortalSearch-ийн индекс
     * кэшлэгддэг тул хүсэлт бүрт файл уншихгүй.
     *
     * @return void
     */
    public function search()
    {
        $code = $this->getLanguageCode();
        $q = \trim($this->getQueryParams()['q'] ?? '');
        $results = [];
        if (\mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';

            // Pages-ээс хайх
            $pages_table = (new PagesModel($this->pdo))->getName();
            $stmt = $this->prepare(
                "SELECT id, title, slug, description, content, source, link, 'page' AS type
                 FROM $pages_table
                 WHERE published=1 AND code=:code
                   AND (title LIKE :q1 OR slug LIKE :q2 OR description LIKE :q3
                        OR content LIKE :q4 OR source LIKE :q5 OR link LIKE :q6)
                 ORDER BY published_at DESC
                 LIMIT 20"
            );
            $stmt->bindValue(':code', $code);
            for ($i = 1; $i <= 6; $i++) {
                $stmt->bindValue(":q$i", $like);
            }
            if ($stmt->execute()) {
                while ($row = $stmt->fetch()) {
                    $row['description'] = $this->searchSnippet($row);
                    $results[] = $row;
                }
            }

            // News-ээс хайх
            $news_table = (new NewsModel($this->pdo))->getName();
            $stmt = $this->prepare(
                "SELECT id, title, slug, description, content, source, 'news' AS type
                 FROM $news_table
                 WHERE published=1 AND code=:code
                   AND (title LIKE :q1 OR slug LIKE :q2 OR description LIKE :q3
                        OR content LIKE :q4 OR source LIKE :q5)
                 ORDER BY published_at DESC
                 LIMIT 20"
            );
            $stmt->bindValue(':code', $code);
            for ($i = 1; $i <= 5; $i++) {
                $stmt->bindValue(":q$i", $like);
            }
            if ($stmt->execute()) {
                while ($row = $stmt->fetch()) {
                    $row['description'] = $this->searchSnippet($row);
                    $results[] = $row;
                }
            }

            // Products-оос хайх
            $products_table = (new ProductsModel($this->pdo))->getName();
            $stmt = $this->prepare(
                "SELECT id, title, slug, description, content, link, 'product' AS type
                 FROM $products_table
                 WHERE published=1 AND code=:code
                   AND (title LIKE :q1 OR slug LIKE :q2 OR description LIKE :q3
                        OR content LIKE :q4 OR link LIKE :q5)
                 ORDER BY published_at DESC
                 LIMIT 20"
            );
            $stmt->bindValue(':code', $code);
            for ($i = 1; $i <= 5; $i++) {
                $stmt->bindValue(":q$i", $like);
            }
            if ($stmt->execute()) {
                while ($row = $stmt->fetch()) {
                    $row['description'] = $this->searchSnippet($row);
                    $results[] = $row;
                }
            }
        }

        // HTML tag attribute дотроос олдсон буруу match-уудыг шүүх
        $results = $this->filterResults($results, $q);

        // Порталын статик хуудсууд ба баримт бичгээс хайх
        $results = \array_merge($results, $this->searchPortal($q, $code));

        $this->webTemplate(__DIR__ . '/search.html', [
            'q' => $q,
            'results' => $results,
            'title' => $this->text('search')
        ])->render();

        $this->log(
            'web',
            LogLevel::NOTICE,
            '[{server_request.code}] Хайлт хийж байна: {query} - {count} үр дүн',
            ['action' => 'search', 'query' => $q, 'count' => \count($results)]
        );
    }

    /**
     * Порталын статик хуудсууд ба баримт бичгээс хайх.
     *
     * Индексийг PortalSearch бүтээх бөгөөд PortalContent (PHP массив) болон
     * багцуудын markdown файлаас л бүтдэг тул кэшлэгдэнэ. Индексийг зөвхөн
     * deploy цэвэрлэдэг (scripts/deploy.ps1 нь cache/*.cache-г устгадаг) -
     * контент нь deploy-ээс өөр замаар өөрчлөгддөггүй учраас.
     *
     * Coupling (English): this cache entry has no explicit invalidation -
     * the deploy script wiping cache/*.cache is what refreshes it. Clear the
     * cache by hand if PortalContent or a docs markdown file is ever edited
     * on a live server without a deploy.
     *
     * @param string $q Хайх үг
     * @param string $code Хэлний код
     * @return array<int, array> Хайлтын үр дүнгийн мөрүүд
     */
    private function searchPortal(string $q, string $code): array
    {
        if (\mb_strlen($q) < 2) {
            return [];
        }

        $lang = PortalContent::lang($code);
        $cache = $this->hasService('cache') ? $this->getService('cache') : null;
        $index = $cache?->get("portal_search.$lang");
        if ($index === null) {
            $index = PortalSearch::index($lang, PortalContent::texts($code));
            $cache?->set("portal_search.$lang", $index);
        }

        $results = [];
        foreach (PortalSearch::search($q, $index) as $row) {
            $row['url'] = $this->generateRouteLink($row['route'], $row['params']);
            unset($row['route'], $row['params']);
            $results[] = $row;
        }
        return $results;
    }
    /**
     * HTML content-ээс img tag алгасаж strip_tags хийсэн текст буцаах.
     */
    private function stripContent(string $html): string
    {
        $text = \preg_replace('/<img[^>]+>/i', '', $html);
        $text = \strip_tags($text);
        return \trim(\preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Хайлтын үр дүнг strip_tags хийсэн content-ээр шүүх.
     */
    private function filterResults(array $results, string $q): array
    {
        $needle = \mb_strtolower($q);
        return \array_values(\array_filter($results, function ($row) use ($needle) {
            foreach ($row as $key => $value) {
                if (\in_array($key, ['id', 'type', 'content'])) {
                    continue;
                }
                if (\is_string($value) && \mb_stripos($value, $needle) !== false) {
                    return true;
                }
            }
            if (!empty($row['content'])) {
                $plain = $this->stripContent($row['content']);
                if (\mb_stripos($plain, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Хайлтын үр дүнд description эсвэл content-ээс snippet гаргах.
     */
    private function searchSnippet(array $row): string
    {
        if (!empty($row['description'])) {
            return $row['description'];
        }
        if (empty($row['content'])) {
            return '';
        }
        $text = $this->stripContent($row['content']);
        if (\mb_strlen($text) > 200) {
            $text = \mb_substr($text, 0, 200) . '...';
        }
        return $text;
    }
}
