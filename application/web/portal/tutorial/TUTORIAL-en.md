# Build a blog with Raptor in 20 minutes

This tutorial takes you from an empty folder to a small multi-language blog whose posts are added from the admin panel and shown on the public website. Every step comes with complete code you can copy and run.

| Step | What | Time |
|------|------|------|
| 1 | Create the project | 3 min |
| 2 | Connect the database | 2 min |
| 3 | Model - `PostsModel` | 3 min |
| 4 | Web controller + routes | 4 min |
| 5 | Templates | 3 min |
| 6 | Add posts from the admin panel | 4 min |
| 7 | Multiple languages | 1 min |

> Raptor is a ready-made starter project: all the code under `application/` becomes your project's code. The module you add here is no different - change it however you like.

## 1. Create the project

Requirements: PHP 8.2.1+ (`ext-gd`, `ext-intl`), Composer, MySQL or PostgreSQL.

```bash
composer create-project codesaur/raptor my-blog
cd my-blog
```

Point the web server's document root at `my-blog/public_html/`. `.env`, `application/` and `vendor/` sit above it, so they can never be reached by URL. For a quick try, PHP's built-in server is enough:

```bash
php -S localhost:8000 -t public_html
```

## 2. Connect the database

Create an empty database and put its settings in `.env` (`composer create-project` has already created the file):

```ini
RAPTOR_DB_DRIVER=mysql
RAPTOR_DB_HOST=127.0.0.1
RAPTOR_DB_NAME=my_blog
RAPTOR_DB_USERNAME=root
RAPTOR_DB_PASSWORD=
```

There is no SQL to write. Opening `http://localhost:8000/` creates every table and the seed data automatically. Sign in to the admin panel at `http://localhost:8000/dashboard` with the initial user `admin` / password `password` - change the password right after signing in.

## 3. Model - PostsModel

A model declares the table's columns. The table is **created automatically** the first time the model is used - no migration to write.

Put the model under `application/dashboard/` so the website and the admin panel share it.

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

`code` is the language code (`mn`, `en`). Each post has one language - step 7 uses it.

## 4. Web controller + routes

Register the new namespaces under `autoload.psr-4` in `composer.json`. Folder names are lowercase (Linux servers are case-sensitive), so map each namespace explicitly:

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
            throw new \Exception('Post not found', 404);
        }

        $this->webTemplate(__DIR__ . '/blog-show.html', $post)->render();
    }
}
```

Things to note:

- User input reaches SQL **only through `PARAM`** (`:code`). Never concatenate a value into the SQL string.
- `webTemplate()` wraps the page in the site layout (menu, footer, SEO meta). `$post` contains `title`, so the page `<title>` is filled in automatically.
- An exception with code `404` shows the site's 404 page.

Add the routes to the constructor of `application/web/WebRouter.php`:

```php
$this->GET('/blog', [Blog\BlogController::class, 'index'])->name('blog');
$this->GET('/blog/{slug}', [Blog\BlogController::class, 'show'])->name('blog-post');
```

`->name()` lets templates build links with `{{ 'blog'|link }}`.

## 5. Templates

The template engine is `codesaur/template`, with Twig-like syntax. Every `{{ }}` is **HTML-escaped automatically**; only trusted HTML is printed with `|raw`.

`application/web/blog/blog-index.html`:

```html
<h1>Blog</h1>
{% for post in posts %}
    <article class="mb-4">
        <h2><a href="{{ 'blog-post'|link({'slug': post['slug']}) }}">{{ post['title'] }}</a></h2>
        <time>{{ post['published_at']|date('Y-m-d') }}</time>
    </article>
{% else %}
    <p>No posts yet.</p>
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

`content` is written by an admin, so `|raw` prints its HTML as is; `title` stays protected by autoescape.

Open `http://localhost:8000/blog` and you get an empty list. Time to add a post.

## 6. Add posts from the admin panel

An admin module has three parts: a router (with CSRF), a controller that checks permissions, and a template.

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

Every route that changes data **must** carry `CsrfMiddleware` - it is not attached automatically.

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

Note: `isUserCan()` checks the user's permissions (RBAC). This example reuses the content permissions Raptor already ships; to create your own, see "Adding a New Module" in the Raptor README.

`application/dashboard/blog/blog-admin.html`:

```html
<h3>Blog</h3>
<form id="blog-form" action="{{ 'blog-insert'|link }}" method="POST" class="mb-4">
    <input class="form-control mb-2" name="title" placeholder="Title" required>
    <input class="form-control mb-2" name="slug" placeholder="slug (e.g. first-post)" required>
    <textarea class="form-control mb-2" name="content" rows="6" placeholder="Content (HTML)"></textarea>
    <button class="btn btn-primary" type="submit">Publish</button>
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

`csrfFetch()` adds the CSRF token for you, and also gets the request past shared-hosting WAFs (method override, body encoding) - no extra code needed.

Register the router in `application/dashboard/Application.php`, next to the other routers:

```php
$this->use(new Blog\BlogRouter());
```

Go to `http://localhost:8000/dashboard/blog`, add your first post, then reload `/blog`.

## 7. Multiple languages

The website language comes from the URL prefix: the default language has none (`/blog`), every other language has one (`/en/blog`). The router and `|link` add the prefix automatically - no extra code.

A post saved with `code` = `en` shows on `/en/blog`, an `mn` post on `/blog`. `code = '*'` shows in every language - that is exactly what the `code IN (:code, '*')` condition in step 4 does.

## Done

You now have a working blog:

- a table created automatically from the model
- parameterized queries and autoescaped templates
- an admin section with RBAC permissions and CSRF protection
- multiple languages driven by the URL prefix

Next steps:

- For full CRUD (edit, delete, trash, logs) study Raptor's news module (`application/dashboard/content/news/`)
- Every rule for tables, translation texts, permissions and menu entries is in the Raptor guide
