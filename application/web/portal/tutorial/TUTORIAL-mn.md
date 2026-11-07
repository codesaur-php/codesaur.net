# Raptor-оор 20 минутад блог хийх

Энэ заавраар хоосон хавтаснаас эхлээд олон хэлтэй, админ самбараас бичлэг нэмдэг, нийтийн вебсайт дээр харагддаг жижиг блог бүтээнэ. Алхам бүр хуулж ажиллуулж болох бүтэн кодтой.

| Алхам | Юу хийх | Хугацаа |
|-------|---------|---------|
| 1 | Төсөл үүсгэх | 3 мин |
| 2 | Өгөгдлийн сан холбох | 2 мин |
| 3 | Model - `PostsModel` | 3 мин |
| 4 | Вэб controller + route | 4 мин |
| 5 | Template | 3 мин |
| 6 | Админ самбараас бичлэг нэмэх | 4 мин |
| 7 | Олон хэл | 1 мин |

> Raptor бол бэлэн суурь төсөл (starter): `application/` доторх бүх код таны төслийн код болно. Энд нэмэх модуль ч мөн адил - хүссэнээрээ өөрчилнө.

## 1. Төсөл үүсгэх

Шаардлага: PHP 8.2.1+ (`ext-gd`, `ext-intl`), Composer, MySQL эсвэл PostgreSQL.

```bash
composer create-project codesaur/raptor my-blog
cd my-blog
```

Вэб серверийн document root-ыг `my-blog/public_html/` руу заана. `.env`, `application/`, `vendor/` нь түүнээс дээр байрлах тул URL-аар хүрэх боломжгүй. Түргэн туршихад PHP-ийн өөрийн сервер хангалттай:

```bash
php -S localhost:8000 -t public_html
```

## 2. Өгөгдлийн сан холбох

Хоосон өгөгдлийн сан үүсгээд `.env` файлд тохиргоогоо бичнэ (`composer create-project` нь `.env`-г автоматаар үүсгэсэн байгаа):

```ini
RAPTOR_DB_DRIVER=mysql
RAPTOR_DB_HOST=127.0.0.1
RAPTOR_DB_NAME=my_blog
RAPTOR_DB_USERNAME=root
RAPTOR_DB_PASSWORD=
```

Хүснэгт үүсгэх SQL бичих шаардлагагүй. `http://localhost:8000/` нээхэд бүх хүснэгт болон анхны өгөгдөл автоматаар үүснэ. `http://localhost:8000/dashboard` хаягаар админ самбарт нэвтэрнэ: анхны хэрэглэгч `admin`, нууц үг `password` - нэвтэрсэн даруйдаа нууц үгээ солино уу.

## 3. Model - PostsModel

Model нь хүснэгтийн багануудыг тодорхойлдог. Хүснэгт нь model-ийг анх ашиглах үед **автоматаар үүснэ** - migration бичихгүй.

Model-ийг `application/dashboard/` дотор тавина. Ингэснээр вэб сайт болон админ самбар хоёулаа нэг model-ийг ашиглана.

`application/dashboard/blog/PostsModel.php`:

```php
<?php

namespace Dashboard\Blog;

use codesaur\DataObject\Model;
use codesaur\DataObject\Column;
use codesaur\DataObject\Constants;

class PostsModel extends Model
{
    public function __construct(\PDO $pdo)
    {
        $this->setInstance($pdo);

        $this->setColumns([
           (new Column('id', 'bigint'))->primary(),
           (new Column('slug', 'varchar', 255))->unique(),
            new Column('title', 'varchar', 255),
            new Column('content', 'mediumtext'),
            new Column('code', 'varchar', Constants::DEFAULT_CODE_LENGTH),
           (new Column('published', 'tinyint'))->default(0),
            new Column('published_at', 'datetime'),
            new Column('created_at', 'datetime'),
            new Column('created_by', 'bigint'),
        ]);

        $this->setTable('blog_posts');
    }
}
```

`code` нь хэлний код (`mn`, `en`). Бичлэг бүр нэг хэлтэй - 7-р алхамд үүнийг ашиглана.

## 4. Вэб controller + route

Шинэ namespace-уудыг `composer.json`-ийн `autoload.psr-4` хэсэгт бүртгэнэ. Хавтасны нэр жижиг үсгээр бичигддэг тул (Linux сервер том, жижиг үсгийг ялгадаг) namespace бүрийг тодорхой заана:

```json
"Web\\Blog\\": "application/web/blog/",
"Dashboard\\Blog\\": "application/dashboard/blog/",
```

```bash
composer dump-autoload
```

