<?php

namespace Web\Portal;

/**
 * Class PortalContent
 * ---------------------------------------------------------------
 * codesaur.net портал - codesaur экосистемийн багцуудын тайлбар,
 * портал UI-ийн хоёр хэлний (mn/en) текстүүдийг нэг дор хадгалагч.
 *
 * Портал нь framework-ийн тайлбар сайт тул агуулга нь кодтой хамт
 * хөгжүүлэгчийн мэдэлд байх ёстой. Тиймээс энэ агуулгыг өгөгдлийн сангийн
 * localization_text хүснэгтэд seed хийхийн оронд PHP массив хэлбэрээр
 * энд хадгална - deploy бүрт кодтой хамт шинэчлэгдэнэ, migration шаардахгүй.
 *
 * Хэлний код нь 'mn' эсвэл 'en'. Танигдаагүй код ирвэл 'en' fallback хийнэ.
 *
 * @package Web\Portal
 */
class PortalContent
{
    /**
     * codesaur-php GitHub байгууллагын хаяг.
     */
    public const GITHUB_ORG = 'https://github.com/codesaur-php';

    /**
     * Packagist дээрх codesaur хэрэглэгчийн багцуудын хаяг.
     */
    public const PACKAGIST_USER = 'https://packagist.org/users/codesaur/packages/';

    /**
     * GitHub Discussions хаяг.
     */
    public const DISCUSSIONS = 'https://github.com/orgs/codesaur-php/discussions';

    /**
     * Aikido Intel дээрх codesaur багцуудын жагсаалт.
     *
     * Aikido Intel нь нээлттэй эхийн багцуудыг хамаарлын эрүүл мэнд,
     * засварлагдсан эмзэг байдал, malware зэргээр 0-100 оноогоор дүгнэдэг.
     */
    public const AIKIDO_INTEL = 'https://intel.aikido.dev/packages?ecosystem=packagist&q=codesaur';

    /**
     * Багцуудын Aikido оноог хамгийн сүүлд шалгасан огноо.
     *
     * Оноо нь PortalContent доторх 'aikido' утгууд дээр гараар
     * шинэчлэгддэг тул огноог хамт нь заавал шинэчилнэ.
     *
     * Coupling (English): the per-package 'aikido' scores are maintained by
     * hand; update this date in the same edit whenever you refresh them.
     */
    public const AIKIDO_CHECKED = '2026-09-04';

    /**
     * Багцын Aikido Intel хуудасны хаяг.
     *
     * @param string $name Packagist дээрх бүтэн нэр (codesaur/raptor)
     * @return string
     */
    public static function aikidoLink(string $name): string
    {
        return "https://intel.aikido.dev/packages/packagist/$name";
    }

    /**
     * Хэлний кодыг дэмжигдсэн хэл рүү хэвийн болгох.
     *
     * @param string $code Хэлний код
     * @return string 'mn' эсвэл 'en'
     */
    public static function lang(string $code): string
    {
        return $code === 'mn' ? 'mn' : 'en';
    }

