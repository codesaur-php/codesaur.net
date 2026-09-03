<?php

namespace Web\Portal;

use codesaur\Router\Router;

/**
 * Class PortalRouter
 * ---------------------------------------------------------------
 * codesaur.net портал - фреймворк, багц, баримт бичгийн хуудсуудын маршрут.
 *
 * Бүх маршрут GET бөгөөд session бичихгүй тул /session/ prefix хэрэггүй.
 * Багц болон баримтын нэрийг контроллер whitelist-ээр шалгана.
 *
 * @package Web\Portal
 */
class PortalRouter extends Router
{
    public function __construct()
    {
        // Raptor фреймворкийн танилцуулга
        $this->GET('/raptor', [PortalController::class, 'raptor'])->name('raptor');

        // Багцуудын жагсаалт ба дэлгэрэнгүй
        $this->GET('/packages', [PortalController::class, 'packages'])->name('packages');
        $this->GET('/package/{key}', [PortalController::class, 'package'])->name('package');

        // Баримт бичиг: хаб, багцын үндсэн баримт, тодорхой баримт
        $this->GET('/docs', [DocsController::class, 'index'])->name('docs');
        $this->GET('/docs/{key}', [DocsController::class, 'doc'])->name('docs-package');
        $this->GET('/docs/{key}/{doc}', [DocsController::class, 'doc'])->name('docs-doc');
    }
}
