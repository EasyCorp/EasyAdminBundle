<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Cache;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CacheWarmer implements CacheWarmerInterface
{
    public const DASHBOARD_ROUTES_CACHE = 'easyadmin/routes-dashboard.php';

    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDirectory)
    {
        $allRoutes = $this->router->getRouteCollection();
        $dashboardRoutes = [];

        /** @var Route $route */
        foreach ($allRoutes as $routeName => $route) {
            $routeControllerFqcn = u($route->getDefault('_controller') ?? '')->beforeLast('::')->toString();

            try {
                if (\in_array(DashboardControllerInterface::class, class_implements($routeControllerFqcn))) {
                    $dashboardRoutes[$routeControllerFqcn] = $routeName;
                }
            } catch (\Exception $e) {
                // ignore these errors that occur for edge-cases like these:
                // Warning: class_implements(): Class error_controller does not exist and could not be loaded
            }
        }

        (new Filesystem())->dumpFile(
            $cacheDirectory.'/'.self::DASHBOARD_ROUTES_CACHE,
            '<?php return '.var_export($dashboardRoutes, true).';'
        );
    }
}
