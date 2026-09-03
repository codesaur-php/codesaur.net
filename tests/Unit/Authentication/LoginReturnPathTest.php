<?php

namespace Tests\Unit\Authentication;

use Psr\Http\Message\ServerRequestInterface;

use Tests\Support\RaptorTestCase;

use Dashboard\Authentication\LoginController;

/**
 * LoginController::resolveReturnPath() - нэвтэрсний дараа буцах замын тест.
 *
 * JWTAuthMiddleware нь нэвтрээгүй хэрэглэгчийг login руу явуулахдаа очих
 * гэсэн замыг ?return= query-гээр дамжуулдаг. Тэр утга нь хэрэглэгчийн
 * хяналтад байдаг тул whitelist нь open redirect-ээс хамгаалах ЦОРЫН ГАНЦ
 * давхарга юм - энэ тестүүд түүнийг хамгаална.
 */
class LoginReturnPathTest extends RaptorTestCase
{
    private \ReflectionMethod $resolveMethod;
    private \ReflectionProperty $requestProperty;
    private LoginController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = (new \ReflectionClass(LoginController::class))
            ->newInstanceWithoutConstructor();

        $this->resolveMethod = new \ReflectionMethod(LoginController::class, 'resolveReturnPath');
        $this->resolveMethod->setAccessible(true);

        $this->requestProperty = new \ReflectionProperty(
            \codesaur\Http\Application\Controller::class,
            'request'
        );
        $this->requestProperty->setAccessible(true);
    }

    /**
     * ?return= утгыг өгөөд resolveReturnPath()-ийн үр дүнг авах.
     *
     * @param mixed  $return     Query дэх түүхий утга (null бол параметргүй)
     * @param string $scriptName Серверийн SCRIPT_NAME (subdirectory deploy тест)
     * @param string $mount      Dashboard app-ийн mount path
     */
    private function resolve($return, string $scriptName = '/index.php', string $mount = '/dashboard'): string
    {
        $application = new class ($mount) {
            public function __construct(private string $mount)
            {
            }

            public function getMountPath(): string
            {
                return $this->mount;
            }
        };

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')
            ->willReturn($return === null ? [] : ['return' => $return]);
        $request->method('getServerParams')
            ->willReturn(['SCRIPT_NAME' => $scriptName]);
        $request->method('getAttribute')
            ->willReturnCallback(
                fn(string $name, $default = null) => $name === 'application' ? $application : $default
            );

        $this->requestProperty->setValue($this->controller, $request);

        return $this->resolveMethod->invoke($this->controller);
    }

    // =============================================
    // Зөвшөөрөгдөх замууд
    // =============================================

    public function testDashboardPathIsAccepted(): void
    {
        $this->assertSame('/dashboard/news', $this->resolve('/dashboard/news'));
    }

    public function testQueryStringIsPreserved(): void
    {
        $this->assertSame('/dashboard/news?page=2&type=post', $this->resolve('/dashboard/news?page=2&type=post'));
    }

    public function testScriptPathIsPrependedOnSubdirectoryInstall(): void
    {
        $this->assertSame(
            '/codesaur.net/public_html/dashboard/news',
            $this->resolve('/dashboard/news', '/codesaur.net/public_html/index.php')
        );
    }

    public function testDeepPathIsAccepted(): void
    {
        $this->assertSame('/dashboard/news/view/42', $this->resolve('/dashboard/news/view/42'));
    }

    public function testCustomMountPathIsHonoured(): void
    {
        $this->assertSame('/admin/news', $this->resolve('/admin/news', '/index.php', '/admin'));
        // Хуучин mount дээрх зам шинэ mount-д хүчингүй
        $this->assertSame('', $this->resolve('/dashboard/news', '/index.php', '/admin'));
    }

    // =============================================
    // Open redirect - бүгд татгалзагдана
    // =============================================

    public function testAbsoluteUrlIsRejected(): void
    {
        $this->assertSame('', $this->resolve('https://evil.example/dashboard/news'));
        $this->assertSame('', $this->resolve('http://evil.example'));
    }

    public function testProtocolRelativeUrlIsRejected(): void
    {
        $this->assertSame('', $this->resolve('//evil.example'));
        $this->assertSame('', $this->resolve('//evil.example/dashboard/news'));
    }

    public function testBackslashVariantIsRejected(): void
    {
        // Зарим browser '\' -ийг '/' шиг тайлдаг тул '/\evil' нь '//evil' болно
        $this->assertSame('', $this->resolve('/\\evil.example'));
        $this->assertSame('', $this->resolve('\\\\evil.example'));
    }

    public function testPathOutsideMountIsRejected(): void
    {
        $this->assertSame('', $this->resolve('/etc/passwd'));
        $this->assertSame('', $this->resolve('/'));
        $this->assertSame('', $this->resolve('/news'));
    }

    public function testMountPrefixMustEndWithSlash(): void
    {
        // '/dashboardevil' нь '/dashboard'-аар эхэлдэг ч өөр зам
        $this->assertSame('', $this->resolve('/dashboardevil.example'));
        $this->assertSame('', $this->resolve('/dashboard'));
    }

    public function testLoginPathIsRejectedToAvoidLoop(): void
    {
        $this->assertSame('', $this->resolve('/dashboard/login'));
        $this->assertSame('', $this->resolve('/dashboard/login?x=1'));
        $this->assertSame('', $this->resolve('/dashboard/login/reset'));
    }

    public function testControlCharactersAreRejected(): void
    {
        $this->assertSame('', $this->resolve("/dashboard/news\nLocation: https://evil.example"));
        $this->assertSame('', $this->resolve("/dashboard/news\r\n"));
        $this->assertSame('', $this->resolve("/dashboard/\x00news"));
    }

    public function testOverlongValueIsRejected(): void
    {
        $this->assertSame('', $this->resolve('/dashboard/' . \str_repeat('a', 520)));
    }

    // =============================================
    // Параметр байхгүй / буруу төрөл
    // =============================================

    public function testMissingParameterReturnsEmpty(): void
    {
        $this->assertSame('', $this->resolve(null));
    }

    public function testEmptyParameterReturnsEmpty(): void
    {
        $this->assertSame('', $this->resolve(''));
    }

    public function testArrayParameterReturnsEmpty(): void
    {
        // ?return[]=/dashboard/news гэж массив болгон илгээх оролдлого
        $this->assertSame('', $this->resolve(['/dashboard/news']));
    }

    public function testUnmountedApplicationDisablesTheFeature(): void
    {
        $this->assertSame('', $this->resolve('/dashboard/news', '/index.php', ''));
    }
}