    /**
     * Багц тус бүрийн мэдээлэл.
     *
     * Түлхүүр нь портал дахь URL slug (/package/{key}, /docs/{key}).
     * 'raptor' нь framework өөрөө бөгөөд баримт нь төслийн root-д,
     * бусад багцын баримт нь vendor/codesaur/{key}/ дотор байрлана.
     *
     * @return array<string, array>
     */
    public static function packages(): array
    {
        return [
            'raptor' => [
                'name' => 'codesaur/raptor',
                'title' => 'Raptor',
                'icon' => 'bi-rocket-takeoff',
                'color' => 'danger',
                'github' => self::GITHUB_ORG . '/Raptor',
                'packagist' => 'https://packagist.org/packages/codesaur/raptor',
                'aikido' => 90,
                'psr' => ['PSR-3', 'PSR-4', 'PSR-7', 'PSR-11', 'PSR-12', 'PSR-14', 'PSR-15', 'PSR-16'],
                'requires' => ['PHP 8.2.1+', 'Composer', 'MySQL / PostgreSQL', 'ext-gd', 'ext-intl'],
                'install' => 'composer create-project codesaur/raptor my-project',
                'summary' => [
                    'mn' => 'Цэвэр архитектуртай объект хандалттай веб хөгжүүлэлтийн фреймворк',
                    'en' => 'Clean architecture object-oriented web development framework',
                ],
                'description' => [
                    'mn' => 'PSR стандартууд дээр суурилсан, олон давхаргат архитектуртай, олон байгууллагын (multi-tenant) RBAC эрхийн удирдлагатай, бүрэн CMS боломжтой PHP веб фреймворк. Анхдагч байдлаараа Web (нийтийн вебсайт) болон Dashboard (админ панель) гэсэн хоёр апп-тай ирдэг бөгөөд codesaur экосистемийн бусад багцуудыг нэгтгэн ажилладаг.',
                    'en' => 'A multi-layered, multi-tenant, full-featured CMS PHP web framework built on PSR standards with RBAC access control. It ships with two apps by default - Web (public website) and Dashboard (admin panel) - and brings the other codesaur ecosystem packages together into one working system.',
                ],
                'features' => [
                    'mn' => [
                        'PSR-7/PSR-15 middleware суурьтай архитектур',
                        'JWT + Session нэвтрэлт баталгаажуулалт',
                        'Олон байгууллагын (multi-tenant) RBAC эрхийн удирдлага',
                        'Олон хэл дэмжлэг (Localization)',
                        'CMS модулиуд: Мэдээ, Хуудас, Файл, Лавлах, Тохиргоо',
                        'Дэлгүүр модуль: Бүтээгдэхүүн, Захиалга, Үнэлгээ',
                        'MySQL, PostgreSQL алийг нь ч дэмжинэ',
                        'SQL файл суурьтай өгөгдлийн сангийн migration систем',
                        'codesaur/template engine (Twig маягийн синтакс)',
                        'OpenAI интеграци (moedit editor)',
                        'PSR-3 лог систем, PSR-14 Event Dispatcher',
                        'И-мэйл (Brevo API, SMTP, PHP mail), Discord webhook мэдэгдэл',
                        'SEO: Хайлт, Sitemap, XML Sitemap, RSS feed',
                        'Спам хамгаалалт (honeypot, HMAC token, rate limiting, Cloudflare Turnstile)',
                        'CSRF хамгаалалт',
                        'Аль ч орчинд ажиллана: VPS, dedicated, cloud, shared hosting - mod_security / LiteSpeed WAF-ын ард ч method override, body encoding-оор саадгүй',
                        'File-based cache (PSR-16), Trash сэргээлт, Sidebar badge систем',
                    ],
                    'en' => [
                        'PSR-7/PSR-15 middleware-based architecture',
                        'JWT + Session authentication',
                        'Multi-tenant organizations with RBAC',
                        'Multi-language support (Localization)',
                        'CMS modules: News, Pages, Files, References, Settings',
                        'Shop module: Products, Orders, Reviews',
                        'MySQL or PostgreSQL supported',
                        'SQL file-based database migration system',
                        'codesaur/template engine (Twig-style syntax)',
                        'OpenAI integration (moedit editor)',
                        'PSR-3 logging, PSR-14 Event Dispatcher',
                        'Email (Brevo API, SMTP, PHP mail), Discord webhook notifications',
                        'SEO: Search, Sitemap, XML Sitemap, RSS feed',
                        'Spam protection (honeypot, HMAC token, rate limiting, Cloudflare Turnstile)',
                        'CSRF protection',
                        'Runs in any environment: VPS, dedicated, cloud or shared hosting - method override and body encoding keep it working even behind a mod_security / LiteSpeed WAF',
                        'File-based cache (PSR-16), Trash recovery, sidebar badge system',
                    ],
                ],
                'classes' => [
                    ['Dashboard\\Application', ['mn' => 'Админ панелийн middleware + router бүртгэл', 'en' => 'Admin panel middleware + router registration']],
                    ['Web\\Application', ['mn' => 'Нийтийн вебсайтын middleware + router бүртгэл', 'en' => 'Public website middleware + router registration']],
                    ['Dashboard\\DatabaseConnection', ['mn' => 'PDO холболт нээнэ - MySQL / PostgreSQL сонголт .env-ээс', 'en' => 'Opens the PDO connection - MySQL / PostgreSQL chosen in .env']],
                    ['Dashboard\\Controller', ['mn' => 'Бүх контроллерын суурь: PDO, RBAC, template, log', 'en' => 'Base for all controllers: PDO, RBAC, template, log']],
                    ['Dashboard\\MethodOverrideMiddleware', ['mn' => 'PUT/PATCH/DELETE-ийг X-HTTP-Method-Override-оос сэргээнэ (WAF)', 'en' => 'Restores PUT/PATCH/DELETE from X-HTTP-Method-Override (WAF)']],
                    ['Dashboard\\BodyEncodingMiddleware', ['mn' => 'base64 кодлогдсон POST body-г тайлна (WAF)', 'en' => 'Decodes base64-encoded POST bodies (WAF)']],
                    ['Dashboard\\ContainerMiddleware', ['mn' => 'PSR-11 container болон service-үүдийг request-д залгана', 'en' => 'Injects the PSR-11 container and its services into the request']],
                    ['Dashboard\\SessionMiddleware', ['mn' => 'Session нээж, бичих шаардлагагүй үед эрт хаана', 'en' => 'Opens the session and closes it early when no write is needed']],
                    ['Dashboard\\Authentication\\JWTAuthMiddleware', ['mn' => 'JWT баталгаажуулж User объект үүсгэнэ', 'en' => 'Validates the JWT and builds the User object']],
                    ['Dashboard\\Localization\\LocalizationMiddleware', ['mn' => 'Хэл болон орчуулгыг тодорхойлно', 'en' => 'Determines language and translations']],
                    ['Dashboard\\Content\\SettingsMiddleware', ['mn' => 'Системийн тохиргоог request-д дамжуулна', 'en' => 'Injects the system settings into the request']],
                    ['Dashboard\\CsrfMiddleware', ['mn' => 'Per-route CSRF хамгаалалт', 'en' => 'Per-route CSRF protection']],
                    ['Dashboard\\Authentication\\User', ['mn' => 'Нэвтэрсэн хэрэглэгч: can(), is(), байгууллагын хамрах хүрээ', 'en' => 'The signed-in user: can(), is(), organization scope']],
                    ['Dashboard\\RBAC\\RBAC', ['mn' => 'Хэрэглэгчийн роль, эрхийн багц (кэштэй)', 'en' => 'A user roles and permissions set (cached)']],
                    ['Dashboard\\Template\\DashboardTrait', ['mn' => 'dashboard.html layout, sidebar, байгууллагын сэлгүүр', 'en' => 'dashboard.html layout, sidebar and organization switcher']],
                    ['Web\\Template\\TemplateController', ['mn' => 'Вебийн index.html layout, SEO meta, цэс', 'en' => 'Web index.html layout, SEO meta and navigation']],
                    ['Dashboard\\CacheService', ['mn' => 'PSR-16 файл суурьтай кэш', 'en' => 'PSR-16 file-based cache']],
                    ['Dashboard\\Log\\Logger', ['mn' => 'PSR-3 лог - {table}_log хүснэгтүүдэд бичнэ', 'en' => 'PSR-3 logging into the {table}_log tables']],
                    ['Dashboard\\Mail\\Mailer', ['mn' => 'И-мэйл илгээгч (Brevo API, SMTP, PHP mail)', 'en' => 'Mailer (Brevo API, SMTP, PHP mail)']],
                    ['Dashboard\\Notification\\EventDispatcher', ['mn' => 'PSR-14 event dispatcher', 'en' => 'PSR-14 event dispatcher']],
                    ['Dashboard\\Notification\\DiscordNotifier', ['mn' => 'Discord webhook мэдэгдэл', 'en' => 'Discord webhook notifications']],
                    ['Dashboard\\Migration\\MigrationRunner', ['mn' => 'SQL migration файлыг гүйцэтгэнэ (driver-aware splitter)', 'en' => 'Runs SQL migration files with a driver-aware statement splitter']],
                    ['Dashboard\\Migration\\MigrationSecurityScanner', ['mn' => 'Эмзэг хүснэгт, DCL үйлдлийг илрүүлж анхааруулна', 'en' => 'Flags writes to sensitive tables and DCL statements']],
                    ['Dashboard\\Trash\\TrashModel', ['mn' => 'Устгасан бичлэгийг хадгалж, буцаан сэргээнэ', 'en' => 'Stores deleted records and restores them']],
                    ['Dashboard\\File\\FileController', ['mn' => 'Файл upload, устгах суурь логик', 'en' => 'Base logic for file uploads and deletion']],
                    ['Dashboard\\Badge\\BadgeController', ['mn' => 'Sidebar-ийн үзээгүй үйлдлийн тоолол', 'en' => 'Counts unseen activity for the sidebar badges']],
                    ['Dashboard\\Exception\\ErrorHandler', ['mn' => 'Template суурьтай алдааны хуудас', 'en' => 'Template-based error pages']],
                ],
                'example' => <<<'PHP'
// application/web/WebRouter.php
use codesaur\Router\Router;

class WebRouter extends Router
{
    public function __construct()
    {
        $this->GET('/', [HomeController::class, 'index'])->name('home');
        $this->GET('/page/{slug}', [Content\PageController::class, 'page'])->name('page');
        $this->GET('/news/{slug}', [Content\NewsController::class, 'news'])->name('news');
    }
}

// application/web/HomeController.php
class HomeController extends Template\TemplateController
{
    public function index()
    {
        $recent = (new NewsModel($this->pdo))->getRecentPublished($this->getLanguageCode());
        $this->webTemplate(__DIR__ . '/home.html', ['recent' => $recent])->render();
    }
}
PHP,
            ],
            'http-message' => [
                'name' => 'codesaur/http-message',
                'title' => 'HTTP Message',
                'icon' => 'bi-envelope-paper',
                'color' => 'primary',
                'github' => self::GITHUB_ORG . '/HTTP-Message',
                'packagist' => 'https://packagist.org/packages/codesaur/http-message',
                'aikido' => 98,
                'psr' => ['PSR-7'],
                'requires' => ['PHP 8.2.1+', 'ext-json', 'Composer'],
                'install' => 'composer require codesaur/http-message',
                'summary' => [
                    'mn' => 'Цэвэр, минимал, объект хандалтат бүтэцтэй HTTP Message компонент (PSR-7)',
                    'en' => 'Clean, minimal, object-oriented HTTP Message component (PSR-7)',
                ],
                'description' => [
                    'mn' => 'PHP-ийн PSR-7 стандартын дагуу Request, Response, ServerRequest, URI, Stream, UploadedFile, OutputBuffer зэрэг HTTP мессежийн бүрэлдэхүүнүүдийг хэрэгжүүлсэн бага жинтэй компонент. Ямар ч фрэймворкоос үл хамааран standalone ашиглаж болно.',
                    'en' => 'A lightweight component implementing the HTTP message building blocks of PSR-7 - Request, Response, ServerRequest, URI, Stream, UploadedFile and OutputBuffer. It can be used standalone, independent of any framework.',
                ],
                'features' => [
                    'mn' => [
                        'PSR-7 MessageInterface, RequestInterface, ResponseInterface бүрэн хэрэгжилт',
                        'ServerRequest - глобал орчноос ($_SERVER, $_GET, $_POST, $_FILES) хүсэлтийг сэргээнэ',
                        'Uri - PSR-7 UriInterface, immutable with* метод',
                        'Stream, Output - StreamInterface хэрэгжилт, output buffering',
                        'UploadedFile - upload хийгдсэн файлын metadata + moveTo()',
                        'NonBodyResponse - body-гүй хөнгөн хариу (fallback prototype)',
                        'Гадны нэмэлт хамааралгүй',
                    ],
                    'en' => [
                        'Full PSR-7 MessageInterface, RequestInterface, ResponseInterface implementation',
                        'ServerRequest - reconstructs the request from globals ($_SERVER, $_GET, $_POST, $_FILES)',
                        'Uri - PSR-7 UriInterface with immutable with* methods',
                        'Stream, Output - StreamInterface implementations with output buffering',
                        'UploadedFile - uploaded file metadata + moveTo()',
                        'NonBodyResponse - lightweight body-less response (fallback prototype)',
                        'No external dependencies',
                    ],
                ],
                'classes' => [
                    ['Message', ['mn' => 'PSR-7 MessageInterface хэрэгжилт (headers, protocol, body)', 'en' => 'PSR-7 MessageInterface implementation (headers, protocol, body)']],
                    ['Request', ['mn' => 'PSR-7 RequestInterface', 'en' => 'PSR-7 RequestInterface']],
                    ['Response', ['mn' => 'PSR-7 ResponseInterface', 'en' => 'PSR-7 ResponseInterface']],
                    ['ServerRequest', ['mn' => 'Глобал орчноос request сэргээдэг хэрэгжилт', 'en' => 'Reconstructs the request from the global environment']],
                    ['Uri', ['mn' => 'PSR-7 UriInterface', 'en' => 'PSR-7 UriInterface']],
                    ['Stream', ['mn' => 'PSR-7 StreamInterface хэрэгжилт', 'en' => 'PSR-7 StreamInterface implementation']],
                    ['UploadedFile', ['mn' => 'Upload хийгдсэн файлын metadata + moveTo()', 'en' => 'Uploaded file metadata + moveTo()']],
                    ['Output', ['mn' => 'StreamInterface хэрэгжилт (output buffering)', 'en' => 'StreamInterface implementation (output buffering)']],
                ],
                'example' => <<<'PHP'
use codesaur\Http\Message\ServerRequest;
use codesaur\Http\Message\Response;

// ServerRequest үүсгэх / Create ServerRequest
$request = (new ServerRequest())->initFromGlobal();

// Query params
$query = $request->getQueryParams();

// PSR-7 headers унших / Read PSR-7 headers
$contentType = $request->getHeaderLine('Content-Type');
$csrfToken = $request->getHeaderLine('X-CSRF-TOKEN');

// Response үүсгэх / Create Response
$response = (new Response())
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json');

// Body-д бичих / Write to body
$response->getBody()->write(json_encode(['message' => 'Hello, World!']));
PHP,
            ],
            'router' => [
                'name' => 'codesaur/router',
                'title' => 'Router',
                'icon' => 'bi-signpost-split',
                'color' => 'success',
                'github' => self::GITHUB_ORG . '/Router',
                'packagist' => 'https://packagist.org/packages/codesaur/router',
                'aikido' => 99,
                'psr' => [],
                'requires' => ['PHP 8.2.1+', 'Composer'],
                'install' => 'composer require codesaur/router',
                'summary' => [
                    'mn' => 'Хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент',
                    'en' => 'Lightweight, fast, object-oriented routing component',
                ],
                'description' => [
                    'mn' => 'Динамик параметрүүд, нэртэй маршрутууд, reverse routing (URL үүсгэх), per-route middleware зэрэг бүх шаардлагатай боломжуудыг дэмждэг routing компонент. RouterInterface contract-ийн ачаар гуравдагч router-уудыг adapter-аар ороож codesaur/http-application дотор ашиглах боломжтой.',
                    'en' => 'A routing component supporting dynamic parameters, named routes, reverse routing (URL generation) and per-route middleware. Thanks to the RouterInterface contract, third-party routers can be adapted and used inside codesaur/http-application.',
                ],
                'features' => [
                    'mn' => [
                        'GET / POST / PUT / PATCH / DELETE болон нийлмэл (POST_PUT, GET_POST) маршрут',
                        'Төрөлтэй параметр: {int:id}, {uint:page}, {float:price}, {utf8:query}, {slug}',
                        'Нэртэй маршрут, reverse routing: generate("name", [...])',
                        'Client-side орлуулалтад бэлэн pattern("name") -> /news/{id}',
                        'Per-route middleware - ->middleware([...]) chain хуримтлагдана',
                        'match() нь тогтмол [callable, params, middleware] 3-tuple буцаана',
                        'UTF-8 (кирилл, CJK) параметрийн дэмжлэг',
                    ],
                    'en' => [
                        'GET / POST / PUT / PATCH / DELETE and compound (POST_PUT, GET_POST) routes',
                        'Typed parameters: {int:id}, {uint:page}, {float:price}, {utf8:query}, {slug}',
                        'Named routes with reverse routing: generate("name", [...])',
                        'pattern("name") -> /news/{id} ready for client-side substitution',
                        'Per-route middleware - chained ->middleware([...]) calls accumulate',
                        'match() returns a stable [callable, params, middleware] 3-tuple',
                        'UTF-8 (Cyrillic, CJK) parameter support',
                    ],
                ],
                'classes' => [
                    ['Router', ['mn' => 'Маршрут бүртгэх, тааруулах, URL үүсгэх үндсэн класс', 'en' => 'Registers routes, matches requests, generates URLs']],
                    ['RouterInterface', ['mn' => 'Router-ийн бүрэн contract (match, generate, pattern, getRoutes)', 'en' => 'Full router contract (match, generate, pattern, getRoutes)']],
                    ['Route', ['mn' => 'Immutable value object - fluent ->name(), ->middleware() API', 'en' => 'Immutable value object with fluent ->name(), ->middleware() API']],
                ],
                'example' => <<<'PHP'
use codesaur\Router\Router;

$router = new Router();

// Энгийн GET маршрут / Simple GET route
$router->GET('/hello', function() {
    echo 'Hello, World!';
});

// Динамик параметртэй маршрут / Route with dynamic parameters
$router->GET('/news/{int:id}', function(int $id) {
    echo "News ID: $id";
})->name('news-view');

// Per-route middleware
$router->POST('/api/users', [UserController::class, 'create'])
    ->middleware([CsrfMiddleware::class, AuthMiddleware::class])
    ->name('users.create');

// Маршрут тааруулах / Match route
$result = $router->match('/news/10', 'GET');
if ($result !== null) {
    [$callable, $params, $middleware] = $result;
    \call_user_func_array($callable, $params);
}

// URL үүсгэх / Generate URL
$url = $router->generate('news-view', ['id' => 10]); // -> /news/10
$pattern = $router->pattern('news-view');           // -> /news/{id}
PHP,
            ],
            'http-application' => [
                'name' => 'codesaur/http-application',
                'title' => 'HTTP Application',
                'icon' => 'bi-diagram-3',
                'color' => 'info',
                'github' => self::GITHUB_ORG . '/HTTP-Application',
                'packagist' => 'https://packagist.org/packages/codesaur/http-application',
                'aikido' => 99,
                'psr' => ['PSR-7', 'PSR-15'],
                'requires' => ['PHP 8.2.1+', 'Composer', 'PSR-7 implementation (codesaur/http-message)'],
                'install' => 'composer require codesaur/http-application',
                'summary' => [
                    'mn' => 'PSR-7 & PSR-15 нийцсэн хөнгөн, уян хатан HTTP Application цөм',
                    'en' => 'Lightweight, flexible HTTP Application core compliant with PSR-7 & PSR-15',
                ],
                'description' => [
                    'mn' => 'PSR-7 (HTTP Message) ба PSR-15 (RequestHandler / Middleware) стандартууд дээр суурилсан минималист, middleware суурьтай Application цөм. Router (нэг эсвэл олон), middleware, controller/action, closure route, exception handler зэргийг хэдхэн мөр кодоор нэгтгэж өөрийн хүссэн бүтэцтэй web application босгох боломжтой.',
                    'en' => 'A minimalist, middleware-based Application core built on PSR-7 (HTTP Message) and PSR-15 (RequestHandler / Middleware). Combine routers (one or many), middleware, controller/action handlers, closure routes and an exception handler in a few lines of code to build the application structure you want.',
                ],
                'features' => [
                    'mn' => [
                        'PSR-7 стандартын ServerRequest + Response',
                        'PSR-15 Middleware & RequestHandler гинжин (onion) бүтэц',
                        'Олон Router-ийг нэг Application-д нэгтгэх (multi-router delegation)',
                        'Application-ийг URL prefix-д mount хийх - Router-ууд reusable',
                        'override() - өмнө бүртгэсэн route-ийг ил дарж бичих lane',
                        'Controller суурь класс (сонголтот)',
                        'Per-route middleware (MiddlewareInterface, Closure, class-string)',
                        'Exception Handler (development mode-той)',
                    ],
                    'en' => [
                        'PSR-7 standard ServerRequest + Response',
                        'PSR-15 Middleware & RequestHandler chain (onion model)',
                        'Multi-router delegation - combine several Routers in one Application',
                        'Mount an Application at a URL prefix - Routers stay reusable',
                        'override() lane for explicitly replacing previously registered routes',
                        'Optional Controller base class',
                        'Per-route middleware (MiddlewareInterface, Closure, class-string)',
                        'Exception Handler with development mode',
                    ],
                ],
                'classes' => [
                    ['Application', ['mn' => 'Middleware stack, router цуглуулга, mount, handle()', 'en' => 'Middleware stack, router collection, mount, handle()']],
                    ['Controller', ['mn' => 'Controller/action суурь класс - request helper-үүдтэй', 'en' => 'Base class for controller/action style with request helpers']],
                    ['ExceptionHandler', ['mn' => 'Exception-ийг HTTP хариу болгох middleware', 'en' => 'Middleware turning exceptions into HTTP responses']],
                    ['ExceptionHandlerInterface', ['mn' => 'Custom exception handler-ийн contract', 'en' => 'Contract for custom exception handlers']],
                ],
                'example' => <<<'PHP'
use codesaur\Router\Router;
use codesaur\Http\Application\Application;
use codesaur\Http\Application\ExceptionHandler;
use codesaur\Http\Message\ServerRequest;
use codesaur\Http\Message\NonBodyResponse;

// Router-д route бүртгэх / Register routes on Router
$router = new Router();
$router->GET('/', function ($req) {
    echo 'Hello World!';
});
$router->GET('/user/{int:id}', [UserController::class, 'show'])->name('user.show');

// Application үүсгэх + middleware + router нэмэх
$app = new Application(new NonBodyResponse());
$app->use(new ExceptionHandler());
$app->use(new AuthMiddleware());
$app->use($router);
$app->mount('/dashboard');   // бүх route /dashboard prefix-тэй болов

// Хүсэлт боловсруулах / Handle request
$request = (new ServerRequest())->initFromGlobal();
$response = $app->handle($request);
PHP,
            ],
            'dataobject' => [
                'name' => 'codesaur/dataobject',
                'title' => 'DataObject',
                'icon' => 'bi-database',
                'color' => 'warning',
                'github' => self::GITHUB_ORG . '/DataObject',
                'packagist' => 'https://packagist.org/packages/codesaur/dataobject',
                'aikido' => 99,
                'psr' => [],
                'requires' => ['PHP 8.2.1+', 'ext-pdo', 'MySQL / PostgreSQL / SQLite', 'Composer'],
                'install' => 'composer require codesaur/dataobject',
                'summary' => [
                    'mn' => 'PDO суурьтай өгөгдлийн модель ба хүснэгт удирдагч компонент (MySQL / PostgreSQL / SQLite)',
                    'en' => 'PDO-based data model and table management component (MySQL / PostgreSQL / SQLite)',
                ],
                'description' => [
                    'mn' => 'codesaur экосистемийн өгөгдлийн давхаргын үндсэн компонент. Model класс дээр багануудаа тодорхойлоход хүснэгтийг анх ашиглах үед автоматаар үүсгэж, insert / update / select / delete үйлдлүүдийг MySQL, PostgreSQL, SQLite дээр адилхан кодоор гүйцэтгэнэ. LocalizedModel нь олон хэлний контентыг {table}_content хүснэгтэд салгаж хадгална.',
                    'en' => 'The core data layer component of the codesaur ecosystem. Declare columns on a Model class and the table is created automatically on first use; insert / update / select / delete run with the same code on MySQL, PostgreSQL and SQLite. LocalizedModel stores multi-language content in a separate {table}_content table.',
                ],
                'features' => [
                    'mn' => [
                        'Model - нэг хүснэгтэд зориулсан загварын суурь класс',
                        'LocalizedModel - олон хэл дээрх контент ({table}_content) хадгалах',
                        'Column - баганын төрөл, урт, default, primary, unique, auto increment',
                        'Хүснэгтийг анх ашиглах үед автоматаар үүсгэх, __initial() hook',
                        'MySQL / PostgreSQL / SQLite нэг кодоор - Constants::DRIVER_*',
                        'getById, getRowWhere, getRows, countRows, insert, updateById, deleteById',
                        'PDOTrait, TableTrait - дахин ашиглагдах PDO/хүснэгтийн үйлдлүүд',
                    ],
                    'en' => [
                        'Model - base class for models targeting a single table',
                        'LocalizedModel - multi-language content stored in {table}_content',
                        'Column - type, length, default, primary, unique, auto increment',
                        'Automatic table creation on first use with an __initial() hook',
                        'MySQL / PostgreSQL / SQLite with one codebase - Constants::DRIVER_*',
                        'getById, getRowWhere, getRows, countRows, insert, updateById, deleteById',
                        'PDOTrait, TableTrait - reusable PDO / table operations',
                    ],
                ],
                'classes' => [
                    ['Model', ['mn' => 'Нэг хүснэгтэд зориулсан загварын суурь класс', 'en' => 'Base class for models targeting a single table']],
                    ['LocalizedModel', ['mn' => 'Олон хэл дээрх контент хадгалах загварын суурь класс', 'en' => 'Base class for models storing content in multiple languages']],
                    ['Column', ['mn' => 'Хүснэгтийн баганын бүтцийг тодорхойлох класс', 'en' => 'Defines table column structure']],
                    ['Constants', ['mn' => 'Driver, error code, column нэрсийн тогтмолууд', 'en' => 'Centralized drivers, error codes, column name constants']],
                    ['PDOTrait', ['mn' => 'PDO үйлдлүүдийг төвлөрүүлсэн trait', 'en' => 'Trait centralizing PDO operations']],
                    ['TableTrait', ['mn' => 'Хүснэгттэй ажиллах үндсэн боломжуудын trait', 'en' => 'Trait with basic table operations']],
                ],
                'example' => <<<'PHP'
use codesaur\DataObject\Model;
use codesaur\DataObject\Column;

class UserModel extends Model
{
    public function __construct(\PDO $pdo)
    {
        $this->setInstance($pdo);

        $this->setColumns([
           (new Column('id', 'bigint'))->primary(),
           (new Column('username', 'varchar', 64))->unique(),
            new Column('password', 'varchar', 255),
           (new Column('is_active', 'tinyint'))->default(1),
            new Column('created_at', 'datetime'),
        ]);

        $this->setTable('users');
    }
}

$pdo = new \PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$userModel = new UserModel($pdo);

// Нэмэх / Insert
$user = $userModel->insert([
    'username' => 'john',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'created_at' => date('Y-m-d H:i:s'),
]);

// Унших / Read
$user = $userModel->getById(1);
$user = $userModel->getRowWhere(['username' => 'john']);
$total = $userModel->countRows(['WHERE' => 'is_active=1']);
PHP,
            ],
            'template' => [
                'name' => 'codesaur/template',
                'title' => 'Template',
                'icon' => 'bi-braces',
                'color' => 'secondary',
                'github' => self::GITHUB_ORG . '/Template',
                'packagist' => 'https://packagist.org/packages/codesaur/template',
                'aikido' => 98,
                'psr' => [],
                'requires' => ['PHP 8.2.1+', 'ext-json', 'ext-mbstring', 'Composer'],
                'install' => 'composer require codesaur/template',
                'summary' => [
                    'mn' => 'Бие даасан PHP template engine - filters, functions, macros, expression parser',
                    'en' => 'Self-contained PHP template engine - filters, functions, macros, expression parser',
                ],
                'description' => [
                    'mn' => 'Хөгжлийн явцад Twig template engine-ийн синтакс, дизайн загвараас санаа авч чадамжуудаа өргөжүүлсэн минимал PHP template engine. Энгийн текст-суурьтай темплейтээс эхлээд if / for / macro / filter бүхий хүчирхэг темплейт хүртэл дэмждэг. Гадны хамааралгүй, нэг файлаас ажиллана.',
                    'en' => 'A minimal PHP template engine whose syntax and design were inspired by Twig during its evolution. It supports everything from simple text placeholders to powerful templates with if / for / macro / filter syntax, with no external dependencies.',
                ],
                'features' => [
                    'mn' => [
                        'if / elseif / else, for / else, set, macro бүтцүүд',
                        'Expression parser: ?:, ??, ~, in, not in, starts with, ends with, matches, ternary',
                        '30+ бэлэн filter: e, date, length, slice, json_encode, merge, replace, number_format ...',
                        'addFilter() / addFunction() - өөрийн filter, function бүртгэх',
                        'Объектын public метод дуудах: {{ user.can("perm") }}',
                        'range(), max(), min(), attribute() функцүүд',
                        'MemoryTemplate (string) ба FileTemplate (файл) хоёр хэлбэр',
                    ],
                    'en' => [
                        'if / elseif / else, for / else, set, macro constructs',
                        'Expression parser: ?:, ??, ~, in, not in, starts with, ends with, matches, ternary',
                        '30+ built-in filters: e, date, length, slice, json_encode, merge, replace, number_format ...',
                        'addFilter() / addFunction() - register your own filters and functions',
                        'Object method calls: {{ user.can("perm") }}',
                        'range(), max(), min(), attribute() functions',
                        'MemoryTemplate (string source) and FileTemplate (file source)',
                    ],
                ],
                'classes' => [
                    ['MemoryTemplate', ['mn' => 'Бүрэн template engine (if, for, filter, function, macro, expression parser)', 'en' => 'Full template engine (if, for, filter, function, macro, expression parser)']],
                    ['FileTemplate', ['mn' => 'Файлын системээс template уншиж рэндэрлэх (MemoryTemplate-ийг өргөтгөнө)', 'en' => 'File-based template loader (extends MemoryTemplate)']],
                ],
                'example' => <<<'PHP'
use codesaur\Template\MemoryTemplate;
use codesaur\Template\FileTemplate;

// Бүрэн engine - if, for, filter, function бүгд дэмжинэ
$page = new MemoryTemplate(
    '{% for item in items %}<li>{{ item|upper }}</li>{% endfor %}',
    ['items' => ['a', 'b', 'c']]
);
echo $page;

// Файл суурьтай template
$page = new FileTemplate('page.html', [
    'title' => 'Hello',
    'items' => ['a', 'b', 'c']
]);
$page->addFunction('link', fn($route) => "/app/$route");
echo $page;
PHP,
            ],
            'http-client' => [
                'name' => 'codesaur/http-client',
                'title' => 'HTTP Client',
                'icon' => 'bi-cloud-arrow-up',
                'color' => 'primary',
                'github' => self::GITHUB_ORG . '/HTTP-Client',
                'packagist' => 'https://packagist.org/packages/codesaur/http-client',
                'aikido' => 99,
                'psr' => [],
                'requires' => ['PHP 8.2.1+', 'ext-curl', 'ext-json', 'Composer'],
                'install' => 'composer require codesaur/http-client',
                'summary' => [
                    'mn' => 'HTTP хүсэлт илгээх болон MIME имэйл боловсруулах/илгээх хөнгөн жинтэй клиент компонент',
                    'en' => 'Lightweight client component for sending HTTP requests and building/sending MIME email',
                ],
                'description' => [
                    'mn' => 'cURL дээр суурилсан уян хатан HTTP клиент (CurlClient), JSON REST API-тэй ажиллах JSONClient, HTTP хариуг объект хэлбэрээр илэрхийлэх Response, HTML + Text + олон хавсралттай MIME имэйл илгээгч Mail гэсэн 4 үндсэн классаас бүрдэнэ. UTF-8 бүрэн дэмжлэгтэй.',
                    'en' => 'Four core classes: CurlClient (flexible cURL-based HTTP client), JSONClient (for JSON REST APIs), Response (status code, headers, body as an object) and Mail (MIME email sender with HTML + Text + multiple attachments). Full UTF-8 support throughout.',
                ],
                'features' => [
                    'mn' => [
                        'CurlClient - request(), send(), upload(), sendWithRetry(), debug горим',
                        'JSONClient - get / post / put / patch / delete, base URL дэмжлэг',
                        'Response - statusCode, getHeader(), json() decode',
                        'Mail - HTML + Text, файл болон URL хавсралт, UTF-8 нэр/гарчиг',
                        'HTTP/1.1 хувилбар сонгох гэх мэт curl option дамжуулах',
                        'Зөвхөн ext-curl, ext-json шаардана',
                    ],
                    'en' => [
                        'CurlClient - request(), send(), upload(), sendWithRetry(), debug mode',
                        'JSONClient - get / post / put / patch / delete with base URL support',
                        'Response - statusCode, getHeader(), json() decode',
                        'Mail - HTML + Text, file and URL attachments, UTF-8 names/subjects',
                        'Pass through curl options such as forcing HTTP/1.1',
                        'Requires only ext-curl and ext-json',
                    ],
                ],
                'classes' => [
                    ['CurlClient', ['mn' => 'cURL дээр суурилсан уян хатан HTTP клиент', 'en' => 'Flexible HTTP client based on cURL']],
                    ['JSONClient', ['mn' => 'JSON өгөгдөлтэй REST API-тэй ажиллах клиент', 'en' => 'Client for JSON REST APIs']],
                    ['Response', ['mn' => 'HTTP хариуг обьект хэлбэрээр илэрхийлэх', 'en' => 'HTTP response object (status code, headers, body)']],
                    ['Mail', ['mn' => 'HTML + Text + олон хавсралттай MIME имэйл илгээгч', 'en' => 'MIME email sender with HTML + Text + multiple attachments']],
                ],
                'example' => <<<'PHP'
use codesaur\Http\Client\JSONClient;
use codesaur\Http\Client\Mail;

// JSON API-тэй ажиллах / Work with a JSON API
$api = new JSONClient('https://api.example.com/v1');
$users = $api->get('/users', ['page' => 1]);
$created = $api->post('/users', ['name' => 'codesaur'], ['Authorization' => 'Bearer token']);

// MIME имэйл илгээх / Send a MIME email
$mail = new Mail();
$mail->targetTo('user@example.com', 'Хэрэглэгч');
$mail->setFrom('no-reply@example.com', 'codesaur');
$mail->setSubject('Сайн байна уу?');
$mail->setMessage('<h1>Hello!</h1><p>Тест имэйл.</p>');
$mail->addFileAttachment(__DIR__ . '/file.pdf');
$mail->sendMail();
PHP,
            ],
            'container' => [
                'name' => 'codesaur/container',
                'title' => 'Container',
                'icon' => 'bi-box-seam',
                'color' => 'dark',
                'github' => self::GITHUB_ORG . '/Container',
                'packagist' => 'https://packagist.org/packages/codesaur/container',
                'aikido' => 96,
                'psr' => ['PSR-11'],
                'requires' => ['PHP 8.2.1+', 'Composer'],
                'install' => 'composer require codesaur/container',
                'summary' => [
                    'mn' => 'Хөнгөн, хурдан, PSR-11 стандартад нийцсэн dependency injection container',
                    'en' => 'Lightweight, fast, PSR-11 compliant dependency injection container',
                ],
                'description' => [
                    'mn' => 'PSR-11 ContainerInterface-ийг хэрэгжүүлсэн, lazy loading, auto-wiring, interface binding, service alias зэрэг боломжтой DI container. Reflection ашиглан dependency-үүдийг автоматаар resolve хийж instance үүсгэнэ. codesaur экосистемийн үндсэн бүрэлдэхүүн боловч ямар ч PHP төслөөс бие даан ашиглах боломжтой.',
                    'en' => 'A DI container implementing PSR-11 ContainerInterface with lazy loading, auto-wiring, interface binding and service aliases. It resolves dependencies automatically through Reflection. A core component of the codesaur ecosystem that can be used independently in any PHP project.',
                ],
                'features' => [
                    'mn' => [
                        'PSR-11 ContainerInterface хэрэгжилт',
                        'Lazy Loading - сервисүүд зөвхөн шаардлагатай үед үүсгэгдэнэ',
                        'Auto-wiring - constructor dependency-үүдийг автоматаар resolve хийх',
                        'Interface Binding - interface-ийг implementation-тай холбох',
                        'Service Aliases - нэг сервисийг олон нэрээр авах',
                        'Closure / callable factory дэмжлэг',
                        'Framework-agnostic, гадны хамааралгүй',
                    ],
                    'en' => [
                        'PSR-11 ContainerInterface implementation',
                        'Lazy Loading - services are created only when needed',
                        'Auto-wiring - automatic constructor dependency resolution',
                        'Interface Binding - bind interfaces to implementations',
                        'Service Aliases - access one service by multiple names',
                        'Closure / callable factory support',
                        'Framework-agnostic with no external dependencies',
                    ],
                ],
                'classes' => [
                    ['Container', ['mn' => 'PSR-11 container - set(), get(), has(), alias, binding', 'en' => 'PSR-11 container - set(), get(), has(), aliases, bindings']],
                    ['ContainerException', ['mn' => 'Сервис үүсгэх үеийн алдаа', 'en' => 'Error while building a service']],
                    ['NotFoundException', ['mn' => 'Сервис олдоогүй үеийн алдаа (PSR-11)', 'en' => 'Service not found error (PSR-11)']],
                ],
                'example' => <<<'PHP'
use codesaur\Container\Container;

// Контейнер үүсгэх / Create container
$container = new Container();

// Класс бүртгэх / Register class
$container->set(MyService::class);

// Параметртэй класс бүртгэх / Register class with parameters
$container->set(Printer::class, ['Hello, World!']);

// Сервис авах / Get service
$service = $container->get(MyService::class);
$printer = $container->get(Printer::class);

// Сервис байгаа эсэхийг шалгах / Check if service exists
if ($container->has(MyService::class)) {
    $printer->print(); // Output: Hello, World!
}
PHP,
            ],
        ];
    }