`application/web/blog/BlogController.php`:

```php
<?php

namespace Web\Blog;

use Dashboard\Blog\PostsModel;

use Web\Template\TemplateController;

class BlogController extends TemplateController
{
    public function index()
    {
        $posts = (new PostsModel($this->pdo))->getRows([
            'WHERE' => "published=1 AND code IN (:code, '*')",
            'ORDER BY' => 'published_at DESC',
            'PARAM' => [':code' => $this->getLanguageCode()],
        ]);

        $this->webTemplate(__DIR__ . '/blog-index.html', [
            'posts' => $posts,
            'title' => 'Blog',
        ])->render();
    }

    public function show(string $slug)
    {
        $post = (new PostsModel($this->pdo))->getRowWhere([
            'slug' => $slug,
            'published' => 1,
        ]);
        if (empty($post)) {
            throw new \Exception('Бичлэг олдсонгүй', 404);
        }

        $this->webTemplate(__DIR__ . '/blog-show.html', $post)->render();
    }
}
```

Анхаарах зүйлс:

- Хэрэглэгчийн оролт SQL-д **үргэлж `PARAM`-аар** орно (`:code`). Утгыг SQL мөрөнд шууд залгаж болохгүй.
- `webTemplate()` нь хуудсыг сайтын үндсэн layout-д (цэс, footer, SEO meta) ороож харуулна. `$post` дотор `title` байгаа тул хуудасны `<title>` автоматаар бөглөгдөнө.
- `404` кодтой exception нь сайтын 404 хуудсыг харуулна.

Route-уудыг `application/web/WebRouter.php`-ийн constructor-т нэмнэ:

```php
$this->GET('/blog', [Blog\BlogController::class, 'index'])->name('blog');
$this->GET('/blog/{slug}', [Blog\BlogController::class, 'show'])->name('blog-post');
```

`->name()` нь template-ээс `{{ 'blog'|link }}` гэж холбоос үүсгэх боломж олгоно.

## 5. Template

Template хөдөлгүүр нь Twig-тэй төстэй синтакстай `codesaur/template`. `{{ }}` бүр **автоматаар HTML-escape** хийгддэг. Зөвхөн итгэлтэй HTML-ийг `|raw`-аар хэвлэнэ.

`application/web/blog/blog-index.html`:

```html
<h1>Blog</h1>
{% for post in posts %}
    <article class="mb-4">
        <h2><a href="{{ 'blog-post'|link({'slug': post['slug']}) }}">{{ post['title'] }}</a></h2>
        <time>{{ post['published_at']|date('Y-m-d') }}</time>
    </article>
{% else %}
    <p>Бичлэг алга.</p>
{% endfor %}
```

`application/web/blog/blog-show.html`:

```html
<article>
    <h1>{{ title }}</h1>
    <time>{{ published_at|date('Y-m-d') }}</time>
    {{ content|raw }}
</article>
```

`content`-ыг админ бичдэг тул `|raw`-аар HTML хэвээр нь хэвлэнэ. Харин `title`-ыг autoescape хамгаалж байна.

`http://localhost:8000/blog` нээвэл хоосон жагсаалт гарна. Одоо бичлэг нэмье.

## 6. Админ самбараас бичлэг нэмэх

Админ самбарын модуль нь гурван хэсэгтэй: router (CSRF-тэй), эрх шалгадаг controller, template.

`application/dashboard/blog/BlogRouter.php`:

```php
<?php

namespace Dashboard\Blog;

use codesaur\Router\Router;

use Dashboard\CsrfMiddleware;

class BlogRouter extends Router
{
    public function __construct()
    {
        $this->GET('/blog', [BlogController::class, 'index'])->name('blog-admin');
        $this->POST('/blog/insert', [BlogController::class, 'insert'])
            ->name('blog-insert')
            ->middleware([CsrfMiddleware::class]);
    }
}
```

Өгөгдөл өөрчилдөг route бүрд `CsrfMiddleware` **заавал** холбоно. Энэ нь автоматаар холбогддоггүй.

`application/dashboard/blog/BlogController.php`:

```php
<?php

namespace Dashboard\Blog;

class BlogController extends \Dashboard\Controller
{
    use \Dashboard\Template\DashboardTrait;

    public function index()
    {
        if (!$this->isUserCan('system_content_index')) {
            $this->dashboardProhibited(null, 401)->render();
            return;
        }

        $posts = (new PostsModel($this->pdo))->getRows(['ORDER BY' => 'id DESC']);
        $dashboard = $this->dashboardTemplate(__DIR__ . '/blog-admin.html', ['posts' => $posts]);
        $dashboard->set('title', 'Blog');
        $dashboard->render();
    }

    public function insert()
    {
        try {
            if (!$this->isUserCan('system_content_insert')) {
                throw new \Exception($this->text('system-no-permission'), 401);
            }

            $payload = $this->getParsedBody();
            if (empty($payload['title']) || empty($payload['slug'])) {
                throw new \InvalidArgumentException($this->text('invalid-request'), 400);
            }

            (new PostsModel($this->pdo))->insert([
                'title' => $payload['title'],
                'slug' => $payload['slug'],
                'content' => $payload['content'] ?? '',
                'code' => $payload['code'] ?? $this->getLanguageCode(),
                'published' => 1,
                'published_at' => \date('Y-m-d H:i:s'),
                'created_at' => \date('Y-m-d H:i:s'),
                'created_by' => $this->getUserId(),
            ]);

            $this->respondJSON(['status' => 'success', 'message' => $this->text('record-insert-success')]);
        } catch (\Throwable $e) {
            $this->respondJSON(['status' => 'danger', 'message' => $e->getMessage()], $e->getCode());
        }
    }
}
```

Анхаарах: `isUserCan()` нь хэрэглэгчийн эрхийг шалгадаг (RBAC). Энд Raptor-д аль хэдийн байгаа контентын эрхүүдийг ашигласан. Өөрийн эрх үүсгэхийг Raptor-ийн README дахь "Adding a New Module" хэсгээс үзнэ үү.

`application/dashboard/blog/blog-admin.html`:

```html
<h3>Blog</h3>
<form id="blog-form" action="{{ 'blog-insert'|link }}" method="POST" class="mb-4">
    <input class="form-control mb-2" name="title" placeholder="Гарчиг" required>
    <input class="form-control mb-2" name="slug" placeholder="slug (жишээ: anhny-bichleg)" required>
    <textarea class="form-control mb-2" name="content" rows="6" placeholder="Агуулга (HTML)"></textarea>
    <button class="btn btn-primary" type="submit">Нийтлэх</button>
</form>
<ul class="list-group">
    {% for post in posts %}
        <li class="list-group-item">{{ post['title'] }} <small class="text-muted">/{{ post['slug'] }}</small></li>
    {% endfor %}
</ul>
<script>
document.getElementById('blog-form').addEventListener('submit', function (e) {
    e.preventDefault();
    csrfFetch(this.action, {method: 'POST', body: new FormData(this)})
        .then(res => res.json())
        .then(data => {
            Notify(data.status, 'Blog', data.message);
            if (data.status === 'success') {
                window.location.reload();
            }
        });
});
</script>
```

`csrfFetch()` нь CSRF токеныг автоматаар нэмдэг. Мөн shared hosting-ийн WAF хаадаг хүсэлтийг давах (method override, body encoding) ажлыг өөрөө хийнэ - нэмэлт код хэрэггүй.

Router-ээ `application/dashboard/Application.php`-д бусад router-ийн хажууд бүртгэнэ:

```php
$this->use(new Blog\BlogRouter());
```

`http://localhost:8000/dashboard/blog` хаягаар орж эхний бичлэгээ нэмээд `/blog` хуудсаа шинэчилнэ үү.

## 7. Олон хэл

Вэб сайтын хэл URL-ийн угтвараар тодорхойлогдоно: үндсэн хэл угтваргүй (`/blog`), бусад хэл угтвартай (`/en/blog`). Router болон `|link` угтварыг автоматаар нэмдэг тул нэмэлт код бичихгүй.

`code` баганыг `en` гэж хадгалсан бичлэг `/en/blog` дээр, `mn` бичлэг `/blog` дээр харагдана. `code = '*'` бол бүх хэл дээр харагдана - 4-р алхмын `code IN (:code, '*')` нөхцөл яг үүнийг хийдэг.

## Дууслаа

Ажиллаж байгаа блог бэлэн боллоо:

- Model-оос автоматаар үүссэн хүснэгт
- Параметржүүлсэн query, autoescape-тэй template
- RBAC эрхтэй, CSRF-ээр хамгаалагдсан админ хэсэг
- URL угтвараар ажилладаг олон хэл

Дараагийн алхмууд:

- Бүрэн CRUD (засах, устгах, хогийн сав, лог) хийх жишээг Raptor-ийн мэдээний модулиас (`application/dashboard/content/news/`) үзээрэй
- Хүснэгт, хэлний текст, эрх, цэс нэмэх бүх дүрэм Raptor-ийн гарын авлагад бий