    /**
     * Нэг багцын мэдээллийг авах.
     *
     * @param string $key Багцын slug
     * @return array|null
     */
    public static function package(string $key): ?array
    {
        return self::packages()[$key] ?? null;
    }

    /**
     * Портал UI-ийн текстүүд (хоёр хэлээр).
     *
     * @param string $code Хэлний код
     * @return array<string, string>
     */
    public static function texts(string $code): array
    {
        $texts = [
            'mn' => [
                'nav_home' => 'Нүүр',
                'nav_raptor' => 'Raptor',
                'nav_packages' => 'Багцууд',
                'nav_docs' => 'Баримт',
                'nav_news' => 'Мэдээ',
                'nav_contact' => 'Холбоо барих',
                'nav_github' => 'GitHub',
                'search_portal' => 'Портал',

                'hero_kicker' => 'codesaur ecosystem',
                'hero_title' => 'Монгол программистын бүтээсэн PHP фреймворк ба багцууд',
                'hero_lead' => 'Raptor фреймворк болон түүний суурь болсон PSR стандартын бие даасан багцууд - цэвэр архитектур, объект хандалтат код, гадны хамаарал хамгийн бага. Нээлттэй эх, MIT лиценз.',
                'hero_start' => 'Эхлэх',
                'hero_packages' => 'Багцуудыг үзэх',
                'hero_github' => 'GitHub дээр үзэх',

                'stat_packages' => 'Багц',
                'stat_psr' => 'PSR стандарт',
                'stat_php' => 'PHP хувилбар',
                'stat_license' => 'Лиценз',
                'stat_db' => 'Өгөгдлийн сан',

                'eco_title' => 'Экосистемийн багцууд',
                'eco_lead' => 'Багц бүр бие даасан, ямар ч PHP төсөлд Composer-оор суулгаж ашиглаж болно. Raptor нь эдгээрийг нэгтгэн бүрэн фреймворк болгодог.',
                'eco_all' => 'Бүх багцууд',

                'why_title' => 'Яагаад Raptor?',
                'why_dialog' => 'Боломжууд',
                'why_lead' => 'Хэрэглэгчийн эрх, олон хэл, CMS, дэлгүүр, лог, кэш зэрэг веб програмд хэрэгтэй бүх зүйл нэг дор, код нь бүхэлдээ таны мэдэлд.',
                'why_1_title' => 'PSR стандарт',
                'why_1_text' => 'PSR-3, 4, 7, 11, 12, 14, 15, 16 - стандартад нийцсэн тул бусад PHP сангуудтай саадгүй нийлнэ.',
                'why_2_title' => 'Multi-tenant RBAC',
                'why_2_text' => 'Олон байгууллага, дүр, эрхийн нарийн зохицуулалт, JWT + Session нэвтрэлт бэлэн.',
                'why_3_title' => 'Код бүхэлдээ таных',
                'why_3_text' => 'vendor/-оос бусад бүх зүйл төслийн энгийн код - тусдаа "framework core" давхарга байхгүй, override хэрэггүй.',
                'why_4_title' => 'MySQL + PostgreSQL',
                'why_4_text' => 'Нэг кодоор хоёр driver дээр ажиллана. Хүснэгтүүд Model-оос автоматаар үүснэ.',
                'why_5_title' => 'Аль ч орчинд ажиллана',
                'why_5_text' => 'VPS, dedicated, cloud, shared hosting - хаана ч нэмэлт тохиргоогүй. cPanel / LiteSpeed / mod_security WAF шиг хатуу орчныг method override, body encoding автоматаар давна.',
                'why_6_title' => 'Монгол хэл суурьтай',
                'why_6_text' => 'Код, тайлбар, баримт бүгд монгол болон англи хэл дээр. Олон хэлний контент удирдлага анхнаасаа суурилсан.',

                'arch_title' => 'Архитектур',
                'arch_lead' => 'Нэг entry point, хэдэн ч апп байж болно - анхдагч байдлаар 2 апп. Хүсэлт middleware гинжээр дамжин router, controller, template хүртэл явна.',
                'arch_flow' => 'Хүсэлтийн урсгал',

                'quick_title' => 'Өгөгдлөө үүсгэнэ',
                'quick_lead' => 'Хоосон өгөгдлийн сангаа үүсгээд нэг команд - бүх хүснэгт, эхний өгөгдөл анх ажиллахад автоматаар үүснэ.',
                'quick_req' => 'Шаардлага',
                'quick_docroot' => 'Document root заавал public_html/ байх ёстой - .env, application/, vendor/ түүнээс дээр байрлаж URL-аар хүрэшгүй байна.',
                'quick_more' => 'Дэлгэрэнгүй заавар',

                'story_title' => 'Яагаад "Raptor" гэж?',
                'story_text' => 'Дэлхийд анх удаа Монголын говиос (1923) олдсон Velociraptor хэмээх гайхалтай динозаврыг бэлэгдэж өгсөн нэр. Зохиогч Наранхүү динозавр, байгалийн түүхийг сонирхон уншиж судалдаг хүн. codesaur гэдэг нь code + saur - тэрбээр 1997 оноос C хэл дээр эхлэн програм зохиосон өөрийгөө "хуучин цагийн код бичигч динозавр" хэмээн хөгжилтэйгээр нэрлэсэн билээ.',

                'community_title' => 'Холбоо',
                'community_lead' => 'Асуулт асуух, санал хэлэх, алдаа мэдээлэх - бүгд GitHub дээр нээлттэй.',
                'community_discussions' => 'Хэлэлцүүлэг',
                'community_issues' => 'Алдаа мэдээлэх',
                'community_packagist' => 'Packagist',

                'packages_title' => 'codesaur багцууд',
                'packages_lead' => 'Packagist дээр нийтлэгдсэн, MIT лицензтэй, PHP 8.2.1+ шаарддаг бие даасан багцууд.',
                'aikido_title' => 'Аюулгүй байдлын үнэлгээ',
                'aikido_lead' => 'Aikido Intel нь нээлттэй эхийн багцуудыг хамаарлын эрүүл мэнд, эмзэг байдал, malware зэргээр 0-100 оноогоор бие даан дүгнэдэг. codesaur багцууд бүгд 90-ээс дээш оноотой, malware илрээгүй.',
                'aikido_score' => 'Aikido оноо',
                'aikido_checked' => 'Шалгасан',
                'aikido_all' => 'Бүх багцын оноог Aikido Intel дээр үзэх',
                'package_installed' => 'Суулгасан хувилбар',
                'package_install' => 'Суулгах',
                'package_features' => 'Онцлогууд',
                'package_classes' => 'Үндсэн классууд',
                'package_class' => 'Класс',
                'package_purpose' => 'Үүрэг',
                'package_requires' => 'Шаардлага',
                'package_example' => 'Жишээ код',
                'package_docs' => 'Баримт бичиг',
                'package_links' => 'Холбоосууд',
                'package_depends' => 'Хамаарал (codesaur)',
                'package_used_by' => 'Raptor-д ашиглагддаг',
                'package_standalone' => 'Бие даан ашиглаж болно',
                'package_tests' => 'Тест ажиллуулах',
                'package_view' => 'Дэлгэрэнгүй',
                'package_source' => 'Эх код',
                'package_other' => 'Бусад багцууд',

                'docs_title' => 'Баримт бичиг',
                'docs_lead' => 'Багц бүрийн бүрэн танилцуулга, API тайлбар, код шалгалтын тайлан, өөрчлөлтийн түүх - монгол, англи хэлээр.',
                'doc_readme' => 'Танилцуулга (README)',
                'doc_guide' => 'Бүрэн танилцуулга',
                'doc_api' => 'API тайлбар',
                'doc_review' => 'Код шалгалтын тайлан',
                'doc_changelog' => 'Өөрчлөлтийн түүх',
                'doc_contents' => 'Агуулга',
                'doc_on_this_page' => 'Энэ хуудсанд',
                'doc_edit' => 'GitHub дээр үзэх',
                'doc_not_found' => 'Баримт олдсонгүй',
                'doc_lang_fallback' => 'Энэ баримт монгол хэлээр хараахан бэлэн болоогүй тул англи хувилбарыг харуулж байна.',
                'doc_copy' => 'Хуулах',
                'doc_copied' => 'Хуулагдлаа',

                'raptor_modules' => 'Хянах самбарын модулиуд',
                'raptor_modules_lead' => 'Админ панель нь модуль тус бүр Controller, Model, template-ээ нэг хавтсанд багцалсан package-by-feature бүтэцтэй.',
                'raptor_dir' => 'Хавтасны бүтэц',
                'raptor_middleware' => 'Middleware pipeline',
                'raptor_middleware_dashboard' => 'Dashboard',
                'raptor_middleware_web' => 'Web',
                'raptor_config' => 'Гол тохиргоо (.env)',
                'raptor_testing' => 'Тест',
                'raptor_testing_text' => 'PHPUnit 11 суурьтай unit болон integration тестүүдтэй. Integration тест .env.testing тохиргоог ашиглан тусдаа test database дээр transaction дотор ажиллаж, дуусахад rollback хийнэ.',
                'raptor_acknowledge' => 'Талархал',
                'raptor_acknowledge_text' => 'Энэ framework-т санхүүгийн дэмжлэг үзүүлж, хэрэгтэй санал хэлж байсан Gerege Systems LLC болон түүний үүсгэн байгуулагч Ц.Эрдэнэбат багшид талархал илэрхийлье.',

                'recent_news' => 'Сүүлийн мэдээ',
                'read_more' => 'Дэлгэрэнгүй',
                'author' => 'Зохиогч',
                'footer_ecosystem' => 'Экосистем',
                'footer_about' => 'codesaur.net нь codesaur-php экосистемийн албан ёсны портал. Raptor фреймворк болон бүх багцууд MIT лицензээр нээлттэй.',
            ],
            'en' => [
                'nav_home' => 'Home',
                'nav_raptor' => 'Raptor',
                'nav_packages' => 'Packages',
                'nav_docs' => 'Docs',
                'nav_news' => 'News',
                'nav_contact' => 'Contact',
                'nav_github' => 'GitHub',
                'search_portal' => 'Portal',

                'hero_kicker' => 'codesaur ecosystem',
                'hero_title' => 'A PHP framework and packages crafted by a Mongolian developer',
                'hero_lead' => 'The Raptor framework and the standalone PSR-standard packages it is built on - clean architecture, object-oriented code, minimal external dependencies. Open source, MIT licensed.',
                'hero_start' => 'Get started',
                'hero_packages' => 'Browse packages',
                'hero_github' => 'View on GitHub',

                'stat_packages' => 'Packages',
                'stat_psr' => 'PSR standards',
                'stat_php' => 'PHP version',
                'stat_license' => 'License',
                'stat_db' => 'Databases',

                'eco_title' => 'Ecosystem packages',
                'eco_lead' => 'Every package is standalone and installable into any PHP project via Composer. Raptor combines them into a complete framework.',
                'eco_all' => 'All packages',

                'why_title' => 'Why Raptor?',
                'why_dialog' => 'Options',
                'why_lead' => 'Access control, multi-language, CMS, shop, logging, cache - everything a production site needs in one place, with all of the code under your control.',
                'why_1_title' => 'PSR standards',
                'why_1_text' => 'PSR-3, 4, 7, 11, 12, 14, 15, 16 - standards compliance means it plugs into the rest of the PHP world without friction.',
                'why_2_title' => 'Multi-tenant RBAC',
                'why_2_text' => 'Multiple organizations, roles, fine-grained permissions and JWT + Session authentication out of the box.',
                'why_3_title' => 'All the code is yours',
                'why_3_text' => 'Everything outside vendor/ is plain project code - there is no separate "framework core" layer and nothing to override.',
                'why_4_title' => 'MySQL + PostgreSQL',
                'why_4_text' => 'One codebase runs on both drivers. Tables are created automatically from Model classes.',
                'why_5_title' => 'Runs anywhere',
                'why_5_text' => 'VPS, dedicated, cloud or shared hosting - it runs with no extra setup. Method override and body encoding clear even strict cPanel / LiteSpeed / mod_security WAFs.',
                'why_6_title' => 'Mongolian first',
                'why_6_text' => 'Code, comments and docs are in Mongolian and English. Multi-language content management is built in from the ground up.',

                'arch_title' => 'Architecture',
                'arch_lead' => 'One entry point, as many apps as you need - two by default. A request travels through the middleware chain to the router, controller and template.',
                'arch_flow' => 'Request flow',

                'quick_title' => 'Quick start',
                'quick_lead' => 'Create an empty database and run one command - all tables and seed data are created automatically on first run.',
                'quick_req' => 'Requirements',
                'quick_docroot' => 'The document root MUST be public_html/ - .env, application/ and vendor/ live above it and stay unreachable by URL.',
                'quick_more' => 'Full guide',

                'story_title' => 'Why "Raptor"?',
                'story_text' => 'The name honors the remarkable Velociraptor, a dinosaur whose fossils were first unearthed in Mongolia\'s Gobi Desert (1923). Its author, Narankhuu, is a keen reader on dinosaurs and natural history. codesaur is his own playful name - code + saur - a nod to writing C since 1997: an old-school "coding dinosaur".',

                'community_title' => 'Community',
                'community_lead' => 'Ask questions, share ideas, report bugs - everything happens in the open on GitHub.',
                'community_discussions' => 'Discussions',
                'community_issues' => 'Report an issue',
                'community_packagist' => 'Packagist',

                'packages_title' => 'codesaur packages',
                'packages_lead' => 'Standalone packages published on Packagist, MIT licensed, requiring PHP 8.2.1+.',
                'aikido_title' => 'Security health',
                'aikido_lead' => 'Aikido Intel independently scores open source packages from 0 to 100 on dependency health, known vulnerabilities and malware. Every codesaur package scores above 90 with no malware found.',
                'aikido_score' => 'Aikido score',
                'aikido_checked' => 'Checked',
                'aikido_all' => 'See every score on Aikido Intel',
                'package_installed' => 'Installed version',
                'package_install' => 'Install',
                'package_features' => 'Features',
                'package_classes' => 'Core classes',
                'package_class' => 'Class',
                'package_purpose' => 'Purpose',
                'package_requires' => 'Requirements',
                'package_example' => 'Quick example',
                'package_docs' => 'Documentation',
                'package_links' => 'Links',
                'package_depends' => 'Dependencies (codesaur)',
                'package_used_by' => 'Used by Raptor',
                'package_standalone' => 'Can be used standalone',
                'package_tests' => 'Running tests',
                'package_view' => 'Details',
                'package_source' => 'Source',
                'package_other' => 'Other packages',

                'docs_title' => 'Documentation',
                'docs_lead' => 'Full guide, API reference, code review report and changelog for every package - in Mongolian and English.',
                'doc_readme' => 'Overview (README)',
                'doc_guide' => 'Full guide',
                'doc_api' => 'API reference',
                'doc_review' => 'Code review report',
                'doc_changelog' => 'Changelog',
                'doc_contents' => 'Contents',
                'doc_on_this_page' => 'On this page',
                'doc_edit' => 'View on GitHub',
                'doc_not_found' => 'Document not found',
                'doc_lang_fallback' => 'This document is not yet available in the selected language, showing the English version.',
                'doc_copy' => 'Copy',
                'doc_copied' => 'Copied',

                'raptor_modules' => 'Dashboard modules',
                'raptor_modules_lead' => 'The admin panel uses a package-by-feature layout: each module bundles its Controller, Model and templates in one folder.',
                'raptor_dir' => 'Directory structure',
                'raptor_middleware' => 'Middleware pipeline',
                'raptor_middleware_dashboard' => 'Dashboard',
                'raptor_middleware_web' => 'Web',
                'raptor_config' => 'Key configuration (.env)',
                'raptor_testing' => 'Testing',
                'raptor_testing_text' => 'Ships with a PHPUnit 11 suite of unit and integration tests. Integration tests use the .env.testing config on a separate test database; each test runs inside a transaction and rolls back on teardown.',
                'raptor_acknowledge' => 'Acknowledgements',
                'raptor_acknowledge_text' => 'Thanks to Gerege Systems LLC and its founder Erdenebat Ts for their financial support and valuable input during the development of this framework.',

                'recent_news' => 'Recent news',
                'read_more' => 'Read more',
                'author' => 'Author',
                'footer_ecosystem' => 'Ecosystem',
                'footer_about' => 'codesaur.net is the official portal of the codesaur-php ecosystem. The Raptor framework and every package are open source under the MIT license.',
            ],
        ];

        return $texts[self::lang($code)];
    }

    /**
     * Raptor dashboard модулиудын жагсаалт (Raptor хуудсанд).
     *
     * @param string $code Хэлний код
     * @return array<int, array{icon: string, title: string, text: string}>
     */
    public static function raptorModules(string $code): array
    {
        $mn = self::lang($code) === 'mn';
        return [
            ['icon' => 'bi-shield-lock', 'title' => $mn ? 'Нэвтрэлт' : 'Authentication', 'text' => $mn ? 'JWT + Session, нууц үг сэргээх, бүртгүүлэх хүсэлт, login оролдлогын хязгаар' : 'JWT + Session, password reset, signup requests, login attempt limits'],
            ['icon' => 'bi-people', 'title' => $mn ? 'Хэрэглэгч ба байгууллага' : 'Users & Organizations', 'text' => $mn ? 'Олон байгууллага (multi-tenant), байгууллага солих, soft delete' : 'Multi-tenant organizations, org switcher, soft delete'],
            ['icon' => 'bi-key', 'title' => 'RBAC', 'text' => $mn ? 'Дүр, эрх, alias-аар бүлэглэсэн зөвшөөрөл; coder / admin / manager / editor / viewer' : 'Roles, permissions grouped by alias; coder / admin / manager / editor / viewer'],
            ['icon' => 'bi-translate', 'title' => $mn ? 'Олон хэл' : 'Localization', 'text' => $mn ? 'Хэл, орчуулгын текст удирдлага, кэштэй' : 'Languages and translation texts with caching'],
            ['icon' => 'bi-newspaper', 'title' => $mn ? 'Контент' : 'Content', 'text' => $mn ? 'Мэдээ, хуудас (мод бүтэц), лавлах хүснэгт, тохиргоо, файл, сэтгэгдэл' : 'News, pages (tree), reference tables, settings, files, comments'],
            ['icon' => 'bi-cart', 'title' => $mn ? 'Дэлгүүр' : 'Shop', 'text' => $mn ? 'Бүтээгдэхүүн, захиалга, үнэлгээ (1-5 од)' : 'Products, orders, reviews (1-5 stars)'],
            ['icon' => 'bi-chat-dots', 'title' => $mn ? 'Мессеж' : 'Messages', 'text' => $mn ? 'Холбоо барих формын мессеж, имэйлээр хариулах' : 'Contact form messages with email replies'],
            ['icon' => 'bi-journal-text', 'title' => $mn ? 'Лог ба протокол' : 'Logs & Protocol', 'text' => $mn ? 'PSR-3 лог хүснэгтүүд, бичлэг бүрийн түүх, sidebar badge' : 'PSR-3 log tables, per-record history, sidebar badges'],
            ['icon' => 'bi-trash', 'title' => $mn ? 'Хогийн сав' : 'Trash', 'text' => $mn ? 'Устгасан бичлэгийг эх ID-тэй нь сэргээх' : 'Restore deleted records with their original IDs'],
            ['icon' => 'bi-database-gear', 'title' => 'Migration', 'text' => $mn ? 'SQL файл суурьтай, forward-only, аюулгүй байдлын сканнертай' : 'SQL file-based, forward-only, with a security scanner'],
            ['icon' => 'bi-envelope', 'title' => $mn ? 'Имэйл ба мэдэгдэл' : 'Mail & Notifications', 'text' => $mn ? 'Brevo API / SMTP / PHP mail, Discord webhook, PSR-14 event' : 'Brevo API / SMTP / PHP mail, Discord webhook, PSR-14 events'],
            ['icon' => 'bi-tools', 'title' => $mn ? 'Хөгжүүлэлт' : 'Development', 'text' => $mn ? 'Хөгжүүлэлтийн хүсэлт, гарын авлага, AI туслах (moedit)' : 'Dev requests, manuals, AI helper (moedit)'],
        ];
    }
}
